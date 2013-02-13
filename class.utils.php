<?php

require_once('class.options.php');
require_once('class.list.php');
require_once('class.shortcode.php');

require_once(ABSPATH.'/wp-includes/class-phpmailer.php');

define('KEY', "\xa3\xb4\xef\xda\x24\xd5\xcc\x3b");

class newsmanUtils {

	var $base64Map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';

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
		$filePath = NEWSMAN_PLUGIN_PATH.'/install/'.$lang.'/emails/'.$fileName;
		
		$eml = array(
			'subject' => '',
			'plain' => '',
			'html' => ''
		);

		if ( !file_exists($filePath) ) {
			return $eml;
		}

		$emailSource = file_get_contents($filePath);

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
				$eml[$state] = $b;
			}
		}
		return $eml;
	}

	function loadTpl($fileName, $lang = 'en_US') {
		$path = NEWSMAN_PLUGIN_PATH.'/install/'.$lang.'/templates/'.$fileName; 
		$tpl = '';
		if ( file_exists($path) ) {
			$tpl = file_get_contents($path);
		}
		return $tpl;
	}

	private function installLangExists($lang = 'en_US') {
		return is_dir(NEWSMAN_PLUGIN_PATH.'/install/'.$lang);
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

		//newsman_log('newsman_Mail: Email from: '.$newsman_email_from);
		//newsman_log('newsman_Mail: Name from: '.$newsman_name_from);	

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
		$msg = '['.date('Y-m-d H:i:s').'] '.$msg."\n";
		file_put_contents(NEWSMAN_PLUGIN_PATH.'/newsmanlog.txt', $msg, FILE_APPEND);
		// if (get_option('newsman_write_debug_log') != '1') {
		// 	return;
		// }
		
		// global $wpdb;	
		// global $newsman_table_log;
		// $sql = 'INSERT INTO '.$newsman_table_log.' VALUES(null, %s)';
		
		// $wpdb->query($wpdb->prepare($sql, $msg));
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
		$valid = preg_match("/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/", $email);
		
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

	function tableExists($tableName) {
		global $wpdb;
		return $wpdb->get_var("show tables like '".$tableName."'") === $tableName;
	}

	function addTrSlash($url) {
		if (substr($url,strlen($url)-1,1) != '/') {
			$url .= '/';
		}		
		return $url;
	}

	function supplant($string, $vars) {
		$tmpstr = $string;		

		if ( is_array($vars) ) {
			foreach($vars as $key => $value) {
				// replace singe variables 
				$tmpstr = preg_replace('/\$'.preg_quote($key).'/si', $value,$tmpstr);
			}			
		}
		return $tmpstr;
	}

	// just the excerpt
	private function first_n_words($text, $number_of_words) {
		// Where excerpts are concerned, HTML tends to behave
		// like the proverbial ogre in the china shop, so best to strip that
		$text = strip_tags($text);

		// \w[\w'-]* allows for any word character (a-zA-Z0-9_) and also contractions
		// and hyphenated words like 'range-finder' or "it's"
		// the /s flags means that . matches \n, so this can match multiple lines
		$text = preg_replace("/^\W*((\w[\w'-]*\b\W*){1,$number_of_words}).*/ms", '\\1', $text);

		// strip out newline characters from our excerpt
		return str_replace("\n", "", $text);
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
		return preg_replace('@<script>.*?</script>@i', '', $src);
	}	

	function cutPostBlock(&$content, $newContent = '') {
		$res = preg_match('/<(\w+)[^>]*?gspost[^>]*?>/i', $content, $matches, PREG_OFFSET_CAPTURE);

		if ( $res && !empty($matches) ) {
			// firstChart offset  +  matched str length'
			$openTag = $matches[0][0];
			$contentStart = $matches[0][1]+strlen($matches[0][0]);
			$tag = $matches[1][0];
		}

		// searching for close tag
		$pos = $contentStart;
		$lvl = 1;

		while ( preg_match('/<(\/|)'.$tag.'[^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE, $pos) ) {

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

		$cutted = substr($content, $contentStart, $contLen);

		$content = substr_replace($content, $contentReplacement, $contentStart, $contLen);

		return $cutted;
	}

	function str_insert($insertstring, $intostring, $offset) {
	   $part1 = substr($intostring, 0, $offset);
	   $part2 = substr($intostring, $offset);
	  
	   $part1 = $part1 . $insertstring;
	   $whole = $part1 . $part2;
	   return $whole;
	}	

	function changeSectionType($html, $section, $newtype) {
		$res = preg_match('/<(\w+)[^>]*?gsedit="'.preg_quote($section).'"[^>]*?>/i', $html, $matches, PREG_OFFSET_CAPTURE);

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
		$res = preg_match('/<(\w+)[^>]*?'.preg_quote($sectionKey).'="'.preg_quote($section).'"[^>]*?>/i', $html, $matches, PREG_OFFSET_CAPTURE);



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
		$res = preg_match('/<(\w+)[^>]*?'.preg_quote($key).'="'.preg_quote($value).'"[^>]*?>/i', $html, $matches, PREG_OFFSET_CAPTURE);

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
		return $matches[1].urldecode(html_entity_decode($matches[4])).$matches[5];
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
		global $NEWSMAN_CURRENT_ASSETS_DIR;
		$url = $matches[3];

		if ( !(strpos($url, '[') === 0) && !( strpos($url, 'http') === 0 ) ) {
			$url = $NEWSMAN_CURRENT_ASSETS_DIR.$url;
		}

		return $matches[1].$url.$matches[4];
	}

	public function expandAssetsURLs($tpl, $assetsDir) {
		global $NEWSMAN_CURRENT_ASSETS_DIR;
		$NEWSMAN_CURRENT_ASSETS_DIR = NEWSMAN_PLUGIN_URL.'/email-templates/'.$assetsDir.'/';

		$rx = '/(\b(?:src|url|placehold|gssource|gsdefault)=(\\\'|"))(.*?)(\2)/i';
		$tpl = preg_replace_callback($rx, array($this, 'expandAssetsURLsCallback'), $tpl);		

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
		//return preg_replace_callback('/<img[^>]*?gsfinal[^>]*?\>/i', array($this, 'compileThumbnailsCallback'), $tpl);
	}


	/* --------------------------------------------------------------------------------------------------------- */
	/* Modified Base64 to create email addres beacon */	
	/* --------------------------------------------------------------------------------------------------------- */

	public function genTranslationMap() {
		return str_shuffle($this->base64Map);
	}

	function encEmail($email) {
		$o = newsmanOptions::getInstance();
		return strtr(base64_encode($email), $this->base64Map, $o->get('base64TrMap'));
	}

	function decEmail($enc_email) {
		$o = newsmanOptions::getInstance();
		return base64_decode(strtr($enc_email, $o->get('base64TrMap'), $this->base64Map));
	}	

	function unsubscribeFromLists($email, $statusStr) {
		$email = $this->extractEmail($email);
		$lists = newsmanList::findAll();
		$opts = '';
		foreach ($lists as $lst) {
			$lst->unsubscribe($email, $statusStr);
		}		
	}

	/* --------------------------------------------------------------------------------------------------------- */
	/* Plugin version transformations */	
	/* --------------------------------------------------------------------------------------------------------- */

	public function versionToNum($ver) {
		$s = preg_replace('/\D+/', '', $ver);
		return is_numeric($s) ? intval($s) : 0;
	}

	public function isUpdate($writeNewVersion = true) {
		$update = false;
		$codeVersion = $this->versionToNum(NEWSMAN_VERSION);
		$storedVersion = $this->versionToNum(get_option('newsman_version'));
		if ( $codeVersion > $storedVersion ) {
			$update = true;
			if ( $writeNewVersion ) {
				require_once('migration.php');
				if ( function_exists('newsman_do_migration') ) {
					newsman_do_migration();
				}		
				do_action('wpnewsman_update');		
				update_option('newsman_old_version', get_option('newsman_version'));
				update_option('newsman_version', NEWSMAN_VERSION);
			}			
		}
		return $update;
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
		$info[] = str_pad('Site URL:', $col).get_bloginfo('siteurl');

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
			
			$data['theme_name'] = $theme->get('Name');
			$data['theme_version'] = $theme->get('Version');
			$data['theme_uri'] = $theme->get('AuthorURI');
		} else {
			$theme_data = get_theme_data( TEMPLATEPATH.'/style.css' );
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

			$pluginFile = ABSPATH.PLUGINDIR.'/'.$plugin;
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

	/* --------------------------------------------------------------------------------------------------------- */
	/* L10n functions */	
	/* --------------------------------------------------------------------------------------------------------- */

	public function installActionPages($lang = 'en_US', $replace = false) {
		// loading pages & email templates
		$options = newsmanOptions::getInstance();

		$pagesData = array(
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
			'changeSubscription' => array(
				'slug' => 'change-subscription',
				'title' => __('Change Subscription', NEWSMAN),
				'template' => 'change-subscription.html',
				'excerpt' => 'change-subscription-ex.html'
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
			)
		);

		foreach ($pagesData as $pageKey => $data) {
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
			} else if ( $replace && $this->installLangExists($lang) ) {
				// replacing the action page
				$ap = get_post($pageId);
				$ap->post_title = $data['title'];
				$ap->post_content = $this->loadTpl($data['template'], $lang);
				$ap->post_excerpt = $this->loadTpl($data['excerpt'],  $lang);

				wp_update_post($ap);
			}
		}		
	}

	public function installSystemEmailTemplates($lang = 'en_US', $replace = false) {
		$options = newsmanOptions::getInstance();
		// loading email templates

		$emailTemplates = array(
			'addressChanged' => array( __('Email address updated', NEWSMAN),'address-changed.txt'),
			'adminSubscriptionEvent' => array( __('Administrator notification - new subscriber', NEWSMAN), 'admin-subscription-event.txt'),
			'adminUnsubscribeEvent' => array(  __('Administrator notification - user unsubscribed', NEWSMAN), 'admin-unsubscribe-event.txt'),
			'confirmation' => array( __('Subscription confirmation', NEWSMAN), 'confirmation.txt'),
			'unsubscribe' => array( __('Unsubscribe notification', NEWSMAN), 'unsubscribe.txt'),
			'welcome' => array( __('Welcome letter, thanks for subscribing', NEWSMAN), 'welcome.txt')
		);

		$tplFileName = NEWSMAN_PLUGIN_PATH."/email-templates/newsman-system/newsman-system.html";
		$baseTpl = file_get_contents($tplFileName);

		foreach ($emailTemplates as $key => $v) {

			$name = $v[0];
			$fileName = $v[1];

			$tplId = $options->get('emailTemplates.'.$key);
			$eml = $this->emailFromFile($fileName, $lang);

			if ( !$tplId ) {				

				$tpl = new newsmanEmailTemplate();

				$tmp_base = $baseTpl;

				$tpl->name = $name;
				$tpl->subject = $eml['subject'];
				$tpl->html = $this->replaceSectionContent($tmp_base, 'std_content', $eml['html']);
				$tpl->plain = $eml['plain'];
				$tpl->system = true;

				$tplId = $tpl->save();

				$options->set('emailTemplates.'.$key, $tplId);
			} else if ( $replace && $this->installLangExists($lang) ) {
				$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

				if ( $tpl ) {
					$tmp_base = $baseTpl;

					$tpl->name = $name;
					$tpl->subject = $eml['subject'];
					$tpl->html = $this->replaceSectionContent($tmp_base, 'std_content', $eml['html']);
					$tpl->plain = $eml['plain'];
					$tpl->system = true;

					$tpl->save();
				}
			}
		}
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
		$changelog = file_get_contents(NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR.'readme.txt');
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
	
}