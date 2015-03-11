<?php

require_once(ABSPATH.DIRECTORY_SEPARATOR.'wp-includes'.DIRECTORY_SEPARATOR.'class-phpmailer.php');

define('KEY', "\xa3\xb4\xef\xda\x24\xd5\xcc\x3b");

class newsmanUtils {

	var $base64Map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
	var $l;
	var $debugLogPath = '';
	var $listsCache;

	function __construct() {
		$this->l = newsmanLocks::getInstance();
		$this->debugLogPath = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'newsmanlog.txt';
		if ( defined('NEWSMAN_STORE_UNKNOWN_BOUNCES') && NEWSMAN_STORE_UNKNOWN_BOUNCES === true ) {
			$this->bhDebugPath = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'unknown-emails';
			if ( !file_exists($this->bhDebugPath) ) {
				mkdir($this->bhDebugPath);		
			}			
		}
	}

	public function saveUnknownEmail($name, $content) {
		if ( defined('NEWSMAN_STORE_UNKNOWN_BOUNCES') && NEWSMAN_STORE_UNKNOWN_BOUNCES === true ) {
			$emlName = $this->bhDebugPath.DIRECTORY_SEPARATOR.$name;
			file_put_contents($emlName, $content);
		}
	}

	function getPagesData() {
		return array(
			'alreadySubscribedAndVerified' => array(
				'slug' => 'already-subscribed-and-verified',
				'title' => __('Already Subscribed and Verified', NEWSMAN),
				'template' => 'already-subscribed-and-verified.html',
				'excerpt' => 'already-subscribed-and-verified-ex.html',
			),
			'badEmail' => array(
				'slug' => 'bad-email-format',
				'title' => __('Bad email address format', NEWSMAN),
				'template' => 'bad-email.html',
				'excerpt' => 'bad-email-ex.html'
			),
			'confirmationRequired' => array(
				'slug' => 'confirmation-required',
				'title' => __('Confirmation Required', NEWSMAN),
				'template' => 'confirmation-required.html',
				'excerpt' => 'confirmation-required-ex.html'
			),
			'confirmationSucceed' => array(
				'slug' => 'confirmation-successful',
				'title' => __('Confirmation Successful', NEWSMAN),
				'template' => 'confirmation-successful.html',
				'excerpt' => 'confirmation-successful-ex.html'
			),
			'emailSubscribedNotConfirmed' => array(
				'slug' => 'email-subscribed-not-confirmed',
				'title' => __('Subscription not yet confirmed', NEWSMAN),
				'template' => 'email-subscribed-not-confirmed.html',
				'excerpt' => 'email-subscribed-not-confirmed-ex.html'
			),
			'unsubscribeSucceed' => array(
				'slug' => 'unsubscribe-succeed',
				'title' => __('Successfully unsubscribed', NEWSMAN),
				'template' => 'unsubscribe-succeed.html',
				'excerpt' => 'unsubscribe-succeed-ex.html'
			),
			'unsubscribeConfirmation' => array(
				'slug' => 'unsubscribe-confirmation-required',
				'title' => __('Please confirm your unsubscribe decision', NEWSMAN),
				'template' => 'unsubscribe-confirmation-required.html',
				'excerpt' => 'unsubscribe-confirmation-required-ex.html'
			)
		);
	}

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self(); 
		} 
		return self::$instance; 
	} 

	function emailFromFile($fileName, $lang = 'en_US') {
		$filePath = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.'emails'.DIRECTORY_SEPARATOR.$fileName;
		
		$eml = array(
			'subject' => '',
			'plain' => '',
			'html' => ''
		);

		if ( !file_exists($filePath) ) {
			return $eml;
		}

		$emailSource = $this->file_get_contents_utf8($filePath);

		$b = '';
		$state = 'subject';

		$c = strlen($emailSource);
		$lineNum = 1;
		
		for ($i=0; $i < $c; $i++) { 
			$b .= $emailSource[$i];

			if ( $emailSource[$i] == "\n" ) {
				$lineNum += 1;			
			}

			if ( $lineNum > 2 && $state == 'subject' ) {
				$state = 'plain';
			}

			if ( substr($b, -13) == '--- plain ---' ) {
				$eml[$state] = substr($b, 0, -13);
				$state = 'plain';
				$b = '';
			}

			if ( substr($b, -12) == '--- html ---' ) {
				$eml[$state] = substr($b, 0, -12);
				$state = 'html';
				$b = '';
			}

			// we are on the last symbol
			if ( $i == ($c-1) ) {
				$eml[$state] = $this->utf8_encode_all($b);
			}
		}
		return $eml;
	}

	function loadTpl($fileName, $lang = 'en_US') {
		$path = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.$lang.DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$fileName; 
		$tpl = '';
		if ( file_exists($path) ) {
			$tpl = $this->file_get_contents_utf8($path);
		}
		return $tpl;
	}

	private function installLangExists($lang = 'en_US') {
		return is_dir(NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.$lang);
	}

	/*
	$opts = array(
		'sender' => array(
			'name' => 'John Doe',
			'email' => 'john@example.com'
		),
		'mailer' => array(
			'mdo' => 'phpmail', // sendmail, smtp
			'smtp' => array(
				'host' => '',
				'user' => '',
				'pass' => '',
				'port' => 25,
				'secure' => 'off', // tls, ssl
			)
		),
		'vars' = array(
			'key' => 'value'
		)
	)
	*/	
	function mail($message, $opts = false){   

		$options = newsmanOptions::getInstance();
		$m = $options->get('mailer');
		$s = $options->get('sender');

		if ( !isset($message['subject']) ) { $message['subject'] = ''; }
		if ( !isset($message['plain']) ) { $message['plain'] = ''; }
		if ( !isset($message['html']) ) { $message['html'] = ''; }


		if ( isset($opts['vars']) ) {
			$vars = $opts['vars'];
		} else {
			$vars = array();
		}

		$subject = $this->supplant($message['subject'], $vars);
		$plain = $this->supplant($message['plain'], $vars);
		$html = $this->supplant($message['html'], $vars);

		if ( isset($opts['mailer']) ) {
			$m = $opts['mailer'];
		}

		if ( isset($opts['sender']) ) {
			$s = $opts['sender'];
		}

		if ( defined('NEWSMAN_TESTS') && !defined('NEWSMAN_TESTS_ENABLE_MAIL') ) {
			// Here the actual email will be sent
			echo "Mailer:\n";
			print_r($m);
			echo "sender:\n";
			print_r($s);
			return;
		}
		
		try {
			$mail = new PHPMailer(true); // defaults to using php "mail()"

			//

			//$mail->Encoding = 'quoted-printable';
			
			switch ($m['mdo']) {
				case 'smtp':

					$mail->IsSMTP();// tell the class to use SMTP

					$username = trim($m['smtp']['user']);
					$pass = trim($m['smtp']['pass']);
					
					if (!empty($username) && !empty($pass)) {
						$mail->SMTPAuth   = true;                // enable SMTP authentication
						$mail->Username   = $username;  // SMTP server username
						$mail->Password   = $pass;  // SMTP server password
					} 
					
					if ($m['smtp']['secure'] != 'off') {
						$mail->SMTPSecure = $m['smtp']['secure'];
					}
					
					$mail->Port       = $m['smtp']['port']; // set the SMTP server port
					$mail->Host       = $m['smtp']['host']; // SMTP server
				break;
				case 'sendmail':
					$mail->IsSendmail();  // tell the class to use Sendmail
				break;
				case 'phpmail':
					// deafult mode - no need to setup
				break;
			}
			
			
			
			$mail->CharSet = 'utf-8';
			
			$mail->AddReplyTo($s['email'], $s['name']);
			
			$mail->SetFrom($s['email'], $s['name']);

			$mail->Sender = $s['returnEmail'];
			
			
			if ( isset($opts['email']) ) {
				$to = $opts['email'];
			} elseif ( isset($opts['to']) ) {
				$to = $opts['to'];
			} elseif ( isset($vars['email']) ) {
				$to = $vars['email'];
			}

			$mail->addCustomHeader("X-WPNewsman-antispam: ".$this->encEmail($to));

			if ( isset($opts['ts']) && isset($opts['ip']) ) {
				$d = $opts['ts'];
				$blogurl = get_bloginfo('wpurl');
				$x_sub = 'Subscribed to '.$blogurl.', on '.$d.', from '.$opts['ip'];
				$mail->addCustomHeader('X-Subscription: '.$x_sub);
			}

			if ( isset($opts['uns_link']) ) {
				$mail->addCustomHeader('List-Unsubscribe: <'.$opts['uns_link'].'>,<mailto:'.$s['email'].'?subject=unsubscribe;'.$opts['uns_code'].'>');
			}
			
			$mail->AddAddress($to);
			
			$mail->Subject = $subject;

			if ( !empty($html) ) {
				$mail->Body = $html;
				$mail->isHTML(true);
				$mail->AltBody = $plain;
			} else {
				$mail->Body = $plain;
			}
			
			$x = $mail->Send();	  		
		// } catch (phpmailerException $e) {
		// 	$x = $e->errorMessage();
		} catch (Exception $e) {
			$x = $e->getMessage(); //Boring error messages from anything else!
		}

		//var_dump($x);

		// if ( !$x ) {
		// 	$x = 'PHPMailer error: '.$mail->ErrorInfo;
		// } else {
		// 	$x = true;
		// }
		return $x;
	}	

	public function log($msg) {
		$args = func_get_args();

		if ( count($args) > 1 ) {
			$msg = call_user_func_array('sprintf', func_get_args() );	
		}		

		if ( defined('NEWSMAN_DEBUG') && NEWSMAN_DEBUG === true ) {
			$msg = '['.date('Y-m-d H:i:s').'] '.$msg."\n";
			file_put_contents($this->debugLogPath, $msg, FILE_APPEND);
		}
	}

	public function logMemUsage($comment = '') {
		if ( defined('NEWSMAN_LOG_MEM_USAGE') && NEWSMAN_LOG_MEM_USAGE == 1 )  {
			$mu_unreal = memory_get_peak_usage(false)/1024/1024;
			$mu_real = memory_get_peak_usage(true)/1024/1024;
			$this->log("Mem usage(unreal/real): ".$mu_unreal." / ".$mu_real." MiB,  ".$comment);
		}
	}

	public function readLog() {
		$log = 'Log file does not exists';
		if (file_exists($this->debugLogPath) ) {
			$log = file_get_contents($this->debugLogPath);	
		}
		return $log;	
	}

	public function emptyLog() {
		file_put_contents($this->debugLogPath, '');
	}

	function getAuthors($selected = '') {
		global $wpdb;
		$options = newsmanOptions::getInstance();

		$sql = "SELECT ID, user_nicename from $wpdb->users ORDER BY display_name";	
		$authors = $wpdb->get_results($sql,ARRAY_A);

		$oSelAuthors = $options->get('broadcast.query.authors');

		$sel_auth_arr = preg_split('/[\s,]+/', $oSelAuthors, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($authors as &$author) {
			$author['selected'] = in_array($author['ID'], $sel_auth_arr);
		}

		return $authors;
	}	

	function getCategories() {
		$cats = get_categories(array(
			'hide_empty' => 0
		));

		$options = newsmanOptions::getInstance();
		$oSelCats = $options->get('broadcast.query.cats');

		$sel_cat_arr = preg_split('/[\s,]+/', $oSelCats, -1, PREG_SPLIT_NO_EMPTY);		

		foreach ($cats as &$item) {
			$item->selected = in_array($item->cat_ID, $sel_cat_arr);
		}

		return $cats;
	}

	function remslashes ( $string ){       
		// all wordress $_POST ,$_GET and $_COOKIE variables
		// are compulsory backslashed!
		return stripslashes( $string );
	}	

	function peerip() {

		if ( defined('NEWSMAN_DEBUG_USE_FAKE_IPS') && NEWSMAN_DEBUG_USE_FAKE_IPS === true ) {
			$fakeIPS = array(
				'110.179.184.4',
				'218.3.166.134',
				'183.87.39.118',
				'199.193.153.72',
				'218.108.168.69',
				'193.182.156.91',
				'221.10.102.199',
				'89.190.195.170',
				'109.196.34.8',
				'213.141.146.146',
				'86.21.218.143',
				'118.97.95.174',
				'200.16.125.92',
				'110.74.195.127',
				'180.150.157.209',
				'77.50.220.92',
				'62.68.95.14',
				'212.41.46.17',
				'120.203.154.17',
				'95.0.22.1',
				'186.235.237.140',
				'115.236.59.194',
				'222.255.27.211',
				'61.163.231.212',
				'110.77.199.225',
				'109.71.179.98',
				'115.124.89.154',
				'76.188.21.208',
				'202.159.20.147',
				'78.39.39.40',
				'212.117.56.248',
				'58.20.127.178',
				'139.0.2.162',
				'27.44.76.230',
				'61.19.51.133',
				'218.108.170.172',
				'200.179.96.132',
				'37.114.221.20',
				'115.227.192.127',
				'202.159.15.226',
				'187.112.1.80',
				'119.31.123.207',
				'222.73.165.25',
				'202.148.27.122',
				'200.50.175.177',
				'220.178.100.181',
				'101.255.73.42',
				'46.99.145.33',
				'122.226.73.248',
				'189.17.66.162'
			);			
	
			return $fakeIPS[rand(9, count($fakeIPS)-1)];
		}

		if ( isset($_SERVER) ) {
			if ( isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ) {
				$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
			} elseif ( isset($_SERVER["HTTP_CLIENT_IP"]) ) {
				$ip = $_SERVER["HTTP_CLIENT_IP"];
			} else {
				$ip = $_SERVER["REMOTE_ADDR"];
			}
		} else {
			if ( getenv( 'HTTP_X_FORWARDED_FOR' ) ) {
				$ip = getenv( 'HTTP_X_FORWARDED_FOR' );
			} elseif ( getenv( 'HTTP_CLIENT_IP' ) ) {
				$ip = getenv( 'HTTP_CLIENT_IP' );
			} else {
				$ip = getenv( 'REMOTE_ADDR' );
			}
		}
		return $ip;
	}	

	function base64DecodeU($data) {
		return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
	}

	function base64EncodeU($data) {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
	}

	function sanitizeFileName($str) {
		return preg_replace('#\s+#','_',$str);
	}

	function sanitizeDBFieldName($str) {
		return strtolower( preg_replace('#[^a-z0-9]+#i','_',$str) );
	}

	function emailValid($email, $die = false) {

		$valid = preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/i', $email);
		
		if ( $die ) {
			if ( !$valid ) {
				$g = newsman::getInstance();				
				header('Location: '.$g->getLink('badEmail'));
				echo " ";
				exit();
			} else {
				return false;
			}
		} else {
			return $valid;
		}
	}

	function extractEmail($str) {
		if ( preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $str, $matches) ) {
			return $matches[0];
		}

		return null;
	}	

	function current_time( $type, $gmt = 0 ) {
		$t =  ( $gmt ) ? gmdate( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s', ( time() + ( get_option( 'gmt_offset' ) * 3600 ) ) );
		switch ( $type ) {
			case 'mysql':
				return $t;
				break;
			case 'timestamp':
				return strtotime($t);
				break;
		}
	}

	function hasColumn($tableName, $colName) {
		global $wpdb;

		$sql = "DESCRIBE $tableName";
		$res = $wpdb->get_results($sql,ARRAY_A);		
		$found = false;
		foreach ($res as $row) {
			if ( $row['Field'] === $colName ) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	function tableExists($tableName) {
		global $wpdb;
		return $wpdb->get_var("show tables like '".$tableName."'") === $tableName;
	}

	function addTrSlash($url, $dir = false) {
		$sep = $dir ? DIRECTORY_SEPARATOR : '/';
		if (substr($url,strlen($url)-1,1) != $sep) {
			$url .= $sep;
		}		
		return $url;
	}

	function supplant($string, $vars) {
		$tmpstr = $string;		

		if ( is_array($vars) ) {
			foreach($vars as $key => $value) {
				// replace singe variables 
				$tmpstr = preg_replace('/\$'.$this->preg_quote($key).'/si', $value,$tmpstr);
			}			
		}
		return $tmpstr;
	}

	public function preg_quote($str, $delim = '/') {
		return str_replace($delim, '\\'.$delim, preg_quote($str));
	}

	// just the excerpt
	private function first_n_words($text, $number_of_words) {
		// Where excerpts are concerned, HTML tends to behave
		// like the proverbial ogre in the china shop, so best to strip that
		$text = strip_tags($text);

		// \w[\w'-]* allows for any word character (a-zA-Z0-9_) and also contractions
		// and hyphenated words like 'range-finder' or "it's"
		// the /s flags means that . matches \n, so this can match multiple lines
		//$text = preg_replace("/^(\W*(\w[\w'-]*\b\W*){1,$number_of_words}).*/ms", '\\1', $text);
		preg_match('/^([\S]+\s+){50}/m', $text, $matches);



		return ( $matches && isset($matches[0]) ) ? str_replace("\n", "", $matches[0]) : $text;
	}

	function fancyExcerpt($content, $maxLength = 350) {
		
		$excerpt = apply_filters('the_content', $content);
			
		//$excerpt = wp_specialchars($excerpt);
		
		$excerpt = trim(strip_tags($excerpt));
		str_replace("&#8212;", "-", $excerpt);

		return $this->first_n_words($excerpt, $maxLength).'(...)';
	}

	function cutImages($content) {
		return preg_replace('/\<img[^>]+>/','',$content);
	}

	function cutScripts($src) {
		return preg_replace('@<script[^>]*>[\s\S]*?</script>@i', '', $src);
	}	

	function str_insert($insertstring, $intostring, $offset) {
	   $part1 = substr($intostring, 0, $offset);
	   $part2 = substr($intostring, $offset);
	  
	   $part1 = $part1 . $insertstring;
	   $whole = $part1 . $part2;
	   return $whole;
	}	

	function changeSectionType($html, $section, $newtype) {
		$res = preg_match('/<(\w+)[^>]*?gsedit="'.$this->preg_quote($section).'"[^>]*?>/i', $html, $matches, PREG_OFFSET_CAPTURE);

		if ( $res && !empty($matches) ) {
			// firstChart offset  +  matched str length'
			$openTag = $matches[0][0];
			$contentStart = $matches[0][1]+strlen($matches[0][0]);
			$tag = $matches[1][0];

			switch ( $newtype ) {
				case 'image':
					$newTag = 'img';
					break;
				case 'html':
					$newTag = 'div';
					break;					
				
				default:
					$newTag = $tag;
					break;
			}

			if ( strtolower($newTag) == strtolower($tag) ) {
				return $html; // tags are the same. no replacement.
			}

		} else {
			return $html;
		}

			$contentStart = $matches[0][1];
			$contLen = strlen($matches[0][0]);

			$tagReplacement = '<'.$newTag.'${2}>';

			if ( $newTag == 'div' ) {
				$tagReplacement .= '</'.$newTag.'>';
			}

			$contentReplacement = preg_replace('/<(\w+)([^>]*)>/i', $tagReplacement, $openTag);			
			$html = substr_replace($html, $contentReplacement, $contentStart, $contLen);

			$contentStart = $contentStart + strlen($contentReplacement);

		if ( $tag == 'div' ) {

			// if converting from div to images, we loose all the div content.
			// cutting it here

			// searching for close tag
			$pos = $contentStart;
			$lvl = 1;

			$contentEnd = false;

			while ( preg_match('/<(\/|)'.$tag.'[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE, $pos) ) {

				$pos = $matches[0][1]+strlen($matches[0][0]);

				if ( $matches[1][0] == '/' ) { // found close tag
					$lvl -= 1;
				} else { // found open tag
					$lvl += 1; 
				}

				if ( $lvl == 0 ) { // this is our close tag
					$contentEnd = $matches[0][1]+strlen($matches[0][0]);
					break;
				}
			}	

			if ( $contentEnd !== false ) {
				$contLen = $contentEnd-$contentStart;
				$html = substr_replace($html, '', $contentStart, $contLen);
			}

		}

		return $html;
	}

	function replaceSectionContent($html, $section, $newContent, $sectionKey = 'gsedit') {
		$res = preg_match('/<(\w+)[^>]*?'.$this->preg_quote($sectionKey).'="'.$this->preg_quote($section).'"[^>]*?>/i', $html, $matches, PREG_OFFSET_CAPTURE);



		if ( $res && !empty($matches) ) {
			// firstChart offset  +  matched str length'
			$openTag = $matches[0][0];
			$contentStart = $matches[0][1]+strlen($matches[0][0]);
			$tag = $matches[1][0];
		}

		if ( strtolower($tag) == 'img' ) {

			$contentStart = $matches[0][1];
			$contLen = strlen($matches[0][0]);

			if ( !preg_match('/\ssrc=(\\\'|")/i', $openTag) ) {
				// no "src" attr in the img tag, adding one
				$contentReplacement = preg_replace('/(<img)([^>]*>)/i', '${1} src="'.$newContent.'" ${2}', $openTag);
			} else {
				$contentReplacement = preg_replace('/(<img[^>]*src=(\\\'|"))(.*?)(\\2[^>]*>)/i', '${1}'.$newContent.'${4}', $openTag);
			}

		} else {

			// searching for close tag
			$pos = $contentStart;
			$lvl = 1;

			while ( preg_match('/<(\/|)'.$tag.'[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE, $pos) ) {

				$pos = $matches[0][1]+strlen($matches[0][0]);

				if ( $matches[1][0] == '/' ) { // found close tag
					$lvl -= 1;
				} else { // found open tag
					$lvl += 1; 
				}

				if ( $lvl == 0 ) { // this is our close tag
					$contentEnd = $matches[0][1];
					break;
				}
			}	

			$contentReplacement = $newContent;

			$contLen = $contentEnd-$contentStart;
		}

		return substr_replace($html, $contentReplacement, $contentStart, $contLen);
	}

	public function getSectionContent($html, $key, $value) {
		$res = preg_match('/<(\w+)[^>]*?'.$this->preg_quote($key).'="'.$this->preg_quote($value).'"[^>]*?>/i', $html, $matches, PREG_OFFSET_CAPTURE);

		if ( $res && !empty($matches) ) {
			// firstChart offset  +  matched str length'

			$openTag = $matches[0][0];
			$contentStart = $matches[0][1]+strlen($openTag);
			$tag = $matches[1][0];
		}

		if ( strtolower($tag) == 'img' ) {
			return $openTag;
		} else {

			// searching for close tag
			$pos = $contentStart;
			$lvl = 1;

			while ( preg_match('/<(\/|)'.$tag.'[^>]*>/i', $html, $matches, PREG_OFFSET_CAPTURE, $pos) ) {

				$pos = $matches[0][1]+strlen($matches[0][0]);

				if ( $matches[1][0] == '/' ) { // found close tag
					$lvl -= 1;
				} else { // found open tag
					$lvl += 1; 
				}

				if ( $lvl == 0 ) { // this is our close tag
					$contentEnd = $matches[0][1];
					break;
				}
			}	

			$contLen = $contentEnd-$contentStart;
		}

		return substr($html, $contentStart, $contLen);		
	}	

	public function linkNormalizationCallback($matches) {
		$url = $matches[4];

		if ( preg_match('/%5D|%5B/i', $url) ) {
			$url = urldecode(html_entity_decode($url));
		}
		return $matches[1].$url.$matches[5];
	}
	
	/**
     *
     * Emogrify applies htmlentities() to the href attributes of the links. 
     * This function is to get them back to normal.
     *
	 */	
	public function normalizeShortcodesInLinks($content) {
		return preg_replace_callback('/(<\w+[^>]+(href|src)=(\\\'|"))([^>]*?)(\3[^>]*>)/i', array($this, 'linkNormalizationCallback'), $content);
	}


	public function expandAssetsURLsCallback($matches) {		
		global $NEWSMAN_CURRENT_ASSETS_URL;
		$url = $matches[3];

		if ( strpos($url, '/') === 0 ) {
			$url = get_bloginfo('wpurl').$url;
		} else if (
			strpos($url, '[') !== 0 &&
			strpos($url, 'http') !== 0
		) {
			$url = $NEWSMAN_CURRENT_ASSETS_URL.$url;
		}

		return $matches[1].$url.$matches[4];
	}

	public function shrinkAssetsURLsCallback($matches) {
		global $NEWSMAN_CURRENT_ASSETS_URL;
		$url = $matches[3];

		$urlStart = strpos($url, $NEWSMAN_CURRENT_ASSETS_URL);

		$relPath = false;

		if ( $urlStart !== false  ) {
			$relPath = substr($url, strlen($NEWSMAN_CURRENT_ASSETS_URL));
		}

		if ( $relPath !== false ) {
			return $matches[1].$relPath.$matches[4];
		} else {
			return $matches[0];
		}
	}

	// TODO: This function was changed in 1.4.0. make sure the second param is URL everywhere 
	/**
	 * This function transforms assets urls in the code to full URL's or relative paths
	 * @param $tpl (String) HTML source
	 * @param $assetsURL (String) URL to the assets directory
	 * @param $operation (String) = (expand || shrink) - operation to perform to the found url
	 */
	public function processAssetsURLs($tpl, $assetsURL, $operation = 'expand') {
		global $NEWSMAN_CURRENT_ASSETS_URL;
		$NEWSMAN_CURRENT_ASSETS_URL = $this->addTrSlash($assetsURL);

		$op = $operation === 'expand' ? 'expandAssetsURLsCallback' : 'shrinkAssetsURLsCallback';

		$rx = '/(\b(?:src|url|background|placehold|gssource|gsdefault)=(\\\'|"))(.*?)(\2)/i';
		$tpl = preg_replace_callback($rx, array($this, $op), $tpl);

		// for css urls expansion
		$rx = '/(\burl\(([\\\'"]{0,1}))(.*?)(\2\))/i';
		$tpl = preg_replace_callback($rx, array($this, $op), $tpl);

		return $tpl;
	}	

	public function jsArrToMySQLSet($jsArr) {
		// jsArray =  [1,2,3]
		// dropping square brackets

		if ( !preg_match('/^[\d\,\[\]\s]+$/', $jsArr) ) {
			return '()';
		}
			
		if ( $jsArr[0] == '[' ) {
			$jsArr = substr($jsArr, 1, strlen($jsArr)-2);
		}
		
		$vals = preg_split('/[\s*,]+/', $jsArr);

		$set = '';

		for ($i=0; $i < count($vals); $i++) { 
			$comma = $i > 0 ? ',' : '';
			$set .= $comma.$vals[$i];
		}

		return '('.$set.')';
	}

	public function listsNamesSortCallback($a, $b) {
		return strcmp($a->name, $b->name);
	}

	public function getListsSelectOptions($selectedId = false, $showAddNew = true) {
		$lists = newsmanList::findAll();
		$opts = '';

		usort($lists, array($this, 'listsNamesSortCallback'));

		foreach ($lists as $lst) {
			$sel = ( $selectedId !== false && $lst->id == $selectedId ) ? 'selected="selected"' : '';
			$opts .= '<option '.$sel.' value="'.$lst->id.'">'.$lst->name.'</option>';
		}

		if ( $showAddNew ) {
			$opts .= 
			'<optgroup id="new-list-group" label="________________________________">
				<option value="add">'.__('Add new...', NEWSMAN).'</option>
			</optgroup>';			
		}

		return $opts;
	}

	/**
	 * SECURITY
	 */

	private function encrypt($str, $key) {
		# Add PKCS7 padding.
		$block = mcrypt_get_block_size('des', 'ecb');
		if (($pad = $block - (strlen($str) % $block)) < $block) {
			$str .= str_repeat(chr($pad), $pad);
		}

		return mcrypt_encrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);
	}

	private function decrypt($str, $key) {
		$str = mcrypt_decrypt(MCRYPT_DES, $key, $str, MCRYPT_MODE_ECB);

		# Strip padding out.
		$block = mcrypt_get_block_size('des', 'ecb');
		$pad = ord($str[($len = strlen($str)) - 1]);
		if ($pad && $pad < $block && preg_match('/' . chr($pad) . '{' . $pad . '}$/', $str) ) {
			return substr($str, 0, strlen($str) - $pad);
		}
		return $str;
	}

	public function encrypt_pwd($pwd) {
		return base64_encode( $this->encrypt($pwd, KEY) );
	}

	public function decrypt_pwd($enc_pwd) {
		return $this->decrypt( base64_decode($enc_pwd), KEY );
	}

	public function extractURLFilename($url){
		if ( preg_match('/\/([^\/]+)$/', $url, $matches) ) {
			return $matches[1];
		}
		return $url;
	} 

	public function compileThumbnailsCallback($matches) {
		$tag = $matches[0];
		$placehold = '';
		$src = '';

		if ( preg_match('/(placehold=")([^"]+)(")/i', $tag, $m) ) {
			$placehold = $this->extractURLFilename($m[2]);
		}

		if ( preg_match('/(src=")([^"]+)(")/i', $tag, $m) ) {
			$src = $this->extractURLFilename($m[2]);
		}

		if ( $src === '' ) {
			return '';
		}

		if ( $placehold == $src ) {
			return '';	
		}
		return $tag;		
	}

	public function compileThumbnails($tpl) {
		return preg_replace_callback('/<img[^>]*\>/i', array($this, 'compileThumbnailsCallback'), $tpl);
	}

	public function getRelativePath($from, $to)	{
		$from     = explode(DIRECTORY_SEPARATOR, $from);
		$to       = explode(DIRECTORY_SEPARATOR, $to);
		$relPath  = $to;

		foreach($from as $depth => $dir) {
			// find first non-matching dir
			if($dir === $to[$depth]) {
				// ignore this directory
				array_shift($relPath);
			} else {
				// get number of remaining dirs to $from
				$remaining = count($from) - $depth;
				if($remaining > 1) {
					// add traversals up to first matching dir
					$padLength = (count($relPath) + $remaining - 1) * -1;
					$relPath = array_pad($relPath, $padLength, '..');
					break;
				} else {
					$relPath[0] = './' . $relPath[0];
				}
			}
		}
		return implode('/', $relPath);
	}

	/**
	 * Unpacks and registers template
	 * @param $uploadedZip (String) Path to uploaded template archive
	 * @param $tplDef (String|Array) template name or template definition
	 */
	public function installTemplate($uploadedZip, $tplDef = false) {

		$newsman_wpfs_creds = request_filesystem_credentials(NEWSMAN_BLOG_ADMIN_URL, '', false, false, null);

		// $uploadedZip = .../wp-content/uploads/newsman/templates/sometemplate.zip
		$extractDir = dirname($uploadedZip); // .../wp-content/uploads/newsman/templates

		$tplDirName = basename($uploadedZip, '.zip'); // sometemplate
		$tplDir = $extractDir.DIRECTORY_SEPARATOR.$tplDirName; // .../wp-content/uploads/newsman/templates/sometemplate
		$tplUrl = $this->getTemplateDirURL($tplDirName);

		$firstZipContentFileName = file_get_contents($uploadedZip, false, null, 30, 255);
		
		if ( strpos($firstZipContentFileName, $tplDirName.'/') !== false ) {
			// packed directory
		} else {			
			// need to create directory
			$extractDir .= DIRECTORY_SEPARATOR.$tplDirName;
		}

		WP_Filesystem($newsman_wpfs_creds);

		if ( $newsman_wpfs_creds['connection_type'] == 'ftp' ) {
			$extractDir = $this->getRelativePath(ABSPATH, $extractDir);	
		}		

		$res = unzip_file($uploadedZip, $extractDir);

		if ( is_wp_error($res) ) {
			$this->log($res->get_error_message());
		}

		if ( $res === true ) {
			unlink($uploadedZip);

			newsman::getInstance()->securityCleanup();

			if ( is_string($tplDef) || is_bool($tplDef) ) {
				$this->registerTemplate($tplDir, $tplUrl, $tplDef);				
			} else {
				$this->registerTemplateWithDef($tplDir, $tplUrl, $tplDef);	
			}

			return true;
		} else {
			return false;
		}

		return ( $res === true );
	}

	/**
	 * Registers template in the plugin(Writes it to the database). 
	 */
	public function registerTemplate($templatePath, $templateURL, $templateName = false) {

		if ( !is_dir($templatePath) ) {
			return;
		}

		$templatePath = $this->addTrSlash($templatePath, 'path');
		$templateURL = $this->addTrSlash($templateURL);

		$n = newsman::getInstance();

		$name = basename($templatePath);

		$templateName = $templateName ? $templateName : $name;

		$fileName = $templatePath."$name.html";
		$particlesFileName = $templatePath."_$name.html";

		$tpl = new newsmanEmailTemplate();
		$tpl->system = false;
		$tpl->name = $templateName;
		$tpl->subject = __('Enter Subject Here', NEWSMAN);
		if ( @file_exists($fileName) ) {
			$tpl->html = $this->processAssetsURLs($this->file_get_contents_utf8($fileName), $templateURL);	
		}		

		if ( @file_exists($particlesFileName) ) {
			$tpl->particles = $this->processAssetsURLs($this->file_get_contents_utf8($particlesFileName), $templateURL);
		} else {
			$defParticles = $this->getDefaultTemplateParticles();
			$tpl->particles = $defParticles ? $defParticles : '';
		}

		$tpl->plain = '';
		$tpl->assetsURL = $templateURL;
		$tpl->assetsPath = $templatePath;

		$id = $tpl->save();

		return $id;
	}

	/**
	 * Registers template with the template definition as defined in the templates store 
	 */
	public function registerTemplateWithDef($templatePath, $templateURL, $tplDef) {

		if ( !is_dir($templatePath) ) {
			return;
		}

		$templatePath = $this->addTrSlash($templatePath, 'path');
		$templateURL = $this->addTrSlash($templateURL);

		$n = newsman::getInstance();

		$templatesToInstall = array();

		if ( isset($tplDef['templates']) && is_array($tplDef['templates']) ) {
			$templatesToInstall = $tplDef['templates'];
		} else {
			$templatesToInstall[] = array(
				'file' => basename($templatePath).".html",
				'name' => $tplDef['name']
			);
		}

		foreach ($templatesToInstall as $tplObj) {
			$fileName = $templatePath.$tplObj['file'];
			$particlesFileName = $templatePath."_".$tplObj['file'];

			$tpl = new newsmanEmailTemplate();
			$tpl->system = false;
			$tpl->name = $tplObj['name'];
			$tpl->subject = __('Enter Subject Here', NEWSMAN);
			$tpl->html = $this->processAssetsURLs($this->file_get_contents_utf8($fileName), $templateURL);

			if ( file_exists($particlesFileName) ) {
				$tpl->particles = $this->processAssetsURLs($this->file_get_contents_utf8($particlesFileName), $templateURL);
			} else {
				$defParticles = $this->getDefaultTemplateParticles();
				$tpl->particles = $defParticles ? $defParticles : '';
			}

			$tpl->plain = '';
			$tpl->assetsURL = $templateURL;
			$tpl->assetsPath = $templatePath;

			$tpl->save();
		}
	}	

	public function delTree($dir) {
		if ( is_dir($dir) ) {
			$files = array_diff(scandir($dir), array('.','..')); 
			foreach ($files as $file) { 
				(is_dir($dir.DIRECTORY_SEPARATOR.$file)) ? $this->delTree($dir.DIRECTORY_SEPARATOR.$file) : unlink($dir.DIRECTORY_SEPARATOR.$file);
			} 
			return rmdir($dir); 			
		}
		return false;
	} 	

	public function assetsDeletable($assetsPath) {
		// we delete only the assetes of uploaded templates
		$assetsPath = newsman_ensure_correct_path($assetsPath);
		$comparePath = DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'wpnewsman';
		return strpos($assetsPath, $comparePath) > -1;
	}

	public function hasSharedAssets($assetsPath) {
		$n = newsmanEmailTemplate::count('`assetsPath` = %s', array($assetsPath));
		$n += newsmanEmail::count('`assetsPath` = %s', array($assetsPath));

		return $n > 1;
	}

	public function tryRemoveTemplateAssets($assetsPath, $deleteSharedAssets = false) {
		if ( $this->assetsDeletable($assetsPath) ) {

			if ( $this->hasSharedAssets($assetsPath) ) {
				return $deleteSharedAssets ? $this->delTree($assetsPath) : false;
			} else {
				return $this->delTree($assetsPath);	
			}
			
		}
		return false;
	}

	public function getTemplateDirURL($tplDir) {
		$dirs = wp_upload_dir();
		$url = $dirs['baseurl']."/wpnewsman/template/$tplDir/";

		return $url;
	}	

	/* get particles from the stock digest template */
	public function getDefaultTemplateParticles() {
		$particlesFile =
			NEWSMAN_PLUGIN_PATH.
			DIRECTORY_SEPARATOR.
			'email-templates'.
			DIRECTORY_SEPARATOR.
			'digest'.
			DIRECTORY_SEPARATOR.
			'_digest.html';

		return $this->file_get_contents_utf8($particlesFile);
	}

	/**
	 * Installes templates bundled with the plugin
	 */
	public function installStockTemplates() {
		$o = newsmanOptions::getInstance();

		// this is just to not to accidentally reinstall templates
		$basicTplId = $o->get('basicTemplate');
		if ( $basicTplId ) { return; }

		$dir = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'email-templates'.DIRECTORY_SEPARATOR;
		$url = NEWSMAN_PLUGIN_URL.'/email-templates/';

		if ( $handle = opendir($dir) ) {
			while (false !== ($entry = readdir($handle))) {
				if ( $entry != "." && $entry != ".." && $entry !== 'newsman-system' ) {
					$id = $this->registerTemplate($dir.$entry, $url.$entry);
					if ( $entry === 'basic' ) {
						$o->set('basicTemplate', $id);
					}				
				}
			}
			closedir($handle);
		}
	}

	/**
	 * ReInstalles template bundled with the plugin
	 */
	public function reInstallStockTemplate($name) {
		$o = newsmanOptions::getInstance();

		$tpl = newsmanEmailTemplate::findOne('`name` = %s and `system` != 1', array( $name ) );

		if ( $tpl ) {
			$tpl->remove();
		}

		$dir = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'email-templates'.DIRECTORY_SEPARATOR;
		$url = NEWSMAN_PLUGIN_URL.'/email-templates/';

		$id = $this->registerTemplate($dir.$name, $url.$name);
		if ( $name === 'basic' ) {
			$o->set('basicTemplate', $id);
		}
	}

	/**
	 * Duplicates template
	 * @param {Integer} Original Template Id
	 * @param [String] New Template name
	 * @return {newsmanEmailTemplate} Copy template object
	 */
	public function duplicateTemplate($origianTplId, $name = false, $newAssignedList = false) {
		$tpl = newsmanEmailTemplate::findOne('id = %d', array( $origianTplId ) );

		if ( $tpl ) {

			$newTpl = new newsmanEmailTemplate();

			$newTpl->system 		= $tpl->system;
			$newTpl->system_type 	= $tpl->system_type;
			$newTpl->assigned_list	= ($newAssignedList !== false) ? $newAssignedList : $tpl->assigned_list;

			if ( $newAssignedList !== false ) { // copying system templates for a new list
				$newTpl->name 		= $tpl->name;
			} else {
				$newTpl->name		= $name ? $name : $tpl->name.' '.__('Copy', NEWSMAN);
			}			

			$newTpl->subject 		= $tpl->subject;
			$newTpl->html 			= $tpl->html;
			$newTpl->plain 			= $tpl->plain;
			$newTpl->assetsURL 		= $tpl->assetsURL;
			$newTpl->assetsPath		= $tpl->assetsPath;
			$newTpl->particles 		= $tpl->particles;

			$newTpl->save();

			return $newTpl;

		} else {
			return null;
		}
	}

	/* --------------------------------------------------------------------------------------------------------- */
	/* Modified Base64 to create email addres beacon */	
	/* --------------------------------------------------------------------------------------------------------- */

	public function genTranslationMap() {
		return str_shuffle($this->base64Map);
	}

	function encEmail($email) {
		$o = newsmanOptions::getInstance();
		return strtr( $this->base64EncodeU($email), $this->base64Map, $o->get('base64TrMap'));
	}

	function decEmail($enc_email) {
		$o = newsmanOptions::getInstance();
		$baseString = strtr($enc_email, $o->get('base64TrMap'), $this->base64Map);
		// proper email encoding landed in 1.6.7 version
		// TODO: remove normal base64 check and fix in class.bounced.php in few version
		$isNormalBase64 = preg_match('#[\+\/]+#', $baseString);

		return $isNormalBase64 ? base64_decode($baseString) : $this->base64DecodeU($baseString);
	}	

	public function unsubscribeFromLists($email, $statusStr, $raw = false) {
		if ( !$raw ) {
			$email = $this->extractEmail($email);	
		}

		if ( !isset( $this->listsCache ) ) {
			$this->listsCache = newsmanList::findAll();
		}

		$opts = '';
		foreach ($this->listsCache as $lst) {
			$lst->unsubscribe($email, $statusStr);
		}		
	}

	/* --------------------------------------------------------------------------------------------------------- */
	/* Plugin version transformations */	
	/* --------------------------------------------------------------------------------------------------------- */

	function versionToNum($ver) {
		if ( preg_match('/^(\d+)\.(\d+)\.(\d+)(.*?)$/', $ver, $matches) ) {		
			$a = $matches[1];
			$b = $matches[2];
			$c = $matches[3];
			$prefix = $matches[4];
			$d = preg_replace('/\D+/', '', $prefix);
		} else {
			return null;
		}	

		// a bb cc prefixNumber ddd
		// prefixNumber = alpha = 1
		// prefixNumber = beta  = 2
		// prefixNumber = gamma = 3
		// prefixNumber = rc    = 3

		$prefixNumber = 0;

		if ( $prefix === '' ) {
			$prefixNumber = 9; // release
		} else {
			if ( preg_match('/\bpre-alpha\b/i', $prefix) ) {
				$prefixNumber = 0;
			} else if ( preg_match('/\balpha\b/i', $prefix) ) {
				$prefixNumber = 1;
			} else if ( preg_match('/\bbeta\b/i', $prefix) ) {
				$prefixNumber = 2;
			} else if ( preg_match('/\b(gamma|rc)\b/i', $prefix) ) {
				$prefixNumber = 3;
			}
		}

		$s = $a.
			 str_pad($b, 2, '0', STR_PAD_LEFT).
			 str_pad($c, 2, '0', STR_PAD_LEFT).
			 $prefixNumber.
			 str_pad($d, 3, '0', STR_PAD_LEFT);

		return is_numeric($s) ? intval($s) : 0;
	}

	public function canUpdate() {

		$codeVersion = $this->versionToNum(NEWSMAN_VERSION);
		$storedVersion = $this->versionToNum(get_option('newsman_version'));

		return $codeVersion > $storedVersion;
	}

	public function runUpdate($doUpdateAction = true) {
		$updated = false;
		$codeVersion = $this->versionToNum(NEWSMAN_VERSION);
		$storedVersion = $this->versionToNum(get_option('newsman_version'));

		$this->installNewsmanMuPlugin();

		if ( $codeVersion > $storedVersion ) {
			$updated = true;
			require_once(NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR."migration.php");
			if ( function_exists('newsman_do_migration') ) {
				try {
					newsman_do_migration();	
				} catch ( Exception $e ) {
					$this->log('[MIGRATION] Exception: '.$e->getMessage());
				}				
			}			
			if ( $doUpdateAction ) {
				do_action('wpnewsman_update');	
			}			
			update_option('newsman_version', NEWSMAN_VERSION);
		}

		return $updated;
	}

	public function installNewsmanMuPlugin() {
		// $muBundledVersion = $this->versionToNum(NEWSMAN_MU_BUNDLED_VERSION);
		// $muInstalledVersion = $this->versionToNum( defined('WPNEWSMAN_MU_VERSION') ? WPNEWSMAN_MU_VERSION : '0.0.0' );
		$skip = defined('NEWSMAN_NO_MU_PLUGIN') && NEWSMAN_NO_MU_PLUGIN;
		$this->log('[installNewsmanMuPlugin] skip installation '.var_export($skip, true));
		if ( $skip ) { return false; }

		$pluginHeader = <<<NEWSMAN_PLUGIN_HEAD_TEMPLATE
/*
Plugin Name: WPNewsman Pro - Worker Stability Enhancement
Plugin URI: http://wpnewsman.com
Description: Disables 3rd party plugins during the calls to internal worker URLs. Enhances stability of the worker.
Version: {{WPNEWSMAN_MU_VERSION}}
Author: Alex Ladyga - G-Lock Software
Author URI: http://www.glocksoft.com
*/
NEWSMAN_PLUGIN_HEAD_TEMPLATE;

		$muPluginsDir = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'mu-plugins';
		$this->log('[installNewsmanMuPlugin] muPluginsDir: %s', $muPluginsDir);
		if ( !is_dir($muPluginsDir) ) {
			$this->log('[installNewsmanMuPlugin] making dir');
			@mkdir($muPluginsDir, 0755, true);
		}
		$wpmuPluginFilename = $muPluginsDir.DIRECTORY_SEPARATOR.'wpnewsman-mu.php';
		$wpmuPluginTpl = file_get_contents(NEWSMAN_PLUGIN_PATH.'/wpnewsman-mu-tpl');

		$this->log('[installNewsmanMuPlugin] wpmuPluginFilename: %s', $wpmuPluginFilename);
		$this->log('[installNewsmanMuPlugin] wpmuPluginTpl: %s', $wpmuPluginTpl);

		// injecting plugin header
		$wpmuPluginTpl = str_replace('{{PLUGIN_HEADER}}', $pluginHeader, $wpmuPluginTpl);

		// injecting version
		$wpmuPluginTpl = str_replace('{{WPNEWSMAN_MU_VERSION}}', NEWSMAN_MU_BUNDLED_VERSION, $wpmuPluginTpl);		
		try {
			file_put_contents($wpmuPluginFilename, $wpmuPluginTpl);
		} catch ( Exception $e ) {
			$this->log('[installNewsmanMuPlugin] file_put_contents error: %s', $e->getMessage());
		}

		$exists = file_exists($wpmuPluginFilename);
		$this->log('[installNewsmanMuPlugin] file exists: %s', var_export($exists, true));
		return $exists;
	}

	public function uninstallNewsmanMuPlugin() {
		$muPluginFilename = WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'mu-plugins'.DIRECTORY_SEPARATOR.'wpnewsman-mu.php';

		if ( file_exists($muPluginFilename) ) {
			@unlink($muPluginFilename);
		}
	}


	// ***************


	public function getSystemInfo() {
		global $wp_locale, $wp_version;   

		$info = array('----- Begin System Info -----', "\n");

		$col = 22;

		$info[] = str_pad('WPNewsman Version:', $col).NEWSMAN_VERSION;
		$info[] = str_pad('WordPress Version:', $col).$wp_version;

		$info[] = "\n";

		$info[] = str_pad('WordPress URL:', $col).get_bloginfo('wpurl');
		$info[] = str_pad('Site URL:', $col).get_bloginfo('url');

		$info[] = "\n";

		$info[] = str_pad('WP_DEBUG:', $col).( ( defined('WP_DEBUG') && WP_DEBUG ) ? 'Enabled' : 'Disabled' );
		$info[] = str_pad('Multi-Site:', $col).( function_exists('is_multisite') & is_multisite() ? 'Enabled' : 'Disabled' );

		$info[] = str_pad('User Agent:', $col).$_SERVER['HTTP_USER_AGENT'];

		$info[] = "\n";
		$info[] = "Active theme: ";

		// Getting theme data

		$data = array();

		if ( function_exists( 'wp_get_theme' ) ) {
			if ( is_child_theme() ) {
				$parentTheme = wp_get_theme();
				$them = wp_get_theme( $parentTheme->get('Template') );
			} else {
				$theme = wp_get_theme();
			}

			if ( $theme && is_object($theme) ) {
				$data['theme_name'] = $theme->get('Name');
				$data['theme_version'] = $theme->get('Version');
				$data['theme_uri'] = $theme->get('AuthorURI');				
			}
			
		} else {
			$theme_data = get_theme_data( newsman_ensure_correct_path(TEMPLATEPATH.DIRECTORY_SEPARATOR.'style.css') );
			$data['theme_name'] = $theme_data['Name'];
			$data['theme_version'] = $theme_data['Version'];
			$data['theme_uri'] = isset($theme_data['Theme URI']) ? $theme_data['Theme URI'] : $theme_data['Autor URI'] ;
		}

		$info[] = "  ".$data['theme_name']." ".$data['theme_version'];
		$info[] = "  ".$data['theme_uri'];


		// Getting plugins info
		$info[] = "\n";
		$info[] = "Active plugins:";

		$plugins = get_option('active_plugins');

		foreach ($plugins as $plugin) {

			$pluginFile = newsman_ensure_correct_path(WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.$plugin);
			$pluginData = get_plugin_data( $pluginFile );

			$info[] = "  ".$pluginData['Name']." ".$pluginData['Version'];
			$info[] = "  ".$pluginData['PluginURI'];
			$info[] = "\n";
		}

		$info[] = "----- End System Info -----";

		return implode("\n", $info);
	}	

	public function getFirstImage($post) {
		if ( is_object($post) ) {
			// do nothing
		} else if ( is_numeric($post) ) {
			$post = get_post($post);
		}
		$content = $post->post_content;
		if ( preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches) ) {
			return $matches[1];
		}
		return null;
	}

	public function getPostFeaturedImageThumbnail($post, $size) {
		$size = ( $size === null ) ? 'thumbnail' : $size;

		$post_thumbnail_id = get_post_thumbnail_id( $post->ID );
		$attrs = wp_get_attachment_image_src( $post_thumbnail_id, $size, false );
		return $attrs[0];
	}

	/**
	 * Gets posts featured image on any first image it can find in the post
	 */
	public function getPostThumbnail($post, $size = null) {
		$url = $this->getPostFeaturedImageThumbnail($post, $size);

		if ( !$url ) {
			$url = $this->getFirstImage($post);	
		}
		return $url;
	}

	/* --------------------------------------------------------------------------------------------------------- */
	/* L10n functions */	
	/* --------------------------------------------------------------------------------------------------------- */

	public function getActionPageNameById($pageId) {
		$options = newsmanOptions::getInstance();
		$pages = $options->get('activePages');
		foreach ($pages as $name => $id) {	
			if ( $id == $pageId ) {
				return $name;
			}
		}
	}

	public function getPageTranslation($lang = 'en_US', $pageId) {
		$name = $this->getActionPageNameById($pageId);
		if ( $name ) {
			$pd = $this->getPagesData();
			$data = $pd[$name];
			$translation = array();
			$translation['title'] = $data['title'];
			$translation['content'] = $this->loadTpl($data['template'], $lang);
			$translation['excerpt'] = $this->loadTpl($data['excerpt'],  $lang);

			return $translation;
		}
	}


	public function installActionPages($lang = 'en_US', $replace = false) {
		// loading pages & email templates
		$options = newsmanOptions::getInstance();


		$langExists = $this->installLangExists($lang);

		if ( !$langExists )  {
			$lang = 'en_US';
		}

		$pd = $this->getPagesData();

		foreach ($pd as $pageKey => $data) {
			$pageId = $options->get('activePages.'.$pageKey);

			if ( !$pageId ) {
				$new_page = array(
					'post_type' => 'newsman_ap',
					'post_title' => $data['title'],
					'post_name' => $data['slug'],
					'post_content' => $this->loadTpl($data['template'], $lang ),
					'post_excerpt' => $this->loadTpl($data['excerpt'],  $lang ),
					'post_status' => 'publish',
					'post_author' => 1
				);
				$pageId = wp_insert_post($new_page);

				$options->set('activePages.'.$pageKey, $pageId);
			} else if ( $replace ) {
				// replacing the action page
				$ap = get_post($pageId);
				$ap->post_title = $data['title'];
				$ap->post_content = $this->loadTpl($data['template'], $lang);
				$ap->post_excerpt = $this->loadTpl($data['excerpt'],  $lang);

				wp_update_post($ap);
			}
		}		
	}

	public function installSystemEmailTemplates($lang = 'en_US', $replace = false, $listId = 0) {
		$options = newsmanOptions::getInstance();
		// loading email templates

		if ( !$this->installLangExists($lang) ) {
			$lang = 'en_US';
		}

		$emailTemplates = array(
			array(
				'type' => NEWSMAN_ET_ADMIN_SUB_NOTIFICATION,
				'name' => __('Administrator notification - new subscriber', NEWSMAN),
				'file' =>  'admin-subscription-event.txt'
			),
			array(
				'type' => NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION,
				'name' => __('Administrator notification - user unsubscribed', NEWSMAN),
				'file' =>  'admin-unsubscribe-event.txt'
			),
			array(
				'type' => NEWSMAN_ET_CONFIRMATION,
				'name' => __('Subscription confirmation', NEWSMAN),
				'file' =>  'confirmation.txt'
			),
			array(
				'type' => NEWSMAN_ET_UNSUBSCRIBE,
				'name' => __('Unsubscribe notification', NEWSMAN),
				'file' =>  'unsubscribe.txt'
			),
			array(
				'type' => NEWSMAN_ET_WELCOME,
				'name' => __('Welcome letter, thanks for subscribing', NEWSMAN),
				'file' =>  'welcome.txt'
			),
			array(
				'type' => NEWSMAN_ET_UNSUBSCRIBE_CONFIRMATION,
				'name' => __('Unsubscribe confirmation', NEWSMAN),
				'file' => 'unsubscribe-confirmation.txt'
			),
			array(
				'type' => NEWSMAN_ET_RECONFIRM,
				'name' => __('Re-subscription confirmation', NEWSMAN),
				'file' => 'reconfirm.txt'
			)			
		);

		$tplFileName = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR."email-templates".DIRECTORY_SEPARATOR."newsman-system".DIRECTORY_SEPARATOR."newsman-system.html";
		$baseTpl = $this->file_get_contents_utf8($tplFileName);

		foreach ($emailTemplates as $tplDef) {

			$name = $tplDef['name'];
			$fileName = $tplDef['file'];

			$eml = $this->emailFromFile($fileName, $lang);

			$tpl = newsmanEmailTemplate::findOne('`system` = 1 AND `assigned_list` = %d AND `system_type` = %d', array($listId, $tplDef['type']));

			if ( !$tpl ) {
				$tpl = new newsmanEmailTemplate();
			} else if ( !$replace ) {
				continue;
			}
			
			$tmp_base = $baseTpl;

			$tpl->name = $name;
			$tpl->subject = $eml['subject'];
			$tpl->html = $this->replaceSectionContent($tmp_base, 'std_content', $eml['html']);
			$tpl->plain = $eml['plain'];

			if ( !isset($tpl->assigned_list) ) {
				$tpl->assigned_list = $listId;	
			}

			$tpl->system = true;
			$tpl->system_type = $tplDef['type'];

			$tpl->save();
		}
	}

	public function copySystemTemplatesForList($listId) {
		$tpls = newsmanEmailTemplate::findAll('`system` = 1 AND `assigned_list` = 0');
		if ( $tpls ) {
			foreach ($tpls as $tpl) {
				$this->duplicateTemplate($tpl->id, false, $listId);
			}
		}
	}

	public function listHasSystemTemplates($listId) {
		$tpls = newsmanEmailTemplate::findAll('`system` = 1 AND `assigned_list` = %d', array($listId));
		return is_array($tpls) && count($tpls) > 0;
	}	

	public function getPostTypes() {
		$res = array();

		$types = get_post_types(array(
			'public'   => true,
			'show_ui'  => true
		));

		foreach ($types as $key => $value) {
			$res[] = array( 'name' => $value, "selected" => false );
		}

		return $res;
	}

	public function getLastChanges() {
		$changes = '';
		$changelog = $this->file_get_contents_utf8(NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'readme.txt');
		if ( preg_match('/==\s*Changelog\s*==[\s\S]*?\=\s*([\d\.]+)\s*\=([\s\S]*?)\=\s+[\d\.]+/i', $changelog, $matches) ) {
			$changes = "<ul>\n";
			foreach (explode("\n", $matches[2]) as $str) {
				$str = trim($str);
				if ( $str !== '' ) {
					$str = preg_replace('/^\*\s+/', '', $str);
					$changes .= "<li>$str</li>\n";
				}				
			}
			$changes .= "</ul>";
		}
		return $changes;
	}

	/* --------------------------------------------------------------------------------------------------------- */
	/* Shortcode fucntions */
	/* --------------------------------------------------------------------------------------------------------- */

	public function modifyShortCode(&$content, $name, $searchParams = array(), $replaceParams = array()) {

		$offset = 0;

		while ( preg_match('/\['.$name.'\s*([^\[\]]*)\]/i', $content, $matches, PREG_OFFSET_CAPTURE, $offset) ) {

			$start = $matches[0][1];
			$length = strlen($matches[0][0]);
			$offset = $start+$length+1;

			$sc = new newsmanShortCode($name, $content, $start, $length);	

			$sc->paramsFromStr($matches[1][0]);

			if ( $sc->matchParams($searchParams) ) {
				$sc->set($replaceParams);
				$newlen = strlen($sc->toString());
				$content = $sc->updateSource();
				$offset = $start + $newlen + 1;
			}		

		}	

		return $content;
	}

	public function findShortCode(&$content, $name, $searchParams = array()) {

		$offset = 0;

		while ( preg_match('/\['.$name.'\s*([^\[\]]*)\]/i', $content, $matches, PREG_OFFSET_CAPTURE, $offset) ) {

			$start = $matches[0][1];
			$length = strlen($matches[0][0]);
			$offset = $start+$length+1;

			$sc = new newsmanShortCode($name, $content, $start, $length);	

			$sc->paramsFromStr($matches[1][0]);

			if ( $sc->matchParams($searchParams) ) {
				return $sc;
			}		

		}	

		return null;
	}

	// --------------

	public function parseListName($fullListName) {
		$ln = array(
			'name' => $fullListName,
			'selectionType' => NEWSMAN_SS_CONFIRMED
		);

		$listNameArr = explode('/', $fullListName);
		$listNameArrCnt = count($listNameArr);

		if ( $listNameArrCnt == 2 ) {
			$ln['name'] = $listNameArr[0];
			switch ( strtoupper($listNameArr[1]) ) {
				case 'CONFIRMED':
					$ln['selectionType'] = NEWSMAN_SS_CONFIRMED;
					break;
				case 'UNCONFIRMED':
					$ln['selectionType'] = NEWSMAN_SS_UNCONFIRMED;
					break;
			}
		}

		return (object)$ln;
	}

	// recursively encodes array strings to UTF8 
	public function utf8_encode_all($mix) {
		if ( is_string($mix) ) return mb_check_encoding($mix, 'UTF-8') ? $mix : utf8_encode($mix);
		if ( !is_array($mix) ) return $mix; 
		$ret = array(); 
		foreach($mix as $k=>$v) {
			$ret[$k] = $this->utf8_encode_all($v);
		} 
		return $ret; 
	} 	

	public function file_get_contents_utf8($fn) {
		$content = file_get_contents($fn);
		return mb_convert_encoding($content, 'UTF-8',
			mb_detect_encoding($content, 'UTF-8, ISO-8859-1', true));
	}

	public function getInstalledLanguageData($locale) {
		$dir = __DIR__.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR.$locale.DIRECTORY_SEPARATOR.'lang.txt';
		$data = explode('|', @file_get_contents($dir));		

		return array(
			'locale' => $locale,
			'name' => $data[0],
			'native' => $data[1]
		);
	}

	public function getAvailableTranslationLocales() {
		$list = array();
		$dir = __DIR__.DIRECTORY_SEPARATOR.'install';
		if ( $handle = opendir($dir) ) {
			while (false !== ($entry = readdir($handle))) {
				$epath = $dir.DIRECTORY_SEPARATOR.$entry;
				if ( $entry[0] !== '.' && is_dir($epath) ) {
					$list[] = $this->getInstalledLanguageData($entry);
				}				
			}
		}
		return $list;
	}

	public function isResponsive($html) {
		return preg_match('/<!--\s*NEWSMAN_RESPONSIVE\s*-->/i', $html);
	}

	public function getGeoLocalName($arrNames) {
		$locale = get_locale();
		$locale = strtr($locale, '_', '-');
		$shortLocale = substr($locale, 0, 2);

		$shortLocaleName = '';

		if ( !$arrNames ) {
			return '';
		}

		foreach ($arrNames as $name_loc => $name) {
			if ( $name_loc === $locale ) {
				// exact locate match
				return $name;
			} elseif ( $shortLocale == $name_loc ) { // short locale match, eg. just "en" instead of "en_GB"
				$shortLocaleName = $name;
			}
		}

		return ( $shortLocaleName ) ? $shortLocaleName : $arrNames['en'];
	}	

	/* --------------------------------------------------------------------------------------------------------- */
	/* Nonce */
	/* --------------------------------------------------------------------------------------------------------- */

	public function createNonce() {
		$o = newsmanOptions::getInstance();
		$rawNonce = $o->get('secret').dechex(time());
		return strtr($rawNonce, $this->base64Map, $o->get('base64TrMap'));
	}

	public function verifyNonce($nonce) {
		$o = newsmanOptions::getInstance();
		$rawNonce = strtr($nonce, $o->get('base64TrMap'), $this->base64Map);
		$secret = substr($rawNonce, 0, 32);
		$ts = hexdec(substr($rawNonce, 32));
		if ( $o->get('secret') !== $secret ) {
			$this->log('nonce %s is invalid. wrong secret');
			return false;
		}

		if ( time() > $ts + 60 ) {
			$this->log('nonce %s expired.');
			return false;			
		}
		return true;
	}

	/* --------------------------------------------------------------------------------------------------------- */
	/* Locks */
	/* --------------------------------------------------------------------------------------------------------- */

	public function lock($name) {
		$this->log('[newsmanUtils lock] locking %s', $name);
		return $this->l->lock($name);
	}

	public function isLocked($name) {
		return $this->l->isLocked($name);
	}

	public function releaseLock($name) {
		$this->log('[newsmanUtils releaseLock] releasing %s', $name);
		return $this->l->releaseLock($name);
	}		
}