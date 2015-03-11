<?php

define('NEWSMAN_WORKER_WAIT_TIMEOUT', 1);
define('NEWSMAN_WORKER_WAIT_STOPPED', 2);

class newsmanMailerWorker extends newsmanWorker {

	var $ts = null;

	function __construct($workerId) {
		parent::__construct($workerId);

		$this->options = newsmanOptions::getInstance();
		$this->lockName = self::getLockName(isset($_REQUEST['email_id']) ? $_REQUEST['email_id'] : null);
	}

	static function getLockName($emailId = null) {
		return $emailId ? 'mailer-lock-'.$emailId : null;
	}
	
	function worker() {

		$u = newsmanUtils::getInstance();

		if ( isset($_REQUEST['email_id']) ) {
			$eml = newsmanEmail::findOne('id = %d', array( $_REQUEST['email_id'] ));

			$runStopped = isset($_REQUEST['run_stopped']) && $_REQUEST['run_stopped'] === '1';

			if ( $eml ) {

				$eml->status = 'inprogress';

				$eml->workerPid = $this->workerId;
				$eml->save();

				$u->log('[worker] Updating eml workerPid to %s', $this->workerId);

				$this->launchSender($eml);
			} else {
				$u->log('Error: Email with id '.$_REQUEST['email_id'].' is not found');	
			}
		} else {
			$u->log('Error: $_REQUEST["email_id"] is not defined');
		}
	}

	private function wait($ms) {
		$start = round(microtime(true)*1000);

		for ( ;; ) {
			$d = round(microtime(true)*1000) - $start;
			if ( $d > $ms ) {
				return NEWSMAN_WORKER_WAIT_TIMEOUT;
			} else {
				usleep(100000); // 100 ms
				if ( $this->stopped ) {
					return NEWSMAN_WORKER_WAIT_STOPPED;	
				}				
			}			
		}
	}

	private function launchSender($email) {
		global $newsman_current_list;
		global $newsman_current_subscriber;
		global $newsman_current_email;

		$newsman_current_email = $email;

		$u = newsmanUtils::getInstance();

		$u->log('[launchSender]: processMessages');

		$this->processMessages();		

		$u->log('[launchSender] Sender with pid '.getmypid().' for email '.$email->id.' started');

		$sl = newsmanSentlog::getInstance();

		$tStreamer = new newsmanTransmissionStreamer($email);

		$u->log('[launchSender] created transmissionStreamer');

		$tStreamer->getTotal();

		$email->msg = '';
		$email->save();

		$u->log('[launchSender] streamer recipients %s', $email->recipients);

		$nmn = newsman::getInstance();

		$o = newsmanOptions::getInstance();
		$throttlingTimeout = 0;
		$thr = $o->get('mailer.throttling.on');
		$u->log('[launchSender] mailer.throttling.on = %s', $thr ? 'true' : 'false');
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

		$email->p_html = $u->processAssetsURLs($email->p_html, $email->assetsURL);
		$email->p_html = $u->compileThumbnails($email->p_html);

		$u->log('[launchSender] processing messages');
		$this->processMessages();

		$u->log('[launchSender] getTransmissions while loop');
		while ( $t = $tStreamer->getTransmission() ) {

			$this->processMessages();

			if ( $this->stopped ) {
				$t->setStaus(NEWSMAN_TS_PENDING);
				break;
			}

			$addrArr = explode('@', $t->email);
			$domain = $addrArr[1];

			$blockedDomain = newsmanBlockedDomain::findOne('domain = %s', array($domain));

			if ( $blockedDomain ) {
				$u->log('[launchSender] domain %s is blocked by BH', $domain);
				$t->setError(NEWSMAN_ERR_DOMAIN_BLOCKED_BY_BH, 'Domain blocked by BH');
				continue;
			}

			$newsman_current_list = $t->list;

			$start = time();

			$data = $t->getSubscriberData();

			$msg = $email->renderMessage($data, false);

			$mail_opts = array(
				 'to' => $t->email,
				 'ts' => isset($t->data['ts']) ? $t->data['ts'] : null,
				 'ip' => isset($t->data['ip']) ? $t->data['ip'] : null,
				 'uns_link' => $nmn->getActionLink('unsubscribe'),
				 'uns_code' => $nmn->getActionLink('unsubscribe', 'code_only'),
			);

			$r = $u->mail($msg, $mail_opts );

			if ( $r === true ) {
				$errorsCount = 0;
				$email->incSent();

				$t->setStaus(NEWSMAN_TS_SENT);
			} else {

				if ( strpos($r, 'SMTP Error: Could not connect to SMTP host') !== false ) {
					$lastError = $r;
					$hasErrors = true;
					$errorStop = true;
					$t->setError(NEWSMAN_ERR_CANNOT_CONNECT_TO_HOST, $r);
					break;
				}

				$u->log('Sending to '.$t->email.': Server response: '.$r);
				if ( strpos($r, 'Invalid address') !== false ) {
					$t->setError(NEWSMAN_ERR_INVALID_EMAIL_ADDR, $r);
					// unsubscribe
					$u->unsubscribeFromLists($t->email, __('Bad Email Address', NEWSMAN), true);
				} else {
					$t->setError(NEWSMAN_ERR_TEMP_ERROR, $r);
				}

				$errorsCount += 1;
				$hasErrors = true;
				$lastError = $r;
			}

			if ( $errorsCount >= 5 ) {
				$u->log('Too many consecutive errors. Sending will be stopped.');
				$lastError = __('Too many consecutive errors. Please check your mail delivery settings and make sure you can send a test email. Last SMTP error: ', NEWSMAN).$lastError;
				$errorStop = true;
				break;
			}

			$elapsed = (time() - $start) * 1000; // time that sending email took in ms

			if ( $throttlingTimeout > 0 ) {
				$tme = $throttlingTimeout - $elapsed; // how much time we've left to wait				
				if ( $tme > 0 ) {
					$this->wait($tme);
				}
			}
			$ts = microtime(true);
		}

		$u->log('[launchSender] No more transmissions ( $this->stopped: '.var_export($this->stopped, true).' )');
		$u->log('[launchSender] last transmissions type '.var_export($t, true));

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

		$this->u->log('[launchSender] end of loop. clearing worker PID for worker '.var_export($email->workerPid, true));

		$email->workerPid = '';
		$email->save();
	}	

}