<?php

define('NEWSMAN_OPEN', 1);
define('NEWSMAN_UNIQUE_OPEN', 2);
define('NEWSMAN_CLICK', 3);
define('NEWSMAN_UNIQUE_CLICK', 4);

// TODO: make clicks, opens, and unsubscribes in email object read only fields

class newsmanAnalytics {

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self();

			
		} 
		return self::$instance; 
	} 

	function __construct() {
		add_action('init', array($this, 'onInit'));
	}

	private function decodeIdsArray($base36IdsArray) {
		$converted = array();

		foreach ($base36IdsArray as $v) {
			$converted[] = base_convert($v, 36, 10);
		}

		return $converted;		
	}

	public function onInit() {
		// /o/{emailId}/{listId}/{subId}
		// http://blog.dev/o/3d-a-oo 
		// /c/{emailId}/{listId}/{subId}/{linkId}
		// http://blog.dev/c/3d-a-oo-go
		if ( preg_match('#\/(o|c)\/((?:[a-z0-9]+\-){2,3}(?:[a-z0-9]+))#', strtolower($_SERVER['REQUEST_URI']), $matches ) ) {
			$op = $matches[1];
			$args = $this->decodeIdsArray(explode('-', $matches[2]));
			$func = ( $op === 'c' ) ? 'registerClick' : 'registerOpen';

			call_user_func_array(array($this, $func), $args);
		}
	}

	public function outputDummyImage() {
		header ("Content-Type: image/gif");
		echo "\x47\x49\x46\x38\x39\x61\x01\x00\x01\x00\x80\x00\x00\xFF\xFF\xFF\x00\x00\x00\x21\xF9\x04\x01\x00\x00\x00\x00\x2C\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02\x44\x01\x00\x3B";
		exit;		
	}

	public function registerOpen($emailId, $listId, $subId) {
		$u = newsmanUtils::getInstance();

		$eml = newsmanEmail::findOne('id = %d', array( $emailId ));
		if ( !$eml ) {
			wp_die( sprintf( __( 'Email with ID "%d" is not found', NEWSMAN), $emailId) , 'Not found', array( 'response' => 404 ));
		}

		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			wp_die( sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId) , 'Not found', array( 'response' => 404 ));
		}

		$sub = $list->findSubscriber("id = %d", array($subId));

		if ( !$sub ) {
			wp_die( sprintf( __( 'Subscriber with id "%s" is not found.', NEWSMAN), $subId) , 'Not found', array( 'response' => 404 ));	
		}

		$sd = new newsmanAnSubDetails();

		$sd->emailId = $emailId;
		$sd->listId = $listId;
		$sd->subId = $subId;
		$sd->emailAddr = $sub->email;
		$sd->ip = $u->peerip();
		$res = $sd->open();



		$debug_temp = '';
		if ($res === NEWSMAN_UNIQUE_OPEN) {
			$debug_temp = 'NEWSMAN_UNIQUE_OPEN';
		}
		if ( $res === NEWSMAN_OPEN ) {
			$debug_temp = 'NEWSMAN_OPEN';
		}
		$u->log('[registerOpen] AnSubDetails->open result %d - %s', $res, $debug_temp);



		if ( $res === NEWSMAN_UNIQUE_OPEN ) {
			$eml->incOpens();

			$tl = new newsmanAnTimeline();
			$tl->emailId = $emailId;
			$tl->open();
			$this->outputDummyImage();	

		} else if ( $res === NEWSMAN_OPEN ) {
			$this->outputDummyImage();

		} else if ( is_wp_error($res) ) {
			var_dump($res);
		} else {
			var_dump($res);
		}
	}

	public function registerClick($emailId, $listId, $subId, $linkId) {
		$u = newsmanUtils::getInstance();

		$eml = newsmanEmail::findOne('id = %d', array( $emailId ));
		if ( !$eml ) {
			wp_die( sprintf( __( 'Email with ID "%d" is not found', NEWSMAN), $emailId) , 'Not found', array( 'response' => 404 ));
		}

		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			wp_die( sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId) , 'Not found', array( 'response' => 404 ));
		}

		$sub = $list->findSubscriber("id = %d", array($subId));

		if ( !$sub ) {
			wp_die( sprintf( __( 'Subscriber with id "%s" is not found.', NEWSMAN), $subId) , 'Not found', array( 'response' => 404 ));	
		}

		$sd = new newsmanAnSubDetails();

		$sdRawResult = null;

		$sd->emailId = $emailId;
		$sd->listId = $listId;
		$sd->subId = $subId;
		$sd->emailAddr = $sub->email;		
		$sd->ip = $u->peerip();
		$res = $sd->click($sdRawResult);

		$u->log('[registerClick] sdRawResult %d', $sdRawResult);

		$lnk = newsmanAnLink::findOne('id = %d', array($linkId));

		if ( !$lnk ) {
			// output 404 here
			wp_die('Link not found');
		}

		$clk = new newsmanAnClicks();
		$clk->listId = $listId;
		$clk->subId = $subId;
		$clk->emailId = $emailId;
		$clk->emailAddr = $sub->email;
		$clk->linkId = $linkId;
		$clk->ip = $sd->ip;
		$clk_res = $clk->click();

		if ( $clk_res == NEWSMAN_UNIQUE_CLICK ) { // unqie for the link
			$lnk->uniqueClick();
		}

		if ( $res === NEWSMAN_UNIQUE_CLICK ) { // unique for email
			
			if ( $sdRawResult == 1 ) { // insert operation. click prior to open. Adding artificial open
				$eml->incOpens();

				$sdo = new newsmanAnSubDetails();
				$sdo->emailId = $emailId;
				$sdo->listId = $listId;
				$sdo->subId = $subId;
				$sdo->emailAddr = $sub->email;
				$sdo->ip = $sd->ip;
				$res = $sdo->open();

				$tlo = new newsmanAnTimeline();
				$tlo->emailId = $emailId;
				$tlo->open();				
			}

			$eml->incClicks();

			$tl = new newsmanAnTimeline();
			$tl->emailId = $emailId;
			$tl->click();

			wp_redirect($lnk->URL);
			exit;

		} else if ( $res === NEWSMAN_CLICK ) {
			//echo 'NEWSMAN_CLICK<br>';			
			//echo 'Redirect to: '.$lnk->URL;
			wp_redirect($lnk->URL);
			exit;
		} else if ( is_wp_error($res) ) {
			var_dump($res);
		} else {
			var_dump($res);
		}



	}
}