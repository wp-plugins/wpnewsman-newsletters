<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.options.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.ajax-fork.php");

// require_once(__DIR__.DIRECTORY_SEPARATOR."lib/php-growl/class.growl.php");

// http://cubicspot.blogspot.com/2010/10/forget-flock-and-system-v-semaphores.html

class newsmanWorker {

	var $secret = '';
	var $u = null;
	var $stopped = false;
	var $pid = null;
	var $lockfile = null;
	var $ts = null;

	public function __construct() {

		if ( class_exists('Growl') ) {
			$this->growl_connection = array('address' => '127.0.0.1', 'password' => 'glocksoft');
			$this->oGrowl = new Growl();			
		}

		$newsmanAdmin = function_exists('current_user_can') && current_user_can( 'newsman_wpNewsman' );

		$o = newsmanOptions::getInstance();
		$this->secret = $o->get('secret');

		if ( defined('NEWSMAN_WORKER') && ( !defined('NEWSMAN_DEBUG') || NEWSMAN_DEBUG === false ) ) {
			if ( !$newsmanAdmin && $_REQUEST['secret'] !== $this->secret ) {
				die('0');
			}
		}	

		$this->tryRemoveAjaxFork();	

		$this->pid = getmypid();
		$this->u = newsmanUtils::getInstance();
	}

	private function tryRemoveAjaxFork() {
		if ( defined('NEWSMAN_WORKER') && isset($_REQUEST['ts']) ) {
			$fork = newsmanAjaxFork::findOne('ts = %s', array($_REQUEST['ts']));
			if ( $fork ) {
				$fork->remove();
			}
		}
	}

	public function growl($str) {
		if ( isset($this->oGrowl) && $this->oGrowl ) {
			$this->oGrowl->addNotification('newsman_worker');
			$this->oGrowl->register($this->growl_connection);

			$this->oGrowl->notify($this->growl_connection, 'newsman_worker', get_called_class(), $str);			
		}
	}	

	/**
	 * This function should be implemented in the ancestor
	 */
	public function worker() {

	}

	public function isStopped() {
		return $this->isProcessStopped($this->pid);
	}

	public function run($worker_lock = null) {

		if ( !$this->initProcess() ) {
			return; // the lock is already obtained
		}

		if ( $worker_lock === null ) {
			$this->worker();
			$this->clearStopFlag($this->pid);
		} else {
			$this->isProcessRunning($this->pid);

			if ( $this->lock($worker_lock) ) { // returns true if lock was successfully enabled

				$this->isProcessRunning($this->pid);

				$this->worker();
				$this->unlock();
				$this->clearStopFlag($this->pid);
			} else {
				$this->growl('WORKER is already processing '.$worker_lock);
			}
		}

		$this->endProcess();
	}


	/*******************************************************
	 * STATIC functions
	 *******************************************************/

	static function fork($params = array()) {
		$o = newsmanOptions::getInstance();
		$secret = $o->get('secret');

		$workerURL = NEWSMAN_PLUGIN_URL.'/worker.php';
		
		$params['newsman_worker_fork'] = get_called_class();
		$params['ts'] = sprintf( '%.22F', microtime( true ) );

		if ( defined('ALTERNATE_WP_CRON') && ALTERNATE_WP_CRON ) {
			// passing url to the ajax forker
			$fork = new newsmanAjaxFork();
			$fork->ts = $params['ts'];
			$fork->method = 'post';
			$fork->url = $workerURL;
			$fork->body = http_build_query($params);
			$fork->save();
			
		} else {
			// exposing secret only in loopback requests
			$params['secret'] = $secret;
			wp_remote_post(
				$workerURL,
				array(
					//'timeout' => 0.01,
					'blocking' => false,
					'body' => $params
				)
			);			
		}

	}

	static function getTmpDir() {
		$u = newsmanUtils::getInstance();
		return $u->addTrSlash(sys_get_temp_dir(), 'path');
	}

	static function stop($pid) {
		$u = newsmanUtils::getInstance();
		return $u->lock('newsman-worker-stop-'.$pid);

	}

	static function clearStopFlag($pid) {		
		$u = newsmanUtils::getInstance();
		return $u->releaseLock('newsman-worker-stop-'.$pid);
	}

	static function isProcessStopped($pid) {
		$u = newsmanUtils::getInstance();
		
		$r = $u->isLocked('newsman-worker-stop-'.$pid);
		return $r;
	}

	static function isProcessRunning($pid) {		
		$u = newsmanUtils::getInstance();
		return $u->isLocked('newsman-worker-running-'.$pid);
	}

	public function initProcess() {
		$u = newsmanUtils::getInstance();

		$this->growl('[!!!] init process');
		return $u->lock('newsman-worker-running-'.$this->pid, true);
	}

	public function endProcess() {
		$u = newsmanUtils::getInstance();

		$this->growl('[!!!] end process');
		$u->releaseLock('newsman-worker-running-'.$this->pid);
	}

	// ----------------------

	/**
	 * Creates lock for some unique value(worker_lock - for example it can be an email id) so other 
	 * workers will not be able to run without obtaining the lock
	 */ 
	public function lock($worker_lock) {
		$this->lockfile = 'newsman-worker-'.$worker_lock;
		return $this->u->lock($this->lockfile);
	}

	public function unlock() {
		if ( $this->lockfile ) {
			return $this->u->releaseLock($this->lockfile);
		}
	}

	/**
	 * Write timestamp to the lock file each 10 seconds
	 */
	public function writeTS() {		
		$ts = gettimeofday(true);
		$timeout = 10;
		if ( $this->lockfile && ($this->ts === null || $ts > $this->ts+$timeout )) {
			file_put_contents($this->u->getLockFilePath($this->lockfile), $ts);
			$this->ts = $ts;
		}
	}

}