<?php


class NBHHeaderField {
	var $mainValue = null;
	var $pairs = array();
	var $values = array();
	var $RAW = null;

	function __construct($fieldValue) {
		$this->RAW = $fieldValue;
		$this->parse($fieldValue);
	}

	private function parse($fieldValue) {
		$hash = array();
		$segments = preg_split('/;\s*/', $fieldValue, -1, PREG_SPLIT_NO_EMPTY);

		$mvArr = explode(' ', $segments[0], 2);
		$this->mainValue = $mvArr[0];

		foreach ($segments as &$seg) {
			$seg = preg_replace('/\s+/', ' ', $seg);
			if ( preg_match_all('/([\S]+)=([^\s;]+)/', $seg, $matches) ) {
				foreach ($matches[1] as $i => $value) {
					$this->pairs[$value] = trim($matches[2][$i],'"\'');
				}
			}
		}
		$this->values = $segments;

	}

	public function __toString() {
		return $this->value();
	}

	public function value() {
		return $this->mainValue;
	}

	public function __isset($name) {
		return isset($this->pairs[$name]);
	}

	public function __get($name) {
		return isset($this->pairs[$name]) ? $this->pairs[$name] : null;
	}
}

class NBHBase {
	var $headers;
	var $multipart = false;
	var $parts = array();
	var $boundary;
	var $root = false;
	protected function parseHeader($rawHeaders) {
		$hash = array();

		$rawHeaders = trim($rawHeaders);

		if ( !is_array($rawHeaders) ) {
			$rawHeaders = explode("\n", $rawHeaders);
		}

		foreach ( $rawHeaders as $line ) {

			if ( $line == '' ) { break; }
			if ( preg_match('/^([^\s.]*):\s*(.*)\s*/', $line, $matches) ) {
				$key = ucfirst(strtolower($matches[1]));
				if ( empty($hash[$key]) ) {
					$hash[$key] = trim($matches[2]);
				} else if ( is_array($hash[$key]) ) {                	
					$hash[$key][] = trim($matches[2]);
				} else {
					$hash[$key] = array($hash[$key]);
					$hash[$key][] = trim($matches[2]);
				}	
			}
			// adding multiline header values to the previous key ( like in Received and Content-type with boundary )
			elseif ( preg_match('/^\s+(.+)\s*/', $line) && isset($key) && $key ) {
				if ( is_array($hash[$key]) ) {
					$hash[$key][count($hash[$key])-1] .= ' '. $line;
				} else {
					$hash[$key] .= ' '. $line;
				}                
			}
			if ( isset($key) && $key === 'Content-type' && preg_match('/boundary="([^"]+)"/', $line, $boundaryMatches) ) {
				$this->multipart = true;
				$this->boundary = $boundaryMatches[1];
				unset($boundaryMatches);
			}
		}

		foreach ($hash as $key => &$value) {
			if ( is_array($value) ) {
				foreach ($value as &$v) {
					$v = new NBHHeaderField($v);
				}
			} else {
				if ( strpos($value, ';') !== false ) {
					$value = new NBHHeaderField($value);
				}
			}
		}

		return $hash;		
	}

	protected function split($eml, $noHeader = false) {

		if ( $noHeader ) {
			return array(
				'headers' => null,
				'rawContent' => $eml
			);			
		}

		$arr = preg_split('/\r\n\r\n/', $eml, 2, PREG_SPLIT_NO_EMPTY);
		if ( count($arr) < 2 ) {
			$arr = preg_split('/\n\n/', $eml, 2, PREG_SPLIT_NO_EMPTY);
		}

		$c = count($arr);

		return array(
			'headers' => $c > 1 ? $arr[0] : null,
			'rawContent' => $c > 1 ? $arr[1] : $arr[0]
		);
	}

	protected function parseContent() {
		$cte = isset($this->headers['Content-transfer-encoding']) ? $this->headers['Content-transfer-encoding'] : null;

		if ( 
			isset($this->headers['Content-description']) &&
			$this->headers['Content-description'] == 'Undelivered Message' &&
			isset($this->headers['Content-type']) &&
			$this->headers['Content-type'] == 'message/rfc822'
		) {
			$this->undeliveredMessage = new NBHPart($this->rawContent);
		}

		if ( $this->multipart ) {
			$contentParts = preg_split('/--'.preg_quote($this->boundary, '/').'(--|)/', $this->rawContent, -1, PREG_SPLIT_NO_EMPTY);
			foreach ($contentParts as $cp) {
				$cp = trim($cp);
				if ( !empty($cp) ) {
					$this->parts[] = new NBHPart($cp);    
				}                
			}
			unset($contentParts);
		} else {
			if ( $cte == 'quoted-printable' ) {
				$c = utf8_encode(quoted_printable_decode($this->rawContent));
			} else if ( $cte == 'base64' ) {
				$c = base64_decode($this->rawContent);
			} else {
				$c = $this->rawContent;
			}

			if ( $this->root ) {
				$this->parts[] = new NBHPart($c, true);
			} else {
				$this->content = $c;
			}
		}	
		unset($this->rawContent);
	}

}

class NBHPart extends NBHBase {
	var $headers = array();
	var $content = null;
	var $rawContent = null;

	function __construct($rawPart, $noHeader = false) {
		$s = $this->split($rawPart, $noHeader);

		// echo "SPLITTING ------------------------------- \n";		
		// echo $rawPart;
		// echo "INTO ------------------------------------ \n";
		// echo var_dump($s);
		// echo "=========================================\n";
		
		$this->headers = $this->parseHeader($s['headers']);
		$this->rawContent = $s['rawContent'];

		$this->parseContent();
	}

