<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.storable.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.subscriber.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.options.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.sentlog.php");

class newsmanListException extends Exception { }

class newsmanList extends newsmanStorable {
	static $table = 'newsman_lists';
	static $props = array(
		'id' => 'autoinc',
		'uid' => 'string',
		'name' => 'string',
		'tblSubscribers' => 'string',
		// form
		'title' => 'text',
		'header' => 'text',
		'footer' => 'text',
		'form' => 'text',
		'extcss' => 'text'
	);

	static $linkFields = array('confirmation-link', 'resend-confirmation-link', 'unsubscribe-link');

	// main selection type used in major queries,
	// this value is modifed by the sender to get "unconfirmed" subset of the list
	var $selectionType = NEWSMAN_SS_CONFIRMED;

	function __construct($listName = 'default', $load = false) {
		parent::__construct();
		$this->name = $listName;
		if ( !$load ) {

			$options = newsmanOptions::getInstance();

			$this->form = newsmanGetDefaultForm();

			$this->uid = $this->getNewUID();

			$this->assignTable();
			$this->createSubscribersTable();			
		}
	}

	private function getNewUID($lvl = 0) {
		$u = newsmanUtils::getInstance();

		$lvl += 1;
		if ( $lvl > 10 ) {
			throw new Exception( __('Cannot find free unique list ID. Recursive operations limit exceeded.', NEWSMAN) );
		}
		$uid = $u->base64EncodeU(sha1(NONCE_SALT.microtime(), true));
		$lst = static::findOne('uid = %s', array($uid));
		if ( $lst === false ) {
			return $uid;
		} else {
			return $this->getNewUID($lvl);			
		}
	}

	public function save() {

		if ( !$this->name ) {
			throw new newsmanListException( __( '"name" field could not be empty.', NEWSMAN ) );
		}

		$r = parent::save();

		$u = newsmanUtils::getInstance();
		if ( !$u->listHasSystemTemplates($this->id) ) {
			$u->copySystemTemplatesForList($this->id);	
		}		

		return $r;
	}

	public function newSub() {
		return new newsmanSub($this->tblSubscribers);
	}

	private function assignTable() {
		global $wpdb;
		$u = newsmanUtils::getInstance();
		if ( !isset($this->tblSubscribers) || !$this->tblSubscribers ) {
			$this->tblSubscribers = $wpdb->prefix.'newsman_lst_'.$u->sanitizeDBFieldName( $this->name );
		}		
	}

