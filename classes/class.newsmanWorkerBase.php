<?php

class newsmanWorkerBase {

	var $_db;
	var $_table;
	var $lockName = null;

	function __construct() {
		global $wpdb;
		$this->_db = $wpdb;
		$this->_table = $wpdb->prefix.'newsman_mqueue';

		$this->createTable();		
	}

	private function createTable() {
		if ( !$this->tableExists() ) {
			$sql = "CREATE TABLE $this->_table (
					`id` int(10) unsigned NOT NULL auto_increment,
					`processed` tinyint(1) unsigned NOT NULL DEFAULT 0,
					`workerId` varchar(255) NOT NULL DEFAULT '',
					`method` varchar(255) NOT NULL DEFAULT '',
					`arguments` varchar(255) NOT NULL DEFAULT '',
					`result` text NOT NULL DEFAULT '',
					PRIMARY KEY (`id`),
							KEY (`workerId`)
					) CHARSET=utf8";

			$result = $this->_db->query($sql);			
		}
	}

	static function dropTable() {
		global $wpdb;
		return $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."newsman_mqueue");
	}	

	private function tableExists() {
		$sql = $this->_db->prepare("show tables like '%s';", $this->_table);
		return $this->_db->get_var($sql) == $this->_table;
	}

	/**
	 * Creates lock for some unique value(worker_lock - for example it can be an email id) so other 
	 * workers will not be able to run without obtaining the lock
	 */ 
	public function lock() {
		//$this->lockfile = 'newsman-worker-'.$worker_lock;
		return $this->u->lock($this->lockName);
	}

	public function unlock() {
		if ( $this->lockName ) {
			return $this->u->releaseLock($this->lockName);
		}
	}	
}
