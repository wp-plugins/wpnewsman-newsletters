<?php

/**
 * Newsman Worker Manager
 */

class newsmanWorkerRecord extends newsmanStorable {
	static $table = 'newsman_workers';
	static $props = array(
		'id' => 'autoinc',
		'workerId' => 'string',
		'workerClass' => 'string',
		'workerParams' => 'text',
		'started' => 'int',
		'ttl' => 'int' // in seconds
	);

	static $keys = array(
		'workerId' => array( 'cols' => array( 'workerId' ) )
	);
}

class newsmanWorkerManager {

	function __construct() {
		$this->utils = newsmanUtils::getInstance();
		$this->locks = newsmanLocks::getInstance();
		$this->options = newsmanOptions::getInstance();

		//newsman_workers_check_event
		add_action('newsman_workers_check_event', array($this, 'pokeWorkers'));

		add_action('plugins_loaded', array($this, 'bindEvents'));
	}

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self(); 
		} 
		return self::$instance;
	}

	public function bindEvents() {
		if ( defined('NEWSMANP') ) {
			add_action('newsman_pro_workers_ready', array($this, 'onProWorkersReady'));	
		} else {
			add_action('init', array($this, 'onProWorkersReady'));	
		}
	}

	public function onProWorkersReady() {

		if ( preg_match('/wpnewsman-pokeback\/check-workers/i', $_SERVER['REQUEST_URI']) ) {
			$this->utils->log('poking workers from pokeback.wpnewsman.com...');
			$this->pokeWorkers();
			exit();
		}		

		if ( isset( $_REQUEST['newsman_worker_fork'] ) && !empty($_REQUEST['newsman_worker_fork']) ) {
			$this->utils->log('[onProWorkersReady] $_REQUEST["newsman_worker_fork"] %s', isset($_REQUEST['newsman_worker_fork']) ? $_REQUEST['newsman_worker_fork'] : '');			

			define('NEWSMAN_WORKER', true);
			
			$workerClass = $_REQUEST['newsman_worker_fork'];

			if ( !class_exists($workerClass) ) {
				$this->utils->log("requested worker class ".htmlentities($workerClass)." does not exist");
				die("requested worker class ".htmlentities($workerClass)." does not exist");
			}

			if ( !isset($_REQUEST['workerId']) ) {
				$this->utils->log('workerId parameter is not defiend in the query');
				die('workerId parameter is not defiend in the query');
			}

			$worker = new $workerClass($_REQUEST['workerId']);
			$worker->run();
			exit();
		}
	}

	public function addWorker($workerInstance) {
		$this->utils->log('[addWorker] %s',get_class($workerInstance));
		$wr = new newsmanWorkerRecord();

		$wr->workerId = $workerInstance->workerId;
		$wr->workerClass = get_class($workerInstance);
		$wr->workerParams = json_encode($_REQUEST);

		$wr->started = time();
		$wr->ttl = $workerInstance->ttl;
		$wr->save();

		$c = newsmanWorkerRecord::count();

		$this->utils->log('[addWorker] newsmanWorkerRecord count %s', $c);

		if ( $c == 1 ) {
			$this->enableWorkersCheck();
		}
	}

	public function removeWorker($workerInstance) {
		$this->utils->log('[removeWorker] %s',get_class($workerInstance));
		$wr = newsmanWorkerRecord::findOne('workerId = %s', array($workerInstance->workerId));
		if ( $wr ) {
			$wr->remove();
			$c = newsmanWorkerRecord::count();
			$this->utils->log('[addWorker] newsmanWorkerRecord count %s', $c);
			if ( $c == 0 ) {
				$this->disableWorkersCheck();
			}
		} else {
			$u = newsmanUtils::getInstance();
			$u->log('[newsmanWorkerManager->removeWorker] worker with workerPid %s is not found ', $workerInstance->workerId);
		}
	}

	// runs by WPCron or PokebackServer 
	// every few minutes to check if all workers ready
	public function pokeWorkers() {
		$u = newsmanUtils::getInstance();
		$workersRecords = newsmanWorkerRecord::findAll();

		foreach ($workersRecords as $wr) {
			$w = new newsmanWorkerAvatar($wr->workerId);
			$running = $w->isRunning();
			$u->log('[pokeWorkers] workerPid('.$w->workerId.') -> isAlive: '.var_export($running, true));
			if ( !$running ) {
				$workerClass = $wr->workerClass;
				$u->log('Found dead worker %s(%s). Trying to respawn.', $workerClass, $wr->workerId);

				$worker = new $workerClass($wr->workerId);
				$params = json_decode($wr->workerParams, true);
				$params['ignoreLock'] = '1';
				$wr->remove();
				$worker->fork($params);
			}
		}
		do_action('newsman_poke_workers');
	}

	private function enableWorkersCheck() {

		$this->utils->log('[enableWorkersCheck]');
		if ( $this->options->get('pokebackMode') ) {

			$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/schedule/?'.http_build_query(array(
				'key' => $this->options->get('pokebackKey'),
				'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/check-workers/',
				'time' => 'every 1 minute'
			));

			$r = wp_remote_get(
				$pokebackSrvUrl,
				array(
					'timeout' => 0.01,
					'blocking' => false
				)
			);
		} else {
			if ( !wp_next_scheduled('newsman_workers_check_event') ) {
				wp_schedule_event( time(), '1min', 'newsman_workers_check_event');
			}			
		}
	}

	private function disableWorkersCheck() {
		$this->utils->log('[disableWorkersCheck]');
		if ( $this->options->get('pokebackMode') ) {		
			$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/unschedule/?'.http_build_query(array(
				'key' => $this->options->get('pokebackKey'),
				'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/check-workers/'
			));

			$r = wp_remote_get(
				$pokebackSrvUrl,
				array(
					'timeout' => 0.01,
					'blocking' => false
				)
			);
		} else {
			wp_clear_scheduled_hook('newsman_workers_check_event');
		}
	}	
}