	function value() {
		return $this->content;
	}
}

class NBHEmail extends NBHPart {
	var $EFR = false; // email feedback report

	var $options = array();
	function __construct($emailSource, $opts = array()) {
		$this->root = true;

		$emailSource = $this->normalizeLineEndings($emailSource);

		parent::__construct($emailSource);
		$this->parse();

		foreach ($this->parts as $p) {
			if (
				isset($p->headers['Content-type']) && $p->headers['Content-type'] == 'message/delivery-status'
				//isset($p->headers['Content-description']) && $p->headers['Content-description'] == 'Delivery Report'
			) {
				$this->deliveryReport = $p;
				$this->deliveryReport->parsedContent = $this->parseHeader(preg_replace('/(\r\n\r\n|\n\n)/', "\n", $p->content));
			} 
		}

	}

	private function normalizeLineEndings($s) {
		// Normalize line endings
		// Convert all line-endings to UNIX format
		$s = str_replace("\r\r\n", "\n", $s); // weird, I know, but I've seen some emails like that

		$s = str_replace("\r\n", "\n", $s);
		$s = str_replace("\r", "\n", $s);
		return $s;
	}

	public function parse() {

		$ct = isset($this->headers['Content-type']) ? $this->headers['Content-type'] : null;

		if ($ct && $ct == 'multipart/report' &&  $ct->{'report-type'} == 'feedback-report' ) {
			$this->EFR = true;
		}
	}

	// private function map_walker(&$item, $key, &$d) {
	// 	// !!! Warning, the walker callback arguments call order is not standard. User data comes first
	// 	$d['results'][] = call_user_func_array($d['callback'], array($d['userdata'], $item));
	// 	$d['x'] += 1;
	// }

	// private function map($callback, $arr, $userdata) {
	// 	$d = array(
	// 		'results' => array(),
	// 		'userdata' => $userdata,
	// 		'callback' =>  $callback,
	// 		'x'=> 0
	// 	);

	// 	var_dump($d['results']);
	// 	array_walk($arr, array($this, 'map_walker'), $d);
	// 	var_dump($d['results']);
		
	// 	return $d['results'];
	// }

	private function walk($path, &$origin = null, $iterateLevels = 0, &$collect = null) {

		if ( $iterateLevels ) {
			$res = array();
			if ( is_array($origin) || is_object($origin) ) {
				foreach ($origin as $o) {
					if ( is_array($o) || is_object($o) ) {
						$res[] = $this->walk($path, $o, 0, $collect); // collect values
						$iterateLevels -= 1;
						if ( $iterateLevels > 0 ) {
							$this->walk($path, $o, $iterateLevels, $collect);	// dive further	
						}						
					}
				}
			}				
			return !empty($res) ? $res : null;
		}

		//echo "WALK ".join($path, ".")." ---------------------------\n";
		
		$v = &$origin;
		$walked = array();
		$restPath = array_merge($path, array());

		foreach ($path as $s) {
			$walked[] = $s;
			array_shift($restPath);
			// TODO: needs refactoring
			if ( is_object($v) ) {
				if ( $s === '*' ) {
					// mapping 	
					return $this->walk($restPath, $v, 1, $collect);
				} else if ( $s === '**' ) {
					return $this->walk($restPath, $v, 10, $collect);
				} else {				
					if ( isset($v->$s) ) {
						$v = $v->$s;
					} else {
						return null;
					}
				}
			} else if ( is_array($v) ) {
				if ( $s === '*' ) {
					// mapping 	
					return $this->walk($restPath, $v, 1, $collect);
				} else if ( $s === '**' ) {
					return $this->walk($restPath, $v, 10, $collect);
				} else {
					// normal way
					if ( isset($v[$s]) ) {
						$v = $v[$s];
					} else {
						return NULL;
					}					
				}
			} else {
				if ( $collect == null) {
					return null;
					//throw new Exception(sprintf("Cannot walk further. Property is nither array nor object. walked: %s, rest: %s", implode('.', $walked), implode('.', $restPath)), 1);	
				}				
			}
		}

		if ( $collect !== null ) {
			$collect[] = $v;
		}

		return $v;
	}    

	public function get($name = false) {
		$collect = ( strpos($name, '*') !== false ) ? array() : null;
		$path = preg_split('/\./', $name);
		if ( $collect !== null ) {
			$this->walk($path, $this, false, $collect);
			return $collect;
		} else {
			return $this->walk($path, $this);
		}
	}

	public function set($name, $value = NULL) {
		$path = preg_split('/\./', $name);
		return $this->walkAndSet($path, $value);
	}

	private function walkAndSet($path, $value) {
		$v = &$this;
		$c = count($path);

		for ($i=0; $i < $c; $i++) { 
			$s = $path[$i];
			$last = ($i === $c-1);
			if ( is_object($v) ) {
				if ( isset($v->$s) ) {
					if ( $last ) {
						$v->$s = $value;
						return true;
					} else {
						$v = &$v->$s;
					}
				} else {
					if ( $last ) {
						$v->$s = $value;
						return true;
					} else {
						$v->$s = array();
						$v = &$v->$s;
					}               
				}
			} else {
				if ( isset($v[$s]) ) {
					if ( $last ) {
						$v[$s] = $value;
						return true;
					} else {
						$v = &$v[$s];
					}
				} else {
					if ( $last ) {
						$v[$s] = $value;
						return true;
					} else {
						$v[$s] = array();
						$v = &$v[$s];
					}               
				}
			}
		}
		return false;
	} 
}