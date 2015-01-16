<?php
/**
 * Transmission is a wrapper around newsmanSubscriber and newsmanEmail
 * that has ability to store it's state in the database
 *
 * Transmisson reflect the state of sending particular email to
 * particular subscriber.
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
