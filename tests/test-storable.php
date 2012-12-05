<?php

require_once('inc.env.php');
require_once('../class.utils.php');
require_once('../lib/emogrifier.php');

error_reporting(E_ALL);

class newsmanStTest extends newsmanStorable {
	static $table = 'newsman_storable_test';
	static $props = array(
		'id' => 'autoinc',
		'to' => 'string', // Email string or array array( 'type' => 'set', 'emails' => array('sub1@example.com', 'sub2@example.com') ),

		'subject' => 'text',

		'html' => 'text',
		'p_html' => 'text',
		
		'plain' => 'text',

		//'bang' => 'int',

		'editor' => 'string',

		'ucode' => 'string', // unique email id

		'assets' => 'text',

		'particles' => 'text', // addition email parts like repeatable post_blocks

		// 'created' => 'datetime',
		// 'schedule' => 'bigtimestamp', // timestamp
		// 'status' => 'string',  //'pending', 'sent', 'inprogress', 'scheduled'
		// 'msg' => 'text',
		// 'sentTo' => 'int', // Current number of recipient the email is sent to
		// 'sent' => 'int', // total number of emails emails
		// 'recipients' => 'int', // Number of recipients which are eligible to receive this email. Sets at the beginig of sending
		// 'workerPid' => 'int'
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

		return parent::save();
	}	
}


$nsTable = newsmanStorable::$table;
$nsProps = newsmanStorable::$props;

newsmanStorable::$table = 'newsman_lst_default';
newsmanStorable::$props = array(
	'id' => 'autoinc',
	'ts' => 'datetime',
	'ip' => 'string',
	'email' => 'string',
	'status' => 'int',
	'ucode' => 'string',
	'fields' => 'text'
);

newsmanStorable::$table = $nsTable;
newsmanStorable::$props = array();

echo '<pre>';
print_r( newsmanStorable::ensureDefinition() );
echo '</pre>';

echo '<pre>';
print_r( newsmanStorable::getDefinition() );
echo '</pre>';


//newsman::ensureDefinition();
// echo '<pre>';
// print_r( newsmanStTest::ensureDefinition() );
// echo '</pre>';
//$nt = newsmanStTest::findOne();

// echo '<pre>';
// print_r(newsmanStTest::getDefinition());
// echo '</pre>';

// if ( $nt ) {
// 	echo '<pre>';
// 	print_r($nt);
// 	echo '</pre>';
// } else {
// 	$nt = new newsmanStTest();
// 	$nt->to = 'somebody';
// 	$nt->subject = 'The Subject';
// 	$nt->html = '<p>Some cool html string</p>';
// 	$nt->p_html = '<p>Some cool html string</p>';
// 	$nt->plain = 'Plain text string';
// 	$nt->editor = 'html';
// 	$nt->save();

// 	echo 'saved';
// }





