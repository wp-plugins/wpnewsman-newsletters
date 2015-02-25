<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."parser.php");

/*
	Available types: success, transient, failed, autoreply
*/

class NBH {
	var $rules;
	var $fetchPaths = array();

	function __construct() {
		$jsonRules = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'rules.json');
		$this->rules = json_decode($jsonRules, true);
	}

	private function log($str="") {
		$str .= "\n";
		if ( class_exists('newsmanUtils') ) {
			$u = newsmanUtils::getInstance();
			call_user_func_array(array($u, 'log'), func_get_args());
		} else {
			call_user_func_array('printf', func_get_args());
		}		
	}	

	/**
	 * Selects values from the parsed email into final array
	 * Notice: During parsing all header names are converted to lover case and capitalized
	 * i.e. Content-Transfer-Encoding header can be accessed with "headers.Content-transfer-encoding" path
	 */
	public function fetch($path, $as) {
		$this->fetchPaths[$path] = $as;		
	}

	/**
	 * Looks for header and outputs the result to the results array with the assigned name
	 */
	public function findHeader($header, $as) {
		$header = ucfirst(strtolower($header));
		$this->fetch('headers.'.$header, $as);
		$this->fetch('parts.**.headers.'.$header, $as);
		$this->fetch('parts.**.undeliveredMessage.headers.'.$header, $as);
	}

	/**
	 * Formats RegExp results string with conversion of
	 * RegExp placehorders("$1") into sprintf placeholders("%2$s")
	 */
	public function formatRxResults($format, $rxResults) {
		// $1 -> %2$s
		$sprintFormat = preg_replace_callback(
			'/\\$(\d+)/',
			function ($matches) {
			    return '%'.(intVal($matches[1])+1).'$s';
			},
			$format
		);

		return call_user_func_array('sprintf', array_merge(array($sprintFormat), $rxResults));
	}

	/**
	 * Returns type from status code
	 */
    private function typeFromStatusCode($code){
        if ( $code=='' ) return '';
        $codeArr = explode('.', $code);
        switch ( $codeArr[0] ) {
            case '2': return 'success';
            case '4': return 'transient';
            case '5': return 'failed';
            default:  return '';
        }
    }

    private function getRuleValue($rule) {
    	$v = 0;
    	foreach ($rule as $key => $value) {
    		// not matched and not a comment

    		if ( !in_array($key, array('ruleName','matcher', 'value', 'debug', 'debugThis', 'type')) && strpos($key, '//') === false ) {
    			$v++;
    		}
    	}
    	if ( isset($rule['value']) && is_numeric($rule['value']) ) {
    		$v += intVal($rule['value']);
    	}

    	if ( !isset($rule['statusCode']) ) {
    		$v -= 2;
    	}

    	return $v;
    }

    /**
     * Fills the final results array with the values of wrapped matched rule
     */
    private function copyRuleValues($wrappedMatchedRule, &$res) {
    	$rule = $wrappedMatchedRule['rule'];
    	$rxMatchResults = $wrappedMatchedRule['rxMatchResults'];

		foreach ($rule as $key => $value) {
			// if not matcher and not comment
			if ( $key !== 'matcher' && strpos($key, '//') === false ) {
				if ( is_string($value) && strpos($value, '$') !== false && strpos($value, '@rx(') === false ) {
					$res[$key] = $this->formatRxResults($value, $rxMatchResults);	
				} else {
					$res[$key] = $value;
				}
			}
		}
    }

    public function selRecipientDomain($res, $e, $param = null, $rxResults = null, $matchedRule = null) {
		if ( isset($res['email']) && preg_match('/@(.*)$/', $res['email'], $mm) ) {
			$r = $mm[1];
			unset($mm);
			return $r;
		}
		return null;
    }	

    public function selGet($res, $e, $param = null, $rxResults = null, $matchedRule = null) {
		if ( $param ) {
			$v = $e->get($param);
			$v = is_array($v) ? $v[0] : $v;
			if ( is_object($v) ) {
				$v = $v->RAW;
			}
			return $v;
		}
		return null;
    }

	public function selRx($res, $e, $param = null, $rxResults = null, $matchedRule = null) {
		if ( $param ) {
			return $this->formatRxResults($param, $rxResults);
		}
		return null;		
	}

	public function selMatch($res, $e, $param = null, $rxResults = null, $matchedRule = null) {
		if ( isset($matchedRule['matchLine']) ) {
			return $matchedRule['matchLine'];
		}
		return null;		
	}

    function findLineBegining($str, $pos) {
    	$pos--;
    	while ( $pos >= 0 ) {
    		$c = $str[$pos];
    		if ( $c === "\n" ) {
    			return $pos+1;
    		}
    		$pos--;
    	}
    	return 0;
    }

    function findLineEnding($str, $pos) {
    	$pos++;
    	$lastCharPos = strlen($str) - 1;
    	while ( $pos < $lastCharPos ) {
    		$c = $str[$pos];
    		if ( $c === "\n" || $c === "\r" ) {
    			return $pos-1;
    		}
    		$pos++;
    	}
    	return $lastCharPos;
    }    


	public function detect($eml, $opts = array()) {
		$opts = array_merge(array(
			// defaults
			'debug' => false
		), $opts);

		$res = array();

		//$t = microtime(true);
		$e = new NBHEmail($eml);
		//$this->log("Email parsing took %s sec\n", microtime(true)-$t);

		$matchedRules = array();

		$hasDebugThisRule = false;
		if ( !is_array($this->rules) ) {
			throw new Exception('Rules is not an array. '.var_export($this->rules, true));
		}
		foreach ($this->rules as $rule) {
			$hasDebugThisRule = isset($rule['debugThis']) && $rule['debugThis'];
		}

		if ( $hasDebugThisRule ) {
			$this->log('DEBUGGING SINGLE RULE '.$rule['debugThis']);
		}

		$rulesExecutionTime = array();

		foreach ($this->rules as $i => $rule) {
			$matched = false;
			$debugRule = (isset($rule['debug']) && $rule['debug']) || ( isset($rule['debugThis']) && $rule['debugThis'] );
			$p = 0;
			$m = 0;		

			$matchLine = null;

			$t = microtime(true);

			if ( $hasDebugThisRule && ( !isset($rule['debugThis']) || !$rule['debugThis'] ) ) {
				continue;
			}

			if ( !isset($rule['matcher']) ) {
				if ( $opts['debug'] ) {
					$this->log('Rule %s, does not have matcher.', print_r($rule, true));
				}
				continue;
			}

			foreach ($rule['matcher'] as $path => $matchValue) {
				$p += 1;
				$v = $e->get($path);

				if ( $debugRule ) {
					$this->log('path: ');
					$this->log($path);
					//$this->log();

					// $this->log('$v: ');
					// $this->log(var_export($v, true));
					//$this->log();

					// $this->log('$matchValue: ');
					// $this->log(print_r($matchValue, true));
					$this->log('------------------------------------------------------------------');
				}

				if ( is_array($matchValue) ) {

					// $exists is always true or false
					if ( isset($matchValue['$exists']) ) {
						if ( $v != null ) {
							$m++;	
						}						
					} else if ( isset($matchValue['$type']) && $matchValue['$type'] == "RegExp" ) {							
						$rxMod = isset($matchValue['modifiers']) ? $matchValue['modifiers'] : '';
						$regexp = '/'.$matchValue['rx'].'/'.$rxMod;
						if ( is_array($v) ) {
							$someMatched = false;
							foreach ($v as $val) {
								$someMatched = preg_match($regexp, $val, $rxMatchResults);
								if ( $someMatched ) {	
									if ( $debugRule ) {
										$this->log('rxMatchResults');
										$this->log($rxMatchResults);
										$this->log('val');
										$this->log($val);
									}
									break;
								}
							}								
							if ( $someMatched ) {
								$m++;
							}
						} else {
							if ( is_object($v) ) {
								$matched = preg_match($regexp, $v->RAW, $rxMatchResults);
							} else {
								$matched = preg_match($regexp, $v, $rxMatchResults);	
							}
							
							if ( $debugRule ) {
								$this->log("rxMatchResults\n");
								$this->log(var_export($rxMatchResults, true));
							}
							if ( $matched ) {
								$m++;
							}
						}
					} elseif ( isset($matchValue['$type']) && in_array($matchValue['$type'], array('match', 'imatch')) ) {
						$func = $matchValue['$type'] === 'match' ? 'strpos' : 'stripos';

						if ( !is_array($matchValue['value']) ) {
							$matchValue['value'] = array($matchValue['value']);
						}

						if ( !is_array($v) ) {
							$v = array($v);
						}

						$found = false;													
						foreach ($v as $val) {
							foreach ($matchValue['value'] as $mv) {
								// TODO: save pos here, and create a function 
								// to get entire string from the source								
								if ( $debugRule ) {
									echo "\n////////////////////////////////////////////////////////////////////////////////////////////////////////////////\n";
									var_dump($func);
									var_dump($val);
									var_dump($mv);
									echo "\n////////////////////////////////////////////////////////////////////////////////////////////////////////////////\n";
								}

								$pos = call_user_func_array($func, array($val, $mv));

								if ( $pos !== false ) {
									$lb = $this->findLineBegining($val, $pos);
									$le = $this->findLineEnding($val, $pos);
									$matchLine = trim(substr($val, $lb, $le-$lb+1));
									$found = true;

									if ( $debugRule ) {									
										echo "Found match at pos $pos\n";
									}

									$m++;
									break;
								}
							}
							if ( $found ) { break; }
						}						

						// if ( is_array($matchValue['value']) ) {

						// 	if ( is_array($v) ) {		
						// 		$found = false;													
						// 		foreach ($v as $val) {
						// 			foreach ($matchValue['value'] as $mv) {
						// 				if ( call_user_func_array($func, array($val, $mv)) !== false ) {
						// 					$found = true;
						// 					$m++;
						// 					break;
						// 				}
						// 			}
						// 			if ( $found ) { break; }
						// 		}
						// 	} else {
						// 		foreach ($matchValue['value'] as $mv) {
						// 			if ( call_user_func_array($func, array($v, $mv)) !== false ) {
						// 				$m++;
						// 				break;
						// 			}
						// 		}
						// 	}
						// } else {
						// 	if ( is_array($v) ) {
						// 		foreach ($v as $val) {
						// 			if ( call_user_func($func, $v, $matchValue['value']) !== false ) {
						// 				$m++;
						// 				break;
						// 			}
						// 		}
						// 	} else {
						// 		if ( call_user_func($func, $v, $matchValue['value']) !== false ) {
						// 			$m++;
						// 		}
						// 	}
						// }
					} else {
						throw new Exception(sprintf("Rule %s does not have known matcher types", print_r($rule, true)), 1);
						
					}

				} else {
					if ( $v == $matchValue ) {
						$m++;
					}
				}
			}
			// end of paths loop

			if ( $debugRule ) {
				$this->log("Paths: %s\n", $p);
				$this->log("Matched: %s\n", $m);				
			}

			$matched = $p == $m;

			$rulesExecutionTime[$i] = array( 
				'time' => round((microtime(true)-$t) * 1000),
				'ruleName' => isset($rule['ruleName']) ? $rule['ruleName'] : ''
			);

			if ( $matched ) {
				$r = array(
					'rule' => $rule,
					'value' => $this->getRuleValue($rule),
					'rxMatchResults' => $rxMatchResults,
					'idx' => $i
				);
				if ( $matchLine ) {
					$r['matchLine'] = $matchLine;
				}

				$matchedRules[] = $r;
			}
		}

		$mc = count($matchedRules);

		if ( $mc === 0 ) {
			return NULL;
		}

		if ( $mc > 1 ) {
			usort($matchedRules, array($this, 'rulesComparator'));
		}

		$rule = $matchedRules[0]['rule'];


		$rxMatchResults = $matchedRules[0]['rxMatchResults'];

		if ( isset($rule['statusCode']) ) {
			if ( strpos($rule['statusCode'], '$') !== false ) {
				$res['statusCode'] = $this->formatRxResults($rule['statusCode'], $rxMatchResults);		
			} else {
				$res['statusCode'] = $rule['statusCode'];
			}				
		}

		$res['idx'] = $matchedRules[0]['idx'];

		if ( $opts['debug'] ) {
			$this->log("Matched : %s\n", print_r($matchedRules, true));
		}

		$this->copyRuleValues($matchedRules[0], $res);
		foreach ($matchedRules as $mr) {
			// if supplementary rule
			if ( !isset($mr['rule']['statusCode']) ) { 
				$this->copyRuleValues($mr, $res);
			}
		}

		if ( !isset($res['type']) ) {
			if ( isset($res['statusCode']) && $res['statusCode'] ) {
				$res['type'] = $this->typeFromStatusCode($res['statusCode']);
			} else {
				if ( $opts['debug'] ) {
					$this->log('Warning! No rule with defined status code matched this email.');
				}				
				return null;
			}
		}

		// looking for extra useful values
		foreach ($this->fetchPaths as $path => $name) {
			$v = $e->get($path);
			// if we found many, select first one
			if ( is_array($v) ) {
				$v = !empty($v) ? $v[0] : null; 
			}
			if ( $v && !isset($res[$name]) ) {
				$res[$name] = $v;
			}
		}

		// processing special selectors like
		// "blockedDomain": "@recipientDomain",
		foreach ($res as $key => $value) {
			if ( is_string($value) && strlen($value) && $value[0] === '@' ) {
				$selectors = explode('|', $value);
				foreach ($selectors as $sel) {
					if ( preg_match('/^@(\w+)(?:\((.*)\)|)$/', $sel, $m) ) {
						$selName = 'sel'.ucfirst($m[1]);
						$param = isset($m[2]) ? $m[2] : null;
						$mr = isset($matchedRules[0]) ? $matchedRules[0] : null;
						unset($m);
						if ( method_exists($this, $selName) ) {
							$r = call_user_func_array(array($this, $selName), array($res, $e, $param, $rxMatchResults, $mr));
							if ( $r ) {
								$res[$key] = $r;
								break;
							}
						}						
					}
				}
				if ( $res[$key] == $value ) {
					$res[$key] = '';
				}				
			}
		}

		if ( $opts['debug'] ) {
			foreach ($rulesExecutionTime as $ruleIdx => $itm) {
				$this->log("RULE %d EXEC TIME %d ms - %s\n", $ruleIdx, $itm['time'], $itm['ruleName']);	
			}
			//$this->log("Matched rules:\n%s", print_r($matchedRules, true));
			$this->log("Result : %s\n", print_r($res, true));			
		}

		unset($e);
		unset($rule);
		unset($rxMatchResults);
		unset($matchedRules);
		unset($rulesExecutionTime);

		return $res;
	}

	function rulesComparator($a, $b) {;
		return intval($b['value']) - intval($a['value']);
	}	
}