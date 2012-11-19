<?php

require_once('class.utils.php');

global $wpdb;

class newsmanStorable {

	static $table = 'newsman_table';
	//static $tblCreated = false;

	static $lastError = '';

	static $props = array(
		// visible in view table
	);

	static $json_serialized = array();

	// types
	// autoinc
	// int -> int(10) unsigned NOT NULL
	// boolean  TINYINT(1) unsigned NOT NULL, DEFAULT 0,
	// string varchar(255) NOT NULL, DEFAULT ''
	// text -> TEXT

	static $quotedTypes = array('string', 'text', 'date', 'time', 'datetime', 'timestamp');

	static function getNativeType($type) {
		$native = '';
		switch ( $type ) {
			case 'int':
				$native = 'int(10) unsigned NOT NULL';
				break;
			case 'autoinc':
				$native = 'int(10) unsigned NOT NULL auto_increment';
				break;
			case 'boolean':
			case 'bool':
				$native = 'TINYINT(1) unsigned NOT NULL DEFAULT 0';
				break;
			case 'string':
				$native = "varchar(255) NOT NULL DEFAULT \"\"";
				break;

			case 'date':
				$native = 'DATE';
				break;
			case 'time': 
				$native = 'TIME';
				break;
			case 'datetime':
				$native = 'DATETIME';
				break;
			case 'bigtimestamp':
				$native = 'BIGINT unsigned NOT NULL DEFAULT 0';
				break;
			case 'timestamp':
				$native = 'timestamp';
				break;
			case 'year':
				$native = 'YEAR';
				break;

			case 'text':
			default:
				$native = 'TEXT';
				break;
		}
		return $native;
	}

	static function getColumns() {
		$cols = ''; $del = '';
		foreach (static::$props as $name => $type) {
			if ( $type === 'autoinc' ) {
				continue;
			}
			$cols .= "$del`$name`";
			$del = ', ';
		}
		return "($cols)";
	}

	static function getValuesPlacehoders() {
		$valsPhs = ''; $del = '';
		foreach (static::$props as $name => $type) {
			if ( $type === 'autoinc' ) { continue; }

			$ph = in_array($type, static::$quotedTypes) ? '"%s"' : '%d';
			$valsPhs .= $del.$ph;
			$del = ', ';
		}
		return "($valsPhs)";
	}

	private function getAutoincName() {
		foreach (static::$props as $name => $type) {
			if ( $type === 'autoinc' ) {
				return $name;
			}
		}
		return null;
	}

	private function getAutoincValue() {
		$name = $this->getAutoincName();
		return isset($this->$name) ? $this->$name : null;
	}

	static function getUpdater() {
		$u = '';
		$del = '';
		foreach (static::$props as $name => $type) {
			if ( $type === 'autoinc' ) { continue; }
			$ph = in_array($type, static::$quotedTypes) ? '"%s"' : '%d';
			$u .= $del."`$name`=".$ph;
			$del = ', ';
		}
		return $u;
	}

	private function getValues() {
		$vals = array();
		$autoincval = null;
		foreach (static::$props as $name => $type) {
			if ( $type === 'autoinc' ) {
				$autoincval = $this->$name;
			} else {
				if ( !isset($this->$name) ) {
					$vals[] = '';
				} else {
					if ( is_array($this->$name) || is_object($this->$name) ) {					
						if ( in_array($name, static::$json_serialized) ) {
							$vals[] = json_encode($this->$name);
						} else {
							$vals[] = serialize($this->$name);	
						}
						
					} else {
						$vals[] = $this->$name;	
					}
				}
			}
		}
		$vals[] = $autoincval;
		return $vals;
	}

	function getTableName() {
		global $wpdb;
		return $wpdb->prefix.static::$table;
	}

	function save() {
		static::ensureTable();

		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;


		if ( !isset($this->id) ) {
			$sql = "INSERT into $tbl ".static::getColumns()." VALUES".static::getValuesPlacehoders().";";
		} else {
			$selector = $this->getAutoincName()."=%d";
			$updater = $this->getUpdater();
			//$updater[] = $this->getAutoincValue();
			$sql = "UPDATE $tbl SET $updater WHERE $selector";
		}


		$args = $this->getValues();

		array_unshift($args, $sql);

		$sql = call_user_method_array('prepare', $wpdb, $args);

		$res = $wpdb->query($sql);
		if ( $res !== false ) {
			if ( !isset($this->id) ) {
				$this->id = $wpdb->insert_id;				
			}
			return $this->id;
		} else {
			static::$lastError = $wpdb->last_error;
			return false;
		}
	}

	public function remove() {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$selector = $this->getAutoincName()."=".$this->getAutoincValue();

		if ( isset($this->id) ) {
			$sql = "DELETE FROM $tbl WHERE $selector";

			$res = $wpdb->query($sql);
			if ( $res !== false ) {
				return $res;
			} else {
				static::$lastError = $wpdb->last_error;
				return false;
			}

		}

		return false;
	}

	static function removeAll($selector = '1=1', $args = array()) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "DELETE FROM $tbl ";

		if ( $selector ) {
			$sql .= " WHERE $selector";
		} else {
			static::$lastError = '[remove()] $selector param must present in remove query.';
			return false;
		}

		array_unshift($args, $sql);

		$sql = call_user_method_array('prepare', $wpdb, $args);

