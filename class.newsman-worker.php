<?php

require_once('class.utils.php');
require_once('class.options.php');

class newsmanWorker {

	var $secret = '';
	var $u = null;
	var $stopped = false;
	var $pid = null;

	public function __construct() {

		$o = newsmanOptions::getInstance();
		$this->secret = $o->get('secret');

		if ( defined('NEWSMAN_WORKER') && ( !defined('NEWSMAN_DEBUG') || NEWSMAN_DEBUG === false ) ) {
			if ( $_REQUEST['secret'] !== $this->secret ) {
				die('0');
			}				
		}

		$this->pid = getmypid();
		$this->u = newsmanUtils::getInstance();
	}

	/**
	 * This function should be implemented in the ancestor
	 */
	public function worker() {

	}

	public function isStopped() {
		return $this->isProcessStopped($this->pid);
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
		file_put_contents('/tmp/newsman-stopworker-'.$pid, 'STOP');
	}

	static function clearStopFlag($pid) {
		$fn = '/tmp/newsman-stopworker-'.$pid;
		if ( file_exists($fn) ) {
			unlink($fn);
		}
	}

	static function isWorker() {
		// if we are called by the worker
		if ( isset( $_REQUEST['newsman_worker_fork'] ) ) {
			$worker = new static();
			$worker->worker();
		}
	}

	static function isProcessStopped($pid) {
		$fn = '/tmp/newsman-stopworker-'.$pid;

		if ( file_exists($fn) ) {
			return true;
		}

		return false;		
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
}