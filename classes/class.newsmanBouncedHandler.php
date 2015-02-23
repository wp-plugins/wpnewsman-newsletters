<?php

//require_once(__DIR__.DIRECTORY_SEPARATOR.'lib/bounced/bounce_driver.class.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'../lib/neo-bounce-handler-php/handler.php');

//apd_set_pprof_trace('/Users/alexladyga/Projects/wpnewsman/');

class newsmanBouncedHandler {

	var $conn;
	var $options;
	var $errors;
	var $alerts;

	var $bouncehandler;
	var $worker;

	var $boucesDebugDir = null;
	var $bounceDebugSaveTypes = array('blocked-domain', 'failed', 'autoreply', 'unknown', 'transient');

	var $blockedDomainsCache = array();

	var $lastUID = null;

	var $stats = array(
			'total' => 0,
			'processed' => 0,
			'bounces' => 0,
			'skipped' => 0,
			'autoreplies' => 0,
			'left' => 0,
			'transient' => 0
		);

	function __construct($opts = null, $worker = null) {		
		$this->u = newsmanUtils::getInstance();

		if ( $worker ) {
			$this->worker = $worker;
			if ( $worker->isRespawnedWorker ) {
				$this->stats = get_option('newsman_bh_last_stats');
			} else {
				$this->setLastUndeletedUID('');
				$this->stats = array(
					'total' => 0,
					'processed' => 0,
					'bounces' => 0,
					'skipped' => 0,
					'autoreplies' => 0,
					'left' => 0,
					'transient' => 0
				);
			}			
		}



		// $this->bouncehandler = new Bouncehandler();
		// $this->bouncehandler->x_header_search_1 = "X-WPNewsman-antispam";

		$this->bounceDebugSaveTypes = array('autoreply', 'unknown');

		if ( defined('NEWSMAN_DEBUG_SAVE_BOUNCES') ) {
			$this->boucesDebugDir = NEWSMAN_PLUGIN_PATH.'/saved-bounces';
			if ( !is_dir($this->boucesDebugDir) ) {
				mkdir($this->boucesDebugDir);
			}
		}


		$this->bouncehandler = new NBH();
		$this->bouncehandler->findHeader('X-WPNewsman-antispam', 'encodedEmail');

		if ( $opts !== null ) {
			$this->options = $opts;
		} else {
			$opts = newsmanOptions::getInstance();
			$this->options = $opts->get('bounced');
		}
	}

	private function getLastUndeletedUID() {
		$o = newsmanOptions::getInstance();
		return $o->get('bhLastUndeletedUID');
	}

	private function setLastUndeletedUID($uid) {
		$o = newsmanOptions::getInstance();
		$o->set('bhLastUndeletedUID', $uid);
	}

	private function getConnectionString() {
		$flags = '/novalidate-cert';

		if ( $this->options['type'] === 'pop3' ) {
			$flags .= '/pop3';
		}

		if ( $this->options['secure'] === 'ssl' ) {
			$flags .= '/ssl';
		} elseif ( $this->options['secure'] === 'tls' ) {
			$flags .= '/tls';
		} else {
			$flags .= '/notls';
		}

		$cs = '{'.$this->options['host'].':'.$this->options['port'].$flags.'}';

		return $cs;
	}

	public function connect() {

		$this->errors = array();
		$this->alerts = array();
		
		$connStr = $this->getConnectionString();

		$this->u->log('[Bounce Handler] Connecting to %s', $connStr);
		try {
			$this->conn = @imap_open($connStr, $this->options['username'], $this->options['password']);	
		} catch( Exception $e ) {
			$this->conn = false;
			$this->errors = array( $e->getMessage() );
			$this->u->log('[Bounce Handler] Error connecting: %s', $e->getMessage());
		}

		$this->u->logMemUsage('connected to IMAP');
		
		$errors = imap_errors();

		if ( is_array($errors) ) {
			$this->errors = array_merge($this->errors, $errors);
		}

		for ($i=0, $errors_len = count($this->errors); $i < $errors_len ; $i++) { 
			$this->u->log('[Bounce Handler] imap error: %s', $this->errors[$i]);
			if ( $this->errors[$i] === 'SECURITY PROBLEM: insecure server advertised AUTH=PLAIN' ) {
				$this->errors[$i] = 'Security problem, please try to use secure connection(SSL or StartTLS). Original error: '.$this->errors[$i];
			}
		}

		$alerts = imap_alerts();
		if ( is_array($alerts) ) {
			$this->alerts = array_merge($this->alerts, $alerts);	
		}

		if ( count($this->errors) === 1 && $this->errors[0] == 'Mailbox is empty' ) {
			$this->u->log('[Bounce Handler] Mailbox is empty');

			$this->stats['processed'] = 0;
			$this->stats['bounces'] = 0;
			$this->stats['skipped'] = 0;
			$this->stats['autoreplies'] = 0;
			$this->stats['left'] = 0;
			$this->stats['transient'] = 0;
		}

		if ( !$this->conn ) {
			return false;
		}

		return count($this->errors) === 0;
	}

