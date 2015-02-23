<?php

class newsmanBlockedDomain extends newsmanStorable {
	static $table = 'newsman_blocked_domains';
	static $props = array(
		'id' => 'autoinc',

		'domain' 		=> 'text', // Email string or array array( 'type' => 'set', 'emails' => array('sub1@example.com', 'sub2@example.com') ),
		'senderIP'		=> 'string',
		'delistingURL'	=> 'text',
		'diagnosticCode' => 'text'
	);

	static $keys = array(
		'domain' => array( 'cols' => array( 'domain' ) )
	);	
}