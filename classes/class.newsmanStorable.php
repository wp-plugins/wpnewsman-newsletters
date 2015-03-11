<?php

global $wpdb;

if ( !defined('NEWSMAN_COLUMN_POS_FIRST') ) { define('NEWSMAN_COLUMN_POS_FIRST', 1); }

define('NEWSMAN_STORABLE_SHOW_HIDDEN_FIELDS', 1);
define('NEWSMAN_STORABLE_DEFAULT_BLOB_KEY_SIZE', 255);

// TODO: sync indexes in ensureDefinition

class newsmanStorable {

	var $__doLoad__ = false;

	static $table = 'newsman_table';
	//static $tblCreated = false;

	static $lastError = '';

	static $props = array(
		// visible in view table
	);

	protected $data = array();

	static $json_serialized = array();

	static $keys = array(
		// 'key_name' => array( 'cols' => array('col1', 'col2'), 'opts' => array('unique') )
	);

	// types
	// autoinc
	// int -> int(10) unsigned NOT NULL
	// boolean  TINYINT(1) unsigned NOT NULL, DEFAULT 0,
	// string varchar(255) NOT NULL, DEFAULT ''
	// text -> TEXT

	static $quotedTypes = array('string', 'text', 'longtext','date', 'time', 'datetime', 'timestamp');

	static function getPropertySize($typeOrProp, $default) {
		return (!is_string($typeOrProp) && isset($typeOrProp['size'])) ? intval($typeOrProp['size']) : $default;
	}

	static function getNativeType($typeOrProp, $short = false) {

		if ( is_array($typeOrProp) && isset($typeOrProp['nativeType']) ) { return $typeOrProp['nativeType']; }

		$native = '';
		$props = is_string($typeOrProp) ? array() : $typeOrProp;
		$type = is_string($typeOrProp) ? $typeOrProp : $typeOrProp['type'];		
		switch ( $type ) {
			case 'int':
				$size = static::getPropertySize($typeOrProp, 10);
				$native = sprintf('int(%d) unsigned NOT NULL', $size);
				break;
			case 'autoinc':
				$size = static::getPropertySize($typeOrProp, 10);
				$native = sprintf('int(%d) unsigned NOT NULL auto_increment', $size);
				break;
			case 'boolean':
			case 'bool':				
				$default = isset($props['default']) ? (int)$props['default'] : '0';
				$native = 'TINYINT(1) unsigned NOT NULL DEFAULT '.$default;
				break;
			case 'string':
				$size = static::getPropertySize($typeOrProp, 255);
				$default = isset($props['default']) ? $props['default'] : '';
				$native = sprintf('varchar(%d) NOT NULL DEFAULT "%s"', $size, $default);
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
				$default = isset($props['default']) ? (int)$props['default'] : 0;
				$native = 'BIGINT unsigned NOT NULL DEFAULT '.$default;
				break;
			case 'timestamp':
				$native = 'timestamp';
				break;
			case 'year':
				$native = 'YEAR';
				break;
			case 'longtext':
			default:
				$default = isset($props['default']) ? $props['default'] : '';
				$native = 'LONGTEXT NOT NULL DEFAULT "'.$default.'"';
				break;				
			case 'text':
			default:
				$default = isset($props['default']) ? $props['default'] : '';
				$native = 'TEXT NOT NULL DEFAULT "'.$default.'"';
				break;
		}
		return strtolower($native);
	}

	static function getColumns($show = null) {
		$cols = ''; $del = '';

		$props = static::getProps();

		foreach ($props as $prop) {
			if ( $show !== 'all' ) {
				if ( $prop['type'] === 'autoinc' ) { continue; } 
				if ( $prop['readOnly'] && $show !== 'showReadOnly' ) { continue; }
			}			
			$n = $prop['name'];
			$cols .= "$del`$n`";
			$del = ', ';
		}
		return $cols;
	}