		$res = $wpdb->query($sql);
		if ( $res !== false ) {
			return $res;
		} else {
			static::$lastError = $wpdb->last_error;
			return false;
		}
	}

	function toJSON() {
		$arr = array();
		foreach (static::$props as $name => $type) {
			switch ( $type ) {
				case 'bool':
				case 'boolean':
					$arr[$name] = (boolean)intval($this->$name);					
					break;
				case 'int':
					$arr[$name] = intval($this->$name);
					break;
				default: 
					$arr[$name] = $this->$name;
					break;
			}			
		}
		return $arr;
	}

	static function is_serialized($val){ 		
		if (!is_string($val)){ return false; } 
		if (trim($val) == "") { return false; } 	

		if ( preg_match("/^(i|s|a|o|d):(.*);/si",$val) != false) { return true; } 
		return false; 
	} 	

	// static function load($id) {
	// 	global $wpdb;
	// 	$bean = R::load($wpdb->prefix.static::$table, $id);
	// 	$camp = new static();

	// 	$beanData = $bean->export();

	// 	foreach ( $beanData as $key => $value ) {
	// 		$camp->$key = static::is_serialized($value) ? unserialize($value) : $value;
	// 	}

	// 	return $camp;
	// }

	// static function loadBean($bean) {

	// 	if ( $bean ) {
	// 		return false;
	// 	}

	// 	$camp = new static();

	// 	$beanData = $bean->export();

	// 	foreach ( $beanData as $key => $value ) {
	// 		$camp->$key = call_user_func(array('newsmanStorable', 'is_serialized'), $value) ? unserialize($value) : $value;
	// 	}

	// 	return $camp;		
	// }

	static function tableExists() {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = $wpdb->prepare("show tables like '%s';", $tbl);
		return $wpdb->get_var($sql) == $tbl;
	}

	static function createTable() {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$prKey = '';

		$sql = "\nCREATE TABLE $tbl (\n";

		foreach (static::$props as $name => $type) {
			$sql .= "\t`$name` ".static::getNativeType($type).", \n";
			if ( $type === 'autoinc' ) {
				$prKey = $name;
			}
		}
		$sql .= "PRIMARY KEY  (`$prKey`)\n) CHARSET=utf8";

		$result = $wpdb->query($sql);
	}

	static function ensureTable() {
		
		//if ( !static::$tblCreated ) {
			if ( !static::tableExists() ) {
				static::createTable();	
			}
		// 	static::$tblCreated = true;
		// }
	}

	static function findAll($selector  = null, $args = array()) {
		static::ensureTable();
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "SELECT * FROM $tbl ";

		if ( $selector ) {
			$sql .= " WHERE $selector";
		};

		array_unshift($args, $sql);

		$sql = call_user_method_array('prepare', $wpdb, $args);

		$storables = array();

		$rows = $wpdb->get_results($sql,ARRAY_A);

		foreach ( $rows as $row ) {
			if ( $row && !empty($row) ) {
				$so = new static();

				foreach ( $row as $key => $value ) {
					$so->$key = call_user_func(array('newsmanStorable', 'is_serialized'), $value) ? unserialize($value) : $value;			
				}
				$storables[] = $so;
			}			
		}

		return $storables;
	}

	static function findAllPaged($pg, $ipp, $selector  = null, $args = array()) {
		$start = ($pg-1)*$ipp;
		$count = $ipp;
		if ( !preg_match('/\bLIMIT\b\d+/i', $selector) ) {
			$selector .= " LIMIT %d,%d";
		}
		$args[] = $start;
		$args[] = $count;

		return static::findAll($selector, $args);
	}

	static function count($selector  = null, $args = array()) {
		static::ensureTable();
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "SELECT count(*) as count FROM $tbl ";

		if ( $selector ) {
			$sql .= " WHERE $selector";
		};

		array_unshift($args, $sql);

		$sql = call_user_method_array('prepare', $wpdb, $args);

		$storables = array();

		$row = $wpdb->get_row($sql, ARRAY_A);

		return ( is_array($row) && isset($row['count']) ) ? $row['count'] : 0;
	}

	static function countAll($groupByField, $selector = null, $args = array()) {
		static::ensureTable();
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		if ( $selector ) {
			$sql = $wpdb->prepare("SELECT COUNT(id) as cnt, $groupByField FROM $tbl WHERE $selector GROUP BY $groupByField", $args);
		} else {
			$sql = "SELECT COUNT(id) as cnt, $groupByField FROM $tbl group BY $groupByField";
		}

		$u = newsmanUtils::getInstance();

		return $wpdb->get_results($sql, ARRAY_A);
	}

	static function findOne($selector  = null, $args = array()){
		static::ensureTable();
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "SELECT * FROM $tbl ";

		if ( $selector ) {
			$sql .= " WHERE $selector";
		};

		$sql .= " LIMIT 1";

		array_unshift($args, $sql);

		$sql = call_user_method_array('prepare', $wpdb, $args);

		$row = $wpdb->get_row($sql);

		if ( $row && !empty($row) ) {
			$so = new static();

			foreach ( $row as $key => $value ) {

				if ( call_user_func(array('newsmanStorable', 'is_serialized'), $value) ) {
					$so->$key = unserialize($value);
				} elseif ( in_array($key, static::$json_serialized) ) {
					$so->$key = json_decode($value, true);
				} else {
					$so->$key = $value;
				}
			}
			return $so;
		} else {
			return false;
		}
	}
		
}