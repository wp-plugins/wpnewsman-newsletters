<?php

require_once('class.subscriber.php');
require_once('class.utils.php');


/**
 * Transmission is a wrapper around newsmanSubscriber and newsmanEmail
 * that has ability to store it's state in the database
 *
 * Transmisson reflect the state of sending particular email to
 * particuler subscriber.
 */

class newsmanEmailTransmission {

	var $recipientAddr;
	var $recipientId;

	var $list;

	var $statusMsg = '';

	var $errorCode = 0;

	var $data = array();

	public function __construct($recipientAddr, $recipientId = null, $list = null) {
		$this->recipientAddr = $recipientAddr;
		$this->recipientId = $recipientId;
		$this->list = $list;
	}

	public function __get($name) {
		if ( $name = 'email' ) {
			return $this->recipientAddr;
		}		
	}

	public function addSubscriberData($data) {
		$this->data = array_merge($this->data, $data);
	}

	public function getSubscriberData() {
		return $this->data;
	}

	public function done($emailId) {
		global $wpdb;

		$tableName = $wpdb->prefix . "newsman_sentlog";

		$sql = "INSERT INTO $tableName (emailId, listId, recipientId, recipientAddr, statusMsg, errorCode) 
							VALUES(%d, %d, %d, %s, %s, %d)";

		if ( $this->recipientId && $this->list ) {
			$sql = $wpdb->prepare($sql, $emailId, $this->list->id, $this->recipientId, '', $this->statusMsg, $this->errorCode);
		} else {
			$sql = $wpdb->prepare($sql, $emailId, 0, 0, $this->recipientAddr, $this->statusMsg, $this->errorCode);
		}		

		$res = $wpdb->query($sql);
		return $res;
	}

}


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
				KEY (`emailId`,`listId`)
				) CHARSET=utf8";
		$result = $this->db->query($sql);
	}

	private function dropTable() {
		$sql = "DROP TABLE $this->tableName";
		return $this->db->query($sql);
	}


	public function getPendingFromList($emailId, $listName, $limit = 25) {

		$list = newsmanList::findOne('name = %s', array($listName) );

		if ( !$list ) {
			die("List with the name $listName is not found");
		}

		$subs = $list->getPendingBatch($emailId, $limit);	

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
				$listIdsClause = ( !$listIdsClause ) ? 'in (' : ',';
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
			`$slTbl`.`recipientAddr` as 'email',
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

class newsmanTransmissionStreamer {
	var $email = null;
	var $buffer = array();
	var $to = array();
	var $sl;
	var $currentList = null;

	var $batchSize = 50;

	var $total = 0;
	var $plainAddresses = array();

	function __construct($email){

		$this->sl = newsmanSentlog::getInstance();

		$this->email = $email;

		$to = $email->to;

		if ( is_string($to) ) {
			$to = explode(',', $to);	
		}

		$this->plainAddresses = array();

		// filling the buffer with plain email address transmissions.
		// It shouldn't be lots of them, so we don't apply streaming 
		// here

		foreach ($to as $dest) {
			$dest = trim($dest);
			if ( preg_match("/^[^@]*@[^@]*\.[^@]*$/", $dest) ) { 
				// email
				$this->plainAddresses[] = $dest;				
			} else {
				$this->to[] = $dest;
			}
		}

		$this->cleanupTempErrors();

		

		if ( count($this->plainAddresses) ) {
			$this->buffer = $this->sl->getPendingByEmails($email->id, $this->plainAddresses);
		}
	}

	function cleanupTempErrors() {
		$listsIds = array();
		foreach ($this->to as $listName) {
			$list = newsmanList::findOne('name = %s', array($listName) );
			if ( $list ) {
				$listsIds[] = $list->id;
			}
		}
		if ( count($listsIds) > 0 ) {
			$this->sl->cleanupTempErrors($listsIds, $this->email->id);	
		}		
	}

	function getTotal() {
		$this->total = count($this->plainAddresses);

		foreach ($this->to as $listName) {
			$list = newsmanList::findOne('name = %s', array($listName) );

			if ( $list ) {
				$this->total += $list->getTotal();
			}
		}

		return $this->total;
	}

	function fillBuffer() {
		if ( !$this->currentList ) { // getting next list from the "To:" field
			$this->currentList = array_shift($this->to);
			if ( $this->currentList ) {
				$this->currentList = trim($this->currentList);
			} else {
				// nothing left, empty buffer
				$this->buffer = array();
				return;
			}
		}
		// list name
		$this->buffer = array_merge($this->buffer, 
			$this->sl->getPendingFromList($this->email->id, $this->currentList, $this->batchSize)
		);

		if ( !count($this->buffer) ) { // buffer is empty, no data left in this list, switching to another
			$this->currentList = null;
			$this->fillBuffer();
		}
	}

	function applyFilter($t, $email) {
		$g = newsman::getInstance();
		if ( class_exists('newsmanPro') ) {
			$gp = newsmanPro::getInstance();
			if ( !$gp->doFilters() ) {
				return $t;
			}
		}
		return $g->prepareTransmission($t, $email);
	}

	function getTransmission() {

		if ( !count($this->buffer) ) {
			$this->fillBuffer();
		}

		$t = array_pop($this->buffer);
		$t = $this->applyFilter($t, $this->email);

		return $t;
	}

}