	static function getValuesPlaceholders($show = null) {
		$valsPhs = ''; $del = '';
		$props = static::getProps();

		foreach ($props as $prop) {
			if ( $show !== 'all' ) {
				if ( $prop['type'] === 'autoinc' ) { continue; }
				if ( $prop['readOnly'] && $show !== 'showReadOnly' ) { continue; }
			}			

			$ph = in_array($prop['type'], static::$quotedTypes) ? '"%s"' : '%d';
			$valsPhs .= $del.$ph;
			$del = ', ';
		}
		return "($valsPhs)";
	}

	public function __construct() {
		$props = static::getProps();

		foreach ($props as $prop) {
			$name = $prop['name'];

			if ( isset($prop['default']) ) {
				$this->data[$name] = $prop['default'];
			}
		}		
	}

	public function __set($name, $value) {

		if ( $this->__doLoad__ ) {
			$this->data[$name] = $value;
		} else {
			$props = static::getProps();
			foreach ($props as $p) {
				if ( $p['name'] == $name ) {
					if ( !$p['readOnly'] ) {
						$this->data[$name] = $value;
					}
					break;
				}
			}
		}
	}	

	public function __get($name) {
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	public function __isset($name) {
		return isset($this->data[$name]);
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
		return isset($this->data[$name]) ? $this->data[$name] : null;
	}

	static function getUpdater() {
		$u = '';
		$del = '';
		$props = static::getProps();
		foreach ($props as $prop) {
			$name = $prop['name'];
			if ( $prop['type'] === 'autoinc' || $prop['readOnly'] ) { continue; }
			$ph = in_array($prop['type'], static::$quotedTypes) ? '"%s"' : '%d';
			$u .= $del."`$name`=".$ph;
			$del = ', ';
		}
		return $u;
	}

	protected function getValues($skipAutoinc = false) {
		$vals = array();
		$autoincval = null;
		$props = static::getProps();

		$u = newsmanUtils::getInstance();

		foreach ($props as $prop) {
			$name = $prop['name'];
			$type = $prop['type'];

			if ( $type === 'autoinc' ) {
				$autoincval = isset($this->data[$name]) ? $this->data[$name] : NULL;
			} else {
				if ( $prop['readOnly'] ) { continue; }
				if ( !isset($this->data[$name]) ) {
					$vals[] = '';
				} else {
					if ( is_array($this->data[$name]) || is_object($this->data[$name]) ) {					
						if ( in_array($name, static::$json_serialized) ) {
							$vals[] = json_encode( $u->utf8_encode_all($this->data[$name]) );
						} else {
							$vals[] = serialize($this->data[$name]);	
						}
						
					} else {
						$vals[] = $this->data[$name];	
					}
				}
			}
		}
		if ( !$skipAutoinc ) {
			$vals[] = $autoincval;	
		}		
		return $vals;
	}

	static function getTableName() {
		global $wpdb;
		return $wpdb->prefix.static::$table;
	}

	static function dropTable() {
		global $wpdb;
		return $wpdb->query("DROP TABLE IF EXISTS ".static::getTableName());
	} 

	function save() {
		$u = newsmanUtils::getInstance();

		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;
		
		if ( !isset($this->id) ) {
			$sql = "INSERT into $tbl (".static::getColumns().") VALUES".static::getValuesPlaceholders().";";			
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

		$u = newsmanUtils::getInstance();		
		// if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
		// 	$u->log('[save] SQL: '.$sql);
		// }		

		$res = $wpdb->query($sql);
		if ( $res !== false ) {
			if ( !isset($this->id) ) {
				$this->id = $wpdb->insert_id;				
			}
			return $this->id;
		} else {
			static::$lastError = $wpdb->last_error;
			if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
				$u->log('[save] error: %s', static::$lastError);
			}			
			return false;
		}
	}

	public function remove() {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$u = newsmanUtils::getInstance();

		$selector = $this->getAutoincName()."=".$this->getAutoincValue();

		if ( isset($this->id) ) {
			$sql = "DELETE FROM $tbl WHERE $selector";

			$u->log('[REMOVE]: %s', $sql);

			$res = $wpdb->query($sql);
			if ( $res !== false ) {
				return $res;
			} else {
				static::$lastError = $wpdb->last_error;
				return false;
			}
		} else {

			$u->log('[REMOVE]: $this->id is not defined %s', $this->id);

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
					$arr[$name] = isset($this->data[$name]) ? (boolean)intval($this->data[$name]) : NULL;
					break;
				case 'int':
					$arr[$name] = isset($this->data[$name]) ? intval($this->data[$name]) : NULL;
					break;
				default: 
					$arr[$name] = isset($this->data[$name]) ? $this->data[$name] : NULL;
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
		$u = newsmanUtils::getInstance();	
		$tbl = $wpdb->prefix.static::$table;

		$prKey = '';

		$sql = "\nCREATE TABLE $tbl (\n";

		$props = static::getProps();

		foreach ($props as $prop) {
			$name = $prop['name'];
			$sql .= "\t`$name` ".static::getNativeType($prop).", \n";
			if ( $prop['type'] === 'autoinc' ) {
				$prKey = $prop['name'];
			}
		}
		$sql .= "PRIMARY KEY  (`$prKey`)";

		if ( count( static::$keys ) ) {
			foreach (static::$keys as $keyName => $v) {
				$keyOpts = isset($v['opts']) ? $v['opts'] : array();
				$sql .= ", ".static::getIndexSQLDefinition($keyName, $v['cols'], $keyOpts);
			}
		}

		$sql .= "\n) CHARSET=utf8 ENGINE=InnoDB";

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[createTable] SQL: '.$sql);	
		}	

		$result = $wpdb->query($sql);
	}

	static function ensureTable() {
		$u = newsmanUtils::getInstance();	

		if ( !static::tableExists() ) {
			$u->log('[ensureTable]: table %s doesn\'t exist. Creating table...' , static::$table);
			static::createTable();	
		} else {
			$u->log('[ensureTable]: table %s alreay exists.' , static::$table);
		}
	}

	static function combineFieldsStr($fields) {
		$f = '';
		foreach ($fields as $key => $val) {
			if ( $f !== '' ) { $f .= ', '; }			
			if ( is_numeric($key) ) {
				$f .= "`$val`";
			} else {
				$f .= "`$key` as '$val'";
			}
		}
		return $f;
	}

	static function findAll($selector  = null, $args = array(), $opts = array()) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$fields = isset($opts['fields']) ? static::combineFieldsStr($opts['fields']) : static::getColumns('all');

		$sql = "SELECT $fields FROM $tbl ";

		if ( $selector ) {
			$sql .= " WHERE $selector";
		};

		array_unshift($args, $sql);

		if ( count($args) > 1 ) {
			$sql = call_user_func_array( array($wpdb, 'prepare'), $args );
		}

		$storables = array();

		$u = newsmanUtils::getInstance();

		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[findAll] SQL: %s', $sql);
		}

