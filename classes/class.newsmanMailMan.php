<?php

class newsmanMailMan {

	var $secret = '';
	var $u = null;

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self(); 
		} 
		return self::$instance; 
	} 

	public function __construct() {

		$this->u = newsmanUtils::getInstance();
	}

	// should be run by WP every minute
	public function checkEmailsQueue() {
		// run through emails queue and spawn
		// senders if needed

		$emails = newsmanEmail::findAll('status = "pending"');

		foreach ($emails as $email) {

			$this->u->log('[checkEmailsQueue] forking sender worker_lock: %s, email_id: %s', $email->id, $email->id);

			newsmanMailerWorker::fork(array(
				'email_id' => $email->id
			));
		}

		$emails = newsmanEmail::findAll('status = "%s" and schedule <= %d', array( 'scheduled', date('U') ));

		foreach ($emails as $email) {
			newsmanMailerWorker::fork(array(
				'email_id' => $email->id
			));
		}
	}
}