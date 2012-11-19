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

			),
			'emails' => array()
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
			if ( !$this->rawRec['ucode'] ) {
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

		$this->rawRec = array(
			'emails' => array()
		);
		
		$this->rawRec['ts'] = $u->current_time('mysql');
		$this->rawRec['ip'] = $u->peerip();
		
		$this->tableName = $tableName;
	}

	public function toJSON() {
		$jo = array(
			'id' => $this->rawRec['id'],
			'ts' => $this->rawRec['ts'],
			'ip' => $this->rawRec['ip'],
			'email' => $this->rawRec['email'],
			'status' => $this->rawRec['status'],
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
			if ( $key == 'email' ) {
				$this->email = $value;
			} else {
				$this->rawRec['fields'][$key] = $value;	
			}			
		}
	}

	public function save() {
		global $wpdb;

		$r = $this->rawRec;

		$r['fields'] = json_encode($r['fields']);
		$r['emails'] = json_encode($r['emails']);

		if ( isset($this->rawRec['id']) ) {
			$sql = "UPDATE $this->tableName SET						
						ts='%s',
						ip='%s',
						email='%s',
						status='%s',
						ucode='%s',
						fields='%s',
						emails='%s'
					WHERE id=%d";
		} else {

			$sql = "INSERT HIGH_PRIORITY IGNORE into 
					$this->tableName (ts, ip, email, status, ucode, fields)
					VALUES('%s','%s','%s','%s','%s','%s');"; //" ON DUPLICATE KEY UPDATE `status`=`status`;";
		}
		$sql = $wpdb->prepare(
					$sql,
					$r['ts'],
					$r['ip'],
					$r['email'],
					$r['status'],
					$r['ucode'],
					$r['fields'],
					$r['emails'],
					$this->rawRec['id']
				);		

		//echo '<pre>'.$sql.'</pre>';

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return -1;
	}

	public function lastBroadcastId($id = false) {
		if ( !isset($this->rawRec['emails']) ) {
			$this->rawRec['emails'] = array();
		}
		if ( $id === false ) { // get
			return $this->rawRec['emails']['broadcast'];
		} else { // set
			$this->rawRec['emails']['broadcast'] = intval($id);
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
		$row['emails'] = json_decode($row['emails'], true);

		$this->rawRec = $row;
		return $this;
	}
}

/*


class _newsmanList {

	var $rec = null;
	var $db;

	public $id = 1;

	public $tableName;

	public function __construct($list_name = false) {
		global $wpdb;
		$this->db = $wpdb;
		$this->tableName = $wpdb->prefix . "newsman_subs";

		if ( !$this->tableExists() ) {
			$this->createBaseTable();
		}
	}

    // end of singletone code	

	private function tableExists() {
		$sql = $this->db->prepare("show tables like '%s';", $this->tableName);
		return $this->db->get_var($sql) == $this->tableName;
	}

	private function createBaseTable() {
		$sql = "CREATE TABLE $this->tableName (
				`id` int(10) unsigned NOT NULL auto_increment,
				`ts` datetime NOT NULL,
				`ip` varchar(50) NOT NULL,
				`email` varchar(255) NOT NULL,
				`status` tinyint(3) unsigned NOT NULL default 0,
				`ucode` varchar(255) NOT NULL,
				`fields` TEXT,
				`emails` TEXT,
				PRIMARY KEY  (`id`)
				) CHARSET=utf8";
		$result = $this->db->query($sql);
	}

	public function newSub() {
		return new newsmanSub($this->tableName);
	}

	public function getStats($q = false) {
		global $wpdb;

		$stats = array();
		$stats['confirmed'] = 0;
		$stats['unconfirmed'] = 0;
		$stats['unsubscribed'] = 0;	
		$stats['all'] = 0;		

		if ( $q ) {
			$sql = $wpdb->prepare("SELECT COUNT(id) as cnt, status FROM $this->tableName WHERE email regexp %s group BY status", preg_quote($q));
		} else {
			$sql = "SELECT COUNT(id) as cnt, status FROM $this->tableName group BY status";
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
		return $stats;
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



		$sql = "UPDATE $this->tableName SET status = %d WHERE id ".$set;
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

		$sql = "DELETE FROM $this->tableName WHERE id in (".$set.")";

		$r = $wpdb->query($sql);

		if ( $r === false ) {
			return $wpdb->last_error;
		}
		return true;
	}	

	public function getPage($p = 1, $ipp = 15, $type = 'all', $q = false) {
		global $wpdb;

		$start = ($p-1)*$ipp;
		$end = $ipp;

		$sel = '';

		if ( $type !== 'all' ) {
			$sel = ' WHERE status = '.$type;
		}

		if ( $q ) {
			$word = empty($sel) ? ' WHERE' : ' AND';
			$sel .= $word.' email regexp %s';
			$sel = $wpdb->prepare($sel, preg_quote($q));
		}



		$sql = "SELECT * FROM $this->tableName ".$sel." ORDER BY id ASC LIMIT %d, %d";
		$sql = $wpdb->prepare($sql, $start, $end);

		// echo '<pre>';
		// echo $sql;
		// echo '</pre>';

		$rows = $wpdb->get_results($sql, ARRAY_A);

		$res = array();

		foreach ( $rows as $row ) {
			$s = new newsmanSub($this->tableName);
			$s->fromDBRow($row);
			$res[] = $s;
		}

		return $res;
	}


	public function findOne() {
		global $wpdb;

		$an = func_num_args();
		$args = array();

		for ($i=0; $i < $an; $i++) { 
			$args[] = func_get_arg($i);
		}

		$sql = "SELECT * FROM $this->tableName ";

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
			$s = new newsmanSub($this->tableName);
			$s->fromDBRow($row);

			return $s;			
		}
	}


	public function find() {
		global $wpdb;

		$an = func_num_args();
		$args = array();

		for ($i=0; $i < $an; $i++) { 
			$args[] = func_get_arg($i);
		}

		$sql = "SELECT * FROM $this->tableName ";

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
				$s = new newsmanSub($this->tableName);
				$s->fromDBRow($row);
				$res[] = $s;
			}
		}

		return $res;
	}

	public function getLatestSubscriberTime() {
		global $wpdb;

		$u = newsmanUtils::getInstance();

		$sql = "SELECT ts FROM $this->tableName order by ts DESC limit 1";
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

}

//*/

// $list = new newsmanList();
// $list->find

// $s = $subs->find(array('id' => 123));

// $s['fields']['first-name'] = 'John';

// $subs->store($s);


// $subs->store