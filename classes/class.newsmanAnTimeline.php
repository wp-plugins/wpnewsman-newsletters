<?php

class newsmanAnTimeline extends newsmanStorable {
	static $table = 'newsman_an_timeline';
	static $props = array(
		'id' => 'autoinc',

		'emailId' => 'int',
		'type' => array( 'type' => 'string', 'size' => 10),
		'dateValue' => 'string',
		'opens' => 'int',
		'clicks' => 'int',
		'unsubscribes' => 'int'
	);

	static $keys = array(
		'emailId_type_value' => array( 'opts' => array('unique'), 'cols' => array('emailId', 'type', 'dateValue') )
	);

	// TODO: add complex key

	function __construct() {
		parent::__construct();

		$this->opens = 0;
		$this->clicks = 0;
		$this->unsubscribes = 0;

		// 1, 5, year, 2014, 100, 200, 1
		// 2, 5, month, 2014-04, 50, 100, 1
		// 3, 5, week, 2014-04-35, 25, 50, 1
		// 4, 5, day, 2014-04-28, 10, 30, 1
	}

	private function getInsertUpdateQuery($updater) {
		$tbl = $this->getTableName();

		return "INSERT INTO $tbl(".static::getColumns().") VALUES".static::getValuesPlaceholders()."  ON DUPLICATE KEY UPDATE $updater";
	}

	private function incCounter($action) {
		global $wpdb;
		$sql = $this->getInsertUpdateQuery("`".$action."s`=`".$action."s`+1");

		$args = $this->getValues();

		array_unshift($args, $sql);
		$sql = call_user_func_array(array($wpdb, 'prepare'), $args);

		$u = newsmanUtils::getInstance();
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[AnTimeline] incCounter(%s): SQL: %s', $action, $sql);
		}		

		$res = $wpdb->query($sql);
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[AnTimeline] incCounter(%s): SQL result: %s', $action, $res);
		}

		if ( is_wp_error($res) ) {
			var_dump($res);
		}
		return $res;
	}

	private function inc($action) {
		$year = date('Y');
		$month = date('Y-m');
		$week = date('Y-m-W');
		$day = date('Y-m-d');

		$this->type = 'year';
		$this->dateValue = date('Y');
		$this->incCounter($action);
		
		$this->type = 'month';
		$this->dateValue = date('Y-m');
		$this->incCounter($action);
				
		$this->type = 'week';
		$this->dateValue = date('Y-m-W');
		$this->incCounter($action);

		$this->type = 'day';
		$this->dateValue = date('Y-m-d');
		$this->incCounter($action);
	}

	function open() {
		$this->opens = 1;
		$this->inc('open');
	}

	function click() {
		$this->clicks = 1;
		$this->inc('click');
	}

	function unsubscribe() {
		$this->unsubscribes = 1;
		$this->inc('unsubscribe');
	}

}