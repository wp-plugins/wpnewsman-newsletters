<?php

class newsmanShortCode {
	var $name;
	var $params = array();

	var $source = null;
	var $start = null;
	var $length = null;

	function __construct($name, &$source = null, $start = null, $length = null) {
		$this->name = $name;

		$this->source = $source;
		$this->start = $start;
		$this->length = $length;
	}

	/**
	 * @param name String || newParams Array
	 * @param [val] Mixin
	 */
	function set() {

		$argsNum = func_num_args();
		if ( $argsNum === 1 ) {
			$params = func_get_arg(0);
			if ( !is_array($params) ) {
				throw new Exception("[newsmanShortCode.set()] Argument should be an array");				
			}
			foreach ($params as $key => $value) {
				$this->params[$key] = $value;
			}			
		} else if ( $argsNum === 2 ) {
			$key = func_get_arg(0);
			$value = func_get_arg(1);

			if ( !is_string($key) ) {
				throw new Exception("[newsmanShortCode.set()] First argument should be a string");				
			}

			$this->params[$key] = $value;

		} else {
			throw new Exception("[newsmanShortCode.set()] Wrong arguemnts number $argsNum. Should be 1 or 2");
		}
	}

	function get($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}

	function toString() {
		$str = '['.$this->name;
		foreach ($this->params as $key => $value) {
			if ( $value !== null ) {
				$str .= ' '.$key.'="'.addslashes($value).'"';	
			} else {
				$str .= ' '.$key;
			}			
		}
		$str .= ']';
		return $str;
	}

	function updateSource() {

		if ( !$this->source ) {	throw new Exception("[newsmanShortCode.updateSource()] shortcode source is not defined"); }
		if ( !$this->start ) {	throw new Exception("[newsmanShortCode.updateSource()] shortcode start position is not defined"); }
		if ( !$this->length ) {	throw new Exception("[newsmanShortCode.updateSource()] shortcode original length is not defined"); }

		$newSC = $this->toString();
		$this->source = substr_replace($this->source, $newSC, $this->start, $this->length);
		return $this->source;
	}

	function matchParams($search) {
		foreach ($search as $key => $value) {
			if ( is_array($value) ) {
				if ( !isset($this->params[$key]) || !in_array($this->params[$key], $value) ) {
					return false;
				}
			} else {
				if ( !isset($this->params[$key]) || $this->params[$key] !== $value ) {
					return false;
				}				
			}
		}
		return true;
	}

	function paramsFromStr($str) {
		$args = explode(' ', $str);

		if ( is_array($args) ) {
			foreach ($args as $arg) {
				if ( preg_match('/(\w+)=(?:\'|")(.*?)(?:\'|")/i', $arg, $m) ) {
					$this->params[$m[1]] = $m[2];
				} else {
					$this->params[$arg] = null;
				}
			}
		}
	}
}