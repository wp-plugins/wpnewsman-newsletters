<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.options.php");

//require_once(__DIR__.DIRECTORY_SEPARATOR."lib/php-growl/class.growl.php");

// http://cubicspot.blogspot.com/2010/10/forget-flock-and-system-v-semaphores.html

class newsmanWorker {

	var $secret = '';
	var $u = null;
	var $stopped = false;
	var $pid = null;
	var $lockfile = null;

	public function __construct() {

		// TODO: remove in production
		// $this->growl_connection = array('address' => '127.0.0.1', 'password' => 'neuroshok');
		// $this->oGrowl = new Growl();

		$o = newsmanOptions::getInstance();
		$this->secret = $o->get('secret');

		if ( defined('NEWSMAN_WORKER') && ( !defined('NEWSMAN_DEBUG') || NEWSMAN_DEBUG === false ) ) {
			if ( $_REQUEST['secret'] !== $this->secret ) {
				die('0');
			}				
		}

		$this->pid = getmypid();
		$this->u = newsmanUtils::getInstance();

		static::cleanStaleFlagFiles();
	}

	// TODO: remove in production
	public function growl($str) {

		// $this->oGrowl->addNotification('newsman_worker');
		// $this->oGrowl->register($this->growl_connection);

		// $this->oGrowl->notify($this->growl_connection, 'newsman_worker', get_called_class(), $str);
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

		if ( $worker_lock === null ) {
			$this->worker();
			$this->clearStopFlag($this->pid);
		} else {
			if ( $this->lock($worker_lock) ) {
				$this->worker();
				$this->unlock();
				$this->clearStopFlag($this->pid);
			}			
		}
	}


	/*******************************************************
	 * STATIC functions
	 *******************************************************/

	static function fork($params = array()) {
		$o = newsmanOptions::getInstance();
		$secret = $o->get('secret');

		$workerURL = NEWSMAN_PLUGIN_URL.'/worker.php';

		$params['secret'] = $secret;
		$params['newsman_worker_fork'] = get_called_class();

		wp_remote_post(
			$workerURL,
			array(
				'timeout' => 0.01,
				'blocking' => false,
				'body' => $params
			)
		);
	}

	static function stop($pid) {
		file_put_contents(sys_get_temp_dir().'newsman-worker-stop-'.$pid, $pid);
	}

	static function clearStopFlag($pid) {
		$fn = sys_get_temp_dir().'newsman-worker-stop-'.$pid;
		if ( file_exists($fn) ) {
			unlink($fn);
		}
	}

	static function isProcessStopped($pid) {
		$fn = sys_get_temp_dir().'newsman-worker-stop-'.$pid;

		if ( file_exists($fn) ) {
			return true;
		}

		return false;
	}

	static function cleanStaleFlagFiles() {
		$u = newsmanUtils::getInstance();

		$tmpdir = sys_get_temp_dir();		
		if ( $handle = opendir( $tmpdir ) ) {
			while (false !== ($entry = readdir($handle))) {
				if ( preg_match('/^newsman-worker/i', $entry) ) {
					$pid = file_get_contents($tmpdir.$entry);
					if ( $pid && is_numeric($pid) && !static::isProcessRunning($pid) ) {
						unlink($tmpdir.$entry);
					}
				}
			}
			closedir($handle);
		}
	}

	static function isProcessRunning($pid) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$wmi=new COM("winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2"); 
			$procs=$wmi->ExecQuery("SELECT * FROM Win32_Process WHERE ProcessId='".$pid."'"); 
			return count($procs) > 0;
		} else {
			return posix_kill($pid, 0);
		}
	}

	// ----------------------

	/**
	 * Creates lock for some unique value(worker_lock - for example it can be an email id) so other 
	 * workers will not be able to run without obtaining the lock
	 */ 
	public function lock($worker_lock) {
		$this->lockfile = sys_get_temp_dir().'newsman-worker-'.$worker_lock.".lock";
		$r = @fopen($this->lockfile, "xb");
		if ( $r ) {
			fwrite($r, $this->pid);
		}
		return $r;
	}

	public function unlock() {
		if ( $this->lockfile ) {
			unlink($this->lockfile);
		}
	}


}