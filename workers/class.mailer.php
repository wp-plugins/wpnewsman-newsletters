<?php

define('NEWSMAN_WORKER_WAIT_TIMEOUT', 1);
define('NEWSMAN_WORKER_WAIT_STOPPED', 2);

class newsmanMailer extends newsmanWorker {

	var $ts = null;

	// function checkpoint($msg) {
	// 	return;
	// 	$u = newsmanUtils::getInstance();
	// 	$elapsed = $this->ts !== null ? microtime(true) - $this->ts : 0;
	// 	$u->log(sprintf($msg, $elapsed));
	// 	$this->ts = microtime(true);
	// }
	
	function worker() {

		$this->growl('Worker started.');

		if ( isset($_REQUEST['email_id']) ) {
			$eml = newsmanEmail::findOne('id = %d', array( $_REQUEST['email_id'] ));

			$this->growl('Email found');

			if ( $eml ) {

				if ( $eml->status == 'inprogress' && ( !isset($_REQUEST['force']) || $_REQUEST['force'] !== '1' ) ) {
					die('Email is already processed by other worker. Use "force=1" in the query to run another worker anyway.');
				} else if ( $eml->status != 'stopped' ) {
					$this->growl('Setting email status to "inprogress"');

					$eml->status = 'inprogress';
					$eml->workerPid = getmypid();
					$eml->save();

					$this->launchSender($eml);
				}
			}			
		}
	}

	private function waitOrStop($ms) {
		$start = round(microtime(true)*1000);

		// $this->u->log('wait for: '.$ms.'ms');
		// $this->u->log('start: '.$start);

		for ( ;; ) {
			$d = round(microtime(true)*1000) - $start;
			//$this->u->log('delta: '.$d.'ms');
			if ( $d > $ms ) {
				return NEWSMAN_WORKER_WAIT_TIMEOUT;
			} else {
				usleep(100000); // 200 ms
				if ( $this->isStopped() ) {
					return NEWSMAN_WORKER_WAIT_STOPPED;	
				}				
			}			
		}
	}

	private function launchSender($email) {
		global $newsman_current_list;

		$this->growl('launching sendner');

		$stopped = false;

		$u = newsmanUtils::getInstance();

		$sl = newsmanSentlog::getInstance();

//		$checkpoint = time();

		$tStreamer = new newsmanTransmissionStreamer($email);

		$email->recipients = $tStreamer->getTotal();
		$email->msg = '';
		$email->save();

		$nmn = newsman::getInstance();

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
				$throttlingTimeout = ($div / $limit) * 1000; // in ms
				$this->growl('Throttling timeout: '.$throttlingTimeout.' ms');
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

		$email->p_html = $u->processAssetsURLs($email->p_html, $email->assets);
		$email->p_html = $u->compileThumbnails($email->p_html);	

//		$this->checkpoint('Assets URLs processed in %.4fus');

		//foreach ($transmissions as $t) {
		while ( $t = $tStreamer->getTransmission() ) {

//			$this->checkpoint('Transmission received in %.4fus');

			$this->growl('Got transmission object...');

			if ( $this->isStopped() ) { // checks with file_exists(), IO operation
				$stopped = true;
				$this->growl('Worker stopped.');
				break;
			}
//			$this->checkpoint('Stopflag checked in %.4fus');

			$newsman_current_list = $t->list;

			$start = time();

			$data = $t->getSubscriberData();

//			$this->checkpoint('Subscribers data taken in %.4fus');

			$msg = $email->renderMessage($data, false);

//			$this->checkpoint('Message rendered in %.4fus');

			// $msg['html'] = $u->processAssetsURLs($msg['html'], $email->assets);

			// $this->checkpoint('Assets URLs processed in %.4fus');

			$mail_opts = array(
				 'to' => $t->email,
				 'ts' => isset($t->data['ts']) ? $t->data['ts'] : null,
				 'ip' => isset($t->data['ip']) ? $t->data['ip'] : null,
				 'uns_link' => $nmn->getActionLink('unsubscribe'),
				 'uns_code' => $nmn->getActionLink('unsubscribe', 'code_only'),
			);

//			$this->checkpoint('Mail opts prepared in %.4fus');

			$this->growl('sending email...');

			$r = $u->mail($msg, $mail_opts );

//			$this->checkpoint('Mail sent in %.4fus');

			if ( $r === true ) {
				$this->growl('Email sent. Setting transmission to "done"');
				$errorsCount = 0;
				$email->incSent();

//				$this->checkpoint('Email counter incremented in %.4fus');

				$t->done($email->id);

//				$this->checkpoint('Transmission finished in %.4fus');
			} else {

				$this->growl('Sending failed: '.$r);

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
				$this->growl($lastError);
				break;
			}

			$elapsed = (time() - $start)* 1000; // time that sending email took in ms

			if ( $throttlingTimeout > 0 ) {
//				$this->checkpoint('Throttling timeout engaged in %.4fus');
				$t = $throttlingTimeout - $elapsed; // how much time we've left to wait				
				if ( $t > 0 ) {
					$this->growl('Falling asleep for '.($throttlingTimeout/1000).' seconds at '.date('H:i:s'));
					if ( $this->waitOrStop($t) === NEWSMAN_WORKER_WAIT_STOPPED ) {
						$stopped = true;
					}
					$this->growl('waking up');
				} else {
					$this->growl('No time for sleep :) Preparing next email...');
				}
			}
			$ts = microtime(true);
		}

		if ( $stopped ) {
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

}