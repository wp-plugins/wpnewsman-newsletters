<?php

require_once(__DIR__.DIRECTORY_SEPARATOR.'class.utils.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.options.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.list.php');

class newsmanAPI {

	function __construct() {
		mb_internal_encoding("UTF-8");
		$loc = "UTF-8";
		putenv("LANG=$loc");

		$loc = setlocale(LC_ALL, $loc);	

		$this->bePositive = isset($_REQUEST['bepositive']);
		unset($_REQUEST['bepositive']);

		// UNCOMMENT IN PRODUCTION

		// if ( function_exists('ob_gzhandler') ) {
		// 	function ob_gz_handler_no_errors($buffer) 
		// 	{
		// 	    ob_gzhandler($buffer);
		// 	}
		// 	ob_start('ob_gzhandler');
		// }

		$o = newsmanOptions::getInstance();
		$key = $o->get('apiKey');

		if ( !$key ) {
			$this->respond(false, 'API key is not defined in the options.', array(), '500 Internal Server Error');
		}

		if ( !isset($_REQUEST['key']) || !$_REQUEST['key'] || $_REQUEST['key'] !== $key ) {
			$this->respond(false, 'API key is missing or wrong');
		}

		if ( !isset($_REQUEST['method']) ) {
			$this->respond(false,'API method is not defined');
		} else {
			$method = 'aj'.ucfirst($_REQUEST['method']);
			if ( !method_exists($this, $method) ) {
				$this->respond(false, 'API method "'.$_REQUEST['method'].'" does not exist');
			}
		}

		call_user_func(array($this, $method));
	}

	public function respond($state, $msg, $params = array(), $httpStatusMsg = false) {
		global $db;

		$u = newsmanUtils::getInstance();

		$msg = array(
			'state' => $state,
			'msg' => $msg
		);

		$msg = array_merge($msg, $params);

		if ( !$this->bePositive ) {
			if ( !$state && !$httpStatusMsg ) {
				header("HTTP/1.0 400 Bad Request");
			} else if ( $httpStatusMsg ) {
				header("HTTP/1.0 ".$httpStatusMsg);
			}			
		}

		header("Content-type: application/json");
		
		echo json_encode( $u->utf8_encode_all($msg) );
		   
		if ($db) $db->close();  
		exit();
	}

	public function param($param) {
		$u = newsmanUtils::getInstance();
		$type = null;
		if ( func_num_args() === 3 ) {
			$type = func_get_arg(2);
		}

		if ( !isset($_REQUEST[$param]) ) {

			if ( func_num_args() >= 2 ) {
				$def = func_get_arg(1);
				return $def;
			} else {
				$this->respond(false, sprintf( __('required parameter "%s" is missing in the request', NEWSMAN), $param) );
			}

		} else {
			$p = is_string( $_REQUEST[$param] ) ? $u->remslashes( $_REQUEST[$param] ) : $_REQUEST[$param];

			switch ( $type ) {
				case 'boolean':
					$p = strtolower($p);
					if ( $p === 'true' ) { return true; }
					if ( $p === 'false' ) { return false; }
					return (boolean)intval($p);
					break;
			}

			return $p;
		}
	}	

	public function ajGetBase64Map() {
		$u = newsmanUtils::getInstance();
		$o = newsmanOptions::getInstance();

		$this->respond(true, array(
			'original' => $u->base64Map,
			'modified' => $o->get('base64TrMap')
		));
	}

	private function getMessageFromSubStatus($s) {
		$u = newsmanUtils::getInstance();
		$u->log('[getMessageFromSubStatus] status %s', $s->meta('status'));
		switch ( $s->meta('status') ) {
			case -1:
				return __('Subscriber added', NEWSMAN);
				break;

			case NEWSMAN_SS_UNCONFIRMED:
				// subscribed but not confirmed
				return sprintf( __( 'The email "%s" is already subscribed but not yet confirmed.', NEWSMAN), $s->email);
				break;                

			case NEWSMAN_SS_CONFIRMED:
				// subscribed and confirmed
				return sprintf( __( 'The email "%s" is already subscribed and confirmed.', NEWSMAN), $s->email);
				break;

			case NEWSMAN_SS_UNSUBSCRIBED:
				// unsubscribed
				return sprintf( __( 'The email "%s" is already already in the database but unsubscribed.', NEWSMAN), $s->email);
				break;
		}
		return false;
	}

