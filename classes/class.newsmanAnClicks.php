<?php

class newsmanAnClicks extends newsmanStorable {
	static $table = 'newsman_an_clicks';
	static $props = array(
		'id' => 'autoinc',

		'listId' => 'int',
		'subId' => 'int',
		'emailId' => 'int',
		'emailAddr' => 'text',
		'linkId' => 'int',
		'ip' => 'string',
		'clicks' => array( 'type' => 'int', 'readOnly' => true )

	);

	static $keys = array(
		'list_sub_link' => array( 'cols' => array('listId', 'subId', 'linkId'), 'opts' => array('unique') )
	);

	private function getInsertUpdateQuery($updater) {
		$tbl = $this->getTableName();

		$flags = 'showReadOnly';

		return "INSERT INTO $tbl(".static::getColumns($flags).") VALUES".static::getValuesPlaceholders($flags)." ON DUPLICATE KEY UPDATE $updater";
	}

	public function click() {
		global $wpdb;

		$this->data['clicks'] = 1;

		$sql = $this->getInsertUpdateQuery("`clicks`=`clicks`+1");

		$args = $this->getValues();

		array_unshift($args, $sql);
		$sql = call_user_func_array(array($wpdb, 'prepare'), $args);

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		} else if ( $r == 1 ) { // INSERT
			return NEWSMAN_UNIQUE_CLICK;
		} else if ( $r == 2 ) { // UPDATE
			return NEWSMAN_CLICK;
		} else {
			return $r;
		}
	}
}