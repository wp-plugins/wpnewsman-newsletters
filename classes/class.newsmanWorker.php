<?php

class newsmanWorker extends newsmanWorkerBase {

	var $secret = '';
	var $u = null;
	var $stopped = false;
	var $pid = null;
	var $lockfile = null;
	var $ts = null;
	var $ttl = 0;
	var $stoptime = 0;
	var $wm = null;	
	var $respawnonexit = false;
	var $isRespawnedWorker = false;

	var $pm_iv = null;

	var $workerId;

	var $db;
	var $mtable;

	public function __construct($workerId) {
		parent::__construct();

		$this->u = newsmanUtils::getInstance();

		$this->workerId = $workerId;

		$newsmanAdmin = function_exists('current_user_can') && current_user_can( 'newsman_wpNewsman' );

		$o = newsmanOptions::getInstance();
		$this->secret = $o->get('secret');

		$this->isRespawnedWorker = isset($_REQUEST['respawn']) && $_REQUEST['respawn'] == '1';

		$this->u->log('[ isRespawnedWorker ]: '.var_export($this->isRespawnedWorker, true));

		@ini_set('max_execution_time',0);
		@ini_set('default_socket_timeout',10);
		@ignore_user_abort(true);

		//we set a stoppage time to avoid broken process
		$timeToLive = ini_get('max_execution_time');
		if ( empty($timeToLive) ) {
			$timeToLive = defined('NEWSMAN_WORKER_TTL') ? NEWSMAN_WORKER_TTL : 120; // 120 sec is a default ttl
		}		

		$this->ttl = $timeToLive;
		$this->stoptime = time()+$timeToLive-10;

		$this->wm = newsmanWorkerManager::getInstance();
	}

	public function stop() {
		$this->stopped = true;
		return true;
	}

	public function isRunning() {
		return !$this->stopped;
	}

	/**
	 * This function should be implemented in the ancestor
	 */
	public function worker() {

	}

	public function run() {

		$u = newsmanUtils::getInstance();
		$u->log('[newsmanWorker run] worker_lock %s', $this->lockName);

		$this->processMessages();

		if ( isset($_REQUEST['ignoreLock']) ) {
			$u->log('[newsmanWorker run] ignoreLock');
			$this->unlock();
		}

		$u->log('locking %s', $this->lockName);
		if ( $this->lock() ) { // returns true if lock was successfully enabled
			$u->log('running worker method...');
			$this->wm->addWorker($this);

			$this->worker();			
			$this->wm->removeWorker($this);
			$this->unlock();
		} else {
			$u->log('[newsmanWorker run] cannot set lock %s to run worker', $this->lockName);
			if ( method_exists($this, 'onError') ) {
				$this->onError(NEWSMAN_WORKER_ERR_CANNOT_SET_LOCK);
			}
		}

		$this->processMessages();

		if ( $this->respawnonexit ) {
			$this->fork(array_merge($_REQUEST, array( 'respawn' => '1' )));			
		}
	}

	/*******************************************************
	 * Message Loop Functions	 
	 *******************************************************/

	public function processMessages() {		

		if ( !$this->stopped && time() >= $this->stoptime ) {
			$this->u->log('[newsman worker] stoptime %s', $this->stoptime);
			$this->respawnonexit = true;
			$this->stop();
		}

		$u = newsmanUtils::getInstance();
		//$u->log('[newsmanWorker processMessages]');

		if ( $this->pm_iv !== null ) {
			$now = microtime(true);	

			if ( $now - $this->pm_iv < 0.2 ) { // return if < then 200 ms elapsed since last call
				return;
			}
		}

		$sql = "SELECT * FROM $this->_table WHERE `workerId` = %s AND `processed` = 0";
		$sql = $this->_db->prepare($sql, $this->workerId);
		$res = $this->_db->get_results($sql);
		if ( is_array($res) ) {
			foreach ($res as $c) {
				if ( method_exists($this, $c->method) ) {
					$args = @unserialize($c->arguments);
					$args = is_array($args) ? $args : array();

					$res = call_user_func_array(array($this, $c->method), $args);
					$res = serialize($res);

					$sql = "UPDATE $this->_table SET `processed` = 1, `result` = %s WHERE `id` = %d";
					$sql = $this->_db->prepare($sql, $res, $c->id);

					$this->_db->query($sql);
				}
			}
		}

		$this->pm_iv = microtime(true);
	}

	/*******************************************************
	 * STATIC functions                                    *
	 *******************************************************/

	static function makeRequest($url, $reqOptions) {
		$u = newsmanUtils::getInstance();
		
		$retries = 5;
		$retryTimeoutSec = 5;

		$reqOptions['method'] = isset($reqOptions['method']) ? $reqOptions['method']  : 'POST';

		while ( $retries > 0 ) {
			$r = wp_remote_request(
				$url,
				$reqOptions
			);
			if ( is_wp_error($r)  ) {
				$u->log('[newsmanWorker::makeRequest] '.print_r($r , true));
				$httpErrors = $r->errors['http_request_failed'];
				$shouldRetry = false;
				foreach ($httpErrors as $err) {
					if ( $err === 'name lookup timed out' ) {							
						$shouldRetry = true;
					}
				}

				$u->log('[newsmanWorker::makeRequest] Retrying request in %s sec. %s retries left.', $retryTimeoutSec, $retries);
				if ( !$shouldRetry ) {
					break;
				}
				$retries--;
				sleep($retryTimeoutSec);
			} else {
				break;
			}				
		}
		return $r;
	}

	static function fork($params = null) {
		if ( $params == null ) {
			$params = $_REQUEST;
		}
		$o = newsmanOptions::getInstance();
		$secret = $o->get('secret');

		$u = newsmanUtils::getInstance();

		$workerURL = get_bloginfo('wpurl').'/wp-newsman-worker-fork.php';
		
		$params['newsman_worker_fork'] = get_called_class();
		$params['workerId'] = sprintf( '%x', time() ).rand(1,100);

		if ( newsmanIsOnWindows() ) {
			$reqOptions = array();	
		} else {
			$reqOptions = array(
				'timeout' => 0.1,
				'blocking' => false				
			);
		}		

		if ( $o->get('pokebackMode') ) {
			$u->log('[newsmanWorker::fork] PokeBack mode enabled');

			// adding nonce
			$params['_wpnonce'] = $u->createNonce();

			$workerURL = $workerURL.'?'.http_build_query($params);

			$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/poke/?'.http_build_query(array(
				'url' => $workerURL,
				'key' => $o->get('pokebackKey'),
				'pokeMethod' => 'POST'
			));

			$u->log('CALLING '.$pokebackSrvUrl);

			$reqOptions['method'] = 'GET';

			$r = static::makeRequest($pokebackSrvUrl, $reqOptions);

			$u->log('PB FORK RES: %s', print_r($r, true));
			
		} else {
			$u->log('[newsmanWorker::fork] NORMAL MODE');
			$u->log('[newsmanWorker::fork] worker url %s', $workerURL);
			$u->log('[newsmanWorker::fork] worker url params %s', http_build_query($params));
			// exposing secret only in loopback requests

			$params['secret'] = $secret;

			$reqOptions['body'] = $params;

			$r = static::makeRequest($workerURL, $reqOptions);
			$u->log('FORK RES: %s', print_r($r, true));
		}
		return $params['workerId'];
	}

	static function getTmpDir() {
		$u = newsmanUtils::getInstance();
		return $u->addTrSlash(sys_get_temp_dir(), 'path');
	}

	// ----------------------
}

