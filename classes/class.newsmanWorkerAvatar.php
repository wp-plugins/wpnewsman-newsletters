<?php

class newsmanWorkerAvatar extends newsmanWorkerBase {

	var $workerId;

	var $totalWait;

	function __construct($workerId, $totalOpWaitTime = 15) {
		parent::__construct();

		$this->totalWait = $totalOpWaitTime * 1000000; //mks - 15 sec

		$this->workerId = $workerId;
		$this->u = newsmanUtils::getInstance();
	}

	function __call($name, $arguments) {
		$opId = $this->_writeCall($name, $arguments);
		$res = $this->_waitForResult($opId);
		return $res;
	}

	function _writeCall($method, $arguments){
		$sql = "INSERT INTO $this->_table (`workerId`,`method`,`arguments`) VALUES(%s, %s, %s);";
		$sql = $this->_db->prepare($sql, $this->workerId, $method, serialize($arguments));
		$res = $this->_db->query($sql);
		if ( $res === 1 ) {
			return $this->_db->insert_id;
		} else {
			return NULL;
		}
	}

	function _waitForResult($opId) {
		$totalWait = $this->totalWait; // mks // 10s
		$count = 0; // 50 * 100 ms = 5s
		$res = NULL;

		while ( $res === NULL ) {
			$count += 1;
			$res = $this->_getOpResult($opId);
			$s = 400000*$count;
			usleep($s); // 100 ms
			$totalWait -= $s;
			if ( $totalWait <= 0 ) { break; }
		}

		if ( $res !== NULL ) {
			$this->_clearOpResult($opId);
		}
		return $res;
	}

	function _getOpResult($opId) {
		$sql = "SELECT `result` from $this->_table WHERE `id` = %d AND `processed` = 1";
		$sql = $this->_db->prepare($sql, $opId);

		$res = $this->_db->get_var($sql);

		if ( $res === NULL ) {
			//$this->u->log('_getOpResult(opId = '.$opId.') - NULL');
			return NULL;
		}

		$data = @unserialize($res);
		//$this->u->log('_getOpResult(opId = '.$opId.') - '.$res.', data - '.$data);
		if ($res === 'b:0;' || $data !== false) {
			return $data;
		} else {
			return NULL;
		}
	}

	function _clearOpResult($opId) {
		$sql = "DELETE FROM $this->_table WHERE id = %s";
		$sql = $this->_db->prepare($sql, $opId);
		$this->_db->query($sql);
	}
}