	private function createSubscribersTable() {
		global $wpdb;
		$u = newsmanUtils::getInstance();
		if (  !$u->tableExists($this->tblSubscribers) ) {
			$sql = "CREATE TABLE $this->tblSubscribers (
					`id` int(10) unsigned NOT NULL auto_increment,
					`ts` datetime NOT NULL,
					`ip` varchar(50) NOT NULL,
					`email` varchar(255) NOT NULL,
					`status` tinyint(3) unsigned NOT NULL default 0,					
					`ucode` varchar(255) NOT NULL,
					`fields` TEXT,
					`bounceStatus` TEXT,
					UNIQUE (`email`),
					PRIMARY KEY  (`id`)
					) CHARSET=utf8";
			$result = $wpdb->query($sql);
		}
	}


	public function getStats($q = false, $ext = false) {
		global $wpdb;

		$u = newsmanUtils::getInstance();

		$stats = array();
		$stats['confirmed'] = 0;
		$stats['unconfirmed'] = 0;
		$stats['unsubscribed'] = 0;	
		$stats['all'] = 0;		

		if ( $q ) {
			$sql = $wpdb->prepare("SELECT COUNT(id) as cnt, status FROM $this->tblSubscribers WHERE email regexp %s group BY status", $u->preg_quote($q));
		} else {
			$sql = "SELECT COUNT(id) as cnt, status FROM $this->tblSubscribers group BY status";
		}		

		$res = $wpdb->get_results($sql, ARRAY_A);

		$all = 0;

		if ( $res && (!empty($res)) ) {
			foreach ( $res as $item ) {
				switch ( $item['status'] ) {
					case NEWSMAN_SS_CONFIRMED:
						$stats['confirmed'] = intval($item['cnt']);
					break;
					case NEWSMAN_SS_UNCONFIRMED:
						$stats['unconfirmed'] = intval($item['cnt']);
					break;
					case NEWSMAN_SS_UNSUBSCRIBED:
						$stats['unsubscribed'] = intval($item['cnt']);
					break;
				}
				$all += $item['cnt'];
			}
			$stats['all'] = $all;
		}

		/*
		if ( $ext ) {
			// today confirmed
			$sql = "select count(*) from $this->tblSubscribers where status = ".NEWSMAN_SS_CONFIRMED." and DATE(ts) = CURDATE()";
			$res = $wpdb->get_var($sql);
			$stats['confirmedToday'] = $res ? $res : 0; 


			// yesterday confirmed
			$sql = "select count(*) from $this->tblSubscribers where status = ".NEWSMAN_SS_CONFIRMED." and DATE(ts) = DATE_SUB(CURDATE(),INTERVAL 1 DAY)";
			$res = $wpdb->get_var($sql);
			$stats['confirmedYesterday'] = $res ? $res : 0;
		}
		//*/

		return $stats;
	}


	public function countSubs($stat) {
		global $wpdb;

		$u = newsmanUtils::getInstance();
		$sql = "SELECT COUNT(id) as cnt FROM $this->tblSubscribers WHERE status = %d";
		return intval($wpdb->get_var($wpdb->prepare($sql, $stat)));;
	}

	public function setStatus($ids, $status) {
		global $wpdb;

		$set = '';

		$c = count($ids);

		if ( $c > 1 ) {
			for ($i=0; $i < count($ids); $i++) { 
				$comma = $i > 0 ? ',' : '';
				$set .= $comma.$ids[$i];
			}

			$set = 'in ('.$set.');';

		} else {
			$set = '= '.$ids[0].';';
		}



		$sql = "UPDATE $this->tblSubscribers SET status = %d WHERE id ".$set;
		$sql = $wpdb->prepare($sql, $status);

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return true;
	}

	public function setStatusForAll($status) {
		global $wpdb;

		$sql = "UPDATE $this->tblSubscribers SET status = %d";
		$sql = $wpdb->prepare($sql, $status);

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return true;
	}	

	public function delete($ids) {
		global $wpdb;

		$set = '';

		for ($i=0; $i < count($ids); $i++) { 
			$comma = $i > 0 ? ',' : '';
			$set .= $comma.$ids[$i];
		}

		$sql = "DELETE FROM $this->tblSubscribers WHERE id in (".$set.")";

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return true;
	}

	public function deleteAll($type = null) {
		global $wpdb;

		$sql = "DELETE FROM $this->tblSubscribers";

		if ( $type ) {

			switch ($type) {
				case 'confirmed':
					$sql .= " WHERE status = ".NEWSMAN_SS_CONFIRMED;
					break;

				case 'unconfirmed':
					$sql .= " WHERE status = ".NEWSMAN_SS_UNCONFIRMED;
					break;

				case 'unsubscribed':
					$sql .= " WHERE status = ".NEWSMAN_SS_UNSUBSCRIBED;
					break;
			}

		}

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return true;
	}

	public function isSentInFull($emailId) {
		global $wpdb;
		$listTotal = $this->getTotal();

		$sl = newsmanSentlog::getInstance();
		$slTbl = $sl->tableName;
		$sql = "SELECT count(*) from $slTbl WHERE $slTbl.`emailId` = %d AND $slTbl.`listId` = %d";
		$sql = $wpdb->prepare($sql, $emailId, $this->id);
		$sentTotal = intval($wpdb->get_var($sql));
		return $listTotal === $sentTotal;
	}

	public function getPendingBatch($emailId, $limit = 25) {
		global $wpdb;

		$sl = newsmanSentlog::getInstance();
		$slTbl = $sl->tableName;

		$sql = "SELECT * FROM $this->tblSubscribers WHERE status = ".$this->selectionType." AND NOT EXISTS (
					SELECT 1 from $slTbl WHERE
						 $slTbl.`emailId` = %d AND 
						 $slTbl.`listId` = %d AND
						 $slTbl.`recipientId` = $this->tblSubscribers.`id`
					) LIMIT %d";

		$sql = $wpdb->prepare($sql, $emailId, $this->id, $limit);

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[getPendingBatch] SQL: '.$sql);
		}

		$rows = $wpdb->get_results($sql, ARRAY_A);

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[getPendingBatch] SQL RESULT: '.print_r($rows, true));
		}


		$res = array();

		foreach ( $rows as $row ) {
			$s = new newsmanSub($this->tblSubscribers);
			$s->fromDBRow($row);
			$res[] = $s;
		}

		return $res;		
	}

	public function getPendingTotal($emailId) {
		global $wpdb;

		$sl = newsmanSentlog::getInstance();
		$slTbl = $sl->tableName;

		$sql = "SELECT count(*) FROM $this->tblSubscribers WHERE status = ".$this->selectionType." AND `id` NOT IN ( SELECT `recipientId` from $slTbl WHERE $slTbl.`emailId` = %d AND $slTbl.`listId` = %d )";
		$sql = $wpdb->prepare($sql, $emailId, $this->id, $limit);

		return intval($wpdb->get_var($sql));
	}

	public function getTotal() {
		global $wpdb;

		$sql = "SELECT count(*) FROM $this->tblSubscribers WHERE status = ".$this->selectionType;

		return intval($wpdb->get_var($sql));
	}	

	public function getPage($p = 1, $ipp = 15, $type = 'all', $q = false) {

		$offset = ($p-1)*$ipp;
		$limit = $ipp;

		return $this->getSubscribers($offset, $limit, $type, $q);
	}	

	public function getSubscribers($offset = 0, $limit = 100, $type = 'all', $q = false, $rawQuery = false) {
		global $wpdb;

		$u = newsmanUtils::getInstance();

		$sel = '';

		if ( $type !== 'all' ) {
			if ( is_string($type) ) {
				switch ($type) {
					case 'confirmed':
						$type = NEWSMAN_SS_CONFIRMED;						
						break;
					case 'unconfirmed': 
						$type = NEWSMAN_SS_UNCONFIRMED;
						break;
					case 'unsubscribed':
						$type = NEWSMAN_SS_UNSUBSCRIBED;
						break;
					default:
						return null;
						break;
				}
			}
			$sel = ' WHERE status = '.$type;
		}

		if ( $q ) {
			if ( $rawQuery ) {
				$word = empty($sel) ? ' WHERE' : ' AND';
				$sel .= $word.' '.$q;
			} else {
				$word = empty($sel) ? ' WHERE' : ' AND';
				$sel .= $word.' email regexp %s';
				$sel = $wpdb->prepare($sel, $u->preg_quote($q));				
			}
		}		

		$sql = "SELECT * FROM $this->tblSubscribers ".$sel." ORDER BY `ts` DESC LIMIT %d, %d";

		$sql = $wpdb->prepare($sql, $offset, $limit);

		$rows = $wpdb->get_results($sql, ARRAY_A);

		$res = array();

		foreach ( $rows as $row ) {
			$s = new newsmanSub($this->tblSubscribers);
			$s->fromDBRow($row);
			$res[] = $s;
		}

		return $res;
	}	


	public function getFieldsPaged($p = 1, $ipp = 15, $type = 'all') {
		global $wpdb;

		$start = ($p-1)*$ipp;
		$end = $ipp;

		$sel = '';

		if ( $type !== 'all' ) {
			$sel = ' AND status = '.$type;
		}

		$sql = "SELECT fields FROM $this->tblSubscribers WHERE fields IS NOT NULL AND FIELDS != 'null' ".$sel." LIMIT %d, %d";
		$sql = $wpdb->prepare($sql, $start, $end);

		$fieldsets = $wpdb->get_col($sql);

		$combined = array();

		foreach ($fieldsets as $fieldset) {
			$fs = json_decode($fieldset, true);
			if ( is_array($fs) && count($fs) > 0 ) {
				$combined = $this->array_unique_merge($combined, array_keys($fs));
			}			
		}

		return $combined;
	}

	public function getLatestSubscriberTime() {
		global $wpdb;

		$u = newsmanUtils::getInstance();

		$sql = "SELECT ts FROM $this->tblSubscribers order by ts DESC limit 1";
		$stime = $wpdb->get_var($sql);
		return $stime ? mysql2date('U',$stime) : $u->current_time('timestamp');
	}

	public function getPageJSON($raw = false, $p = 1, $ipp = 15, $type = 'all', $q = false) {
		$res = array();
		$subs = $this->getPage($p, $ipp, $type, $q);

		foreach ($subs as $sub) {
			if ( $raw ) {
				$res[] = $sub->toRawJSON();	
			} else {
				$res[] = $sub->toJSON();	
			}
		}
		return $res;
	}

	/**
	 * finds first subscriber matched the selector
	 * @param [$selector] - sprintf() formated selector
	 * @param [$arg1] argument
	 * @param [$arg2] argument
	 * @param [$argN] argument
	 */
	public function findSubscriber() {
		global $wpdb;

		$an = func_num_args();
		$args = array();

		for ($i=0; $i < $an; $i++) { 
			$args[] = func_get_arg($i);
		}

		$sql = "SELECT * FROM $this->tblSubscribers ";

		if ( $an > 0 ) {
			
			$selector = array_shift($args);
			$sql .= 'WHERE '.$selector;

			array_unshift($args, $sql);
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);			

		}

		$row = $wpdb->get_row($sql, ARRAY_A);

		if ( !$row || empty($row) ) {
			return false;
		} else {
			$s = new newsmanSub($this->tblSubscribers);
			$s->fromDBRow($row);

			return $s;			
		}
	}

	/**
	 * finds subscribers matched the selector
	 * @param [$selector] - sprintf() formated selector
	 * @param [$arg1] argument
	 * @param [$arg2] argument
	 * @param [$argN] argument
	 */
	public function findAllSubscribers() {
		global $wpdb;

		$an = func_num_args();
		$args = array();

		for ($i=0; $i < $an; $i++) { 
			$args[] = func_get_arg($i);
		}

		$sql = "SELECT * FROM $this->tblSubscribers ";

		if ( $an > 0 ) {
			
			$selector = array_shift($args);
			$sql .= 'WHERE '.$selector;

			array_unshift($args, $sql);
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);			

		}

		$res = array();

		$rows = $wpdb->get_results($sql, ARRAY_A);

		if ( $rows && !empty($rows) ) {
			foreach ($rows as $row) {
				$s = new newsmanSub($this->tblSubscribers);
				$s->fromDBRow($row);
				$res[] = $s;
			}
		}

		return $res;
	}

	/**
	 * CSV export
	 */

	private function escapeCol($col) {
		if ( strpos($col, '"') !== false ) {
			$col = '"'.str_replace('"', '""', $col).'"';
		}

		return $col;		
	}

	private function arrayToCSVRow($arr) {
		$r = '';
		foreach ($arr as $col) {
			if ( $r !== '' ) { $r .= ','; }
			$r .= $this->escapeCol($col);
		}
		return $r;		
	}

	private function subToCSVRow($sub, $fields) {
		global $newsman_current_subscriber;
		global $newsman_current_list;
		
		$row = $sub->toJSON();

		$newsman_current_list = $this;
		$newsman_current_subscriber = $row;

		$n = newsman::getInstance();

		$r = '';

		foreach ($fields as $field) {
			if ( in_array($field, static::$linkFields) ) {
				$row[$field] = $n->getActionLink(str_replace('-link', '', $field));
			}
			if ( $r !== '' ) { $r .= ','; }
			$r .= $this->escapeCol( isset($row[$field]) ? $row[$field] : '' );
		}
		return $r;				
	}

	private function subToJSON($sub, $fields, $map) {
		global $newsman_current_subscriber;
		global $newsman_current_list;
		
		$row = $sub->toJSON();

		$newsman_current_list = $this;
		$newsman_current_subscriber = $row;

		$n = newsman::getInstance();

		$j = array();

		foreach ($fields as $field) {
			if ( in_array($field, static::$linkFields) ) {
				$row[$field] = $n->getActionLink(str_replace('-link', '', $field));
			}
			if ( isset( $map[$field] ) ) {
				$j[$map[$field]] = $row[$field];
			} else {
				$j[$field] = $row[$field];
			}
		}
		return json_encode($j);
	}

	private function array_unique_merge() {       
		return array_unique(call_user_func_array('array_merge', func_get_args())); 
	} 

	public function getAllFields() {
		$p = 1;
		$done = false;
		$fields = array('id', 'ts', 'ip', 'email', 'status', 'ucode'); // standart fields
		do {			
			$res = $this->getFieldsPaged($p, 100);
			if ( is_array($res) && count($res) > 0  ) {
				$fields = $this->array_unique_merge($fields, $res);	
				$p += 1;
			} else {
				$done = true;
			}
		} while ( !$done );

		return $fields;		
	}

	/**
	 * Fetches subscribers in batches and conver them to csv rows
	 */
	private function subsToCSV($file, $fields, $type = 'all') {

		if ( 
			defined('newsman_csv_export_limit') || 
			defined('newsman_csv_export_offset') ||
			defined('newsman_csv_export_query') 
		) {
			$offset = defined('newsman_csv_export_offset') ? newsman_csv_export_offset : 0;
			$limit  = defined('newsman_csv_export_limit') ? newsman_csv_export_limit : 100;
			$query  = defined('newsman_csv_export_query') ? newsman_csv_export_query : false;

			$res = $this->getSubscribers($offset, $limit, $type, $query, 'RAW_SQL_QUERY');
			if ( is_array($res) && !empty($res)  ) {
				// csv
				foreach ($res as $sub) {
					fputs($file, $this->subToCSVRow($sub, $fields)."\n");
				}					

				$p += 1;
			}

			return;			
		}

		$p = 1;
		$done = false;
		do {
			$res = $this->getPage($p, 1000, $type);
			if ( is_array($res) && !empty($res)  ) {
				foreach ($res as $sub) {
					fputs($file, $this->subToCSVRow($sub, $fields)."\n");
				}
				$p += 1;
			} else {
				$done = true;
			}
		} while ( !$done );
	}

	/**
	 * Fetches subscribers in batches and conver them to csv rows
	 */
	private function subsToJSON($file, $fields, $map, $type = 'all') {

		if ( 
			defined('newsman_csv_export_limit') || 
			defined('newsman_csv_export_offset') ||
			defined('newsman_csv_export_query') 
		) {
			$offset = defined('newsman_csv_export_offset') ? newsman_csv_export_offset : 0;
			$limit  = defined('newsman_csv_export_limit') ? newsman_csv_export_limit : 100;
			$query  = defined('newsman_csv_export_query') ? newsman_csv_export_query : false;

			$res = $this->getSubscribers($offset, $limit, $type, $query, 'RAW_SQL_QUERY');
			if ( is_array($res) && !empty($res)  ) {

					$delim = '';
					foreach ($res as $sub) {
						fputs($file, $delim.$this->subToJSON($sub, $fields, $map));
						$delim = ', ';
					}

				$p += 1;
			}

			return;			
		}

		$p = 1;
		$done = false;
		do {
			$res = $this->getPage($p, 1000, $type);
			if ( is_array($res) && !empty($res)  ) {
				$delim = '';
				foreach ($res as $sub) {
					fputs($file, $delim.$this->subToJSON($sub, $fields, $map));
					$delim = ',';
				}
				$p += 1;
			} else {
				$done = true;
			}
		} while ( !$done );
	}

	public function exportToCSV($filename, $type = 'all', $linksFields = array(), $forceFileOutput = true) {
		global $newsman_export_fields_map;

		if ( defined('newsman_export_format') ) {			
			switch (newsman_export_format) {
				case 'json':
					$ct = 'application/json';
					break;

				case 'csv':					
				default:
					$ct = 'text/json';
					break;
			}
			header( 'Content-Type: '.$ct );
		}

		if ( $forceFileOutput ) {
			header( 'Content-Disposition: attachment;filename='.$filename);			
		}

		//var_dump($newsman_export_fields_map);

		$out = fopen('php://output', 'w');

		if ( $out ) {
			if ( defined('newsman_export_format') && newsman_export_format === 'json' ) {
				fwrite($out, '[');
			}

			$fields = $this->getAllFields();

			$fields = array_merge($fields, $linksFields);
			$mappedFields = array();



			if ( isset($newsman_export_fields_map) ) {
				$map = $newsman_export_fields_map;
				foreach ($fields as &$field) {
					$mappedFields[] = isset($newsman_export_fields_map[$field]) ? $newsman_export_fields_map[$field] : $field;
				}
			} else {
				$map = array();
				$mappedFields = $fields;
			}

			if ( defined('newsman_export_format') && newsman_export_format === 'json' ) {
				$this->subsToJSON($out, $fields, $newsman_export_fields_map);
			} else {
				fputcsv($out, $mappedFields, ',', '"');
				$this->subsToCSV($out, $fields, $type);				
			}

			if ( defined('newsman_export_format') && newsman_export_format === 'json' ) {
				fwrite($out, ']');
			}		

			@fclose($out);			
		}


	}

	public function remove() {
		global $wpdb;
		$res = $wpdb->query("DROP TABLE $this->tblSubscribers");

		if ( $res === false ) {
			static::$lastError = $wpdb->last_error;
			return false;
		} else {
			$tpls = newsmanEmailTemplate::findAll($wpdb->prepare('`system` = 1 AND `assigned_list` = %d', array($this->id)));
			if ( $tpls ) {
				foreach ($tpls as $tpl) {
					$tpl->remove();
				}
			}			
			return parent::remove();	
		}		
	}

	public function unsubscribe($email, $statusStr) {
		global $wpdb;
		$c = 0;

		$sql = "UPDATE $this->tblSubscribers SET status = %d, bounceStatus = %s where email = %s";
		$sql = $wpdb->prepare($sql, NEWSMAN_SS_UNSUBSCRIBED, $statusStr, $email);

		return $wpdb->query($sql) == false;
	}

}