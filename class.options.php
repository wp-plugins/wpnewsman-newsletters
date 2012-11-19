<?php

require_once('class.utils.php');

class newsmanOptions {

	var $opts;

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self(); 
		} 
		return self::$instance; 
	} 	
	
	public function __construct() {
		$sopts = get_option('newsman_options');
		$u = newsmanUtils::getInstance();
		if ( $sopts ) {
			$o = unserialize($sopts);

			$pass = $this->get('mailer.smtp.pass', $o);
			if ( $pass !== null ) {
				$pass = $u->decrypt_pwd($pass);
				$this->set('mailer.smtp.pass', $pass, false, $o);
			}

			$this->opts = $o;

		} else {
			$this->opts = array();
		}
	}

	public function isEmpty() {
		return empty($this->opts);
	}

	public function load($options) {
		if ( is_string($options) ) {
			$o = unserialize($options);	
		} elseif ( is_array($options) ) {
			$o = $options;
		}		

		$this->opts = $this->mergeArrays($this->opts, $o);		
		$this->save();
	}

	private function mergeArrays($Arr1, $Arr2) {
		foreach($Arr2 as $key => $Value) {

			if ( array_key_exists($key, $Arr1) && is_array($Value) ) {
				$Arr1[$key] = $this->mergeArrays($Arr1[$key], $Arr2[$key]);	
			} else {
				$Arr1[$key] = $Value;	
			}

		}

		return $Arr1;
	}	

	private function save() {		
		$o = $this->opts;
		$u = newsmanUtils::getInstance();

		$pass = $this->get('mailer.smtp.pass', $o);
		if ( $pass !== null ) {
			$pass = $u->encrypt_pwd($pass);
			$this->set('mailer.smtp.pass', $pass, false, $o);
		}

		$str = serialize($o);
		update_option('newsman_options', $str);
	}

	private function walk($path, &$origin = null) {
		if ( $origin === null ) {
			$v = &$this->opts;
		} else {
			$v = &$origin;
		}
		foreach ($path as $s) {
			if ( isset($v[$s]) ) {
				$v = &$v[$s];				
			} else {
				return NULL;
			}
		}
		return $v;
	}

	private function walkAndSet($path, $value, &$origin = null) {
		if ( $origin === null ) {
			$v = &$this->opts;
		} else {
			$v = &$origin;
		}
		$c = count($path);

		for ($i=0; $i < $c; $i++) { 
			$s = $path[$i];
			$last = ($i === $c-1);						
			if ( isset($v[$s]) ) {
				if ( $last ) {
					$v[$s] = $value;
					return true;
				} else {
					$v = &$v[$s];
				}
			} else {
				return false;
			}
		}
	}	

	public function get($name = false, &$origin = null) {
		if ( !$name ) {
			return ($origin === null) ? $this->opts : $origin;
		} else {
			$path = split('\.', $name);
			return $this->walk($path, $origin);
		}
	}

	public function set($name, $value = NULL, $store = true, &$origin = null) {
		$r = true;
		if ( is_array($name) ) {
			foreach ($name as $key => $value) {
				$this->set($key, $value, false, $origin);
			}
		} else {
			$u = newsmanUtils::getInstance();			
			$path = split('\.', $name);
			$r = $this->walkAndSet($path, $value, $origin);
		}

		if ( $store ) {
			$this->save();
		}
		return $r;
	}

}