	public function close() {	
		if ( $this->conn ) {
			imap_close( $this->conn );	
		}		
	}

	public function findBounces($isStoppedCB = null) {
		$this->u->logMemUsage('[findBounces]');
		$u = newsmanUtils::getInstance();

		$o = newsmanOptions::getInstance();

		$bounceOpts = array(
			'deleteFailed' => $o->get('bounced.removeFromServer'),
			'deleteAR' => $o->get('bounced.removeAutoreplies'),
			'deleteTB' => $o->get('bounced.removeTransients'),
			'deleteUnknown' => $o->get('bounced.removeUnknown')
		);

		if ( $o->get('bounced.skipLargeMessages') ) {
			$bounceOpts['skipThresholdBytes'] = $o->get('bounced.skipThreshold')*1024;
		}

		$this->lastUID = $this->getLastUndeletedUID();

		//$emails = imap_search($this->conn, 'ALL', SE_UID);
		//$emails = imap_search($this->conn, 'ALL', SE_UID);

		// $this->u->log('[Bounce Handler] calling Expunge on connect');
		// imap_expunge($this->conn);		

		$mc = imap_check($this->conn);

		$this->u->log('[Bounce Handler] number of messages %s', $mc->Nmsgs);

		// We save total number of messages only for the BH session
		if ( !$this->worker->isRespawnedWorker ) {
			$this->u->log('SETTING TOTAL TO %s, isRespawnedWorker: %s', $mc->Nmsgs, var_export($this->worker->isRespawnedWorker, true));
			$this->stats['total'] = $mc->Nmsgs;	

			if ( isset( $this->onTotal ) ) {
				call_user_func($this->onTotal, $mc->Nmsgs);
			}			
		}

		$this->updateStats();

		$total = $mc->Nmsgs;
		$limit = defined('NEWSMAN_PRO_BH_BATCH_SIZE') ? NEWSMAN_PRO_BH_BATCH_SIZE : 50;
		$pos = 1;
		$done = false;
		$endStats = array();

		$this->u->logMemUsage('[findBounces] before processing batches');


		while ( !$done ) {
			$x = $pos;
			$y = $pos + $limit;

			if ( $y >= $total ) {
				$y = $total;
				$done = true;
			}

			if  ( !$this->processBatch($x, $y, $isStoppedCB, $stats, $endStats, $bounceOpts) ) {
				break;
			}

			imap_gc($this->conn, IMAP_GC_ELT);
			imap_gc($this->conn, IMAP_GC_ENV);
			imap_gc($this->conn, IMAP_GC_TEXTS);

			if ( $isStoppedCB ) {
				call_user_func($isStoppedCB);
			}
			if ( $this->worker ) {
				$this->worker->processMessages();
			}

			if ( defined('NEWSMAN_PRO_BH_BATCH_DELAY') && NEWSMAN_PRO_BH_BATCH_DELAY ) {
				$this->u->log('Sleeping '.NEWSMAN_PRO_BH_BATCH_DELAY.' seconds');
				sleep(NEWSMAN_PRO_BH_BATCH_DELAY);
			}

			$this->u->log('[findBounces] after processing batch isStoppedCB ');

			$this->u->logMemUsage('[findBounces] after processing batch '.$x.':'.$y);

			$pos += $limit+1;			

			$this->updateStats();
		}

		$this->u->log('[Bounce Handler] calling Expunge');
		imap_expunge($this->conn);

		if ( isset( $this->onFinalStats ) ) {
			call_user_func($this->onFinalStats, $endStats);
		}	

		$this->updateStats();
	}

