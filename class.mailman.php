<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.emails.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.options.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.sentlog.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."workers/class.mailer.php");

define('NEWSMAN_ERR_INVALID_EMAIL_ADDR', 10);
define('NEWSMAN_ERR_TEMP_ERROR', 1);

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

	// shoud be run	by WP every minute
	// check if workers alive
	public function pokeWorkers() {
		$emails = newsmanEmail::findAll('status = %s', array('inprogress'));

		foreach ($emails as $email) {

			// double checke the status here to solve possible race condition
			if ( !$email->isWorkerAlive() && $email->getStatus() === 'inprogress' ) {
				$email->releaseLocks();
				$email->status = 'pending';
				$email->workerPid = '';

				$email->save();
			}
		}
	}

	// should be run by WP every minute
	public function checkEmailsQueue() {
		// run through emails queue and spawn
		// senders if needed

		$emails = newsmanEmail::findAll('status = "pending"');

		foreach ($emails as $email) {

			newsmanMailer::fork(array(
				'worker_lock' => $email->id,
				'email_id' => $email->id
			));
		}

		$emails = newsmanEmail::findAll('status = "%s" and schedule <= %d', array( 'scheduled', date('U') ));

		foreach ($emails as $email) {
			newsmanMailer::fork(array(
				'worker_lock' => $email->id,
				'email_id' => $email->id
			));
		}
	}
}