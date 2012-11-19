<?php

require_once('class.options.php');
require_once('class.list.php');

require_once(ABSPATH.'/wp-includes/class-phpmailer.php');

define('KEY', "\xa3\xb4\xef\xda\x24\xd5\xcc\x3b");

class newsmanUtils {

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self(); 
		} 
		return self::$instance; 
	} 

	function emailFromFile($fileName) {
		$emailSource = file_get_contents(NEWSMAN_PLUGIN_PATH.'/install/emails/'.$fileName);

		$eml = array(
			'subject' => '',
			'plain' => '',
			'html' => ''
		);

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

	function loadTpl($fileName) {
		return file_get_contents(NEWSMAN_PLUGIN_PATH.'/install/templates/'.$fileName);
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

		$vars = $opts['vars'];

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

		$rx = '/(src=(\\\'|"))(.*?)(\2)/i';
		$rx2 = '/(url\((\\\'|"|))(.*?)(\2\))/i';
		$rx3 = '/(placehold=(\\\'|"))(.*?)(\2)/i';

		$tpl = preg_replace_callback($rx, array($this, 'expandAssetsURLsCallback'), $tpl);		
		$tpl = preg_replace_callback($rx2, array($this, 'expandAssetsURLsCallback'), $tpl);
		$tpl = preg_replace_callback($rx3, array($this, 'expandAssetsURLsCallback'), $tpl);

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

	public function getListsSelectOptions($selectedId = false, $showAddNew = true) {
		$lists = newsmanList::findAll();
		$opts = '';
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
		$gsfinal = '';
		$src = '';

		if ( preg_match('/(placehold=")([^"]+)(")/i', $tag, $m) ) {
			$placehold = $this->extractURLFilename($m[2]);
		}

		if ( preg_match('/(src=")([^"]+)(")/i', $tag, $m) ) {
			$src = $this->extractURLFilename($m[2]);
		}

		if ( preg_match('/(gsfinal=")([^"]+)(")/i', $tag, $m) ) {
			$gsfinal = $this->extractURLFilename($m[2]);
		}

		// echo 'placehold = '; var_dump($placehold);
		// echo 'gsfinal = '; var_dump($gsfinal);
		// echo 'src = '; var_dump($src);

		if ( $src === '' && $gsfinal === '' ) {
			return '';
		}

		if ( $placehold == $src ) {
			if ( !$gsfinal ) { // if there's no thumbnail image in the post - removeing img tag at all
				return '';	
			} else {
				return preg_replace('/(src=")([^"]+)(")/i', '$1'.$gsfinal.'$3', $tag);		
			}			
		}
		return $tag;		
	}

	public function compileThumbnails($tpl) {
		return preg_replace_callback('/<img[^>]*\>/i', array($this, 'compileThumbnailsCallback'), $tpl);
		//return preg_replace_callback('/<img[^>]*?gsfinal[^>]*?\>/i', array($this, 'compileThumbnailsCallback'), $tpl);
	}

}