	//TODO: record last undeleted email UID and with each new respawn skip email till that uid

	private function processBatch($offset, $limit, $isStoppedCB = null, &$stats, &$endStats, $opts) {

		$this->u->log('[Bounce Handler] downloading headers batch %s:%s', $offset, $limit);

		$emails = imap_fetch_overview($this->conn, $offset.':'.$limit);

		$this->u->log('[Bounce Handler] imap_fetch_overview returned %s headers', count($emails));

		$t = microtime(true);
		$speedCnt = 0;

		$this->u->log('---------------------------------------------');

		foreach ($emails as $eml) {

			if ( $this->lastUID ) {
				// skipping emails till last remembered
				if ( $eml->uid == $this->lastUID ) {
					$this->lastUID = null;
				}
				continue;
			}

			$this->stats['processed'] += 1;

			$msgId = isset($eml->message_id) ? $eml->message_id : '';

			if ( defined('NEWSMAN_FETCH_SINGLE_BOUNCE') ) {				
				if ( !isset($msgId) || $msgId !== NEWSMAN_FETCH_SINGLE_BOUNCE ) {
					continue;
				}
			}			

			// skip messages larger then threshold
			if ( isset($bounceOpts['skipThresholdBytes']) && $eml->size > $bounceOpts['skipThresholdBytes'] ) {
				$this->u->log('[Bounce Handler] Skipping email, email size: %s', $eml->size);
				$this->stats['skipped'] += 1;
				continue;
			}

			$st = $this->getBouncedStatus($eml->uid);

			if ( defined('NEWSMAN_FETCH_SINGLE_BOUNCE') ) {
				$this->u->log("Bounce status: \n".print_r($st, true));
			}

			$speedCnt += 1;
			$elapsed = microtime(true) - $t;
			if ( $elapsed >= 1 ) {
				$this->stats['speed'] = floor($speedCnt / $elapsed);
				$speedCnt = 0;
				$t = microtime(true);
			}

			if ( isset( $this->onEmail ) ) {
				call_user_func($this->onEmail, $st);
			}			

			$bounceType = isset($st['type']) ? $st['type'] : null;
			if ( !$bounceType ) {
				$bounceType = 'unknown';
				// $this->u->log('[Bounce Handler] email with uid %s is UNKNOWN', $eml->uid);
				// $this->u->saveUnknownEmail($eml->uid.'.eml', $st['fullEmail']);
			}

			if ( isset($endStats[$bounceType]) ) {
				$endStats[$bounceType] += 1;
			} else {
				$endStats[$bounceType] = 1;
			}

			if ( $bounceType === 'failed' || $bounceType === 'blocked-domain' ) {

				if ( $bounceType === 'blocked-domain' && isset($st['blockedDomain']) && trim($st['blockedDomain']) && !in_array($st['blockedDomain'], $this->blockedDomainsCache) ) {
					$blockedDomain = newsmanBlockedDomain::findOne('domain = %s', array($st['blockedDomain']));
					if ( !$blockedDomain ) {
						$blockedDomain = new newsmanBlockedDomain();
						$blockedDomain->domain = $st['blockedDomain'];

						$blockedDomain->delistingURL = isset($st['delistingURL']) ? $st['delistingURL'] : '';
						$blockedDomain->diagnosticCode = isset($st['diagnosticCode']) ? $st['diagnosticCode'] : '';
						$blockedDomain->senderIP = isset($st['senderIP']) ? $st['senderIP'] : '';
						$blockedDomain->save();						
					}						
					$this->blockedDomainsCache[] = $st['blockedDomain'];
				}

				$this->stats['bounces'] += 1;
			}

			// if ( $bounceType !== 'failed' && $bounceType !== 'transient' ){
			// 	$this->u->saveUnknownEmail($eml->uid.'.eml', $st['fullEmail']);
			// }

			if ( $bounceType == 'transient' ) {
				$this->stats['transient'] += 1;
				if ( $opts['deleteTB'] ) {
					imap_delete($this->conn, $eml->uid, FT_UID);
				} else {
					$this->setLastUndeletedUID($eml->uid);
				}
			}

			if ( $bounceType === 'autoreply' ) {
				$this->stats['autoreplies'] += 1;
				if ( $opts['deleteAR'] ) {
					imap_delete($this->conn, $eml->uid, FT_UID);
				} else {
					$this->setLastUndeletedUID($eml->uid);
				}
			}			

			if( $bounceType === 'unknown' ) {
				if ( isset( $this->onUnknown ) ) {
					call_user_func($this->onUnknown, $eml->subject);
				}
			}

			if ( $bounceType === 'failed' || $bounceType === 'blocked-domain' ) {
				if ( isset($st['email']) ) {
					$this->u->unsubscribeFromLists($st['email'], __('Unsubscribed by Bounce Handler', NEWSMAN));	
				}				
				if ( $opts['deleteFailed'] ) {
					imap_delete($this->conn, $eml->uid, FT_UID);
				} else {
					$this->setLastUndeletedUID($eml->uid);
				}
			}

			if ( $bounceType === 'unknown' ) {
				if ( $opts['deleteUnknown'] ) {
					imap_delete($this->conn, $eml->uid, FT_UID);	
				} else {
					$this->setLastUndeletedUID($eml->uid);
				}
			}

			if ( defined('NEWSMAN_DEBUG_SAVE_BOUNCES') && in_array($bounceType, $this->bounceDebugSaveTypes) ) {
				file_put_contents($this->boucesDebugDir.'/'.$bounceType.'-'.microtime(true).'.eml', $st['fullEmail']);
			}

			if ( $isStoppedCB ) {
				if ( call_user_func($isStoppedCB) ) {
					// stopped
					return false;
				}
			}

			if ( $this->stats['processed'] % 30 === 0 ) {
				$this->updateStats();
			}
		}

		$this->updateStats();

		unset($emails);
		unset($st);

		return true;
	}

