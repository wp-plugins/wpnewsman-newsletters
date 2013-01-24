<?php

	require_once('class.utils.php');
	require_once('class.options.php');
	require_once('class.emails.php');
	require_once('class.list.php');
	require_once('class.emailtemplates.php');
	require_once('class.sentlog.php');

	if ( !defined('NEWSMAN_SS_UNCONFIRMED') )  { define('NEWSMAN_SS_UNCONFIRMED',0);  }
	if ( !defined('NEWSMAN_SS_CONFIRMED') )    { define('NEWSMAN_SS_CONFIRMED',1);    }
	if ( !defined('NEWSMAN_SS_UNSUBSCRIBED') ) { define('NEWSMAN_SS_UNSUBSCRIBED',2); }

	class newsmanAJAX {

		public function __construct() {

			mb_internal_encoding("UTF-8");
			$loc = "UTF-8";
			putenv("LANG=$loc");
			$loc = setlocale(LC_ALL, $loc);			

			if ( current_user_can( 'newsman_wpNewsman' ) ) {
				$methods = get_class_methods(__CLASS__);

				foreach ($methods as $meth) {
					if ( strpos($meth, 'aj') === 0 ) {
						add_action('wp_ajax_newsman'.ucfirst($meth), array($this, $meth));
					}
				}
			}

		}

		public function respond($state, $msg, $params = array()) {
			global $db;

			$msg = array(
				'state' => $state,
				'msg' => $msg
			);

			$msg = array_merge($msg, $params);

			if ( defined('NEWSMAN_TESTS') ) {
				echo json_encode($msg);
				return;
			}			

		    // if ( !ob_start("ob_gzhandler") ) { 
		    //     ob_start(); 			
		    // }
			
			if ( !$state ) {
				header("HTTP/1.0 400 Bad Request");
			}

			header("Content-type: application/json");
			
			echo json_encode($msg);
			//echo "<pre>";
			//print_r(var_dump($msg),true);
			//echo "</pre>";    

		    //$size = ob_get_length();    
		    // send headers to tell the browser to close the connection    
    		//header("Content-Length: $size");
			//header('Connection: close');    

			// flush all output 
			// ob_end_flush(); 
			// ob_flush(); 
			// flush();
			   
			if ($db) $db->close();  
			exit();
		}

		public function param($param) {
			$u = newsmanUtils::getInstance();

			if ( defined('NEWSMAN_TESTS') ) {
				global $AJ_PARAMS;
				return $AJ_PARAMS[$param];
			}

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

		public function statusCodeToText($code) {
			switch ( $code ) {
				case NEWSMAN_SS_CONFIRMED:		return __('Confirmed', NEWSMAN);
				case NEWSMAN_SS_UNCONFIRMED:	return __('Unconfirmed', NEWSMAN);
				case NEWSMAN_SS_UNSUBSCRIBED:	return __('Unsubscribed', NEWSMAN);
			}
		}	

		// error methods

		private function errEmailNotFound($id) {
			$this->respond(false, sprintf(__('Cannot edit email. Email with id "%s" is not found.', NEWSMAN), $id));
		}	

		private function errCantWriteEmail($id) {
			$this->respond(false, sprintf(__('Cannot edit email. Email with id "%s" cannot be written.', NEWSMAN), $id) );
		}

		private function errEmailDoesntHaveProp($key) {
			$this->respond(false, sprintf(__('Email does not have a "%s" property.', NEWSMAN), $key) );
		}


		private function errTemplateNotFound($id) {
			$this->respond(false, sprintf(__('Cannot edit template. Template with id "%s" is not found.', NEWSMAN), $id) );
		}


		private function errCantWriteTemplate($id) {
			$this->respond(false, sprintf(__('Cannot edit template. Template with id "%s" cannot be written.', NEWSMAN), $id) );
		}

		private function errListNotFound($id) {
			$this->respond(false, sprintf(__('List with id "%s" is not found.', NEWSMAN), $id));
		}

		// ajax accessible methods

		// "ajGetSubscribers" method could be accessed by the "newsmanAjGetSubscribers" ajax action

		public function ajGetSubscribers() {			
			global $wpdb;

			$res = array();

			$listId = $this->param('listId', '1');
			
			$pg		= intval($this->param('pg','1'));
			$ipp	= intval($this->param('ipp','25'));
			// search params
			$email	= $this->param('email','');
			$show	= $this->param('show','all');
			$q		= $this->param('q',false);	

			switch ($show) {
				case 'confirmed':
					$show = NEWSMAN_SS_CONFIRMED;
					break;

				case 'subscribed':
				case 'unconfirmed':
					$show = NEWSMAN_SS_UNCONFIRMED;
					break;

				case 'unsubscribed':
					$show = NEWSMAN_SS_UNSUBSCRIBED;
					break;					
				
				default:
					$show = 'all';
					break;
			}

			$list = newsmanList::findOne('id = %d', array($listId));
			
			$res['count'] = $list->getStats($q);
			$res['rows'] = $list->getPageJSON(true, $pg, $ipp, $show, $q);

			$this->respond(true, 'success', $res);
		}

		public function ajSendBugReport() {
			$utils = newsmanUtils::getInstance();

			$response = $this->param('response');
			$extra = $this->param('extra');

			$email = 'wpnewsman-reports@glocksoft.com';

			$message = array(
				'subject' => sprintf(__('WPNEWSMAN Bug Report from %s', NEWSMAN), get_bloginfo('wpurl')),
				'plain' => "$response\n\n$extra"
			);
			
			$res = $utils->mail($message, array(
				'to' => $email
			));
			
			//----------------------------------------- 
			
			if ( $res === true ) {
				$this->respond(true, sprintf(__('Bug report was sent to %s.', NEWSMAN), $email) );
			} else {
				$this->respond(false, $res);
			}
		}

		public 	function ajTestMailer() {

			$utils = newsmanUtils::getInstance();
			
			$host		= $this->param('host');
			$user		= $this->param('user');
			$pass		= $this->param('pass');
			$port		= $this->param('port');
			$email		= $this->param('email');
			$secure		= $this->param('secure');
			$mdo		= $this->param('mdo');

			$opts = array(
				'mailer' => array(
					'mdo' => $mdo,
					'smtp' => array(
						'host' => $host,
						'user' => $user,
						'pass' => $pass,
						'port' => $port,
						'secure' => $secure
					)
				),
				'vars' => array(
					'email' => $email
				)
			);

			$message = array(
				'subject' => __('Your SMTP settings are correct', NEWSMAN),
				'plain' => __(' If you read this message, your SMTP settings in the G-Lock WPNewsman plugin are correct', NEWSMAN)
			);
			
			$res = $utils->mail($message, $opts);
			
			//----------------------------------------- 
			
			if ($res === true)
			    $this->respond(true, sprintf(__('Test email was sent to %s.', NEWSMAN), $email) );
			else
			    $this->respond(false, $res);		
		}

		public function ajUnsubscribe() {
			$listId = $this->param('listId', '1');
			$all = ( $this->param('all', '0') === '1' );

			$list = newsmanList::findOne('id = %d', array($listId));
			if ( !$list ) {
				$this->errListNotFound($listId);
			}

			if ( $all ) {
				$r = $list->setStatusForAll(NEWSMAN_SS_UNSUBSCRIBED);
			} else {
				$ids = $this->param('ids');
				$ids = preg_split('/[\s*,]+/', $ids);
				$r = $list->setStatus($ids, NEWSMAN_SS_UNSUBSCRIBED);
			}

			if ( $r !== true ) {
				$this->respond(false, $r);				
			} else {
				$this->respond(true, __('success', NEWSMAN));
			}
		}

		public function ajSetStatus() {

			$ids = $this->param('ids');

			$all = ($this->param('all') == '1');

			$status = $this->param('status');
			$status = intval($status);

			$ids = preg_split('/[\s*,]+/', $ids);

			$listId = $this->param('listId', '1');
			$list = newsmanList::findOne('id = %d', array($listId));
			if ( !$list ) {
				$this->errListNotFound($listId);
			}

			if ( $all === true ) {
				$r = $list->setStatusForAll($status);
			} else {
				$r = $list->setStatus($ids, $status);
			}			

			if ( $r !== true ) {
				$this->respond(false, $r);				
			} else {
				$this->respond(true, __('success', NEWSMAN) );
			}
		}		

		public function ajDeleteSubscribers() {

			$ids = $this->param('ids');
			$all = ( $this->param('all', '0') === '1' );
			$listId = $this->param('listId', '1');
			$type = $this->param('type');

			$list = newsmanList::findOne('id = %d', array($listId));

			if ( $all ) {
				$r = $list->deleteAll($type);
			} else {
				$ids = preg_split('/[\s*,]+/', $ids);

				if ( !$list ) {
					$this->errListNotFound($listId);
				}
				$r = $list->delete($ids);				
			}


			if ( $r !== true ) {
				$this->respond(false, $r);				
			} else {
				$this->respond(true, __('Successfully deleted selected subscribers.', NEWSMAN) );
			}
		}		

		public function ajGetOptions() {
			$o = newsmanOptions::getInstance();

			$opts = json_encode($o->get());

			$this->respond(true, 'options', array(
				'options' => $opts
			));
		}

		public function ajSetOptions() {
			$o = newsmanOptions::getInstance();
			$u = newsmanUtils::getInstance();

			$opts = $this->param('options', false);

			if ( !$opts ) {
				$this->respond(false, __('Error: "options" request parameter was empty or not defined.', NEWSMAN));
			} else {
				$opts = json_decode($opts, true);
				$o->load($opts, 'PRESERVE_OLD_VALUES');
				do_action('newsman_options_updated');
				$this->respond(true, __('Options were successfully saved.', NEWSMAN) );
			}
		}	

		public function ajGetEmails() {
			global $wpdb;

			$res = array();
			
			$pg		= intval($this->param('pg','1'));
			$ipp	= intval($this->param('ipp','25'));

			// search params
			$show	= $this->param('show', 'all');
			$q		= $this->param('q', false);	

			$where = null;
			$args = array();

			if ( $q ) {
				$where = 'subject regexp %s OR html regexp %s OR plain regexp %s';
				$q = preg_quote($q);

				if ( $show === 'all' ) {
					$args = array( $q, $q, $q );
				} else {
					$where = 'status = %s AND ( '.$where.' )';
					$args = array( $show, $q, $q, $q );
				}
			} else {
				if ( $show === 'all' ) {
					$where = '1=1';
				} else {
					$where = 'status = %s';
					$args = array( $show );
				}				
			}

			$whereFind = $where . '  ORDER BY id DESC';

			$stats = newsmanEmail::countAll('status', '1=1', array());
			$emails = newsmanEmail::findAllPaged($pg, $ipp, $whereFind, $args);
			
			$res['count'] = array(
				'pending' => 0,
				'sent' => 0,
				'drafts' => 0,
				'inprogress' => 0,
				'all' => 0
			);

			foreach ($stats as $row) {
				$res['count'][$row['status']] = intval($row['cnt']);
			}

			$all = 0;
			foreach ( $res['count'] as $key => $value ) {
				$all += $value;
			}

			$res['count']['all'] = $all;

			$res['rows'] = array();

			foreach ($emails as $email) {
				$eml = $email->toJSON();
				$eml['created'] = strtotime($eml['created']);
				$res['rows'][] = $eml;
			}

			$this->respond(true, __('success', NEWSMAN), $res);
		}

		public function ajGetPosts() {

			global $wpdb;
			global $post;
			global $newsman_ajresponse;
			
			$search		= $this->param('search', false);

			$cats	= $this->param('cats', '[]');
			$auths	= $this->param('auths', false);

			$postType = $this->param('postType', 'post');

			$ipp = $this->param('ipp', 15);
			$page = $this->param('page', 1);

			$cats = json_decode('['.$cats.']');

			$includePrivate = $this->param('includePrivate', 0);
			$includePrivate = intval($includePrivate) ? true : false;
			
			$i = 1;
			$posts = array();
			
			$df = get_option('date_format');
			$tf = get_option('time_format');       
			
			$show_full_post = get_option('newsman_show_full_posts') == '1' ? true : false;

			//$exlen = get_option('newsman_rss_excerpt_length');
			
		    /*
		    	category_name=
		    	cat=22
		    	year=$current_year
		    	monthnum=$current_month
		    	order=ASC
		    	tag=bread,baking
		    	author=3
		    	caller_get_posts=1
		    	author=1
		    	post_type=page
		    	post_status=publish
		    	orderby=title
		    	order=ASC
		    	
				*  hour= - hour (from 0 to 23)
				* minute= - minute (from 0 to 60)
				* second= - second (0 to 60)
				* day= - day of the month (from 1 to 31)
				* monthnum= - month number (from 1 to 12)
				* year= - 4 digit year (e.g. 2009)
				* w= - week of the year (from 0 to 53) and uses the MySQL WEEK command Mode=1.     	
		    */

			//$newsman_ajresponse['limit'] = $limit;

			$u = newsmanUtils::getInstance();

			$args = array(		
				'post_type' => $postType,
				'post_status' => array('publish'),
				'posts_per_page' => $ipp,
				'paged' => $page,
				'orderby' => 'date',
				'order' => 'DESC'
			);


			if ( !empty($cats) ) {
				$args['category__in'] = $cats;
			}

			if ( !empty($auths) ) {
				$args['author'] = $auths;
			}

			if ( $includePrivate ) {
				$args['post_status'][] = 'private';
				//$args['perm'] = 'readable';
			}

			if ( isset($search) && $search ) {
				$query = new WP_Query('posts_per_page='.$ipp.'&paged='.$page.'&post_type=post&s='.$search.'&order=DESC&orderby=date');
			} else {
				$query = new WP_Query($args);
			}			

			// array( 'post_status' => array( 'publish', 'private' ), 'perm' => 'readable' ) 

			while ( $query->have_posts() ) {
				$query->the_post();
				$pt = mysql2date('U', $post->post_date); 

				$content = $u->fancyExcerpt($post->post_content, 50);

				//if ('publish' == $post->post_status) {
					$posts[] =	array(
						'id' => $post->ID,
						'date' => date($df.' '.$tf, $pt),
						'title' => $post->post_title,
						'description' => $content,
						'link' => get_permalink($post->ID),
						'number' => $i
					);
					$i++;
				//}
			}

			$this->respond(true, 'success', array(
				'posts' => $posts
			));
		}

		public function ajGetLists() {

			$lists = newsmanList::findAll();
			$listsNames = array();

			foreach ($lists as $list) {
				$listsNames[] = array(
					'id' => $list->id,
					'name' => $list->name,
					'stats' => $list->getStats($q)
				);
			}

			$this->respond(true, 'lists', array(
				'lists' => $listsNames
			));
		}

		public function ajSavePlainEmail() {
			$emlId = $this->param('id', false);
			$to = $this->param('to');
			$subj = $this->param('subj');
			$contentHtml = $this->param('html');
			$contentPlain = $this->param('plain');
			$ts = $this->param('ts', 0);

			$to = split(',', $to);

			if ( $emlId ) {
				$email = newsmanEmail::findOne('id = %d', array( $emlId ) );
			}
		
			if ( !$email ) {
				$email = new newsmanEmail();	
			}			

			$email->to = $to;

			$email->subject = $subj;
			$email->html = $contentHtml;
			$email->plain = $contentPlain;

			$email->schedule = intval($ts);

			$email->editor = 'wp';
			$email->status = 'draft';

			$r = $email->save();

			if ( !$emlId ) {
				$emlId = $r;
			}

			if ( $r ) {
				$this->respond(true, __('Saved', NEWSMAN), array(
					'id' => $emlId
				));
			} else {
				$this->respond(false, $email->lastError);
			}
		}

		public function ajQueuePlainEmail() {
			$to = $this->param('to');
			$subj = $this->param('subj');
			$contentHtml = $this->param('html');
			$contentPlain = $this->param('plain');
			$send = $this->param('send');
			$ts = $this->param('ts', 0);

			$to = split(',', $to);


			$email = new newsmanEmail();

			$email->to = $to;

			$email->subject = $subj;
			$email->html = $contentHtml;
			$email->plain = $contentPlain;

			$email->editor = 'wp';

			if ( $send == 'now' ) {
				$email->status = 'pending';
			} elseif ( $send == 'schedule' ) {
				$email->schedule = intval($ts);
				$email->status = 'scheduled';				
			}		

			$r = $email->save();

			if ( $r ) {
				$this->respond(true, __('Your email was successfully queued for sending.', NEWSMAN), array(
					'redirect' => get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox'
				));
			} else {
				$this->respond(false, $email->lastError);
			}
		}

		public function ajGetTemplates() {
			global $wpdb;

			$res = array();
			
			$pg		= intval($this->param('pg','1'));
			$ipp	= intval($this->param('ipp','25'));

			// search params
			$show	= $this->param('type', 'all');
			$q		= $this->param('q', false);	

			$selector = '';
			$args = array();

			if ( $show != 'all' ) {
				$selector = 'system = %d';
				$args[] = ( $show == 'system' );
			} else {
				$selector = '1';
			}

			$selector .= ' ORDER BY id DESC';
			

			$tpls = newsmanEmailTemplate::findAllPaged($pg, $ipp, $selector, $args);
			
			$res['count'] = newsmanEmailTemplate::count($selector, $args);
			$res['rows'] = array();

			foreach ($tpls as $tpl) {
				$res['rows'][] = $tpl->toJSON();
			}

			$this->respond(true, 'success', $res);
		}

		public function ajGetEmailData() {
			$emlId = $this->param('id');
			$key = $this->param('key');

			$eml = newsmanEmail::findOne('id = %d', array( $emlId ) );

			if ( !$eml ) {
				$this->errEmailNotFound($emlId);
				return;
			}

			if ( !isset($eml->$key) ) {
				$this->errEmailDoesntHaveProp($key);				
			} else {
				$data = array();
				$data[$key] = $eml->$key;

				$this->respond(true, __('success', NEWSMAN), $data);
			}			
		}

		public function ajSetEmailData() {
			$emlId = $this->param('id');
			$key = $this->param('key');
			$value = $this->param('value');

			$eml = newsmanEmail::findOne('id = %d', array($emlId));

			if ( !$eml ) {
				$this->errEmailNotFound($emlId);
				return;
			}

			// if ( !isset($eml->$key) ) {
			// 	$this->respond(false, "Email doesn't have \"$key\".");
			// } else {
				$eml->$key = $value;
				$eml->save();
				$this->respond(true, 'success');
			//}
		}


		public function ajGetTemplateData() {

			$tplId = $this->param('id');
			$key = $this->param('key');

			$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

			if ( !$tpl ) {
				$this->errTemplateNotFound($tplId);
				return;
			}

			if ( !isset($tpl->$key) ) {
				$this->respond(false, sprintf(__('Template does not have a "%s" property.', NEWSMAN), $key) );
			} else {
				$data = array();
				$data[$key] = $tpl->$key;

				$this->respond(true, __('success', NEWSMAN), $data);
			}
		}

		public function ajSetTemplateData() {

			$tplId = $this->param('id');
			$key = $this->param('key');
			$value = $this->param('value');

			$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

			if ( !$tpl ) {
				$this->errTemplateNotFound($tplId);
				return;
			}

			if ( !isset($tpl->$key) ) {
				$this->respond(false, sprintf(__('Template does not have a "%s" property.', NEWSMAN), $key) );
			} else {
				$tpl->$key = $value;
				$tpl->save();
				$this->respond(true, 'success');
			}
		}		


		public function ajChangeTemplateSection() {
			
			$u = newsmanUtils::getInstance();

			$tplId = $this->param('id');
			$section = $this->param('section');
			$newType = $this->param('new_type');

			$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

			if ( !$tpl ) {
				$this->errTemplateNotFound($tplId);
				return;
			}

			$tpl->html = $u->changeSectionType($tpl->html, $section, $newType);

			$res = $tpl->save();

			if ( $res === false ) {
				$this->errCantWriteTemplate($tplId);
				return;
			}			

			$this->respond(true, __('Saved', NEWSMAN) );
		}

		public function ajChangeEmailSection() {
			$u = newsmanUtils::getInstance();

			$id = $this->param('id');
			$section = $this->param('section');
			$newType = $this->param('new_type');

			$eml = newsmanEmail::findOne('id = %d', array($id));


			if ( !$eml ) {
				$this->errEmailNotFound($id);
				return;
			}

			$eml->html = $u->changeSectionType($eml->html, $section, $newType);

			$res = $eml->save();

			if ( $res === false ) {
				$this->errCantWriteEmail($id);
				return;
			}			

			$this->respond(true, __('Saved', NEWSMAN) );	
		}


		public function ajEditTemplate() {

			$u = newsmanUtils::getInstance();

			$tplId = $this->param('id');
			$section = $this->param('section');
			$newContent = $this->param('new_content');

			$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

			if ( !$tpl ) {
				$this->errEmailNotFound($id);
				return;
			}

			$tpl->html = $u->replaceSectionContent($tpl->html, $section, $newContent);

			$res = $tpl->save();

			if ( $res === false ) {
				$this->errCantWriteEmail($tplId);
				return;
			}			

			$this->respond(true, __('Saved', NEWSMAN) );
		}

		public function ajEditEmail() {

			$u = newsmanUtils::getInstance();

			$id = $this->param('id');
			$section = $this->param('section');
			$newContent = $this->param('new_content');

			$eml = newsmanEmail::findOne('id = %d', array($id));


			if ( !$eml ) {
				$this->errEmailNotFound($id);
				return;
			}

			$eml->html = $u->replaceSectionContent($eml->html, $section, $newContent);

			$res = $eml->save();

			if ( $res === false ) {
				$this->errCantWriteEmail($id);
				return;
			}			

			$this->respond(true, __('Saved', NEWSMAN) );			
		}

		public function ajCreateEmailTemplate() {
			$u = newsmanUtils::getInstance();

			$name = $this->param('name');
			$type = $this->param('type');

			if ( !$name ) {
				$this->respond(false, sprintf(__('"%s" parameter is missing in the request.', NEWSMAN), 'name') );
				return;
			}

			if ( !$type ) {
				$this->respond(false, sprintf(__('"%s" parameter is missing in the request.', NEWSMAN), 'type') );
				return;
			}			

			$dir = NEWSMAN_PLUGIN_PATH."/email-templates/$type";
			$fileName = "$dir/$type.html";

			$particlesFileName = "$dir/_$type.html";

			$tpl = new newsmanEmailTemplate();
			$tpl->system = false;
			$tpl->name = $name;
			$tpl->subject = __('Enter Subject Here', NEWSMAN);
			$tpl->html = file_get_contents($fileName);

			if ( file_exists($particlesFileName) ) {
				$tpl->particles = file_get_contents($particlesFileName);
				$tpl->particles = $u->expandAssetsURLs($tpl->particles, $type);

			}

			$tpl->plain = '';
			$tpl->assets = $type;
			$id = $tpl->save();

			$this->respond(true, __("Template was successfully created."), array( 'id' => $id ));
		}

		public function ajCreateEmailFromBasicTemplate() {
			$u = newsmanUtils::getInstance();
			$type = $this->param('type');

			if ( !$type ) {
				$this->respond(false, sprintf(__('%s" parameter is missing in the request.', NEWSMAN), 'type') );
				return;
			}			

			$dir = NEWSMAN_PLUGIN_PATH."/email-templates/$type";
			$fileName = "$dir/$type.html";

			$particlesFileName = "$dir/_$type.html";

			$eml = new newsmanEmail();

			$eml->subject = __('Enter Subject Here', NEWSMAN);
			$eml->html = file_get_contents($fileName);
			$eml->plain = '';
			$eml->assets = $type;

			if ( file_exists($particlesFileName) ) {
				$particles = file_get_contents($particlesFileName);
				$eml->particles = $particles;
				$eml->particles = $u->expandAssetsURLs($eml->particles, $type);
			}			

			$id = $eml->save();

			$this->respond(true, __('Email was successfully created.', NEWSMAN), array( 'id' => $id ));
		}		

		public function ajDeleteEmailTemplates() {

			$ids = $this->param('ids');

			$ids = preg_split('/[\s*,]+/', $ids);

			$s = '(';
			$del = '';
			foreach ( $ids as $id ) {
				$s .= $del.$id;
				$del = ',';
			}
			$s .= ')';

			$r = newsmanEmailTemplate::removeAll('`id` in '.$s.' and `system` != 1');

			if ( $r === false ) {
				$this->respond(false, newsmanEmailTemplate::$lastError);				
			} else {
				$note = ( $r == 0 ) ? __('You can\'t delete system templates marked with a gear icon.', NEWSMAN) : '';
				$this->respond(true, sprintf( _n("You have successfully deleted %d template.", "You have successfully deleted %d templates.", $r), $r ).$note);
			}
		}

		public function ajDeleteEmails() {

			$ids = $this->param('ids');
			$all = ( $this->param('all', '0') === '1' );

			if ( $all ) {
				$r = newsmanEmail::removeAll();
			} else {
				$ids = preg_split('/[\s*,]+/', $ids);

				$s = '(';
				$del = '';
				foreach ( $ids as $id ) {
					$s .= $del.$id;
					$del = ',';
				}
				$s .= ')';

				$r = newsmanEmail::removeAll('status <> "inprogress" AND `id` in '.$s);
			}

			if ( !$r ) {
				$this->respond(false, newsmanEmail::$lastError);				
			} else {
				$this->respond(true, __('success', NEWSMAN) );
			}
		}		

		public function ajEditStyle() {

			$tplId = $this->param('id');
			$selector = $this->param('selector');
			$name = $this->param('name');
			$value = $this->param('value');

			$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

			if ( !$tpl ) {
				$this->errTemplateNotFound($tplId);
				return;
			}

			$rx = '/('.preg_quote($selector).'\s*\{[\s\S]*?\/\*\@editable\*\/'.preg_quote($name).':)([^;]*)(;[\s\S]*?\})/i';

			$tpl->html = preg_replace($rx, '${1}'.$value.'${3}', $tpl->html);

			$res = $tpl->save();

			if ( $res === false ) {
				$this->errCantWriteTemplate($tplId);
				return;
			}

			$this->respond(true, __('Saved', NEWSMAN) );
		}

		public function ajEditEmailStyle() {

			$id = $this->param('id');
			$selector = $this->param('selector');
			$name = $this->param('name');
			$value = $this->param('value');

			$eml = newsmanEmail::findOne('id = %d', array($id));

			if ( !$eml ) {
				$this->errEmailNotFound($id);
				return;
			}

			$rx = '/('.preg_quote($selector).'\s*\{[\s\S]*?\/\*\@editable\*\/'.preg_quote($name).':)([^;]*)(;[\s\S]*?\})/i';

			$u = newsmanUtils::getInstance();

			$eml->html = preg_replace($rx, '${1}'.$value.'${3}', $eml->html);

			$res = $eml->save();

			if ( $res === false ) {
				$this->errCantWriteEmail($id);
				return;
			}

			$this->respond(true, __('Saved',NEWSMAN));
		}		


		public function ajScheduleEmail() {
			$id = $this->param('id');
			$send = $this->param('send');
			$ts = $this->param('ts', 0);

			if ( in_array($send, array( 'now', 'schedule' ) ) ) {

				$eml = newsmanEmail::findOne('id = %d', array($id));

				if ( $eml ) {

					$u = newsmanUtils::getInstance();

					if ( $send == 'schedule' ) {
						$eml->schedule = intval($ts);
						$eml->status = 'scheduled';
					} else {
						$eml->status = 'pending';
					}
					
					$eml->save();

					$g = newsman::getInstance();
					$g->mailman();
					
					$this->respond(true, __('Your email is successfully scheduled.', NEWSMAN), array(
						'redirect' => get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox'
					));					
				} else {
					$this->errEmailNotFound($id);
				}
			} else {
				$this->respond(false, sprintf(__('Unrecognized "send" parameter - %s', NEWSMAN), $send) );
			}
		}

		private function unsubscribeFromList($tbl, $emailsList) {
			global $wpdb;			
			$c = 0;

			if ( preg_match_all('/\b[a-z0-9]+(?:[-\._]?[a-z0-9]+)*@(?:[a-z0-9]+(?:-?[a-z0-9]+)*\.)+[a-z]+\b/i', $emailsList, $matches) ) {

				$set = '(';
				$del = '';

				foreach ($matches[0] as $email) {
					$email = strtolower($email);
					$set .= $del.'"'.mysql_real_escape_string($email).'"';
					$del = ', ';
					$c += 1;
				}		

				$set .= ')';
			}

			$sql = "UPDATE $tbl SET status = ".NEWSMAN_SS_UNSUBSCRIBED." where email in $set";

			return $wpdb->query($sql) == false;
		}

		public function ajBulkUnsubscribe() {

			global $wpdb;

			$emails	= $this->param('emails');
			$allLists = ($this->param('allLists', '0') === '1') ? true : false ;

			if ( $allLists ) {
				$lists = newsmanList::findAll();

				foreach ($lists as $list) {
					$this->unsubscribeFromList($list->tblSubscribers, $emails);
				}

			} else {
				$listId = $this->param('listId', '1');
				$list = newsmanList::findOne('id = %d', array($listId));
				if ( !$list ) {
					$this->errListNotFound($listId);
				} else {
					$this->unsubscribeFromList($list->tblSubscribers, $emails);
				}
			}
 		
			$this->respond(true, __('Emails were successfully unsubscribed.', NEWSMAN));
		}


		/**
		 * Subscribers import
		 */

		private function getUploadedFiles() {
			$files = array();

			$n = newsman::getInstance();
			$upath = $n->ensureUploadDir();

			if ( $handle = opendir($upath) ) {

			    /* This is the correct way to loop over the directory. */
			    while (false !== ($entry = readdir($handle))) {
			    	if ( $entry !== '.' && $entry !== '..' && $entry[0] !== '.' ) {
			    		$files[] = $entry;	
			    	}			    	
			    }

			    closedir($handle);
			}

			return $files;
		}

		private function parseCSVfile($filePath, $delimiter) {
			$csvArr = array();
			if (($handle = @fopen($filePath, "r")) !== FALSE) {
				while (is_array($data = @fgetcsv($handle, 1000, $delimiter)) /*!== FALSE */) {
					$csvArr[] = $data;
				}
				fclose($handle);
			}
			return $csvArr;
		}

		private function importSubscriber($list, $fields, $row) {
			$form = array();

			$s = $list->newSub();

			foreach ($fields as $col => $name) {
				$form[$name] = $row[ intval($col) ];	
			}

			$s->fill($form);
			$s->confirm();
			
			return $s->save();
		}

		public function importFile($importParams) {
			global $wpdb;
			/*
				delimiter: ","
				fields: Object
					0: "email"
				fileName: "with-quoted-strings.txt"
				listId: "1"
				skipFirstRow: true
			*/

			if ( !isset($importParams['listId']) || trim($importParams['listId'] == '') ) {
				$importParams['listId'] = '1';
			} 

			$n = newsman::getInstance();

			$filePath = $n->ensureUploadDir().DIRECTORY_SEPARATOR.$importParams['fileName'];

			$imported = 0;

			$list = newsmanList::findOne('id = %d', array($importParams['listId']));
			if ( !$list ) {				
				wp_die( sprintf(__('List with id "%s" is not found.', NEWSMAN), $listId) );
			}

			//$csv = $this->parseCSVfile($filePath, $importParams['delimiter']);
			$delimiter = $importParams['delimiter'];

			$fields = $importParams['fields'];

			$c = 0;
			$skipFirst = ($importParams['skipFirstRow'] === true);

			// start transaction here
			$wpdb->query('START TRANSACTION');

			if (($handle = @fopen($filePath, "r")) !== FALSE) {
				while (is_array($data = @fgetcsv($handle, 1000, $delimiter)) /*!== FALSE */) {
					$c += 1;
					if ( $c === 1 && $skipFirst ) {
						continue;
					}						
					if ( $this->importSubscriber($list, $fields, $data) !== false ) {
						$imported += 1;
					}
				}
				fclose($handle);
			}			
			$wpdb->query('COMMIT');

			unlink($filePath);

			//if ( preg_match_all('/[a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?/ig', $content, $matches) ) {
			// if ( preg_match_all('/\b[a-z0-9]+(?:[-\._]?[a-z0-9]+)*@(?:[a-z0-9]+(?:-?[a-z0-9]+)*\.)+[a-z]+\b/i', $content, $matches) ) {

			// }

			return $imported;
		}

		public function ajGetUploadedFiles() {
			$this->respond(true, __('success', NEWSMAN), array(
				'files' => $this->getUploadedFiles()
			));
		}

		public function ajImportFiles() {
			$imported = 0;

			$files = $this->getUploadedFiles();

			$importParams = $this->param('params');
			$importParams = json_decode($importParams, true);
			$imported += $this->importFile($importParams);

			$msg = sprintf( _n( 'Imported %d subscriber. Make sure you send him confirmation email.', 'Imported %d subscribers. Make sure you send them confirmation email.', $imported, NEWSMAN), $imported);

			$this->respond(true, $msg, array(
				'files' => $this->getUploadedFiles()
			));			
		}

		public function ajGetCSVFields() {
			$filename	= $this->param('filename');

			$n = newsman::getInstance();			
			$path = $n->ensureUploadDir().DIRECTORY_SEPARATOR.$filename;

			$maxLines = 3; 
			$count = 0;
			$lines = array();

			$handle = @fopen($path, "r");
			if ( $handle ) {
				while (($buffer = fgets($handle, 4096)) !== false) {
					$lines[] = $buffer;
					$count += 1;
					if ( $count >= $maxLines ) {
						break;
					}
				}
				fclose($handle);
			}
			$this->respond(true, __('success', NEWSMAN), array(
				'filename' => $filename,
				'header' => $lines
			));
		}		


		public function ajGetFormFields() {
			$id = $this->param('id');

			$form = new newsmanForm($id);

			if ( !$form ) {
				$this->errListNotFound($id);
			} else {
				$fields = $form->getFields();

				$fields['ip'] = 'IP Address';
				
				$this->respond(true, __('success', NEWSMAN), array(
					'fields' => $fields
				));
			}
		}

		//private function 

		public function ajStopSending() {
			$u = newsmanUtils::getInstance();

			$ids = $this->param('ids');
			$set = $u->jsArrToMySQLSet($ids);	
			
			$emails = newsmanEmail::findAll('id in '.$set);

			foreach ($emails as $email) {
				newsmanWorker::stop($email->workerPid);
				$email->status = 'stopped';
				$email->save();								
			}
			$this->respond(true, 'Success');
		}
			
		public function ajResumeSending() {
			$u = newsmanUtils::getInstance();

			$ids = $this->param('ids');
			$set = $u->jsArrToMySQLSet($ids);	
			
			$emails = newsmanEmail::findAll('(status = "stopped" OR status = "error") AND id in '.$set);

			foreach ($emails as $email) {
				$email->status = 'pending';
				$email->save();
			}

			$g = newsman::getInstance();
			$g->mailman();
			
			$this->respond(true, __('Selected emails were successfully resumed', NEWSMAN));
		}

		/**
		 * Gets a particle(post block tempalte or posts divider) of an
		 * entity ( email or template )
		 */
		// private function getEntityParticle($entityId, $entType, $particleName) {			

		// 	$u = newsmanUtils::getInstance();

		// 	$ent = $this->getEntityById($entityId, $entType);

		// 	$particles = $ent->particles;

		// 	$content = $u->getSectionContent($particles, 'gsedit', $particleName);

		// 	return $content;
		// }

		private function getEntityById($entityId, $entType) {
			$ent = null;
			switch ( $entType ) {
				case 'email':
					$ent = newsmanEmail::findOne('id = %d', array( $entityId ) );
					break;
				case 'template':
					$ent = newsmanEmailTemplate::findOne('id = %d', array( $entityId ) );
					break;
			}
			return $ent;
		}

		/**
		 * Compiles posts block for digest template
		 * &pids - post ids to include in format "1,2,3,4"
		 * &entType - entity type. "email" or "template"
		 * &entity - entity id
		 */
		public function ajCompilePostsBlock() {
			global $post;

			global $newsman_post_tpl;
			global $newsman_loop_post;
			global $newsman_loop_post_nr;			

			$u = newsmanUtils::getInstance();

			$pids = $this->param('pids');
			$pids = preg_split('/[\s*,]+/', $pids);

			$entityId = $this->param('entity');
			$entType = $this->param('entType');

			if ( !in_array($entType, array('email', 'template')) ) {
				$this->respond(false, sprintf(__('"entType" parameter value "%s" should be "email" or "template".', NEWSMAN), $entType) );
			}

			$ent = $this->getEntityById($entityId, $entType);

			$postBlockTpl = $u->getSectionContent($ent->particles, 'gsedit', 'post_block');
			$postDividerTpl = $u->getSectionContent($ent->particles, 'gsedit', 'post_divider');

			$sc = $u->findShortCode($postBlockTpl, 'newsman', array( 'post' => array('fancy_excerpt', 'post_excerpt', 'post_content') ));

			$postType = 'post';

			if ( $sc ) {
				$ptype = $sc->get('type');
				if ( $ptype ) {
					$postType = $ptype;
				}
			}	

			$args = array(
				'post__in' => $pids,
				'post_type' => $postType
			);

			$output = '';

			$query = new WP_Query($args);

			$first = true;

			while ( $query->have_posts() ) {
				$query->the_post();

				$newsman_loop_post = $post;

				if ( $first ) {
					$first = false;
				} else {
					$output .= do_shortcode($postDividerTpl);	
				}

				$output .= do_shortcode(preg_replace('/(gsedit=")([^"]+)(")/i', '$1$2_'.$post->ID.'$3', $postBlockTpl));
			}

			$ent->html = $u->replaceSectionContent($ent->html, 'posts', $output, 'gsspecial');

			$ent->save();


			$this->respond(true, __('Posts block successfully compiled', NEWSMAN), array('content' => $output ));
		}

		public function ajSetPostTemplateType() {
			$u = newsmanUtils::getInstance();
			
			$entityId = $this->param('entity');
			$entType = $this->param('entType');
			$newTplType = $this->param('postTemplateType');
			$postType = $this->param('postType', 'post');

			if ( !in_array($newTplType, array('post_content', 'post_excerpt', 'fancy_excerpt')) ) {
				$this->respond(false, sprintf(__('"postTemplateType" parameter value "%s" should be "post_content", "post_excerpt" or "fancy_excerpt".', NEWSMAN), $newTplType) );
			}

			if ( !in_array($entType, array('email', 'template')) ) {
				$this->respond(false, sprintf(__('"entType" parameter value "%s" should be "email" or "template".', NEWSMAN), $entType) );
			}

			$ent = $this->getEntityById($entityId, $entType);
			$postBlockTpl = $u->getSectionContent($ent->particles, 'gsedit', 'post_block');

			//$postBlockTpl = preg_replace('/(\[newsman[^\[\]]+post=(?:\'|"))(fancy_excerpt|post_excerpt|post_content)((?:\'|")[^\[\]]+\])/', '$1'.$newTplType.'$3', $postBlockTpl);
			$postBlockTpl = $u->modifyShortCode($postBlockTpl, 'newsman', array( 'post' => array('fancy_excerpt', 'post_excerpt', 'post_content') ), array( 'post'=> $newTplType, 'type'=>$postType ));	

			$ent->particles = $u->replaceSectionContent($ent->particles, 'post_block', $postBlockTpl);
			$ent->save();
			$this->respond(true, __('Posts block successfully updated', NEWSMAN));
		}

		public function ajGetEntityParticle() {
			$u = newsmanUtils::getInstance();

			$entityId = $this->param('entity');
			$entType = $this->param('entType');
			$name = $this->param('name');

			$ent = $this->getEntityById($entityId, $entType);

			$tpl = $u->getSectionContent($ent->particles, 'gsedit', $name);

			$this->respond(true, __('success', NEWSMAN), array( 'particle' => $tpl ));
		}

		public function ajSetEntityParticle() {
			$u = newsmanUtils::getInstance();
			
			$entityId = $this->param('entity');
			$entType = $this->param('entType');
			$name = $this->param('name');
			$content = $this->param('content');

			$ent = $this->getEntityById($entityId, $entType);

			$ent->particles = $u->replaceSectionContent($ent->particles, $name, $content);

			$ent->save();

			$this->respond(true, __('Saved', NEWSMAN));
		}		

		public function ajResendConfirmation() {
			global $newsman_current_subscriber;		
			global $newsman_current_email;
			global $newsman_current_list;

			$all = ( $this->param('all', '0') === '1' );

			$u = newsmanUtils::getInstance();

			$ids = $this->param('ids'); // js array or comma sep. enumeration
			
			$set = $u->jsArrToMySQLSet($ids);

			$listId = $this->param('listId', '1');
			$list = newsmanList::findOne('id = %d', array($listId));
			if ( !$list ) {
				$this->errListNotFound($listId);
			}

			$newsman_current_list = $list;

			if ( $all ) {
				$subs = $list->findAllSubscribers();
			} else {
				$subs = $list->findAllSubscribers("id in ".$set);
			}

			foreach ($subs as $s) {
				$newsman_current_subscriber = $s->toJSON();

				$newsman = newsman::getInstance();

				$newsman->sendEmail('confirmation');
			}

			// ajax responce
			$this->respond(true, __('success', NEWSMAN) );
		}

		public function ajGetListSettings() {
			$id = $this->param('id');

			$list = newsmanList::findOne('`id` = %d', array($id));
			if ( !$list ) {
				$this->errListNotFound($id);
			} else {
				$this->respond(true, __('success', NEWSMAN), array(
					'list' => $list->toJSON()
				));
			}
		}

		public function ajSetListSettings() {
			$id = $this->param('id');

			// $footer = $this->param('footer');
			// $header = $this->param('header');
			// $title = $this->param('title');

			$json = $this->param('json');
			$name = $this->param('name');

			$list = newsmanList::findOne('id = %d', array($id));

			if ( !$list ) {
				$this->errListNotFound($id);
			} else {
				$list->id = $id;

				$list->name = $name;
				// $list->title = $title;
				// $list->footer = $footer;
				// $list->header = $header;
				$list->form = $json;

				do_action('newsman_set_ext_from_options', $this, $list);

				$list->save();

				$this->respond(true, __('Saved', NEWSMAN));
			}
		}

		public function ajCreateList() {
			$name = $this->param('name');
			$name = trim($name);

			$lst = newsmanList::findOne('name = %s', array($name));

			if ( $lst ) {
				$this->respond(false, sprintf(__('List with the name "%s" already exists.', NEWSMAN), $name) );
			} else {
				$list = new newsmanList($name);
				$list->save();
				$this->respond(true, __('Created', NEWSMAN), array(
					'name' => $list->name,
					'id' => $list->id
				));
			}
		}

		public function ajSendTestEmail() {

			global $newsman_current_list;
			global $newsman_current_subscriber;

			$u = newsmanUtils::getInstance();

			$toEmail = $this->param('toEmail');
			$entityId = $this->param('entity');
			$entType = $this->param('entType');

			$list = newsmanList::findOne('name = %s', array('default'));
			$s = $list->newSub();

			$newsman_current_list = $list;
			$newsman_current_subscriber = $s;

			$s->email = $toEmail;
			$s->firstName = 'John';
			$s->lastName = 'Doe';

			$data = $s->toJSON();

			$ent = $this->getEntityById($entityId, $entType);

			if ( $ent instanceof newsmanEmail ) {
				$email = $ent;
			} else {
				$email = new newsmanEmail();

				$email->subject = $ent->subject;
				$email->p_html = $ent->p_html;
				$email->plain = $ent->plain;
			}


			$msg = $email->renderMessage($data);
			$msg['html'] = $u->expandAssetsURLs($msg['html'], $ent->assets);

			$r = $u->mail($msg, array( 'to' => $toEmail) );

			if ( $r === true ) {
				$this->respond(true, sprintf(__('Test email was sent to %s.', NEWSMAN), $toEmail) );
			} else {
				$this->respond(false, $r);
			}			
		}



		/**
		 * External app API
		 * Cannot be changed without public notification
		 */

		public function ajExtTestConnection() {
			$this->respond(true, 'NEWSMAN_TEST_OK');
		}

		public function ajExtGetListExportLink() {
			$listId = $this->param('listId', '1');
			$list = newsmanList::findOne('id = %d', array($listId));
			if ( !$list ) {
				$this->errListNotFound($listId);
			}
			$this->respond(true, __('success', NEWSMAN) );
		}

		public function ajExtGetLists() {

			$lists = newsmanList::findAll();
			$listsNames = array();

			foreach ($lists as $list) {
				$listsNames[] = array(
					'id' => $list->id,
					'name' => $list->name,
					'stats' => $list->getStats()
				);
			}

			$this->respond(true, 'lists', array(
				'lists' => $listsNames
			));
		}		

		public function  ajRunUninstall() {
			$g = newsman::getInstance();
			$g->uninstall();
			deactivate_plugins(NEWSMAN_PLUGIN_PATHNAME);
			if ( defined('NEWSMANP_PLUGIN_PATHNAME') ) {
				deactivate_plugins(NEWSMANP_PLUGIN_PATHNAME);	
			}			
			$this->respond(true, 'Successfully uninstalled', array(
				'redirect' => get_bloginfo('wpurl').'/wp-admin/plugins.php'
			));			
		}

		public function ajRemoveImportedFile() {
			$fileName = $this->param('fileName');

			$n = newsman::getInstance();

			$filePath = $n->ensureUploadDir().DIRECTORY_SEPARATOR.$fileName;

			if ( file_exists($filePath) ) {
				unlink($filePath);
				$this->respond(true, 'Successfully deleted');
			} else {
				$this->respond(false, 'Error: File not found');
			}
		}

		public function ajGetErrorLog() {
			$res = array(
				'sent' => 0,
				'recipients' => 0
			);

			$emailId = $this->param('emailId');
			$sl = newsmanSentlog::getInstance();

			$errors = $sl->getErrors($emailId);
			$res['errors'] = $errors;

			$email = newsmanEmail::findOne('id = %d', array($emailId));
			if ( $email ) {
				 $res['sent'] = $email->sent;
				 $res['recipients'] = $email->recipients;
				 $res['msg'] = $email->msg;
			}

			$this->respond(true, 'Errors log', $res);
		}

		public function ajGetSystemInfo() {
			$u = newsmanUtils::getInstance();
			$this->respond(true, $u->getSystemInfo());
		}

		public function ajSwitchLocale() {
			$u = newsmanUtils::getInstance();
			$n = newsman::getInstance();

			$switchLocale = $this->param('switch-locale', false);
			$swtichLocalePages = $this->param('swtich-locale-pages', false);
			$swtichLocaleTemplates = $this->param('swtich-locale-templates', false);

			switch ( $switchLocale ) {
				case 'replace-all':
					$u->installActionPages($n->wplang, 'REPLACE');
					$u->installSystemEmailTemplates($n->wplang, 'REPLACE');

					$n->options->set('lang', $n->wplang);
					$n->lang = $n->wplang;
					break;

				case 'just-update-locale':
					$n->options->set('lang', $n->wplang);
					$n->lang = $n->wplang;
					break;

				case 'custom':

					if ( $swtichLocalePages ) {
						$u->installActionPages($n->wplang, 'REPLACE');
					}

					if ( $swtichLocaleTemplates ) {
						$u->installSystemEmailTemplates($n->wplang, 'REPLACE');
					}

					$n->options->set('lang', $n->wplang);
					$n->lang = $n->wplang;
					break;

				case 'nothing':
					$n->options->set('hideLangNotice', $n->wplang);
					break;
			}

			$this->respond(true, 'success');			
		}

		public function ajDeleteForms() {
			$u = newsmanUtils::getInstance();

			$ids = $this->param('ids');
			$set = $u->jsArrToMySQLSet($ids);

			$r = newsmanList::removeAll('`id` in '.$set);

			if ( !$r ) {
				$this->respond(false, newsmanList::$lastError);
			} else {
				$this->respond(true, __('success', NEWSMAN) );
			}			
		}

		public function ajSetEmailAnalytics() {
			$emlId   = $this->param('id');
			$type = $this->param('type', '');
			$camp = $this->param('campaign', '');

			$email = newsmanEmail::findOne('id = %d', array( $emlId ) );			

			if ( $email ) {
				$email->analytics = $type;
				$email->campName = $camp;
				$email->save();
				$this->respond(true, 'success');
			} else {
				$this->errEmailNotFound($emlId);				
			}
		}

	}
	
?>