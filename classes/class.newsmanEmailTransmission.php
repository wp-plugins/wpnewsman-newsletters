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
	var $id;

	var $statusMsg = '';

	var $errorCode = 0;

	var $data = array();

	public function __construct($trId, $recipientAddr, $recipientId = null, $list = null) {
		$this->id = $trId;
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

	public function setError($errorCode, $errorMsg) {
		global $wpdb;

		$tableName = $wpdb->prefix . "newsman_sentlog";

		$sql = "UPDATE `$tableName` SET `errorCode` = %d, `statusMsg` = %s, `status`= %d WHERE id = %d";

		return $wpdb->query( $wpdb->prepare($sql, array($errorCode, $errorMsg, NEWSMAN_TS_ERROR, $this->id)) );		
	}

	public function setStaus($status) {
		global $wpdb;

		$tableName = $wpdb->prefix . "newsman_sentlog";

		$sql = "UPDATE `$tableName` SET `status` = %d WHERE id = %s";
		return $wpdb->query( $wpdb->prepare($sql, array($status, $this->id)) );
	}

	public function done() {
		return $this->setStaus(NEWSMAN_TS_SENT);
	}

}
