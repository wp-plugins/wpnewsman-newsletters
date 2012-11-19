<?php

require_once("class.utils.php");
require_once('class.storable.php');
require_once('lib/emogrifier.php');

class newsmanEmail extends newsmanStorable {
	static $table = 'newsman_emails';
	static $props = array(
		'id' => 'autoinc',
		'to' => 'string', // Email string or array array( 'type' => 'set', 'emails' => array('sub1@example.com', 'sub2@example.com') ),

		'subject' => 'text',
		'html' => 'text',
		'p_html' => 'text',
		'plain' => 'text',

		'editor' => 'string',

		'ucode' => 'string', // unique email id

		'assets' => 'text',

		'particles' => 'text', // addition email parts like repeatable post_blocks

		'created' => 'datetime',
		'schedule' => 'bigtimestamp', // timestamp
		'status' => 'string',  //'pending', 'sent', 'inprogress', 'scheduled'
		'msg' => 'text',
		'sentTo' => 'int', // Current number of recipient the email is sent to
		'sent' => 'int', // total number of emails emails
		'recipients' => 'int', // Number of recipients which are eligible to receive this email. Sets at the beginig of sending
		'workerPid' => 'int'
	);

	static $json_serialized = array('to');

	function __construct() {

		$this->sent = 0;
		$this->recipients = 0;
		$this->status = 'draft';
		$this->created = date('Y-m-d H:i:s');
	}

	function save() {
		$u = newsmanUtils::getInstance();
		
		if ( !isset($this->ucode) ) {
			$this->ucode = $u->base64EncodeU( sha1($this->created.$this->subject.microtime(), true) );
		}

		$this->embedStyles();

		return parent::save();
	}

	function isStopped() {

		$fn = '/tmp/newsman-stop-email-'.$this->id;

		if ( file_exists($fn) ) { // && file_get_contents($fn) === 'STOP' ) 
			return true;
		}

		return false;
	}

	public function incSent($n = 1) {
		global $wpdb;
		$tbl = $this->getTableName();
		$sql = "UPDATE $tbl SET `sent`=`sent`+%d where id = %d";
		$this->sent += 1;
		return $wpdb->query($wpdb->prepare($sql, $n, $this->id));
	}

	public function clearStopFlag() {
		$fn = '/tmp/newsman-stop-email-'.$this->id;

		if ( file_exists($fn) ) {
			unlink($fn);
		}		
	}

	public function embedStyles() {
		$u = newsmanUtils::getInstance();

		if ( empty($this->html) ) {
			$this->p_html = $this->html;
		} else {
			$emo = new Emogrifier($this->html);

			if ( !$this->editor ) {
				$this->editor = 'html';
			}

			$this->p_html = $u->normalizeShortcodesInLinks( $emo->emogrify() );			
		}
	}

	public function getToLists() {
		$lists = array();
		$to = $this->to;

		if ( is_string($to) ) {
			$to = explode(',', $to);
		}

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
		return $lists;
	}

	public function renderMessage($data) {
		$u = newsmanUtils::getInstance();

		global $newsman_current_subscriber;
		global $sortcode_vars;
		global $newsman_current_email;

		$newsman_current_email = $this;

		$sortcode_vars = $data;

		$newsman_current_subscriber = $data;

		// apply shortcodes here
		$rendered = array(
			'subject' => do_shortcode( $this->subject ),
			'html' => do_shortcode( $this->p_html ),
			'plain' => do_shortcode( $this->plain )
		);

		$rendered['html'] = $u->compileThumbnails($rendered['html']);

		return $rendered;
	}	
}