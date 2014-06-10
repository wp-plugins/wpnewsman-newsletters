<?php

define('WP_ADMIN', true);
require_once(__DIR__.DIRECTORY_SEPARATOR.'wp_env.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.utils.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.options.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.list.php');

class newsmanAPI {

	function __construct() {
		mb_internal_encoding("UTF-8");
		$loc = "UTF-8";
		putenv("LANG=$loc");
		$loc = setlocale(LC_ALL, $loc);	

		if ( function_exists('ob_gzhandler') ) {
			function ob_gz_handler_no_errors($buffer) 
			{
			    @ob_gzhandler($buffer);
			}
			ob_start('ob_gzhandler');
		}

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

		call_user_method($method, $this);
	}

	public function respond($state, $msg, $params = array(), $httpStatusMsg = false) {
		global $db;

		$u = newsmanUtils::getInstance();

		$msg = array(
			'state' => $state,
			'msg' => $msg
		);

		$msg = array_merge($msg, $params);

		if ( !$state && !$httpStatusMsg ) {
			header("HTTP/1.0 400 Bad Request");
		} else if ( $httpStatusMsg ) {
			header("HTTP/1.0 ".$httpStatusMsg);
		}

		header("Content-type: application/json");
		
		echo json_encode( $u->utf8_encode_all($msg) );
		   
		if ($db) $db->close();  
		exit();
	}

	public function param($param) {
		$u = newsmanUtils::getInstance();

		if ( !isset($_REQUEST[$param]) ) {

			if ( func_num_args() == 2 ) {
				$def = func_get_arg(1);
				return $def;
			} else {
				$this->respond(false, sprintf( __('required parameter "%s" is missing in the request', NEWSMAN), $param) );
			}

		} else {				
			return is_string( $_REQUEST[$param] ) ? $u->remslashes( $_REQUEST[$param] ) : $_REQUEST[$param];
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

	public function ajAddEmail() {
		$listId = $this->param('listId');

		$list = newsmanList::findOne('id = %d', array($listId));

		if ( !$list ) {
			$this->respond(false, sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId), array(), '404 Not found');
		}

		$email = strtolower($_REQUEST['email']);

		$u = newsmanUtils::getInstance();

		if ( !$u->emailValid($email) ) {
			$this->respond(false, sprintf( __( 'Bad email address format "%s".', NEWSMAN), $email), array(), '400 Bad request');
		}

		$s = $list->findSubscriber("email = %s", $email);
		if ( $s ) {
			$st = $s->meta('status');
			switch ( $res ) {
				case NEWSMAN_SS_UNCONFIRMED:
					// subscribed but not confirmed
					$this->respond(false, sprintf( __( 'The email "%s" is already subscribed but not yet confirmed.', NEWSMAN), $s->email), array('status' => $res));
				break;                
				case NEWSMAN_SS_CONFIRMED:
					// subscribed and confirmed
					$this->respond(false, sprintf( __( 'The email "%s" is already subscribed and confirmed.', NEWSMAN), $s->email), array('status' => $res));
				break;
				case NEWSMAN_SS_UNSUBSCRIBED:
					// unsubscribed
					$this->respond(false, sprintf( __( 'The email "%s" is already already in the database but unsubscribed.', NEWSMAN), $s->email), array('status' => $res));
				break;
			}			
			wp_die('Please, check your link. It seems to be broken.');
		}

		unset($_REQUEST['listId']);
		unset($_REQUEST['key']);
		unset($_REQUEST['method']);

		$s = $list->newSub();
		$s->fill($_REQUEST);
		$s->confirm();
		$s->save();

		$this->respond(true, 'Subscriber added', array('id' => $s->id));
	} 

	public function ajGetLists() {
		$r = array();

		$lists = newsmanList::findALL();

		foreach ($lists as $list) {
			$r[] = array(
				'id' => $list->id,
				'uid' => $list->uid,
				'name' => $list->name
			);
		}

		$this->respond(true, $r);
	}

	public function ajDownloadList() {
		$listId = $this->param('listId');
		$links = $this->param('links', '');
		$limit = $this->param('limit', false);
		$offset = $this->param('offset', false);

		if ( $limit ) {
			define('newsman_csv_export_limit', $limit);
		}

		if ( $offset ) {
			define('newsman_csv_export_offset', $offset);
		}

		$list = newsmanList::findOne('id = %d', array($listId));

		if ( !$list ) {
			$this->respond(false, sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId), array(), '404 Not found');
		}

		$u = newsmanUtils::getInstance();

		$fileName = date("Y-m-d").'-'.$list->name;
		$fileName = $u->sanitizeFileName($fileName).'.csv';

		$linkTypes = explode(',', $links); // we pass them as is because we have a check later in the code
		if ( !$linkTypes ) {
			$linkTypes = array();
		} else {
			for ($i=count($linkTypes); $i >= 0; $i--) { 
				if ( !$linkTypes[$i] ) {
					array_splice($linkTypes, $i, 1);
				} else {
					$linkTypes[$i] = trim($linkTypes[$i]);
				}
			}
		}

		// 'confirmation-link', 'resend-confirmation-link', 'unsubscribe-link'

		$list->exportToCSV($fileName, 'all', $linkTypes);
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

