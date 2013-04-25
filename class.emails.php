<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.storable.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."lib/emogrifier.php");

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

		'assetsURL' => 'string', // URL of the email's assets directory
		'assetsPath' => 'string', // Path of the email's assets directory

		'particles' => 'text', // addition email parts like repeatable post_blocks

		'created' => 'datetime',
		'schedule' => 'bigtimestamp', // timestamp
		'status' => 'string',  //'pending', 'sent', 'inprogress', 'scheduled'
		'msg' => 'text',
		'sentTo' => 'int', // Current number of recipient the email is sent to
		'sent' => 'int', // total number of emails emails
		'recipients' => 'int', // Number of recipients which are eligible to receive this email. Sets at the beginig of sending
		'workerPid' => 'int',
		'analytics' => 'string', // analytics type  '', or ga or piwik
		'campName' => 'string' // analytics  campaign name
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
		$this->addAnalytics();

		return parent::save();
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
				$emo = new Emogrifier($this->html);
				$this->p_html = $u->normalizeShortcodesInLinks( $emo->emogrify() );
			}
		}
	}

	private function addTracking($matches) {
		$u = newsmanUtils::getInstance();

		$url = $matches[3];

		$url = apply_filters('newsman_apply_analytics', $url, $this->analytics, $this->campName);

		return $matches[1].$url.$matches[4];
	}

	public function addAnalytics() {

		if ( $this->analytics ) {			
			$this->p_html = preg_replace_callback('/(<\w+[^>]+href=(\\\'|"))(\w+\:[^>]*?)(\2[^>]*>)/i', array($this, 'addTracking'), $this->p_html);
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

	public function renderMessage($data, $compileThumbnails = true) {
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

		if ( $compileThumbnails ) {
			$rendered['html'] = $u->compileThumbnails($rendered['html']);	
		}	

		return $rendered;
	}	
}