	public function ajAddEmail() {
		$listId = $this->param('listId');

		$list = newsmanList::findOne('id = %d', array($listId));

		if ( !$list ) {
			$this->respond(false, sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId), array(), '404 Not found');
		}

		$_REQUEST['email'] = strtolower($_REQUEST['email']);
		$email = $_REQUEST['email'];

		$u = newsmanUtils::getInstance();

		if ( !$u->emailValid($email) ) {
			$this->respond(false, sprintf( __( 'Bad email address format "%s".', NEWSMAN), $email), array(), '400 Bad request');
		}

		unset($_REQUEST['listId']);
		unset($_REQUEST['key']);
		unset($_REQUEST['method']);

		$fullCycle = $this->param('full-cycle', false, 'boolean');
		$use_excerpts = $this->param('use-excerpts', false, 'boolean');

		unset($_REQUEST['full-cycle']);
		unset($_REQUEST['use-excerpts']);

		if ( $fullCycle ) {
			$n = newsman::getInstance();
			$s = null;
			$res = $n->subscribe($list, $_REQUEST, $use_excerpts, true, $s);

			$msg = $this->getMessageFromSubStatus($s);

			$this->respond(true, $msg, array('result' => $res));
		} else {

			$s = $list->findSubscriber("email = %s", $email);
			if ( $s ) {
				$res = $s->meta('status');
				$msg = $this->getMessageFromSubStatus($s);

				if ( $msg ) {
					$this->respond(false, $msg, array('status' => intVal($res)), '409 Conflict');
				} else {
					wp_die('Please, check your link. It seems to be broken.');
				}				
			}

			$s = $list->newSub();
			$s->fill($_REQUEST);
			$s->confirm();
			$s->save();

			$this->respond(true, 'Subscriber added', array('id' => $s->id));
		}
		
	} 

	public function ajGetLists() {
		global $newsman_current_subscriber;
		global $newsman_current_list;

		$newsman_current_subscriber = array( 'ucode' => '' );

		$r = array();

		$n = newsman::getInstance();

		$lists = newsmanList::findALL();		

		foreach ($lists as $list) {
			$newsman_current_list = $list;
			$r[] = array(
				'id' => $list->id,
				'uid' => $list->uid,
				'name' => $list->name,
				'confirmed' => $list->countSubs(NEWSMAN_SS_CONFIRMED),
				'unsubscribeURL' => $n->getActionLink('unsubscribe', false, false, 'DROP_EMAIL_IDENTIFIER')
			);
		}

		$this->respond(true, $r);
	}

	private function buildExtraQuery(&$exportArgs) {
		global $wpdb;

		$validTimeUnits = array('MICROSECOND', 'SECOND', 'MINUTE', 'HOUR', 'DAY', 'WEEK', 'MONTH', 'QUARTER', 'YEAR');
		
		$extraQuery = array();

		$timeInList = $this->param('timeInList', false);		
		if ( $timeInList ) {
			$til = explode(',', $timeInList);
			$tilNum = intval($til[0]);
			$tilUnit = isset($til[1]) ? strtoupper($til[1]) : 'DAY';
			if ( in_array($tilUnit, $validTimeUnits) ) {
				// no need to use wpdb->prepare here becuase all vars are checked
				$extraQuery[] = "TIMESTAMPDIFF($tilUnit, ts, NOW()) >= $tilNum";
			}
		}

		$timeOffset = $this->param('timeOffset', false);		
		if ( $timeOffset && preg_match('/^\d{4}\-\d{2}\-\d{2}(\s\d{2}:\d{2}:\d{2}|)$/', $timeOffset) ) {
			$extraQuery[] = $wpdb->prepare("TIMESTAMPDIFF(SECOND, %s, ts) > 0", $timeOffset);
		}

		//view-source:blog.dev/wp-content/plugins/wpnewsman/api.php?method=downloadList&key=575d037167f77f3b1c01c7fff7b9d31282009867&listId=18

		$emailIn = $this->param('emailIn', false);		

		if ( $emailIn ) {
			$args = explode(',', $emailIn);
			$placeholders = array();
			foreach ($args as $email) {
				$placeholders[] = '%s';
			}

			$set = 'email in ('.implode(',', $placeholders).')'; // (%s, %s, ...)

			array_unshift($args, $set); // placeing set SQL part before the emails
			
			$set = call_user_func_array(array($wpdb, 'prepare'), $args);
			$extraQuery[] = $set;
		}

		if ( !empty($extraQuery) ) {
			$exportArgs['extraQuery'] = implode(' AND ', $extraQuery);
		}
	}

	public function ajDownloadList() {

		$u = newsmanUtils::getInstance();

		$listId 	= $this->param('listId');
		$limit 		= $this->param('limit', false);
		$offset 	= $this->param('offset', false);
		$map 		= $this->param('map', ''); // fields mapping &map=first-name:FIRST_NAME,last-name:LAST_NAME
		$type 		= strtolower($this->param('type', 'all')); 
		$noheader 	= $this->param('noheader', false); // removes header in CSV output
		$fields 	= $this->param('fields', false); // select only listed fields plus you can specify extra links fields 'confirmation-link', 'resend-confirmation-link', 'unsubscribe-link'
		$format 	= $this->param('format', 'csv'); // output format csv or json
		$nofile 	= $this->param('nofile', false); // dont force content-disposition header for CSV output

		if ( is_string($nofile)  ) {
			$nofile = ( $nofile === '1' || strtolower($nofile) == 'true' );
		}

		$exportArgs = array(
			'type' => $type,
			'nofile' => $nofile
		);
		
		if ( $map ) {
			$map = explode(',', $map);
			$newsman_export_fields_map = array();
			foreach ($map as $pair) {
				$p = explode(':', $pair);
				$newsman_export_fields_map[ $p[0] ] = $p[1];
			}
			$exportArgs['fieldsMap'] = $newsman_export_fields_map;
		}

		if ( $fields ) 	 { $exportArgs['fieldsList'] = explode(',', $fields); }
		if ( $noheader ) { $exportArgs['noheader'] = true; }
		if ( $format )   { $exportArgs['format'] = $format; }
		if ( $limit )    { $exportArgs['limit'] = $limit; }
		if ( $offset )   { $exportArgs['offset'] = $offset; }

		$this->buildExtraQuery($exportArgs);

		// one of these params triggers the page querying
		$exportArgs['customized'] = ( $limit || $offset || ( isset($exportArgs['extraQuery']) && $exportArgs['extraQuery'] ) );

		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			$this->respond(false, sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId), array(), '404 Not found');
		}

		$fileName = date("Y-m-d").'-'.$list->name;
		$fileName = $u->sanitizeFileName($fileName).'.'.strtolower($format);

		$exportArgs['fileName'] = $fileName;

		$list->export($exportArgs);
	}

	public function ajGetListFields() {
		$listId = $this->param('listId');

		$list = newsmanList::findOne('id = %d', array($listId));

		if ( !$list ) {
			$this->respond(false, sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId), array(), '404 Not found');
		}

		$fields = $list->getAllFields();

		$this->respond(true, array(
			'fields' => $fields
		));
	}

	public function ajUnsubscribe() {
		$u = newsmanUtils::getInstance();

		$listId 	= $this->param('listId');
		$emails 	= $this->param('emails');
		$status 	= $this->param('status', 'Unsubscibed with API');

		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			$this->respond(false, sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId), array(), '404 Not found');
		}

		foreach (explode(',', $emails) as $email) {
			$list->unsubscribe($email, $status);
		}

		// TODO: check double opt-out option and 
		// send email

		$this->respond(true, __('Successfully unsubscribed.', NEWSMAN));
	}
}

new newsmanAPI();


// add_email
// 	listId - 
// 	email - 
// 	someOtherField=value

// get_lists 
	
// download_list ( with unsubscribe links )
// 	listId

