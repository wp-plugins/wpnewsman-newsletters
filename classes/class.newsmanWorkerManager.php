<?php

/**
 * Newsman Worker Manager
 */

class newsmanWorkerManager {

	function __construct() {
		$this->utils = newsmanUtils::getInstance();
		$this->locks = newsmanLocks::getInstance();
		$this->options = newsmanOptions::getInstance();

		//newsman_workers_check_event
		add_action('newsman_workers_check_event', array($this, 'pokeWorkers'));
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

		if ( $c >= 1 ) {
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
			if ( $running === null ) {
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

	public function enableWorkersCheck() {

		$this->utils->log('[enableWorkersCheck] PB: %s', var_export($this->options->get('pokebackMode'), true));
		if ( $this->options->get('pokebackMode') ) {

			$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/schedule/?'.http_build_query(array(
				'key' => $this->options->get('pokebackKey'),
				'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/check-workers/',
				'time' => 'every 1 minute'
			));

			$this->utils->log('[enableWorkersCheck]: %s', $pokebackSrvUrl);

			$r = wp_remote_get(
				$pokebackSrvUrl,
				array(
					'timeout' => 5,
					'blocking' => true
				)
			);

			$this->utils->log('[enableWorkersCheck]: %s', var_export($r, true));

			return $r['body'] === 'ok';

		} else {
			if ( !wp_next_scheduled('newsman_workers_check_event') ) {
				wp_schedule_event( time(), '1min', 'newsman_workers_check_event');
			}			
			return true;
		}
	}

	public function disableWorkersCheck() {
		$this->utils->log('[disableWorkersCheck]');
		if ( $this->options->get('pokebackMode') ) {		
			$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/unschedule/?'.http_build_query(array(
				'key' => $this->options->get('pokebackKey'),
				'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/check-workers/'
			));

			$this->utils->log('[disableWorkersCheck]: %s', $pokebackSrvUrl);

			$r = wp_remote_get(
				$pokebackSrvUrl,
				array(
					'timeout' => 5,
					'blocking' => true
				)
			);

			$this->utils->log('[disableWorkersCheck]: %s', var_export($r, true));
			if ( is_wp_error($r) ) {
				return false;
			}
			return $r['body'] === 'ok';
		} else {
			wp_clear_scheduled_hook('newsman_workers_check_event');
			return true;
		}
	}

	public function clearWorkers() {
		global $wpdb;
		$tbl = newsmanWorkerRecord::getTableName();
		$this->utils->log('[clearWorkers]');
		if ( $this->utils->tableExists($tbl) ) {
			$this->utils->log('[clearWorkers] truncating %s', $tbl);
			return $wpdb->query("truncate table $tbl") === 1;	
		} else {
			$this->utils->log('[clearWorkers] table %s does not exist', $tbl);
		}
	}
}