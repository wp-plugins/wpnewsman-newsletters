<?php

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

	var $lastError = '';

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

	public function unsubscribe($bounceStatus = '') {		
		$this->rawRec['status'] = NEWSMAN_SS_UNSUBSCRIBED;
		if ( $bounceStatus ) {
			$this->rawRec['bounceStatus'] = $bounceStatus;
		}
	}

	public function confirm() {
		$this->rawRec['status'] = NEWSMAN_SS_CONFIRMED;
	}

	public function unconfirm() {
		$this->rawRec['status'] = NEWSMAN_SS_UNCONFIRMED;
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
			'id' => isset($this->rawRec['id']) ? $this->rawRec['id'] : NULL,
			'ts' => isset($this->rawRec['ts']) ? $this->rawRec['ts'] : NULL,
			'ip' => isset($this->rawRec['ip']) ? $this->rawRec['ip'] : NULL,
			'email' => isset($this->rawRec['email']) ? $this->rawRec['email'] : NULL,
			'status' => isset($this->rawRec['status']) ? $this->rawRec['status'] : NULL,
			'bounceStatus' => isset($this->rawRec['bounceStatus']) ? $this->rawRec['bounceStatus'] : NULL,
			'ucode' => isset($this->rawRec['ucode']) ? $this->rawRec['ucode'] : NULL
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
						fields='%s',
						bounceStatus='%s'
					WHERE id=%d";
		} else {
			$sql = "INSERT into 
					$this->tableName (ts, ip, email, status, ucode, fields, bounceStatus)
					VALUES('%s','%s','%s','%s','%s','%s','%s');"; //" ON DUPLICATE KEY UPDATE `status`=`status`;";
		}

		$sql = $wpdb->prepare(
					$sql,
					isset($r['ts']) 	? $r['ts'] : null,
					isset($r['ip']) 	? $r['ip'] : null,
					isset($r['email'])	? $r['email'] : null,
					isset($r['status']) ? $r['status'] : null,
					isset($r['ucode'])	? $r['ucode'] : null,
					isset($r['fields']) ? $r['fields'] : null,
					isset($r['bounceStatus']) ? $r['bounceStatus'] : null,
					isset($r['id'])		? $r['id'] : null
				);

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			$this->lastError = $wpdb->last_error;
			return false; 
		} else {
			if ( !isset($this->rawRec['id']) ) {
				$this->rawRec['id'] = $wpdb->insert_id;				
			}
			return $this->id;
		}
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