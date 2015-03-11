<?php

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
					) CHARSET=utf8 ENGINE=InnoDB";
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


	public function countSubs($stat, $q = null, $args = array()) {
		global $wpdb;

		$sel = '';

		$criteria = array();

		if ( $stat !== 'all' ) {
			if ( is_string($stat) ) {
				switch ($stat) {
					case 'confirmed':
						$stat = NEWSMAN_SS_CONFIRMED;						
						break;
					case 'unconfirmed': 
						$stat = NEWSMAN_SS_UNCONFIRMED;
						break;
					case 'unsubscribed':
						$stat = NEWSMAN_SS_UNSUBSCRIBED;
						break;
					default:
						return null;
						break;
				}
			}
			$criteria[] = 'status = '.$stat;
		}		

		$u = newsmanUtils::getInstance();

		if ( $q ) {
			$criteria[] = $q;
		}

		$sql = "SELECT COUNT(id) as cnt FROM $this->tblSubscribers";

		if ( count($criteria) > 0 ) {
			$sql .= " WHERE ".implode(' AND ', $criteria);
		}

		array_unshift($args, $sql);

		if ( count($args) > 1 ) {
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);	
		}

		return intval($wpdb->get_var($sql));
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

	public function deleteAll($type = null, $q = null) {
		global $wpdb;

		$sql = "DELETE FROM $this->tblSubscribers";

		$criteria = array();

		if ( $type ) {
			switch ($type) {
				case 'confirmed':
					$criteria[] = "status = ".NEWSMAN_SS_CONFIRMED;
					break;

				case 'unconfirmed':
					$criteria[] = "status = ".NEWSMAN_SS_UNCONFIRMED;
					break;

				case 'unsubscribed':
					$criteria[] = "status = ".NEWSMAN_SS_UNSUBSCRIBED;
					break;
			}
		}

		if ( $q ) {
			$criteria[] = $q;
		}

		if ( count($criteria) > 0 ) {
			$sql .= " WHERE ".implode(' AND ', $criteria);
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

		$blockedDomains = apply_filters('newsman_blocked_domains', array());

		if ( is_array($blockedDomains) && !empty($blockedDomains) ) {
			$excludeBlocked = ' AND `email` NOT REGEXP "@('.implode('|', $blockedDomains).')$"';
		} else {
			$excludeBlocked = '';
		}

		$sql = "SELECT * FROM $this->tblSubscribers WHERE status = ".$this->selectionType." AND NOT EXISTS (
					SELECT 1 from $slTbl WHERE
						 $slTbl.`emailId` = %d AND 
						 $slTbl.`listId` = %d AND
						 $slTbl.`recipientId` = $this->tblSubscribers.`id`
					)$excludeBlocked LIMIT %d";

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
		global $newsman_export_fields_list;

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

		$u = newsmanUtils::getInstance();

		$u->log('newsman_export_fields_list %s', print_r('newsman_export_fields_list', true));

		$fields = '*';
		if ( isset($newsman_export_fields_list) ) {
			$fields = '';
			$delim = '';
			foreach ($newsman_export_fields_list as $f) {
				$fields .= $delim.'`'.$f.'`';
				$delim = ', ';
			}
		}

		$sql = "SELECT $fields FROM $this->tblSubscribers ".$sel." ORDER BY `ts` DESC LIMIT %d, %d";

		$sql = $wpdb->prepare($sql, $offset, $limit);
		
		$u->log('getSubscribers: %s', $sql);

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

	public function getAllFields($fieldsList = array()) {
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

		$foundSpecial = false;

		foreach ($fieldsList as $f) {
			if ( $f[0] === '-' ) {
				$foundSpecial = true;
				$xField = substr($f, 1);
		
				$idx = array_search($xField, $fields);				
		
				if ( $idx !== false ) {
					array_splice($fields, $idx, 1);
				}
			} elseif ( $f[0] === '+' || $f[0] === ' ' ) {
				$foundSpecial = true;	
				$xField = substr($f, 1);			
				$idx = array_search($xField, $fields);				
				// if not found
				if ( $idx === false ) {
					$fields[] = $xField;
				}
			}
		}

		// if no special fields are found 
		// treat the list of field as the only once which need to be inlcuded

		if ( !$foundSpecial && !empty($fieldsList) ) {
			$fields = $fieldsList;
		}

		return $fields;		
	}

	/**
	 * Fetches subscribers in batches and conver them to csv rows
	 */
	private function subsToCSV($exportArgs) {

		if ( $exportArgs['customized'] ) {

			$res = $this->getSubscribers($exportArgs['offset'], $exportArgs['limit'], $exportArgs['type'], $exportArgs['extraQuery'], 'RAW_SQL_QUERY');
			if ( is_array($res) && !empty($res)  ) {
				// csv
				foreach ($res as $sub) {
					fputs($exportArgs['out'], $this->subToCSVRow($sub, $exportArgs['fields'])."\n");
				}					

				$p += 1;
			}
		} else {
			// dump all data
			$p = 1;
			$done = false;
			do {
				$res = $this->getPage($p, 1000, $exportArgs['type']);
				if ( is_array($res) && !empty($res)  ) {
					foreach ($res as $sub) {
						fputs($exportArgs['out'], $this->subToCSVRow($sub, $exportArgs['fields'])."\n");
					}
					$p += 1;
				} else {
					$done = true;
				}
			} while ( !$done );
		}
	}

	/**
	 * Fetches subscribers in batches and conver them to csv rows
	 */
	private function subsToJSON($exportArgs) {
		// $file, $fields, $map, $type = 'all'

		if ( $exportArgs['customized'] ) {			
			$res = $this->getSubscribers($exportArgs['offset'], $exportArgs['limit'], $exportArgs['type'], $exportArgs['extraQuery'], 'RAW_SQL_QUERY');
			if ( is_array($res) && !empty($res)  ) {

					$delim = '';
					foreach ($res as $sub) {
						fputs($exportArgs['out'], $delim.$this->subToJSON($sub, $exportArgs['fields'], $exportArgs['fieldsMap']));
						$delim = ', ';
					}

				$p += 1;
			}
		} else {
			// dump all data
			$p = 1;
			$done = false;
			do {
				$res = $this->getPage($p, 1000, $exportArgs['type']);
				if ( is_array($res) && !empty($res)  ) {
					$delim = '';
					foreach ($res as $sub) {
						fputs($exportArgs['out'], $delim.$this->subToJSON($sub, $exportArgs['fields'], $exportArgs['fieldsMap']));
						$delim = ',';
					}
					$p += 1;
				} else {
					$done = true;
				}
			} while ( !$done );
		}
	}

	public function export($exportArgs) {
		global $newsman_export_fields_map;
		global $newsman_export_fields_list;

		$exportArgs = array_merge(array(
			// defaults
			'type' => 'all',
			'linksFields' => array(),
			'fieldsMap' => array(),
			'fieldsList' => array(),
			'nofile' => false,
			'noheader' => false,
			'format' => 'csv',
			'offset' => 0,
			'limit' => 100,
			'extraQuery' => false
		), $exportArgs);

		$u = newsmanUtils::getInstance();

		//*
		switch ($exportArgs['format']) {
			case 'json':
				$ct = 'application/json';
				break;

			case 'csv':					
			default:
				$ct = 'text/json';
				break;
		}
		header( 'Content-Type: '.$ct );
		//*/

		if ( !$exportArgs['nofile'] ) {
			header( 'Content-Disposition: attachment;filename='.$exportArgs['fileName']);			
		}

		$out = fopen('php://output', 'w');

		if ( $out ) {
			$exportArgs['out'] = $out;
			$exportArgs['fields'] = $this->getAllFields($exportArgs['fieldsList']);

			// mapping fileds for CSV header
			$mappedCSVHeader = array();
			if ( isset($exportArgs['fieldsMap']) ) {
				$map = $exportArgs['fieldsMap'];
				foreach ($exportArgs['fields'] as $field) {
					$mappedCSVHeader[] = isset($map[$field]) ? $map[$field] : $field;
				}
			} else {
				$mappedCSVHeader = $fields;
			}

			if ( $exportArgs['format'] === 'json' ) {
				fwrite($out, '[');
				//$this->subsToJSON($out, $fields, $exportArgs['fieldsMap'], $exportArgs['type']);
				$this->subsToJSON($exportArgs);
				fwrite($out, ']');
			} else {
				if ( !$exportArgs['noheader'] ) {
					fputcsv($out, $mappedCSVHeader, ',', '"'); // CSV header output
				}
				//$this->subsToCSV($out, $fields, $exportArgs['type']);				
				$this->subsToCSV($exportArgs);
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