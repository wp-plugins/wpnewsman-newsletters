<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.storable.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.an-links.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."lib/emogrifier.php");

class newsmanEmail extends newsmanStorable {
	static $table = 'newsman_emails';
	static $props = array(
		'id' => 'autoinc',
		'to' => 'string', // Email string or array array( 'type' => 'set', 'emails' => array('sub1@example.com', 'sub2@example.com') ),

		'subject'	 	=> 'text',
		'html' 			=> 'longtext',
		'p_html' 		=> 'longtext',
		'plain' 		=> 'longtext',

		'editor' 		=> 'string',
		'ucode' 		=> 'string', // unique email id

		'assetsURL' 	=> 'string', // URL of the email's assets directory
		'assetsPath' 	=> 'string', // Path of the email's assets directory

		'particles' 	=> 'longtext', // addition email parts like repeatable post_blocks

		'created' 		=> 'datetime',
		'schedule' 		=> 'bigtimestamp', // timestamp
		'status' 		=> 'string',  //'pending', 'sent', 'inprogress', 'scheduled'
		'msg' 			=> 'longtext',
		'sentTo' 		=> 'int', // Current number of recipient the email is sent to
		'sent' 			=> 'int', // total number of emails emails
		'recipients' 	=> 'int', // Number of recipients which are eligible to receive this email. Sets at the beginig of sending
		'workerPid' 	=> 'string',
		'analytics' 	=> 'string', // analytics type  '', or ga or piwik
		'campName' 		=> 'string', // analytics  campaign name

		'emailAnalytics'=> array( 'type' => 'bool', 'default' => true ),

		'opens'			=> array( 'type' => 'int', 'readOnly' => true ),
		'clicks'		=> array( 'type' => 'int', 'readOnly' => true ),
		'unsubscribes' 	=> array( 'type' => 'int', 'readOnly' => true )
	);

	static $json_serialized = array('to');

	function __construct() {
		parent::__construct();

		$this->sent = 0;
		$this->recipients = 0;
		$this->status = 'draft';
		$this->created = date('Y-m-d H:i:s');		
	}

	function save() {		
		$u = newsmanUtils::getInstance();
		
		if ( !isset($this->ucode) || !$this->ucode ) {
			$this->ucode = $u->base64EncodeU( sha1($this->created.$this->subject.microtime(), true) );
		}

		$this->embedStyles();
		$this->addAnalytics();

		return parent::save();
	}

	public function getStatus() {
		global $wpdb;
		if ( isset($this->id) ) {
			$tbl = $this->getTableName();
			return $wpdb->get_var($wpdb->prepare("SELECT status FROM $tbl WHERE id = %d", $this->id));
		}
		return null;
	}

	public function incSent($n = 1) {
		global $wpdb;
		$tbl = $this->getTableName();
		$sql = "UPDATE $tbl SET `sent`=`sent`+%d where id = %d";
		$this->sent += 1;
		return $wpdb->query($wpdb->prepare($sql, $n, $this->id));
	}

	public function embedStyles() {
		$u = newsmanUtils::getInstance();

		if ( empty($this->html) ) {
			$this->p_html = $this->html;
		} else {			

			if ( !isset($this->editor) || !$this->editor ) {
				$this->editor = 'html';
			}


			if ( trim($this->html) === '' ) {
				$this->p_html = $this->html;
			} else {
				if ( $u->isResponsive($this->html) ) {
					$this->p_html = $u->normalizeShortcodesInLinks( $this->html );
				} else {
					$emo = new Emogrifier($this->html);
					$this->p_html = $u->normalizeShortcodesInLinks( @$emo->emogrify() );					
				}
			}
		}
	}

	private function addWebAnalytics($matches) {
		$u = newsmanUtils::getInstance();

		$url = $matches[3];

		$url = apply_filters('newsman_apply_analytics', $url, $this->analytics, $this->campName);

		return $matches[1].$url.$matches[4];
	}

	private function addWebAnalyticsPlainText($matches) {
		$u = newsmanUtils::getInstance();
		$url = $matches[0];
		$url = apply_filters('newsman_apply_analytics', $url, $this->analytics, $this->campName);
		return $url;
	}


	public function addAnalytics() {

		if ( isset($this->analytics) && $this->analytics ) {			
			$this->p_html = preg_replace_callback('/(<\w+[^>]+href=(\\\'|"))(\S+\:[^>]*?)(\2[^>]*>)/i', array($this, 'addWebAnalytics'), $this->p_html);
			$this->plain = preg_replace_callback('/http(?:s|):\/\/\S+/i', array($this, 'addWebAnalyticsPlainText'), $this->plain);
		}

	}

