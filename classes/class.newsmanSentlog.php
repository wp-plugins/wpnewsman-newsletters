<?php

class newsmanSentlog {

	var $db;
	var $tableName;

	var $u;

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self(); 
		} 
		return self::$instance; 
	} 

	public function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->tableName = $wpdb->prefix . "newsman_sentlog";

		if ( !$this->tableExists() ) {
			$this->createBaseTable();
		}

		$this->u = newsmanUtils::getInstance();

	}

    // end of singletone code	

	private function tableExists() {
		$sql = $this->db->prepare("show tables like '%s';", $this->tableName);
		return $this->db->get_var($sql) == $this->tableName;
	}

	private function createBaseTable() {
		$sql = "CREATE TABLE $this->tableName (
				`id` int(10) unsigned NOT NULL auto_increment,
				`emailId` int(10) unsigned NOT NULL,
				`listId` int(10) unsigned NOT NULL  DEFAULT 0,
				`recipientId` int(10) unsigned NOT NULL  DEFAULT 0,
				`recipientAddr` varchar(255) NOT NULL DEFAULT '',
				`statusMsg` TEXT NOT NULL DEFAULT '',
				`errorCode` int(10) unsigned NOT NULL  DEFAULT 0,
				PRIMARY KEY  (`id`),
				KEY (`emailId`,`listId`),
				KEY (`recipientId`)
				) CHARSET=utf8";
		$result = $this->db->query($sql);
	}

	private function dropTable() {
		$sql = "DROP TABLE $this->tableName";
		return $this->db->query($sql);
	}


	public function getPendingFromList($emailId, $listName, $limit = 25) {

		$ln = $this->u->parseListName($listName);

		$list = newsmanList::findOne('name = %s', array($ln->name) );

		$list->selectionType = $ln->selectionType;

		if ( !$list ) {
			$this->u->log('[getPendingFromList] List with the name %s is not found', $listName);
			die("List with the name $listName is not found");
		}

		$subs = $list->getPendingBatch($emailId, $limit, $ln->selectionType);	

		$this->u->log('[getPendingFromList] pending batch length %s', count($subs));

		$result = array();

		foreach ($subs as $sub) {

			$tr = new newsmanEmailTransmission($sub->email, $sub->id, $list);
			$tr->addSubscriberData($sub->toJSON());

			$result[] = $tr;
		}

		return $result;
	}

	public function cleanupTempErrors($listId, $emailId) {
		global $wpdb;
		$set = '';
		$listIdsClause = '';

		if ( is_numeric($listId) ) {
			$listIdsClause = ' = '.$listId;
		} else if ( is_array($listId) ) {
			foreach ($listId as $id) {
				$listIdsClause .= ( !$listIdsClause ) ? 'in (' : ',';
				$listIdsClause .= $id;
			}
			$listIdsClause .= ')';
		}		

		$sql = "DELETE FROM $this->tableName WHERE 
					`emailId` = %d AND
					`listId` $listIdsClause AND
					`errorCode` > 0 AND
					`errorCode` <> 10";

		$sql = $wpdb->prepare($sql, $emailId);
		$wpdb->query($sql);
	}

	public function getPendingByEmails($emailId, $emails) {
		global $wpdb;
		// $set = '';
		// $del = '';
		// foreach ($emails as $email) {
		// 	$set .= $del.'"'.mysql_real_escape_string($email).'"';
		// 	$del = ', ';
		// }

		$sql = "SELECT `recipientAddr` FROM $this->tableName WHERE 
					`emailId` = %d AND
					`listId` = 0 AND
					`recipientId` = 0";

		$sql = $wpdb->prepare($sql, $emailId);

		$doneEmails = $this->db->get_col($sql);
		$result = array();

		foreach ($emails as $addr) {

			if ( !in_array($addr, $doneEmails) ) {
				$result[] = new newsmanEmailTransmission($addr);
			}
		}

		return $result;
	}

	public function getErrorsForList($emailId, $list) {
		global $wpdb;

		$listTbl = $list->tblSubscribers;

		$lsTbl = $list->getTableName();

		$slTbl = $this->tableName;

		$sql = "
			SELECT 
			`$slTbl`.`listId`,
			`$lsTbl`.`name` as 'listName',
			`$slTbl`.`recipientId`,
			`$slTbl`.`statusMsg`,
			`$listTbl`.`email` as 'email'
			FROM `$slTbl`, `$listTbl`, `$lsTbl`
			WHERE 
			`$slTbl`.`emailId` = %d AND
			`$slTbl`.`errorCode` > 0 AND
			`$listTbl`.`id` = `$slTbl`.`recipientId` AND
			`$lsTbl`.`id` = `$slTbl`.`listId`
		";

		$sql = $wpdb->prepare($sql, $emailId);

		$results = $wpdb->get_results($sql, ARRAY_A);


		$sqlPlainAddrs = "
			SELECT 
			`$slTbl`.`listId`,
			'' as 'listName',
			`$slTbl`.`recipientId`,
			`$slTbl`.`statusMsg`,
			`$slTbl`.`recipientAddr` as 'email'
			FROM `$slTbl`
			WHERE 
			`$slTbl`.`emailId` = %d AND
			`$slTbl`.`recipientId` = 0 AND
			`$slTbl`.`errorCode` > 0
		";

		$sql = $wpdb->prepare($sqlPlainAddrs, $emailId);

		$results = array_merge($results, $wpdb->get_results($sql, ARRAY_A));

		return $results;		
	}

	public function getErrors($emailId) {
		global $wpdb;

		$results = array();

		$email = newsmanEmail::findOne('id = %d', array( $emailId ) );

		if ( $email ) {
			$lists = $email->getToLists();

			

			foreach ($lists as $list) {
				$results = array_merge($results, $this->getErrorsForList($emailId, $list));
			}
		}

		return $results;
	}

	public function getPendingTransmissions($email) {
		$transmissions = array();

		$to = $email->to;

		if ( is_string($to) ) {
			$to = explode(',', $to);	
		}

		$plainAddresses = array();

		foreach ($to as $dest) {
			$dest = trim($dest);
			if ( preg_match("/^[^@]*@[^@]*\.[^@]*$/", $dest) ) { 
				// email
				$plainAddresses[] = $dest;				
			} else {
				// list name
				$dest = trim($dest);
				$transmissions = array_merge($transmissions, $this->getPendingFromList($email->id, $dest) );
			}
		}

		if ( count($plainAddresses) ) {
			$transmissions = array_merge($transmissions, $this->getPendingByEmails($email->id, $plainAddresses) );			
		}

		return $transmissions;
	}
}