		$rows = $wpdb->get_results($sql,ARRAY_A);

		foreach ( $rows as $row ) {
			if ( $row && !empty($row) ) {
				$so = new static();

				$so->__beginLoad__();

				foreach ( $row as $key => $value ) {
					if ( call_user_func(array('newsmanStorable', 'is_serialized'), $value) ) {
						$so->$key = unserialize($value);
					} elseif ( in_array($key, static::$json_serialized) ) {
						$so->$key = json_decode($value, true);
					} else {
						$so->$key = static::castVals($key, $value);
					}
				}

				$so->__endLoad__();

				$storables[] = $so;
			}			
		}

		return $storables;
	}

	static function findRange($start, $limit, $selector  = null, $args = array(), $opts = array()) {

		if ( !$selector ) { $selector = '1=1'; }

		if ( $limit !== 0 && !preg_match('/\bLIMIT\b\d+/i', $selector) ) {
			$selector .= " LIMIT %d,%d";
			$args[] = $start;
			$args[] = $limit;
		}

		return static::findAll($selector, $args, $opts);
	}	

	static function findAllPaged($pg, $ipp, $selector  = null, $args = array(), $opts = array()) {
		$start = ($pg-1)*$ipp;
		$count = $ipp;
		if ( !preg_match('/\bLIMIT\b\d+/i', $selector) ) {
			$selector .= " LIMIT %d,%d";
		}
		$args[] = $start;
		$args[] = $count;

		return static::findAll($selector, $args, $opts);
	}

	static function count($selector  = null, $args = array()) {
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

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[count] SQL: '.$sql);
		}

		$row = $wpdb->get_row($sql, ARRAY_A);

		return ( is_array($row) && isset($row['count']) ) ? $row['count'] : 0;
	}

	static function countAll($groupByField, $selector = null, $args = array()) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		if ( $selector ) {
			$sql = "SELECT COUNT(id) as cnt, $groupByField FROM $tbl WHERE $selector GROUP BY $groupByField";

			array_unshift($args, $sql);

			if ( count($args) > 1 ) {
				$sql = call_user_func_array(array($wpdb, 'prepare'), $args);
			}

		} else {
			$sql = "SELECT COUNT(id) as cnt, $groupByField FROM $tbl group BY $groupByField";
		}

		$u = newsmanUtils::getInstance();

		$r =  $wpdb->get_results($sql, ARRAY_A);
		return $r;
	}

	static function castVals($key, $value) {
		$prop = static::getProp($key);

		switch ( $prop['type'] ) {
			case 'int':
			case 'autoinc':
				return (int)$value;
			case 'boolean':
			case 'bool':
				return (boolean)$value;
			default:
				return $value;
		}
	}

	static function findOne($selector  = null, $args = array()){
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "SELECT ".static::getColumns('all')." FROM $tbl ";

		if ( $selector ) {
			$sql .= " WHERE $selector";
		};

		$sql .= " LIMIT 1";

		array_unshift($args, $sql);

		if ( count($args) > 1 ) {
			$sql = call_user_func_array(array($wpdb, 'prepare'), $args);
		}

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[findOne] SQL: '.$sql);
		}		

		$row = $wpdb->get_row($sql);

		if ( $row && !empty($row) ) {
			$so = new static();

			$so->__beginLoad__();

			foreach ( $row as $key => $value ) {

				if ( call_user_func(array('newsmanStorable', 'is_serialized'), $value) ) {
					$so->$key = unserialize($value);
				} elseif ( in_array($key, static::$json_serialized) ) {
					$so->$key = json_decode($value, true);
				} else {
					$so->$key = static::castVals($key, $value);
				}
			}

			$so->__endLoad__();
			return $so;
		} else {
			return false;
		}
	}

	static function typesDifferent($tableType, $classType) {
		$tableType = strtolower($tableType);
		$classType = strtolower($classType);

		$ttArr = explode(' ', $tableType);

		$found = 0;
		foreach ($ttArr as $token) {
			if ( strpos($classType, $token) !== false ) {
				$found += 1;
			}
		}

		return ( $found / count($ttArr) <= 0.5 ); // 50% match
	} 

	static function ensureDefinition() {
		$u = newsmanUtils::getInstance();

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

		for ($i=0; $i < count($modDefs); $i++) { 
			$md = $modDefs[$i];

			for ($j=0; $j < count($tblDefs); $j++) { 
				$td = $tblDefs[$j];
				if ( $td['name'] === $md['name'] ) {

					if ( static::typesDifferent($td['type'], $md['nativeType']) ) {
						$u->log('[ensureDefinition] on table %s: %s column definition %s different from class\'s one %s. Modyfing column...', static::getTableName(), $td['name'], $td['type'], $md['nativeType']);
						static::modColumn($md['name'], $md);
					}
				}
			}

			if ( !in_array($md['name'], $tblCols) ) { // new column
				$u->log('[ensureDefinition] on table %s: column %s is missing. Adding column...', static::getTableName(), $md['name']);
				static::addColumn($md['name'], $md);
			}
		}
	}

	static function getIndexSQLDefinition($name, $colsArr, $opts) {
		$cols = ''; $del = '';

		foreach ($colsArr as $c) {
			$p = static::getProp($c);
			$keySize = '';
			if ( in_array($p['type'], array('text', 'longtext')) ) {
				$keySize = '('.NEWSMAN_STORABLE_DEFAULT_BLOB_KEY_SIZE.')';
			}
			$cols .= "$del`$c`$keySize";
			$del = ', ';
		}

		$u = in_array('unique', $opts) ? 'UNIQUE ' : '';

		$sql = $u."INDEX ($cols)";
		return $sql;
	}

	static function addIndex($name, $colsArr, $opts) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "ALTER TABLE $tbl ADD ".static::getIndexSQLDefinition($name, $colsArr, $opts);

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[addIndex] SQL: '.$sql);
		}		

		return $wpdb->query($sql);
	}

	static function addColumn($name, $typeOrProp, $posAfter = null) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$nativeType = static::getNativeType($typeOrProp);

		$sql = "ALTER TABLE $tbl ADD COLUMN `$name` $nativeType";

		if ( $posAfter  === NEWSMAN_COLUMN_POS_FIRST ) {
			$sql .= ' FIRST';
		} else if ( $posAfter !== null ) {
			$sql .= " AFTER `$posAfter`";
		}

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[addColumn] SQL: '.$sql);
		}

		return $wpdb->query($sql);
	}

	static function dropColumn($name) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$sql = "ALTER TABLE $tbl DROP COLUMN `$name`";

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[dropColumn] SQL: '.$sql);
		}

		return $wpdb->query($sql);
	}

	static function modColumn($name, $typeOrProp, $posAfter = null ) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$nativeType = static::getNativeType($typeOrProp);

		$sql = "ALTER TABLE $tbl MODIFY COLUMN `$name` $nativeType";

		if ( $posAfter  === NEWSMAN_COLUMN_POS_FIRST ) {
			$sql .= ' FIRST';
		} else if ( $posAfter !== null ) {
			$sql .= " AFTER `$posAfter`";
		}

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[modColumn] SQL: '.$sql);
		}

		return $wpdb->query($sql);
	}

	static function renameColumn($oldName, $newName) {
		global $wpdb;
		$tbl = $wpdb->prefix.static::$table;

		$type = static::$props[$newName];
		$nativeType = static::getNativeType($type);

		$sql = "ALTER TABLE $tbl CHANGE COLUMN `$oldName` `$newName` $nativeType";

		$u = newsmanUtils::getInstance();		
		if ( defined('NEWSMAN_DEBUG_EXPOSE_QUERIES') && NEWSMAN_DEBUG_EXPOSE_QUERIES === true ) {
			$u->log('[renameColumn] SQL: '.$sql);
		}


		return $wpdb->query($sql);
	}	

	static function getDefinition() {
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
			if ( is_array($type) ) {
				$p = array_merge( array(
					'name' => $name,
					'readOnly' => false
				), $type);
				if ( !isset($p['nativeType']) ) {
					$p['nativeType'] = static::getNativeType($p);
				}
				if ( !isset($p['name']) ) { $p['name'] = $name; }
			} else {
				$p = array( 'name' => $name, 'type' => $type, 'nativeType' => static::getNativeType($type), 'readOnly' => false );
			}
			if ( $type === 'autoinc' ) {
				$hasAutoinc = true;
			}
			$props[] = $p;
		}

		if ( !$hasAutoinc ) {
			$props = array_unshift($props, array( 'name' => 'id', 'type' => 'autoinc' ) );
		}
		return $props;
	}

	static function getProp($key) {

		$t = static::$props[$key];

		if ( is_array($t) ) {
			$p = array_merge( array(
				'name' => $key,
				'readOnly' => false
			), $t);
			if ( !isset($p['nativeType']) ) {
				$p['nativeType'] = static::getNativeType($p);
			}
			if ( !isset($p['name']) ) { $p['name'] = $key; }
		} else {
			$p = array( 'name' => $key, 'type' => $t, 'nativeType' => static::getNativeType($t), 'readOnly' => false );
		}		

		return $p;
	}	

	private function __beginLoad__() {
		$this->__doLoad__ = true;
	}

	public function __endLoad__() {
		$this->__doLoad__ = false;
	}
		
}