	public function getToLists() {
		$lists = array();
		$to = $this->to;

		if ( is_string($to) ) {
			$to = explode(',', $to);
		}

		if ( is_array($to) ) {
			foreach ($to as $dest) {
				$dest = trim($dest);
				if ( preg_match("/^[^@]*@[^@]*\.[^@]*$/", $dest) ) { 
					// email
					$this->plainAddresses[] = $dest;				
				} else {
					$list = newsmanList::findOne('name = %s', array($dest));
					if ( $list ) {
						$lists[] = $list;
					}
				}
			}			
		}
		return $lists;
	}

	private function addEmailAnalytics($matches) {
		global $newsman_current_subscriber;
		global $newsman_current_list;

		$u = newsmanUtils::getInstance();

		$url = $matches[3];

		// if not unsubscribe or "view online" link
		if ( strpos($url, 'newsman=unsubscribe') === false &&
			 strpos($url, 'newsman=email') === false ) {
			$link = newsmanAnLink::findOne('emailId = %d AND URL = %s', array(
				$this->id,
				$url
			));

			if ( !$link ) {
				$link = new newsmanAnLink();
				$link->emailId = $this->id;
				$link->URL = $url;
				$link->save();
			}

			// /c/{emailId}/{listId}/{emailAddrId}/{urlId}

			$url = get_bloginfo('wpurl').sprintf(
				'/c/%s-%s-%s-%s', 
				base_convert($this->id, 10, 36),
				base_convert($newsman_current_list->id, 10, 36),
				base_convert($newsman_current_subscriber['id'], 10, 36),
				base_convert($link->id, 10, 36)
			);			
		}

		return $matches[1].$url.$matches[4];
	}

	private function addEmailAnalyticsPlainText($matches) {
		global $newsman_current_subscriber;
		global $newsman_current_list;

		$u = newsmanUtils::getInstance();

		$url = $matches[0];

		// if not unsubscribe or "view online" link
		if ( strpos($url, 'newsman=unsubscribe') === false &&
			 strpos($url, 'newsman=email') === false ) {
			$link = newsmanAnLink::findOne('emailId = %d AND URL = %s', array(
				$this->id,
				$url
			));

			if ( !$link ) {
				$link = new newsmanAnLink();
				$link->emailId = $this->id;
				$link->URL = $url;
				$link->save();
			}

			// /c/{emailId}/{listId}/{emailAddrId}/{urlId}

			$url = get_bloginfo('wpurl').sprintf(
				'/c/%s-%s-%s-%s', 
				base_convert($this->id, 10, 36),
				base_convert($newsman_current_list->id, 10, 36),
				base_convert($newsman_current_subscriber['id'], 10, 36),
				base_convert($link->id, 10, 36)
			);			
		}

		return $url;
	}	

	private function wrapLinks($html) {

		if ( isset($this->emailAnalytics) && $this->emailAnalytics ) {			
			$html = preg_replace_callback('/(<\w+[^>]+href=(\\\'|"))(\w+\:[^>]*?)(\2[^>]*>)/i', array($this, 'addEmailAnalytics'), $html);
		}
		return $html;
	}

	private function wrapLinksInPlainText($html) {

		if ( isset($this->emailAnalytics) && $this->emailAnalytics ) {			
			$html = preg_replace_callback('/http(?:s|):\/\/\S+/i', array($this, 'addEmailAnalyticsPlainText'), $html);
		}
		return $html;
	}	

	private function addTrackingCode($html) {
		global $newsman_current_list;
		global $newsman_current_subscriber;

		if ( isset($this->emailAnalytics) && $this->emailAnalytics ) {
			$url = get_bloginfo('wpurl').sprintf(
				'/o/%s-%s-%s', 
				base_convert($this->id, 10, 36),
				base_convert($newsman_current_list->id, 10, 36),
				base_convert($newsman_current_subscriber['id'], 10, 36)
			);

			$code = '
			<style media="screen">.trkimg { background: transparent;}
			div.OutlookMessageHeader .trkimg,.gmail_quote .trkimg,#MailContainerBody .trkimg,table.moz-email-headers-table,blockquote .trkimg {background-image:url(\''.$url.'\');}</style>
			<div class="trkimg"></div><img src="'.$url.'" width="1" height="1" border="0" />';

			$html = preg_replace('#</body>#i', $code.'$0', $html);
		}
		return $html;
	}

