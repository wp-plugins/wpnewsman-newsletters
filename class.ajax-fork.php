<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.storable.php");

class newsmanAjaxFork extends newsmanStorable {
	static $table = 'newsman_ajax_forks';
	static $props = array(
		'id' => 'autoinc',
		'ts' => 'string',
		'method' => 'string',
		'url'=> 'string',
		'body' => 'string'
	);


	function __construct() {
		$this->method = 'get';
	}

}