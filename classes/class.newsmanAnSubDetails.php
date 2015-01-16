<?php

class newsmanAnSubDetails extends newsmanStorable {
	static $table = 'newsman_an_sub_details';
	static $props = array(
		'id' => 'autoinc',

		'emailId' => 'int',
		'listId' => 'int',
		'subId' => 'int', 
		'emailAddr' => 'text', 
		'opens' => 'int', 
		'clicks' => 'int', 
		'unsubscribes' => 'int',
		'ip' => 'string'
	);

	static $keys = array(
		'eml_lst_sub' => array( 'cols' => array('emailId', 'listId', 'subId'), 'opts' => array('unique') )
	);

	function __construct() {
		parent::__construct();

		$this->emailId = 0;
		$this->listId = 0;
		$this->subId = 0;
		$this->emailAddr = '';
		$this->opens = 0;
		$this->clicks = 0;
		$this->unsubscribed = 0;
	}

	private function getInsertUpdateQuery($updater) {
		$tbl = $this->getTableName();

		return "INSERT INTO $tbl(".static::getColumns().") VALUES".static::getValuesPlaceholders()."  ON DUPLICATE KEY UPDATE $updater";
	}

	private function perform($action, &$rawResult = null) {
		global $wpdb;

		$u = newsmanUtils::getInstance();

		if ( !in_array($action, array('open', 'click', 'unsubscribe')) ) {
			return new WP_Error( 'Unknown action', sprintf(__( "There's no action '%s'", NEWSMAN ), $action) );
		}

		$sql = $this->getInsertUpdateQuery("`".$action."s`=`".$action."s`+1");		

		$args = $this->getValues('SKIP_AUTOINC');

		if ( $action === 'click' ) {
			// overwriting the IP address field
			$sql .= ", ip = %s";
			array_push($args, $this->ip);
		}

		array_unshift($args, $sql);
		$sql = call_user_func_array(array($wpdb, 'prepare'), $args);

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[AnSubDetails] perform(%s):  SQL: %s', $action, $sql);
		}

		$r = $wpdb->query($sql);

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[AnSubDetails] perform(%s):  SQL RESULT: %s', $action, $r);
		}

		$sd = $this->findOne('emailId = %d AND listId = %d AND subId = %d', array($this->emailId, $this->listId, $this->subId));

		$rawResult = $r;

		if ( $r === false ) {
			$u->log('[AnSubDetails] perform(%s):  SQL QUERY ERROR: %s', $action, $wpdb->last_error);
			return $wpdb->last_error;			
		} else if ( $r == 1 ) { // INSERT - always unique
			return ($action === 'click') ? NEWSMAN_UNIQUE_CLICK : NEWSMAN_UNIQUE_OPEN;
		} else if ( $r == 2 ) { // UPDATE - click can be unique
			if ( $action === 'click' ) {
				return ($sd->clicks == 1) ? NEWSMAN_UNIQUE_CLICK : NEWSMAN_CLICK;
			} else {
				return NEWSMAN_OPEN;
			}
		} else {
			return $r;
		}
	}

	public function open(&$rawResult = null) {
		$this->opens = 1;
		return $this->perform('open', $rawResult);
	}

	public function click(&$rawResult = null) {
		$this->opens = 1; // artificial open. if images are turned off in the client and a click comes
		$this->clicks = 1;
		return $this->perform('click', $rawResult);
	}

	public function unsubscribe(&$rawResult = null) {
		$this->opens = 1; // artificial open. if images are turned off in the client and a click comes
		$this->unsubscribed = 1;
		return $this->perform('unsubscribe', $rawResult);
	}
}