	public function renderMessage($data, $compileThumbnails = true) {
		$u = newsmanUtils::getInstance();

		global $newsman_current_subscriber;
		global $sortcode_vars;
		global $newsman_current_email;

		$newsman_current_email = $this;

		$newsman_current_subscriber = $data;

		$sortcode_vars = $data;

		// apply shortcodes here
		$rendered = array(
			'subject' => do_shortcode( $this->subject ),
			'html' => $this->addTrackingCode( $this->wrapLinks( do_shortcode( $this->p_html ) ) ),
			'plain' => $this->wrapLinksInPlainText( do_shortcode( $this->plain ) )
		);

		if ( $compileThumbnails ) {
			$rendered['html'] = $u->compileThumbnails($rendered['html']);	
		}	

		return $rendered;
	}	

	public function isWorkerAlive() {
		$maxWorkerTimout = 360; // 3 minutes

		$u = newsmanUtils::getInstance();
		$t = newsmanTimestamps::getInstance();

		$ts = $t->getTS($this->workerPid);

		$elapsed = time() - $ts;

		$a = $elapsed <= $maxWorkerTimout;

		$u->log('[isWorkerAlive] workerPid('.$this->workerPid.') -> isAlive: '.$a);
		
		return $a;
	}

	public function releaseLocks() {
		$u = newsmanUtils::getInstance();
		$u->releaseLock('newsman-worker-'.$this->id);
	}

	public function getPublishURL() {
		$blogurl = get_bloginfo('wpurl');

		return "$blogurl/?newsman=email&email=".$this->ucode;
	} 

	// Analytics

	public function incOpens() {
		global $wpdb;
		$u = newsmanUtils::getInstance();
		$tn = $this->getTableName();
		$this->data['opens'] += 1;
		$sql = "UPDATE $tn SET `opens` = `opens`+1 WHERE id = %d";
		$sql = $wpdb->prepare($sql, $this->id);
		$res = $wpdb->query($sql);

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[newsmanEmail->incOpens] SQL: %s', $sql);
			$u->log('[newsmanEmail->incOpens] SQL res: %s', $res);
		}

		if ( is_wp_error($res) ){
			$u->log('[newsmanEmail->incOpens] Error: %s', $res->get_error_message());
			wp_die('[incOpens] Error: '.$res->get_error_message());
		} else {
			return $res;
		}
	}	

	public function incClicks() {
		global $wpdb;
		$u = newsmanUtils::getInstance();
		$tn = $this->getTableName();
		$this->data['clicks'] += 1;
		$sql = "UPDATE $tn SET `clicks` = `clicks`+1 WHERE id = %d";
		$sql = $wpdb->prepare($sql, $this->id);
		$res = $wpdb->query($sql);

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[newsmanEmail->incClicks] SQL: %s', $sql);
			$u->log('[newsmanEmail->incClicks] SQL res: %s', $res);
		}		

		if ( is_wp_error($res) ){
			$u->log('[newsmanEmail->incClicks] Error: %s', $res->get_error_message());
			wp_die('[incClicks] Error: '.$res->get_error_message());
		} else {
			return $res;
		}
	}		

	public function incUnsubscribes() {
		global $wpdb;
		$u = newsmanUtils::getInstance();
		$tn = $this->getTableName();
		$this->data['unsubscribes'] += 1;
		$sql = "UPDATE $tn SET `unsubscribes` = `unsubscribes`+1 WHERE id = %d";
		$sql = $wpdb->prepare($sql, $this->id);
		$res = $wpdb->query($sql);

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[newsmanEmail->incUnsubscribes] SQL: %s', $sql);
			$u->log('[newsmanEmail->incUnsubscribes] SQL res: %s', $res);
		}

		if ( is_wp_error($res) ){
			$u->log('[newsmanEmail->incUnsubscribes] Error: %s', $res->get_error_message());
			wp_die('[incUnsubscribes] Error: '.$res->get_error_message());
		} else {
			return $res;
		}
	}	

	/**
	 * $scale - Scale (string) year | month | week | day
	 */ 
	public function getStats($scale = 'day') {
		$points = newsmanAnTimeline::findAll('emailId = %d AND type = %s', array($this->id, $scale));

		return $points;
	}

	public function remove() {
		newsmanAnLink::removeAll('emailId = %d', array($this->id));
		newsmanAnTimeline::removeAll('emailId = %d', array($this->id));
		newsmanAnSubDetails::removeAll('emailId = %d', array($this->id));
		newsmanAnClicks::removeAll('emailId = %d', array($this->id));	

		parent::remove();
	}

}