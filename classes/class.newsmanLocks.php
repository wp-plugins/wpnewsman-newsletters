<?php

class newsmanLocks {

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	var $table;
	var $db;

	function __construct() {
		global $wpdb;
		$this->db = $wpdb;
		$this->table = $wpdb->prefix.'newsman_locks';

		$this->createTable();
	}

	static function dropTable() {
		global $wpdb;
		return $wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."newsman_locks");
	}

	private function createTable() {
		if ( !$this->tableExists() ) {
			$sql = "CREATE TABLE $this->table (
					`id` int(10) unsigned NOT NULL auto_increment,
					`name` varchar(255) NOT NULL DEFAULT '',
					`locked` tinyint(1) NOT NULL DEFAULT 0,
					PRIMARY KEY  (`id`),
					UNIQUE KEY (`name`)
					) CHARSET=utf8 ENGINE=InnoDB";

			$result = $this->db->query($sql);			
		}
	}

	private function tableExists() {
		$sql = $this->db->prepare("show tables like '%s';", $this->table);
		return $this->db->get_var($sql) == $this->table;
	}


	// name(string) locked(boolean)
	// someLock		1

	public function lock($name) {
		$sql = "INSERT INTO $this->table(`name`, `locked`) VALUES (\"$name\", 1) ON DUPLICATE KEY UPDATE locked=1";
		$res = $this->db->query($sql);
		// 1 - new row inserted
		// 2 - row updated
		return $res === 1;
	}

	public function isLocked($name) {
		$sql = $this->db->prepare("SELECT `locked` FROM $this->table WHERE `name` = %s", $name);
		return $this->db->get_var($sql) == 1;
	}

	public function releaseLock($name) {
		$sql = $this->db->prepare("DELETE FROM $this->table WHERE `name` = %s", $name);
		return $this->db->query($sql) === 1;
	}

	public function clearLocks() {
		return $this->db->query("truncate table $this->table") === 1;
	}
}

?>