	private function updateStats() {
		// live update
		$this->stats['left'] =
			$this->stats['processed'] -
			$this->stats['transient'] -
			$this->stats['bounces'] -
			$this->stats['autoreplies'] +
			$this->stats['skipped'];

		update_option('newsman_bh_last_stats', $this->stats);
	}

	private function fetchEmail($uid) {
		$headers = imap_fetchheader($this->conn, $uid, FT_UID || FT_PREFETCHTEXT);
		$body = imap_body($this->conn, $uid, FT_UID);

		return $headers;// . "\n" . $body;		
	}

	private function getBouncedStatus($uid) {
		$u = newsmanUtils::getInstance();

		$bounceStatus = array();

		$headers = imap_fetchheader($this->conn, $uid, FT_UID || FT_PREFETCHTEXT);
		$body = imap_body($this->conn, $uid, FT_UID);

		$eml = $headers . "\n" . $body;

		$t = microtime(true);
		$results = $this->bouncehandler->detect($eml);

		// $u->log('WARNING! Remove this in production!!!');
		if ( defined('NEWSMAN_DEBUG_SAVE_BOUNCES') ) {
			$results['fullEmail'] = $eml;
		}
		// $results['fullEmail'] = $eml;
		// $u->log("----------------------------------------------------------------\n");
		// $u->log($eml);
		// $u->log("----------------------------------------------------------------\n");

		//$u->log("---> processed %s in %s ms", $uid, (microtime(true)-$t)*1000);

		if ( is_array($results) && !empty($results) ) {
			if ( isset($results['encodedEmail']) && !isset($results['email']) ) {
				$results['email'] = strtolower($u->decEmail($results['encodedEmail']));
			}
		} else {
			$results = null;
		}
		unset($headers);
		unset($body);
		unset($eml);

		return $results;
	}	

	/******************************************************
	 * STATIC FUNCTIONS
	 ******************************************************/

	static function scheckEnvironment() {
		return function_exists('imap_open');
	}

	static function getStatus() {
		$u = newsmanUtils::getInstance();

		$wrs = newsmanWorkerRecord::findAll('workerClass = "newsmanBounceHandlerWorker"');

		return count($wrs) > 0 ? 'running' : 'stopped';
	}

	static function getLastStats() {
		return get_option('newsman_bh_last_stats');
	}
}