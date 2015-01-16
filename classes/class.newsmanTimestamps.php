<?php

class newsmanTimestamps {

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
		$this->table = $wpdb->prefix.'newsman_timestamps';

		$this->createTable();
	}

	private function createTable() {
		if ( !$this->tableExists() ) {
			$sql = "CREATE TABLE $this->table (
					`id` int(10) unsigned NOT NULL auto_increment,
					`workerId` varchar(255) NOT NULL DEFAULT '',
					`timestamp` int(10) NOT NULL DEFAULT 0,
					PRIMARY KEY  (`id`),
					UNIQUE KEY (`workerId`)
					) CHARSET=utf8";

			$result = $this->db->query($sql);			
		}
	}

	public function dropTable() {
		$sql = "DROP TABLE IF EXISTS ".$this->table;
		return $this->db->query($sql) === 1;

	}

	private function tableExists() {
		$sql = $this->db->prepare("show tables like '%s';", $this->table);
		return $this->db->get_var($sql) == $this->table;
	}



	public function setTS($workerId) {
		$ts = time();
		$sql = "INSERT INTO $this->table(`workerId`, `timestamp`) VALUES (\"$workerId\", $ts) ON DUPLICATE KEY UPDATE `timestamp`=$ts";
		$res = $this->db->query($sql);

		return $res === 1;
	}

	public function getTS($workerId) {
		$sql = $this->db->prepare("SELECT `timestamp` FROM $this->table WHERE `workerId` = %s", $workerId);
		return $this->db->get_var($sql);
	}

	public function deleteTS($workerId) {
		$sql = $this->db->prepare("DELETE FROM $this->table WHERE `workerId` = %s", $workerId);
		return $this->db->query($sql) === 1;
	}
}