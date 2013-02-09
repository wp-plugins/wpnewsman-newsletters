<?php

require_once('class.utils.php');

global $wpdb;

if ( !defined('NEWSMAN_COLUMN_POS_FIRST') ) { define('NEWSMAN_COLUMN_POS_FIRST', 1); }

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

	static function getNativeType($type, $short = false) {
		$native = '';
		switch ( $type ) {
			case 'int':
				$native = 'int(10) unsigned';
				if ( !$short ) { $native .= ' NOT NULL'; }
				break;
			case 'autoinc':
				$native = 'int(10) unsigned';
				if ( !$short ) { $native .= ' NOT NULL auto_increment'; }
				break;
			case 'boolean':
			case 'bool':
				$native = 'TINYINT(1) unsigned';
				if ( !$short ) { $native .= ' NOT NULL DEFAULT 0'; }
				break;
			case 'string':
				$native = "varchar(255)";
				if ( !$short ) { $native .= ' NOT NULL DEFAULT ""'; }
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
				$native = 'BIGINT unsigned';
				if ( !$short ) { $native .= ' NOT NULL DEFAULT 0'; }
				break;
			case 'timestamp':
				$native = 'timestamp';
				break;
			case 'year':
				$native = 'YEAR';
				break;
			case 'text':
			default:
				$native = 'TEXT NOT NULL DEFAULT ""';
				break;
		}
		return strtolower($native);
	}

	static function getColumns() {
		$cols = ''; $del = '';

		$props = static::getProps();

		foreach ($props as $prop) {
			if ( $prop['type'] === 'autoinc' ) {
				continue;
			}
			$n = $prop['name'];
			$cols .= "$del`$n`";
			$del = ', ';
		}
		return "($cols)";
	}

	static function getValuesPlacehoders() {
		$valsPhs = ''; $del = '';
		$props = static::getProps();

		foreach ($props as $prop) {
			if ( $prop['type'] === 'autoinc' ) { continue; }

			$ph = in_array($prop['type'], static::$quotedTypes) ? '"%s"' : '%d';
			$valsPhs .= $del.$ph;
			$del = ', ';
		}
		return "($valsPhs)";
	}

	private function getAutoincName() {
		$props = static::getProps();
		foreach ($props as $prop) {
			if ( $prop['type'] === 'autoinc' ) {
				return $prop['name'];
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
		$props = static::getProps();
		foreach ($props as $prop) {
			$name = $prop['name'];
			if ( $prop['type'] === 'autoinc' ) { continue; }
			$ph = in_array($prop['type'], static::$quotedTypes) ? '"%s"' : '%d';
			$u .= $del."`$name`=".$ph;
			$del = ', ';
		}
		return $u;
	}

	private function getValues() {
		$vals = array();
		$autoincval = null;
		$props = static::getProps();

		$u = newsmanUtils::getInstance();

		foreach ($props as $prop) {
			$name = $prop['name'];
			$type = $prop['type'];

			if ( $type === 'autoinc' ) {
				$autoincval = isset($this->$name) ? $this->$name : NULL;
			} else {
				if ( !isset($this->$name) ) {
					$vals[] = '';
				} else {
					if ( is_array($this->$name) || is_object($this->$name) ) {					
						if ( in_array($name, static::$json_serialized) ) {
							$vals[] = json_encode( $u->utf8_encode_all($this->$name) );
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

		if ( count($args) > 1 ) {
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);
		}

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

		if ( count($args) > 1 ) {
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);
		}

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
		$props = static::getProps();
		foreach ( $props as $prop ) {
			$name = $prop['name'];
			$type = $prop['type'];

			switch ( $type ) {
				case 'bool':
				case 'boolean':
					$arr[$name] = isset($this->$name) ? (boolean)intval($this->$name) : NULL;
					break;
				case 'int':
					$arr[$name] = isset($this->$name) ? intval($this->$name) : NULL;
					break;
				default: 
					$arr[$name] = isset($this->$name) ? $this->$name : NULL;
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

		$props = static::getProps();

		foreach ($props as $prop) {
			$name = $prop['name'];
			$sql .= "\t`$name` ".static::getNativeType($prop['type']).", \n";
			if ( $prop['type'] === 'autoinc' ) {
				$prKey = $prop['name'];
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

		if ( count($args) > 1 ) {
			$sql = call_user_func_array( array($wpdb, 'prepare'), $args );
		}

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

		if ( count($args) > 1 ) {
			$sql = call_user_func_array( array($wpdb, 'prepare'), $args );	
		}

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

		if ( count($args) > 1 ) {
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);
		}

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

	static function ensureDefinition() {
		$tblDefs = static::getDefinition();
		$modDefs = static::getProps();

		$tblCols = static::getColumnsFromDefs($tblDefs);
		$modCols = static::getColumnsFromDefs($modDefs);

		$prevColum = null;

		foreach ($tblDefs as $td) {
			if ( !in_array($td['name'], $modCols) ) {
				static::dropColumn($td['name']);
			}
		}	

		foreach ($modDefs as $i => $md) {
			$afterCol = ($prevColum === null) ? NEWSMAN_COLUMN_POS_FIRST : $prevColum;

			if ( !in_array($md['name'], $tblCols) ) { // new column
				static::addColumn($md['name'], $md['type'], $afterCol);
			} elseif ( $modDefs[$i]['name'] !== $tblDefs[$i]['name'] ) { // order changed
				static::modColumn($md['name'], $md['type'], $afterCol);
			}
			$prevColum = $md['name'];
		}
	}

	static function addColumn($name, $type, $posAfter = null) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$nativeType = static::getNativeType($type);

		$sql = "ALTER TABLE $tbl ADD COLUMN `$name` $nativeType";

		if ( $posAfter  === NEWSMAN_COLUMN_POS_FIRST ) {
			$sql .= ' FIRST';
		} else if ( $posAfter !== null ) {
			$sql .= " AFTER `$posAfter`";
		}

		return $wpdb->query($sql);
	}

	static function dropColumn($name) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "ALTER TABLE $tbl DROP COLUMN `$name`";

		return $wpdb->query($sql);
	}

	static function modColumn($name, $type, $posAfter = null ) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$nativeType = static::getNativeType($type);

		$sql = "ALTER TABLE $tbl MODIFY COLUMN `$name` $nativeType";

		if ( $posAfter  === NEWSMAN_COLUMN_POS_FIRST ) {
			$sql .= ' FIRST';
		} else if ( $posAfter !== null ) {
			$sql .= " AFTER `$posAfter`";
		}

		return $wpdb->query($sql);
	}

	static function getDefinition() {
		static::ensureTable();
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$def = array();
		$res = $wpdb->get_results("DESCRIBE $tbl", ARRAY_A);

		foreach ( $res as $fld ) {
			$def[] = array( 'name' => $fld['Field'], 'type' => $fld['Type'] );
		}

		return $def;
	}

	/**
	 * Returns the names of the fields in table definition 
	 * @param $def Array the array returnd from the getDefinition() function
	 * @return Array Array of columns names
	 */
	static function getColumnsFromDefs($def) {
		$fields = array();
		foreach ($def as $prop) {
			$fields[] = $prop['name'];
		}
		return $fields;
	} 

	static function getProps() {
		$props = array();
		$hasAutoinc = false;
		foreach (static::$props as $name => $type) {
			if ( $type === 'autoinc' ) {
				$hasAutoinc = true;
			}
			$props[] = array( 'name' => $name, 'type' => $type, 'nativeType' => static::getNativeType($type) );
		}

		if ( !$hasAutoinc ) {
			$props = array_unshift($props, array( 'name' => 'id', 'type' => 'autoinc' ) );
		}
		return $props;
	}
		
}