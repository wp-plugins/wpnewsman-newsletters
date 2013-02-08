<?php

define('NEWSMAN_SS_UNCONFIRMED',0);
define('NEWSMAN_SS_CONFIRMED',1);
define('NEWSMAN_SS_UNSUBSCRIBED',2);

class newsmanSub {
	private $rawRec;
	private $tableName;

	/*
		$rawRec = array(
			'id'
			'ts'
			'ip'
			'email'
			'status'
			'ucode'
			'fields' => array(

			)
		);

		{
			"broadcast":1,
			"welcome":1,
			"confirmation":1,
			"eml12":1
		}

	*/

	public function __get($name) {
		if ( $name == 'email' ) {
			return $this->rawRec['email'];
		} else {
			return !isset($this->rawRec['fields'][$name]) ? $this->rawRec[$name] : $this->rawRec['fields'][$name];
		}		
	}
	public function __set($name, $value) {
		$u = newsmanUtils::getInstance();

		if ( $name == 'email' ) {
			$this->rawRec['email'] = $value;
			// Leaving ucode if already exists
			if ( !isset($this->rawRec['ucode']) || !$this->rawRec['ucode'] ) {
				$this->rawRec['ucode'] = $u->base64EncodeU( md5($value.time(), true) );	
			}			
		} else {
			$this->rawRec['fields'][$name] = $value;
		}		
	}

	// subscriber status methods

	public function subscribe() {
		$this->rawRec['status'] = NEWSMAN_SS_UNCONFIRMED;
	}

	public function unsubscribe() {
		$this->rawRec['status'] = NEWSMAN_SS_UNSUBSCRIBED;
	}

	public function confirm() {
		$this->rawRec['status'] = NEWSMAN_SS_CONFIRMED;
	}

	public function is_subscribed() {
		return $this->rawRec['status'] == NEWSMAN_SS_CONFIRMED || $this->rawRec['status'] == NEWSMAN_SS_UNCONFIRMED;
	}

	public function is_unconfirmed() {
		return $this->rawRec['status'] == NEWSMAN_SS_UNCONFIRMED;
	}

	public function is_unsubscribed() {
		return $this->rawRec['status'] == NEWSMAN_SS_UNSUBSCRIBED;
	}

	public function is_confirmed() {
		return $this->rawRec['status'] == NEWSMAN_SS_CONFIRMED;
	}	

	// --- 

	public function __construct( $tableName ) {
		global $wpdb;
		$u = newsmanUtils::getInstance();
		
		$this->rawRec['ts'] = $u->current_time('mysql');
		$this->rawRec['ip'] = $u->peerip();
		
		$this->tableName = $tableName;
	}

	public function setDate($date) {
		$this->rawRec['ts'] = $date;
	}

	public function toJSON() {
		$jo = array(
			'id' => $this->rawRec['id'],
			'ts' => $this->rawRec['ts'],
			'ip' => $this->rawRec['ip'],
			'email' => $this->rawRec['email'],
			'status' => $this->rawRec['status'],
			'bounceStatus' => $this->rawRec['bounceStatus'],
			'ucode' => $this->rawRec['ucode']
		);
		if ( isset($this->rawRec['fields']) ) {
			$jo = array_merge($jo, $this->rawRec['fields']);	
		}
		return $jo;
	}

	public function toRawJSON() {
		$arr = $this->rawRec;
		unset($arr['fields']['nwsmn-subscribe']);
		return $arr;
	}	

	public function fill($data) {
		foreach ($data as $key => $value) {
			if ( $key === 'email' ) {
				$this->email = $value;
			} else if ( $key === 'ip' ) {
				$this->rawRec['ip'] = $value;
			} else {
				$this->rawRec['fields'][$key] = $value;	
			}			
		}
	}

	public function save() {
		global $wpdb;

		$u = newsmanUtils::getInstance();

		$r = $this->rawRec;

		if ( isset($r['fields']) ) {
			$r['fields'] = json_encode( $u->utf8_encode_all($r['fields']) );	
		}	

		if ( isset($this->rawRec['id']) ) {
			$sql = "UPDATE $this->tableName SET						
						ts='%s',
						ip='%s',
						email='%s',
						status='%s',
						ucode='%s',
						fields='%s'
					WHERE id=%d";
		} else {

			$sql = "INSERT HIGH_PRIORITY IGNORE into 
					$this->tableName (ts, ip, email, status, ucode, fields)
					VALUES('%s','%s','%s','%s','%s','%s');"; //" ON DUPLICATE KEY UPDATE `status`=`status`;";
		}
		$sql = $wpdb->prepare(
					$sql,
					isset($r['ts']) 	? $r['ts'] : null,
					isset($r['ip']) 	? $r['ip'] : null,
					isset($r['email'])	? $r['email'] : null,
					isset($r['status']) ? $r['status'] : null,
					isset($r['ucode'])	? $r['ucode'] : null,
					isset($r['fields']) ? $r['fields'] : null,
					isset($r['id'])		? $r['id'] : null
				);		

		//echo '<pre>'.$sql.'</pre>';

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return -1;
	}

	public function meta($name, $val = NULL) {
		if ( $name != 'fields' && array_key_exists($name, $this->rawRec) ) {
			if ( $val === NULL ) { // read
				return $this->rawRec[$name];
			} else { // write
				$this->rawRec[$name] = $val;
			}
		} else {
			return NULL;
		}
	}

	public function fromDBRow($row) {
		$row['fields'] = json_decode($row['fields'], true);

		$this->rawRec = $row;
		return $this;
	}
}