<?php

require_once('class.utils.php');
require_once('class.emails.php');
require_once('class.options.php');
require_once('class.sentlog.php');

define('NEWSMAN_ERR_INVALID_EMAIL_ADDR', 10);
define('NEWSMAN_ERR_TEMP_ERROR', 1);

class newsmanMailMan {

	var $secret = '';
	var $u = null;
	var $stopped = false;

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

		$o = newsmanOptions::getInstance();
		$this->secret = $o->get('secret');

		if ( defined('NEWSMAN_WORKER') ) {
			if ( $_REQUEST['secret'] !== $this->secret ) {
				die('0');
			}
		}

		$this->u = newsmanUtils::getInstance();
	}

	public function stop() {
		$this->stopped = true;
	}

	public function runWorker() {

		if ( isset($_REQUEST['email_id']) ) {
			$eml = newsmanEmail::findOne('id = %d', array( $_REQUEST['email_id'] ));

			if ( $eml ) {

				if ( $eml->status == 'inprogress' && ( !isset($_REQUEST['force']) || $_REQUEST['force'] !== '1' ) ) {
					die('Email is already processed by other worker. Use "force=1" in the query to run another worker anyway.');
				} else if ( $eml->status != 'stopped' && !$eml->isStopped() ) {
					$eml->status = 'inprogress';
					$eml->workerPid = getmypid();
					$eml->save();

					$this->launchSender($eml);					
				}
			}
			
		}
	}


	private function launchSender($email) {
		global $newsman_current_list;

		$sl = newsmanSentlog::getInstance();

		$checkpoint = time();

		$tStreamer = new newsmanTransmissionStreamer($email);

		// for debug only
		//$transmissions[] = new newsmanEmailTransmission('neocoder@gmail.com');

		//sleep(15);

		// echo '<pre>';
		// print_r($email->toJSON());
		// echo '</pre><hr>';

		// die();

		$email->recipients = $tStreamer->getTotal();
		$email->msg = '';
		$email->save();

		$o = newsmanOptions::getInstance();
		$throttlingTimeout = 0;
		$thr = $o->get('mailer.throttling.on');
		if ( $thr ) {
			$limit = intval($o->get('mailer.throttling.limit'));
			switch ( $o->get('mailer.throttling.period') ) {
				case 'day':
					$div = 12 * 60 * 60;
					break;

				case 'hour':
					$div = 60 * 60;
					break;

				case 'min':
					$div = 60;
					break;
			}
			if ( $limit !== 0 ) {
				$throttlingTimeout = ($div / $limit) * 1000000; // in mcs
				/* 
					actually it's not a timeout but a minimum time between send operations.
				*/
			}
		}

		$email->sent;
		$hasErrors = false;
		$lastError = '';
		$errorsCount = 0;
		$errorStop = false;

		//foreach ($transmissions as $t) {
		while ( $t = $tStreamer->getTransmission() ) {

			if ( $email->isStopped() ) {
				$this->stopped = true;
				break;
			}

			$newsman_current_list = $t->list;

			$start = time();

			$data = $t->getSubscriberData();

			// echo '<p>Subsciber data:</p>';
			// echo '<pre>';
			// print_r($data);
			// echo '</pre><hr>';

			$msg = $email->renderMessage($data);
			$msg['html'] = $this->u->expandAssetsURLs($msg['html'], $email->assets);

			$r = $this->u->mail($msg, array( 'to' => $t->email) );

			if ( $r === true ) {
				$errorsCount = 0;
				//$email->sent += 1;
				$email->incSent();
				$t->done($email->id);
			} else {

				if ( strpos($r, 'SMTP Error: Could not connect to SMTP host') !== false ) {
					$lastError = $r;
					$hasErrors = true;
					$errorStop = true;
					break;
				}

				if ( strpos($r, 'Invalid address') !== false ) {
					$t->errorCode = NEWSMAN_ERR_INVALID_EMAIL_ADDR;	
				} else {
					$t->errorCode = NEWSMAN_ERR_TEMP_ERROR;
				}
				
				$t->statusMsg = $r;
				$t->done($email->id);

				$errorsCount += 1;
				$hasErrors = true;
				$lastError = $r;
			}

			if ( $errorsCount >= 5 ) {
				$lastError = __('Too many consecutive errors. Please check your mail delivery settings and make sure you can send a test email. Last SMTP error: ', NEWSMAN).$lastError;
				$errorStop = true;
				break;
			}

			$elapsed = time() - $start; // time that sending email took

			if ( $throttlingTimeout > 0 ) {
				$t = $throttlingTimeout - $elapsed; // how much time we've left to wait
				if ( $t > 0 ) {	
					usleep($t);	
				}				
			}
		}

		if ( $this->stopped ) {
			$email->status = 'stopped';
		} else {
			if ( $email->status !== 'stopped' ) {
				if ( $email->sent > 0 && !$errorStop ) {
					$email->status = 'sent';
				} else if ( $email->recipients === 0 ){
					$email->status = 'error';
					$email->msg = __('No "confirmed" subscribers found in the selected list(s).', NEWSMAN);
				} else {
					$email->status = 'error';
					$email->msg = $lastError;
				}
			}
		}

		$email->workerPid = 0;
		$email->save();
	}


	private function spawnWorker($emailId) {
		$workerURL = NEWSMAN_PLUGIN_URL.'/worker.php';

		wp_remote_post(
			$workerURL,
			array(
				'timeout' => 0.01,
				'blocking' => false,
				'body' => array( 'secret' => $this->secret, 'email_id' => $emailId )
			)
		);

	}

	// shoud be run	by WP every minute
	// check if workers alive
	public function pokeWorkers() {
		$emails = newsmanEmail::findAll('status = "%s"', array('inprogress'));

		foreach ($emails as $email) {
			if ( !$this->isProcessRunnign( $email->workerPid ) && !$email->isStopped() ) {

				$email->status = 'pending';
				$email->workerPid = 0;

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
			$this->spawnWorker($email->id);
		}


		$emails = newsmanEmail::findAll('status = "%s" and schedule <= %d', array( 'scheduled', date('U') ));

		foreach ($emails as $email) {
			$this->spawnWorker($email->id);
		}
	}




	public function isProcessRunnign($pid) {
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			$wmi=new COM("winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2"); 
			$procs=$wmi->ExecQuery("SELECT * FROM Win32_Process WHERE ProcessId='".$pid."'"); 
			return count($procs) > 0;
		} else {
			return posix_kill($pid, 0);
		}
	}
}