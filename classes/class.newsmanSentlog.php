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
				`errorCode` int(10) unsigned NOT NULL DEFAULT 0,
				`status` TINYINT(1) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (`id`),
				KEY emailIdIdx (`emailId`),
				KEY emailIdStatusIdx (`emailId`, `status`),
				KEY emailIdListIdIdx (`emailId`,`listId`),
				KEY recipientIdIdx (`recipientId`),
				UNIQUE KEY emailIdListIdRecIdIdx (`emailId`,`listId`, `recipientId`)
				) CHARSET=utf8 ENGINE=InnoDB";
		$result = $this->db->query($sql);
	}

	public function getTotal($emailId) {
		$sql = $this->db->prepare("select count(*) from $this->tableName where `emailId` = %d", $emailId);
		return $this->db->get_var($sql);
	}

	private function dropTable() {
		$sql = "DROP TABLE $this->tableName";
		return $this->db->query($sql);
	}

	public function clearBrokenRecipients($emailId) {
		$sql = "DELETE FROM `$this->tableName` WHERE `emailId` = %d AND status = 0";		

		$this->db->query( $this->db->prepare($sql, intval($emailId)) );
	}

	public function deleteForEmail($emailId) {
		$sql = "DELETE FROM `$this->tableName` WHERE `emailId` = %d";		

		$this->db->query( $this->db->prepare($sql, intval($emailId)) );
	}


	public function initEmailRecipients($emailId, $listName) {
		if ( !$emailId ) { return; } 
		if ( !is_string($listName) ) {
			$list = $listName;
		} else {
			$ln = $this->u->parseListName($listName);
			$list = newsmanList::findOne('name = %s', array($ln->name) );
		}

		// $ln = $this->u->parseListName($listName);
		if ( !$list ) { return; }

		$list->selectionType = $ln->selectionType;

		$sql = "
			INSERT IGNORE INTO `$this->tableName`(
			                `emailId`,
			                `listId`,
			                `recipientId`,
			                `recipientAddr`,
			                `statusMsg`,
			                `errorCode`,
			                `status`)
			    SELECT 
			        $emailId as emailId,
			        $list->id as listId,
			        `$list->tblSubscribers`.`id` as recipientId,
			        `$list->tblSubscribers`.`email` as recipientAddr,
			        '' as statusMsg,
			        0 as errorCode,
			        0 as status
			    FROM $list->tblSubscribers 
			    WHERE 
			        `$list->tblSubscribers`.`status` = $list->selectionType
		";

		if ( defined('NEWSMAN_SHOW_INIT_EMAILS_QUERY') ) {
			 $this->u->log('[initEmailRecipients] SQL: '.$sql);
		}

		return $this->db->query($sql);
	}

	private function setSQLWaitTimeout() {
		$this->db->query('SET @old_wait_timeout := @@session.wait_timeout');
		$this->db->query('SET @@session.wait_timeout := 60');		
	}

	private function restoreSQLTimeout() {
		$this->db->query('SET @@session.wait_timeout := @old_wait_timeout');
	}

	/**
	 * Returns a single pending transmission from list
	 * Returns:
	 * - Transmission object
	 * - null - meens no more pendings in the list
	 */
	public function getSinglePendingFromList($emailId, $listName) {
		$ln = $this->u->parseListName($listName);

		$list = newsmanList::findOne('name = %s', array($ln->name) );
		// $this->u->log('[getSinglePendingFromList] list name: %s', $ln->name);

		$list->selectionType = $ln->selectionType;
		// $this->u->log('[getSinglePendingFromList] list selectionType: %s', $ln->selectionType);

		if ( !$list ) {
			$this->u->log('[getSinglePendingFromList] List with the name %s is not found', $ln->name);
			die("List with the name $listName is not found");
		}

		$this->setSQLWaitTimeout();
		$this->db->query('START TRANSACTION');

		try {
			$trData = $this->db->get_row(
						$this->db->prepare("
							SELECT * FROM $this->tableName
							WHERE 
								`emailId` = %d AND
								`listId` = %d AND
								`status` = 0
							LIMIT 1
							FOR UPDATE
						", $emailId, $list->id)					
					);

			if ( !$trData ) { 
				$this->db->query('ROLLBACK');
				$this->restoreSQLTimeout();
				return null;
			}

			$tr = new newsmanEmailTransmission($trData->id, $trData->recipientAddr, $trData->recipientId, $list);

			$sub = $list->findSubscriber("id = %d", array($trData->recipientId));
			if ( !$sub ) {
				$tr->setError(NEWSMAN_ERR_SUBSCRIBER_NOT_FOUND, 'Data for subscriber '.$trData->recipientAddr.' of list '.$listName.' not found.');				
			} else {
				$tr->addSubscriberData($sub->toJSON());
				$tr->setStaus(NEWSMAN_TS_SENDING);
			}			
			$this->db->query('COMMIT');
			$this->restoreSQLTimeout();

			return $sub ? $tr : $this->getSinglePendingFromList($emailId, $listName);

		} catch ( Exception $e ) {			
			$this->db->query('ROLLBACK');
			$this->restoreSQLTimeout();
		}

		return null;
	}

	/**
	 * Returns a single pending transmission for email
	 * Returns:
	 * - Transmission object
	 * - null - meens no more pendings for email
	 */
	public function getSinglePendingForEmail($emailId) {
		$this->setSQLWaitTimeout();
		$this->db->query('START TRANSACTION');

		try {
			$trData = $this->db->get_row(
						$this->db->prepare("
							SELECT * FROM $this->tableName
							WHERE 
								`emailId` = %d AND
								`status` = 0
							LIMIT 1
							FOR UPDATE
						", $emailId)
					);

			if ( !$trData ) { 
				$this->db->query('ROLLBACK');
				$this->restoreSQLTimeout();
				return null;
			}

			$list = newsmanList::findOne('id = %d', array($trData->listId) );

			if ( !$list ) {
				$this->u->log('[getSinglePendingForEmail] Error: List with id %s is not found', $trData->listId);
			}			

			$tr = new newsmanEmailTransmission($trData->id, $trData->recipientAddr, $trData->recipientId, $list);

			if ( !$list ) {
				$tr->setError(NEWSMAN_ERR_LIST_NOT_FOUND, 'List with id '.$trData->listId.' is not found.');

				$this->db->query('COMMIT');
				$this->restoreSQLTimeout();

				return $this->getSinglePendingForEmail($emailId);
			}

			$sub = $list->findSubscriber("id = %d", array($trData->recipientId));
			if ( !$sub ) {
				$tr->setError(NEWSMAN_ERR_SUBSCRIBER_NOT_FOUND, 'Data for subscriber '.$trData->recipientAddr.' of list '.$listName.' not found.');
			} else {
				$tr->addSubscriberData($sub->toJSON());
				$tr->setStaus(NEWSMAN_TS_SENDING);
			}			
			$this->db->query('COMMIT');
			$this->restoreSQLTimeout();

			return $sub ? $tr : $this->getSinglePendingForEmail($emailId);

		} catch ( Exception $e ) {			
			$this->db->query('ROLLBACK');
			$this->restoreSQLTimeout();
		}

		return null;
	}
	

	/**
	 * Resets email transmissions which where initiated with SENDING flag 
	 * but where abandoned afterwards
	 */
	public function resetAbandonedEmails($emailId) {
		global $wpdb;

		$sql = "UPDATE $this->tableName 
				SET `status` = 0
				WHERE 
					`emailId` = %d AND
					`status` = 1";

		$sql = $wpdb->prepare($sql, $emailId);
		return $wpdb->query($sql);	
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