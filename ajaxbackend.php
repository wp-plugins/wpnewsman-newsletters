<?php

ignore_user_abort(true);
set_time_limit(0);

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

		$this->u = newsmanUtils::getInstance();
		$this->n = newsman::getInstance();
		$this->o = newsmanOptions::getInstance();
	}

	public function respond($state, $msg, $params = array(), $stop = true) {
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
		
		if ( !$state ) {
			header("HTTP/1.0 400 Bad Request");
		}

		$content = json_encode( $this->u->utf8_encode_all($msg) );

		while(@ob_end_clean());
		
		header("Connection: close");
		ignore_user_abort(); // optional
		ob_start();
		echo $content;
		$size = ob_get_length();
		header("Content-type: application/json");
		header("Content-Length: $size");
		ob_end_flush(); // Strange behaviour, will not work
		flush();            // Unless both are called !

		if ( $stop ) {
			if ($db) $db->close();  
			exit();
		}			   
	}

	public function param($param) {

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
			return is_string( $_REQUEST[$param] ) ? $this->u->remslashes( $_REQUEST[$param] ) : $_REQUEST[$param];
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

	public function ajTestMailer() {

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
		
		if ($res === true) {
		    $this->respond(true, sprintf(__('Test email was sent to %s.', NEWSMAN), $email) );
		} else {
		    $this->respond(false, $res);
		}
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
		global $wpdb;
		$u = newsmanUtils::getInstance();

		$ids = $this->param('ids');
		$all = ( $this->param('all', '0') === '1' );
		$listId = $this->param('listId', '1');
		$type = $this->param('type');
		$q = $this->param('q');

		$list = newsmanList::findOne('id = %d', array($listId));

		if ( $all ) {
			if ( $q ) {
				$q = $wpdb->prepare('email regexp %s', array($u->preg_quote($q)));
				$r = $list->deleteAll($type, $q);
			} else {
				$r = $list->deleteAll($type);	
			}				
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

	public function ajCountSubscribers() {
		$u = newsmanUtils::getInstance();

		$listId = $this->param('listId', '1');
		$type = $this->param('type');
		$q = $this->param('q');

		$list = newsmanList::findOne('id = %d', array($listId));

		if ( $q ) {
			$c = $list->countSubs($type, 'email regexp %s', array($u->preg_quote($q)));	
		} else {
			$c = $list->countSubs($type);
		}			

		if ( !is_numeric($c) ) {
			$this->respond(false, 'Some error occured');
		} else {
			$this->respond(true, 'success', array( 'count' => $c ));
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
		

		$opts = $this->param('options', false);

		if ( !$opts ) {
			$this->respond(false, __('Error: "options" request parameter was empty or not defined.', NEWSMAN));
		} else {
			$opts = json_decode($opts, true);
			$oldPBM = $o->get('pokebackMode');
			$o->load($opts, 'PRESERVE_OLD_VALUES');

			if ( $oldPBM !== $o->get('pokebackMode') ) {
				do_action('newsman_events_mode_changed');
			}

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
		$opts = array(
			'fields' => array(
				'id', 'subject', 'to', 'created', 'schedule', 'status',
				'msg', 'recipients', 'sent', 'ucode', 'opens', 'clicks',
				'unsubscribes'
			)
		);

		if ( $q ) {
			$where = 'subject regexp %s OR html regexp %s OR plain regexp %s';
			$q = $this->u->preg_quote($q);

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
		$emails = newsmanEmail::findAllPaged($pg, $ipp, $whereFind, $args, $opts);
		
		$res['count'] = array(
			'pending' => 0,
			'sent' => 0,
			'draft' => 0,
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
			//$eml = $email;
			$eml['publicURL'] = $email->getPublishURL();
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

		$cats		= $this->param('cats', '[]');
		$auths		= $this->param('auths', false);

		$postType 	= $this->param('postType', 'post');

		$ipp 		= $this->param('ipp', 15);
		$page 		= $this->param('page', 1);

		$cats 		= json_decode('['.$cats.']');

		$includePrivate = $this->param('includePrivate', 0);
		$includePrivate = intval($includePrivate) ? true : false;			
		
		$i = 1;
		$posts = array();
		
		$df = get_option('date_format');
		$tf = get_option('time_format');

		$selectRange = $this->param('select', false);

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
	    	
			* hour= - hour (from 0 to 23)
			* minute= - minute (from 0 to 60)
			* second= - second (0 to 60)
			* day= - day of the month (from 1 to 31)
			* monthnum= - month number (from 1 to 12)
			* year= - 4 digit year (e.g. 2009)
			* w= - week of the year (from 0 to 53) and uses the MySQL WEEK command Mode=1.     	
	    */

		//$newsman_ajresponse['limit'] = $limit;

		$args = array(		
			'post_type' => $postType,
			'post_status' => array('publish'),
			'posts_per_page' => $ipp,
			'paged' => $page,
			'orderby' => 'date',
			'order' => 'DESC',
			'suppress_filters' => true
		);

		$ts = $this->u->current_time('timestamp');

		$today = getdate( $ts );
		if ( $selectRange ) {
			$args['posts_per_page'] = -1;
			switch ( $selectRange ) {
				case 'day':
					$args['year']		= $today["year"];
					$args['monthnum']	= $today["mon"];
					$args['day']		= $today["mday"];
					break;

				case 'week':
					$args['year']	= date('Y', $ts);
					$args['w'] 		= date('W', $ts);
					break;

				case 'month':
					$args['year']		= $today["year"];
					$args['monthnum']	= $today["mon"];
					break;
			}
		}

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
			$query = new WP_Query('posts_per_page='.$ipp.'&paged='.$page.'&post_type=post&s='.$search.'&order=DESC&orderby=date&suppress_filters=1');
		} else {
			$query = new WP_Query($args);
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$pt = mysql2date('U', $post->post_date); 

			$content = $this->u->cutScripts($post->post_content);

			$content = $this->u->fancyExcerpt($content, 25);

			$posts[] =	array(
				'id' => $post->ID,
				'date' => date($df.' '.$tf, $pt),
				'title' => $post->post_title,
				'description' => $content,
				'link' => get_permalink($post->ID),
				'number' => $i
			);
			$i++;
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
				'stats' => $list->getStats()
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

		if ( $emlId ) {
			$email = newsmanEmail::findOne('id = %d', array( $emlId ) );
		}
	
		if ( !$email ) {
			$email = new newsmanEmail();	
		}			

		$email->remeberToField();
		$email->to = json_decode($to);

		$email->subject = $subj;
		$email->html = $contentHtml;
		$email->plain = $contentPlain;

		$email->schedule = intval($ts);

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

		$email = new newsmanEmail();

		$email->remeberToField();
		$email->to = json_decode($to);

		$email->subject = $subj;
		$email->html = $contentHtml;
		$email->plain = $contentPlain;

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

		$selector = 'system = %d';
		$args[] = 0;

		$selector .= ' ORDER BY id DESC';			

		$tpls = newsmanEmailTemplate::findAllPaged($pg, $ipp, $selector, $args, array(
			'fields' => array('id', 'name', 'assigned_list', 'system', 'system_type')
		));
		
		$res['count'] = newsmanEmailTemplate::count($selector, $args);
		$res['rows'] = array();

		$listIds = array();
		$lists = array();

		foreach ($tpls as $tpl) {
			$jsonTpl = $tpl->toJSON();
			if ( $tpl->assigned_list > 0 ) {
				if ( !in_array($tpl->assigned_list, $listIds) ) {
					$list = newsmanList::findOne('id = %d', array($tpl->assigned_list));
					if ( $list ) {
						$lists[$tpl->assigned_list] = $list;
					}
				} else {
					$list = $listIds[$tpl->assigned_list];
				}

				$jsonTpl['assigned_list_name'] = $list->name;
			}
			$res['rows'][] = $jsonTpl;
		}

		$this->respond(true, 'success', $res);
	}

	public function ajGetSystemTemplates() {
		global $wpdb;

		$res = array(
		);

		// getting default templates

		$tpls = newsmanEmailTemplate::findAll('`system` = 1 AND `assigned_list` = 0', array(), array(
			'fields' => array('id', 'name', 'assigned_list', 'system', 'system_type')
		));

		$lst = array(
			'default' => true,
			'templates' => array()
		);
		if ( $tpls ) {
			foreach ($tpls as $tpl) {
				$lst['templates'][] = $tpl->toJSON();
			}
		}
		$res[] = $lst;	

		// getting other list templates

		$lists = newsmanList::findAll();

		foreach ($lists as $list) {
			$lst = array(
				'listId' 	=> $list->id,
				'listName' 	=> $list->name,
				'templates' => array()
			);
			$tpls = newsmanEmailTemplate::findAll('`system` = 1 AND `assigned_list` = %d', array($list->id));
			if ( $tpls ) {
				foreach ($tpls as $tpl) {
					$lst['templates'][] = $tpl->toJSON();
				}
			}
			$res[] = $lst;
		}		
		
		$this->respond(true, 'success', array('lists' => $res));
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

		//$eml->remeberToField();
		if ( $key === 'to' ) {
			$value = json_decode($value);
		}
		$eml->$key = $value;
		$eml->save();
		$this->respond(true, 'success');
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


	/**
	 * Replaces the value of the section attribute. for ex. gesdit="html"
	 * to gsedit="img" where gsedit is "section" and img is new_type
	 */
	public function ajChangeTemplateSection() {
		
		$tplId = $this->param('id');
		$section = $this->param('section');
		$newType = $this->param('new_type');

		$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

		if ( !$tpl ) {
			$this->errTemplateNotFound($tplId);
			return;
		}

		$tpl->html = $this->u->changeSectionType($tpl->html, $section, $newType);

		$res = $tpl->save();

		if ( $res === false ) {
			$this->errCantWriteTemplate($tplId);
			return;
		}			

		$this->respond(true, __('Saved', NEWSMAN) );
	}

	public function ajChangeEmailSection() {

		$id = $this->param('id');
		$section = $this->param('section');
		$newType = $this->param('new_type');

		$eml = newsmanEmail::findOne('id = %d', array($id));


		if ( !$eml ) {
			$this->errEmailNotFound($id);
			return;
		}

		$eml->html = $this->u->changeSectionType($eml->html, $section, $newType);

		$res = $eml->save();

		if ( $res === false ) {
			$this->errCantWriteEmail($id);
			return;
		}			

		$this->respond(true, __('Saved', NEWSMAN) );	
	}

	public function ajEditTemplate() {
		$tplId = $this->param('id');
		$section = $this->param('section');
		$newContent = $this->param('new_content');

		$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

		if ( !$tpl ) {
			$this->errEmailNotFound($id);
			return;
		}

		$tpl->html = $this->u->replaceSectionContent($tpl->html, $section, $newContent);

		$res = $tpl->save();

		if ( $res === false ) {
			$this->errCantWriteEmail($tplId);
			return;
		}			

		$this->respond(true, __('Saved', NEWSMAN) );
	}

	public function ajSetTemplate() {
		$tplId = $this->param('id');
		$html = $this->param('html');
		$plain = $this->param('plain');
		$name = $this->param('name');
		$subj = $this->param('subj');

		$tpl = newsmanEmailTemplate::findOne('id = %d', array($tplId));

		if ( !$tpl ) {
			$this->errTemplateNotFound($tplId);
			return;
		}

		$tpl->html = $html;
		$tpl->plain = $plain;
		$tpl->name = $name;
		$tpl->subject = $subj;

		$res = $tpl->save();

		if ( $res === false ) {
			$this->errCantWriteTemplate($tplId);
			return;
		}

		$this->respond(true, __('Saved', NEWSMAN) );
	}

	public function ajSetEmail() {
		$id = $this->param('id');
		$html = $this->param('html');
		$plain = $this->param('plain');
		$subj = $this->param('subj');
		$emailAnalytics = $this->param('emailAnalytics') == '1';


		$eml = newsmanEmail::findOne('id = %d', array($id));

		if ( !$eml ) {
			$this->errEmailNotFound($id);
			return;
		}

		$eml->html = $html;
		$eml->plain = $plain;
		$eml->subject = $subj;
		$eml->emailAnalytics = $emailAnalytics;

		$res = $eml->save();

		if ( $res === false ) {
			$this->errCantWriteEmail($id);
			return;
		}

		$this->respond(true, __('Saved', NEWSMAN) );			
	}

	public function ajHasSharedAssets() {
		$ids = $this->param('ids');
		$set = $this->u->jsArrToMySQLSet($ids);

		$s = 0;

		$tpls = newsmanEmailTemplate::findAll('`id` in '.$set.' and `system` = 0');

		foreach ($tpls as $tpl) {
			if ( $this->u->hasSharedAssets($tpl->assetsPath) ) {
				$s += 1;	
			}				
		}

		$this->respond(true, 'Success', array('shared' => $s ));
	}

	public function ajDeleteEmailTemplates() {
		$ids = $this->param('ids');
		$deleteSharedAssets = $this->param('delSharedAssets', '0') === '1';
		$set = $this->u->jsArrToMySQLSet($ids);

		$r = 0;

		$tpls = newsmanEmailTemplate::findAll('`id` in '.$set.' and ( `system` = 0 OR `assigned_list` = 0 )');

		foreach ($tpls as $tpl) {
			$this->u->tryRemoveTemplateAssets($tpl->assetsPath, $deleteSharedAssets);
			$tpl->remove();
			$r += 1;
		}

		$this->respond(true, sprintf( _n("You have successfully deleted %d template.", "You have successfully deleted %d templates.", $r), $r ));
	}

	public function ajDeleteEmails() {
		$ids = $this->param('ids');
		$all = ( $this->param('all', '0') === '1' );
		$set = $this->u->jsArrToMySQLSet($ids);

		$r = 0;

		if ( $all ) {
			$emails = newsmanEmail::findAll('`status` != "inprogress"');
		} else {
			$emails = newsmanEmail::findAll('`id` in '.$set.' and `status` != "inprogress"');
		}

		$this->u->log('[ajDeleteEmails]: found %d emails', count($emails));

		foreach ($emails as $eml) {
			try {
				$this->u->tryRemoveTemplateAssets($eml->assetsPath);	
			} catch ( Exception $e ) {
				$this->u->log('[ajDeleteEmails] cannot email assets for email with id %s - %s', $eml->id, $e->getMessage());
			}
			
			$eml->remove();
			$r += 1;
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

		$rx = '/('.$this->u->preg_quote($selector).'\s*\{[\s\S]*?\/\*\@editable\*\/'.$this->u->preg_quote($name).':)([^;]*)(;[\s\S]*?\})/i';

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

		$rx = '/('.$this->u->preg_quote($selector).'\s*\{[\s\S]*?\/\*\@editable\*\/'.$this->u->preg_quote($name).':)([^;]*)(;[\s\S]*?\})/i';

		

		$eml->html = preg_replace($rx, '${1}'.$value.'${3}', $eml->html);

		$res = $eml->save();

		if ( $res === false ) {
			$this->errCantWriteEmail($id);
			return;
		}

		$this->respond(true, __('Saved',NEWSMAN));
	}		

	public function ajScheduleEmail() {
		$o = newsmanOptions::getInstance();
		$u = newsmanUtils::getInstance();

		$id = $this->param('id');
		$send = $this->param('send');
		$ts = $this->param('ts', 0);

		if ( in_array($send, array( 'now', 'schedule' ) ) ) {

			$eml = newsmanEmail::findOne('id = %d', array($id));

			if ( $eml ) {

				

				if ( $send == 'schedule' ) {
					$eml->schedule = intval($ts);
					$eml->status = 'scheduled';
					//*
					if ( $o->get('pokebackMode') ) {
						$u->log('[newsmanWorker::fork] scheduling email with PokeBack mode ts = %s', $eml->schedule);

						$workerURL = get_bloginfo('wpurl').'/wpnewsman-pokeback/run-scheduled/?'.http_build_query(array(
							'emlucode' => $eml->ucode
						));

						$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/schedule/?'.http_build_query(array(
							'key' => $o->get('pokebackKey'),
							'url' => $workerURL,
							'time' => $eml->schedule
						));

						$r = wp_remote_get(
							$pokebackSrvUrl,
							array(
								'timeout' => 0.01,
								'blocking' => false
							)
						);	
					}		
					//*/			
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

		if ( preg_match_all('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $emailsList, $matches) ) {

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

	private function getUploadedFiles($type = false, $fullPath = false) {
		$files = array();

		$n = newsman::getInstance();
		$upath = $n->ensureUploadDir($type);

		if ( $handle = opendir($upath) ) {

		    /* This is the correct way to loop over the directory. */
		    while (false !== ($entry = readdir($handle))) {
		    	if ( $entry !== '.' && $entry !== '..' && $entry[0] !== '.' ) {			    		
		    		$pathToEntry = $upath.DIRECTORY_SEPARATOR.$entry;
		    		if ( is_file($pathToEntry) ) {
		    			$files[] = $fullPath ? $pathToEntry : $entry;
		    		}
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

	private function importSubscriber($list, $fields, $status, $row) {
		$form = array();

		$s = $list->newSub();

		$badEmail = false;

		foreach ($fields as $col => $name) {
			$value = $row[ intval($col) ];

			if ( $name === 'email' && !$this->u->emailValid($value) ) {
				$badEmail = true;
			}

			$form[$name] = $value;
		}

		$s->fill($form);

		if ( $badEmail ) {
			$s->unsubscribe(__('Bad email address', NEWSMAN));
		} else if ( $status === 'unconfirmed' ) {
			$s->unconfirm();	
		} else {
			$s->confirm();
		}				
		
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

		$filePath = $n->ensureUploadDir('csv').DIRECTORY_SEPARATOR.$importParams['fileName'];

		$imported = 0;

		$list = newsmanList::findOne('id = %d', array($importParams['listId']));
		if ( !$list ) {				
			wp_die( sprintf(__('List with id "%s" is not found.', NEWSMAN), $listId) );
		}

		//$csv = $this->parseCSVfile($filePath, $importParams['delimiter']);
		$delimiter = $importParams['delimiter'];

		$st = $importParams['status'];

		$fields = $importParams['fields'];

		$c = 0;
		$skipFirst = ($importParams['skipFirstRow'] === true);

		// start transaction here
		$wpdb->query('START TRANSACTION');
		
		ini_set("auto_detect_line_endings", true);

		if (($handle = @fopen($filePath, "r")) !== FALSE) {
			while (is_array($data = @fgetcsv($handle, 1000, $delimiter)) /*!== FALSE */) {
				$c += 1;
				if ( $c === 1 && $skipFirst ) {
					continue;
				}

				//$this->u->log('[importingCSV] CSV row count %s, %s', count($data), print_r($data, true));
				
				if ( $this->importSubscriber($list, $fields, $st, $data) !== false ) {
					$imported += 1;
				}
			}
			fclose($handle);
		}			
		$wpdb->query('COMMIT');

		unlink($filePath);

		return $imported;
	}

	public function ajGetUploadedFiles() {
		$type = $this->param('type', 'csv');			
		$this->respond(true, __('success', NEWSMAN), array(
			'files' => $this->getUploadedFiles($type)
		));
	}

	public function ajImportFiles() {
		$type = 'csv';
		$imported = 0;

		$files = $this->getUploadedFiles($type);

		$importParams = $this->param('params');
		$importParams = json_decode($importParams, true);

		if ( !isset($importParams['fileName']) || !$importParams['fileName'] ) {
			$this->respond(false, __('Please select a file to import', NEWSMAN));
		}

		$imported += $this->importFile($importParams);

		$msg = sprintf( _n( 'Imported %d subscriber. Make sure you send him confirmation email.', 'Imported %d subscribers. Make sure you send them confirmation email.', $imported, NEWSMAN), $imported);

		$this->respond(true, $msg, array(
			'files' => $this->getUploadedFiles($type)
		));			
	}

	public function ajGetCSVFields() {
		ini_set("auto_detect_line_endings", true);
		$filename	= $this->param('filename');

		$n = newsman::getInstance();			
		$path = $n->ensureUploadDir('csv').DIRECTORY_SEPARATOR.$filename;

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

			$fields['ip'] = __('IP Address', NEWSMAN);
			
			$this->respond(true, __('success', NEWSMAN), array(
				'fields' => $fields
			));
		}
	}

	public function ajInstallTemplates() {
		$installed = 0;

		$files = $this->getUploadedFiles('template', true);
		if ( $files ) {
			foreach ($files as $file) {
				if ( $this->u->installTemplate($file) ) {
					$installed += 1;
				}
			}
		}
		$this->respond(true, sprintf( _n('Imported %d template.', 'Imported %d templates.', $installed, NEWSMAN), $installed) );
	}

	public function ajDuplicateTemplate() {
		$id = $this->param('id');

		$tpl = $this->u->duplicateTemplate($id);

		if ( $tpl ) {
			$this->respond(true, 'Success', array(
				'name' => $tpl->name,
				'id'   => $tpl->id	
			));
		} else {
			$this->errTemplateNotFound();
		}
	}

	public function ajStopWorker() {
		$id = $this->param('id');
		$w = new newsmanWorkerAvatar($id);
		$w->stop();
		$this->respond(true, 'Success', array());
	}

	public function ajStopSending() {
		$u = newsmanUtils::getInstance();
		
		$emailIds = $this->param('ids');
		$EmailIdsArr = preg_split('/[^a-fA-F0-9]/', $emailIds, -1, PREG_SPLIT_NO_EMPTY);

		foreach ($EmailIdsArr as $emailId) {
			$wrs = newsmanWorkerRecord::findAll('workerParams REGEXP \'"email_id":"'.$emailId.'"\'');

			// kiling workers
			foreach ($wrs as $wr) {
				if ( $wr ) {
					$w = new newsmanWorkerAvatar($wr->workerId);
					$w->stop();					
				}					
			}

			// markign email as stopped				
			$email = newsmanEmail::findOne('id = %d', array( $emailId ) );

			$u->log('Looking for email '.$emailId);

			if ( $email ) {
				$u->log('found email...');
				$email->status = 'stopped';
				$email->save();
				$u->log('email saved.');
			}				
		}

		$this->respond(true, 'Success', array());
	}
		
	public function ajResumeSending() {
		$ids = $this->param('ids');
		$set = $this->u->jsArrToMySQLSet($ids);	
		
		if ( $set === '()' ) {
			return $this->respond(false, __('Cannot resume emails MySQL set for '.$ids.' is empty.', NEWSMAN));
		}

		$emails = newsmanEmail::findAll('(status = "stopped" OR status = "error") AND id in '.$set);

		foreach ($emails as $email) {
			//??? this is hacky. need a better way to tell worker how to release lock					
			if ( $email->status === 'stopped' ) {
				$lockName = newsmanMailerWorker::getLockName($email->id);
				$this->u->releaseLock($lockName);
			}

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
	// 	
	// 	$ent = $this->getEntityById($entityId, $entType);
	// 	$particles = $ent->particles;
	// 	$content = $this->u->getSectionContent($particles, 'gsedit', $particleName);
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
		global $newsman_show_thumbnail_placeholders;
		global $newsman_current_email;

		//TODO: make posts shortcode resolving during sending			

		$pids = $this->param('pids');
		$pids = preg_split('/[\s*,]+/', $pids);

		$entityId = $this->param('entity');
		$entType = $this->param('entType');

		$newsman_show_thumbnail_placeholders = intval($this->param('showTmbPlaceholder', 0)) ? true : false;

		if ( !in_array($entType, array('email', 'template')) ) {
			$this->respond(false, sprintf(__('"entType" parameter value "%s" should be "email" or "template".', NEWSMAN), $entType) );
		}

		$ent = $this->getEntityById($entityId, $entType);

		$newsman_current_email = $ent;

		$postBlockTpl = $this->u->getSectionContent($ent->particles, 'gsedit', 'post_block');
		$postDividerTpl = $this->u->getSectionContent($ent->particles, 'gsedit', 'post_divider');

		$sc = $this->u->findShortCode($postBlockTpl, 'newsman', array( 'post' => array('fancy_excerpt', 'post_excerpt', 'post_content') ));

		$postType = 'post';

		if ( $sc ) {
			$ptype = $sc->get('type');
			if ( $ptype ) {
				$postType = $ptype;
			}

			// content type
			$ctype = $this->param('ctype', false);
			$typesMap = array(
				'full' 		=> 'post_content',
				'excerpt' 	=> 'post_excerpt',
				'fancy'		=> 'fancy_excerpt'
			);

			if ( $ctype && $sc->get('post') ) {
				$sc->set('post', $typesMap[$ctype]);
				$postBlockTpl = $sc->updateSource();					
			}
			// ----				
		}

		$t = $this->param('type', false);
		if ( $t ) {
			$postType = $t;
		}

		$args = array(
			'nopaging' => true,
			'post__in' => $pids,
			'post_type' => $postType,
			'suppress_filters' => true
		);

		$output = '';

		$query = new WP_Query($args);

		$first = true;
		$n = 0;

		while ( $query->have_posts() ) {
			$n += 1;
			$newsman_loop_post_nr = $n;

			$query->the_post();

			$newsman_loop_post = $post;

			if ( $first ) {
				$first = false;
			} else {
				$output .= do_shortcode($postDividerTpl);	
			}

			$output .= do_shortcode(preg_replace('/(gsedit=")([^"]+)(")/i', '$1$2_'.$post->ID.'$3', $postBlockTpl));
		}

		$output = $this->u->processAssetsURLs($output, $ent->assetsURL);

		$this->respond(true, __('Posts block successfully compiled', NEWSMAN), array('content' => $output ));
	}

	public function ajSetPostTemplateType() {
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
		$postBlockTpl = $this->u->getSectionContent($ent->particles, 'gsedit', 'post_block');

		//$postBlockTpl = preg_replace('/(\[newsman[^\[\]]+post=(?:\'|"))(fancy_excerpt|post_excerpt|post_content)((?:\'|")[^\[\]]+\])/', '$1'.$newTplType.'$3', $postBlockTpl);
		$postBlockTpl = $this->u->modifyShortCode($postBlockTpl, 'newsman', array( 'post' => array('fancy_excerpt', 'post_excerpt', 'post_content') ), array( 'post'=> $newTplType, 'type'=>$postType ));	

		$ent->particles = $this->u->replaceSectionContent($ent->particles, 'post_block', $postBlockTpl);
		$ent->save();
		$this->respond(true, __('Posts block successfully updated', NEWSMAN));
	}

	public function ajGetEntityParticle() {
		$entityId = $this->param('entity');
		$entType = $this->param('entType');
		$name = $this->param('name');

		$ent = $this->getEntityById($entityId, $entType);

		$tpl = $this->u->getSectionContent($ent->particles, 'gsedit', $name);

		$this->respond(true, __('success', NEWSMAN), array( 'particle' => $tpl ));
	}

	public function ajSetEntityParticle() {
		$entityId = $this->param('entity');
		$entType = $this->param('entType');
		$name = $this->param('name');
		$content = $this->param('content');

		$ent = $this->getEntityById($entityId, $entType);

		$ent->particles = $this->u->replaceSectionContent($ent->particles, $name, $content);

		$ent->save();

		$this->respond(true, __('Saved', NEWSMAN));
	}		

	public function ajSendResubscribeRequest() {
		global $newsman_current_subscriber;
		global $newsman_current_email;
		global $newsman_current_list;
		
		$n = newsman::getInstance();

		$listId = $this->param('listId', '1');

		if ( $n->createReConfirmEmail($listId) ) {
			$this->respond(true, __('Your email is successfully scheduled.', NEWSMAN), array(
				'redirect' => get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox'
			));
		} else {
			$this->respond(false, __('ReConfirmation system email template is not found.', NEWSMAN));
		}
	}

	public function ajResendConfirmation() {
		global $newsman_current_subscriber;		
		global $newsman_current_email;
		global $newsman_current_list;			

		$ids = $this->param('ids'); // js array or comma sep. enumeration
		
		$set = $this->u->jsArrToMySQLSet($ids);

		$listId = $this->param('listId', '1');
		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			$this->errListNotFound($listId);
		}

		$newsman_current_list = $list;

		$subs = $list->findAllSubscribers("id in ".$set, array());

		foreach ($subs as $s) {
			$newsman_current_subscriber = $s->toJSON();
			$newsman = newsman::getInstance();
			$newsman->sendEmail($list->id, NEWSMAN_ET_CONFIRMATION);
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

			$list->extcss = $this->param('extcss');

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

		$toEmail = $this->param('toEmail');
		$entityId = $this->param('entity');
		$entType = $this->param('entType');

		$testListName = '_Test';

		$list = newsmanList::findOne('name = %s', array($testListName));

		$subCreated = false;

		if ( !$list ) {
			$list = new newsmanList($testListName);
			$list->save();
		}

		$s = $list->findSubscriber("email = %s", array($toEmail));

		if ( !$s ) {
			$s = $list->newSub();
			$s->email = $toEmail;
			$s->firstName = 'Test';
			$s->lastName = 'User';
			$s->confirm();
			$s->save();
			$subCreated = true;
		}			

		$newsman_current_list = $list;
		$newsman_current_subscriber = $s->toJSON();

		$ent = $this->getEntityById($entityId, $entType);

		if ( $ent instanceof newsmanEmail ) {
			$email = $ent;
		} else {
			$email = new newsmanEmail();

			$email->emailAnalytics = false; // tunring of analytics for templates

			$email->subject = $ent->subject;
			$email->p_html = $ent->p_html;
			$email->plain = $ent->plain;
		}


		$msg = $email->renderMessage($newsman_current_subscriber);
		$msg['html'] = $this->u->processAssetsURLs($msg['html'], $ent->assetsURL);

		$r = $this->u->mail($msg, array( 'to' => $toEmail ) );

		if ( $r === true ) {
			$s = $subCreated ?
				sprintf(__('Test email was sent to %s and subscriber with this email was created in "%s" list.', NEWSMAN), $toEmail, $testListName) :
				sprintf(__('Test email was sent to %s.', NEWSMAN), $toEmail);
			$this->respond(true, $s);
		} else {
			$this->respond(false, $r);
		}			
	}

	/**
	 * Deprecated since 1.7.3
	 */
	public function ajSetSubscriberEmail() {
		$email = $this->param('value');
		$id = $this->param('pk');
		$listId = $this->param('list');

		$list = newsmanList::findOne('id = %d', array($listId));

		$s = $list->findSubscriber("id = %d", array($id));
		if ( $s ) {
			$s->email = $email;
			$s->save();
			$this->respond(true, 'Updated.');
		} else {
			$this->respond(false, 'Subscriber is not found.');
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

		$deleteSubs = (boolean)intval($this->param('deleteSubs'));

		$g = newsman::getInstance();
		$g->uninstall($deleteSubs);
		deactivate_plugins(NEWSMAN_PLUGIN_PATHNAME);
		if ( defined('NEWSMANP_PLUGIN_PATHNAME') ) {
			deactivate_plugins(NEWSMANP_PLUGIN_PATHNAME);	
		}			
		$this->respond(true, 'Successfully uninstalled', array(
			'redirect' => get_bloginfo('wpurl').'/wp-admin/plugins.php'
		));			
	}

	public function ajRemoveUploadedFile() {
		$fileName = $this->param('fileName');
		$type = $this->param('type');

		if ( !in_array($type, array('template', 'csv'))  ) {
			$this->respond(false, __('Wrong "type" parameter. Must be "csv" or "template"', NEWSMAN));
		}

		$n = newsman::getInstance();

		$filePath = $n->ensureUploadDir($type).DIRECTORY_SEPARATOR.$fileName;

		if ( file_exists($filePath) ) {
			unlink($filePath);
			$this->respond(true, 'Successfully deleted');
		} else {
			$this->respond(false, 'Error: File not found', array('x' => $filePath));
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
		
		$this->respond(true, $this->u->getSystemInfo());
	}

	public function ajSwitchLocale() {
		
		$n = newsman::getInstance();

		$switchLocale = $this->param('switch-locale', false);
		$swtichLocalePages = $this->param('swtich-locale-pages', false);
		$swtichLocaleTemplates = $this->param('swtich-locale-templates', false);

		switch ( $switchLocale ) {
			case 'replace-all':
				$this->u->installActionPages($n->wplang, 'REPLACE');
				$this->u->installSystemEmailTemplates($n->wplang, 'REPLACE');

				$n->options->set('lang', $n->wplang);
				$n->lang = $n->wplang;
				break;

			case 'just-update-locale':
				$n->options->set('lang', $n->wplang);
				$n->lang = $n->wplang;
				break;

			case 'custom':

				if ( $swtichLocalePages ) {
					$this->u->installActionPages($n->wplang, 'REPLACE');
				}

				if ( $swtichLocaleTemplates ) {
					$this->u->installSystemEmailTemplates($n->wplang, 'REPLACE');
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

	public function ajRemoveLists() {
		

		$ids = $this->param('ids');
		$set = $this->u->jsArrToMySQLSet($ids);

		$r = newsmanList::findAll('`id` in '.$set);

		if ( is_array($r) ) {
			foreach ($r as $lst) {
				$lst->remove();
			}
			$this->respond(true, __('success', NEWSMAN) );
		} else {
			$this->respond(false, newsmanList::$lastError);
		}
	}

	public function ajSetEmailAnalytics() {
		$emlId   = $this->param('id');
		$type = $this->param('type', '');
		$camp = $this->param('campaign', '');

		$this->u->log('[ajSetEmailAnalytics] id: '.$emlId);

		$email = newsmanEmail::findOne('id = %s', array( $emlId ) );

		if ( $email ) {
			$email->analytics = $type;
			$email->campName = $camp;
			$r = $email->save();

			$u = newsmanUtils::getInstance();
			$u->log('[ajSetEmailAnalytics] '.print_r($r, true));


			$this->respond(true, 'success 123', array(
				'type' => $type,
				'camp' => $camp
			));
		} else {
			$this->errEmailNotFound($emlId);				
		}
	}

	public function ajDownloadTemplate() {
		$n = newsman::getInstance();
		

		$json = $this->param('json');

		$tpl = json_decode($json, true);

		$url = $tpl['downloadURL'];

		$res = wp_remote_get($url, array(
			'haeders' => array(
				'Pragma' => 'no-cache',
				'Cache-Control' => 'no-cache'
			)
		));

		if ( is_wp_error( $res ) ) {
			$this->respond(false, $response->get_error_message());
		} else {
			$filePath = $n->ensureUploadDir('template').DIRECTORY_SEPARATOR.basename($url);

			if ( $res['body'] ) {
				$length = file_put_contents($filePath, $res['body']);

				$this->u->installTemplate($filePath, $tpl);
				$this->respond(true, $filePath);					
			} else {
				$this->respond(false, 'Some error occured. Downloaded file size is 0.');
			}
		}
	}

	public function ajGetInstalledTemplates() {
		$templates = array();

		$n = newsman::getInstance();
		$upath = $n->ensureUploadDir('template');

		if ( $handle = opendir($upath) ) {

		    /* This is the correct way to loop over the directory. */
		    while (false !== ($entry = readdir($handle))) {
		    	if ( $entry !== '.' && $entry !== '..' && $entry[0] !== '.' ) {			    		
		    		$pathToEntry = $upath.DIRECTORY_SEPARATOR.$entry;
		    		if ( is_dir($pathToEntry) ) {
		    			$templates[] = $entry;
		    		}
		    	}			    	
		    }

		    closedir($handle);
		}

		$this->respond(true, 'Success', array( 'installed' => $templates ));
	}

	public function ajHideCronAlternativeModeMessage() {
		$o = newsmanOptions::getInstance();
		$o->set('hideAlternateCronWarning', true);
		$this->respond(true, 'Success');
	}

	public function ajHideCronFailWarning() {
		$o = newsmanOptions::getInstance();
		$enablePokeback = $this->param('enablePokeback', false);

		$this->u->log('[ajHideCronFailWarning] enablePokeback %s', $enablePokeback ? 'true' : 'false');

		if ( $enablePokeback ) {
			$o->set('pokebackMode', true);
		}
		
		$o->set('hideCronFailWarning', true);
		$this->respond(true, 'Success');
	}

	public function ajCheckEmailAddresses() {
		$listId = $this->param('listId');

		

		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			$this->errListNotFound($listId);
		}

		$p = 1;
		$done = false;
		do {
			$res = $list->getPage($p, 1000);
			if ( is_array($res) && !empty($res)  ) {
				foreach ($res as $sub) {
					if ( !$this->u->emailValid($sub->email) ) {
						$sub->unsubscribe(__('Bad email address', NEWSMAN));
						$sub->save();
					}
				}
				$p += 1;
			} else {
				$done = true;
			}
		} while ( !$done );

		$this->respond(true, 'Success');
	}

	public function ajGetActivePageTranslation() {

		$pageId = $this->param('pageId');
		$loc = $this->param('loc');

		
		
		$translation = $this->u->getPageTranslation($loc, $pageId);

		if ( $translation ) {
			$this->respond(true, __('Success', NEWSMAN), $translation);
		} else {
			$this->respond(false, __('Translation not found', NEWSMAN));
		}
	}

	public function ajGetAvailableLanuages() {
		
		$langs = $this->u->getAvailableTranslationLocales();

		$this->respond(true, 'Success', array( 'languages' => $langs ));
	}

	public function ajInstallSystemEmailTemplatesForList() {
		$listId = $this->param('listId');
		$loc = $this->param('loc');

		$this->u->installSystemEmailTemplates($loc, true, $listId);
		$this->respond(true, __('Success', NEWSMAN));
	}

	public function ajGetDebugLog() {
		$log = $this->u->readLog();
		$this->respond(true, __('Success', NEWSMAN), array('log' => $log));
	}

	public function ajEmptyDebugLog() {
		$this->u->emptyLog();
		$this->respond(true, __('Success', NEWSMAN));
	}

	public function ajReinstallStockTemplate() {
		$name = $this->param('name');
		$this->u->reInstallStockTemplate($name);
		$this->respond(true, __('Success', NEWSMAN));
	}

	// analytics

	public function ajGetRecipinetsActivity() {
		$emlId 	= intval($this->param('emlId'));

		$pg		= intval($this->param('pg','1'));
		$ipp	= intval($this->param('ipp','25'));

		$selector = 'emailId = %d';
		$args = array($emlId);

		$listNames = array();

		try {
			$reader = new newsmanGeoLiteDbReader();			
			$geoDBexists = true;
		} catch (Exception $e) {
			$geoDBexists = false;
		}			

		$res = array();
		$res['count'] = intval(newsmanAnSubDetails::count($selector, $args));
		$res['rows'] = array();

		$subs = newsmanAnSubDetails::findAllPaged($pg, $ipp, $selector, $args);

		foreach ($subs as $s) {
			$x = array();
			$x['email'] = $s->emailAddr;
			$x['opens'] = $s->opens;
			$x['clicks'] = $s->clicks;
			$x['unsubscribed'] = $s->unsubscribed;
			$x['listId'] = $s->listId;

			if ( $geoDBexists ) {
				try {
					$record = @$reader->city($s->ip);
					$x['location'] = array(
						'city' =>  $this->u->getGeoLocalName( $record->city->names ),
						'sub' => $this->u->getGeoLocalName( $record->mostSpecificSubdivision->names ),
						'country' => $this->u->getGeoLocalName( $record->country->names )
					);
				} catch (Exception $e) {
					$x['location'] = array(
						'city' => __('Unknown', NEWSMAN),
						'sub' => __('Unknown', NEWSMAN),
						'country' => __('Unknown', NEWSMAN)
					);
				}					
			} else {
				$x['location'] = array(
					// 'city' => __('No Data. GeoLite DB was not downloaded', NEWSMAN),
					// 'sub' => __('No Data. GeoLite DB was not downloaded', NEWSMAN),
					'country' => __('Error: no data. Please download GeoLite2 database <a href="'.NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-settings">in the settings</a>', NEWSMAN)
				);
			}



			if ( isset($listNames[$s->listId] ) ) {
				$x['listName'] = $listNames[$s->listId];
			} else {
				$list = newsmanList::findOne('id = %d', array($s->listId));
				if ( $list ) {
					$x['listName'] = $list->name;
				} else {
					$x['listName'] = 'DELETED';
				}
				$listNames[$s->listId] = $x['listName'];
			}
			
			$res['rows'][] = $x;
		}

		$this->respond(true, 'success', $res);
	}


	public function ajGetLinkClicksDetails() {
		$emlId 	= intval($this->param('emlId'));

		$pg		= intval($this->param('pg','1'));
		$ipp	= intval($this->param('ipp','25'));

		$selector = 'emailId = %d';
		$args = array($emlId);

		$res = array();
		$res['count'] = intval(newsmanAnLink::count($selector, $args));
		$res['rows'] = array();		

			$links = newsmanAnLink::findAllPaged($pg, $ipp, $selector, $args);

			foreach ($links as $link) {
				$r = array(
					'url' => $link->URL,
					'uniqueClicks' => $link->uniqueClicks
				);
				$res['rows'][] = $r;
			}

		$this->respond(true, 'success', $res);
	}

	public function ajGetAdminForm() {
		$listId = $this->param('listId');
		$subId = $this->param('subId', false);


		$sub = array();
		$list = newsmanList::findOne('id = %s', array($listId));
		if ( $list && $subId ) {
			$sub = $list->findSubscriber('id = %s', $subId);
			if ( $sub ) {
				$sub = $sub->toJSON();
			}
		}

		$html = newsman::getInstance()->putForm(false, $listId, false, 'ADMIN_MODE');
		$this->respond(true, 'success', array(
			'html' => $html,
			'sub' => $sub
		));
	}

	public function ajSaveSubscriber() {
		$json = $this->param('json');
		$listId = $this->param('listId');
		$subId = $this->param('subId', false);

		$list = newsmanList::findOne('id = %d', array($listId));

		$sub = json_decode($json, true);

		if ( !$list ) {
			return $this->errListNotFound($listId);
		}

		$offsetSec = get_option('gmt_offset') * 3600;
		$op = $offsetSec > 0 ? 'add' : 'sub';
		$offsetSec = abs($offsetSec);
					
		$offsetModifier = 'PT'.$offsetSec.'S';

		$d = new DateTime($sub['date']);
		$d->$op(new DateInterval($offsetModifier));

		$u = newsmanUtils::getInstance();
		$u->log('[ajSaveSubscriber] offsetPart %s', $offsetModifier);
		$u->log('[ajSaveSubscriber] full datetime  %s', $sub['date']);			

		if ( $subId ) {
			$s = $list->findSubscriber('id = %s', $subId);
		} else {
			$s = $list->newSub();	
		}

		$s->fill($sub['form']);
		$s->setDate($d->format('Y-m-d H:i:s'));
		switch ( $sub['type'] ) {
			case 'confirmed':
				$s->confirm();
				break;
			case 'unconfirmed':
				$s->unconfirm();
				break;
			case 'unsubscribed':
			default:
				$s->unsubscribe();
				break;
		}
		$r = $s->save();

		if ( $r ) {
			$this->respond(true, __('Saved', NEWSMAN), array(
				'id' => $r
			));
		} else {
			if ( stripos($s->lastError, 'Duplicate entry') !== false  ) {
				$this->respond(false, sprintf(__('Subscriber with email %s already exists.', NEWSMAN), $s->email));
			} else {
				$this->respond(false, $s->lastError);	
			}				
		}
	}


	// ---------------

	public function ajSetActivationCode() {
		if ( !isset($_REQUEST['code']) || $_REQUEST['code'] == '' ) {
			$this->respond(false, __('Required parameter "code" is not defined.', NEWSMANP) );
		} else {

			$domain = $this->n->getSelfDomain();

			$response = wp_remote_request(
				'http://wpnewsman.com/wp-newsman-check-key?key='.$_REQUEST['code'].'&domain='.$domain,
				array( 'timeout' => 10 )
			);

			$error = false;

			if ( is_wp_error($response) ) {
				$error = $response->get_error_message();
				$this->respond(false, $error, array('url'=>'http://wpnewsman.com/wp-newsman-check-key?key='.$_REQUEST['code'].'&domain='.$domain));
			} else {
				$response = $response['body'];

				if ( $response !== 'FAIL' ) {
					update_option('newsman_code', $_REQUEST['code']);
					$this->respond(true, __('Your license key is valid. Pro functionality is unlocked.', NEWSMANP), array(
						'redirect' => get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox'
					));
				} else {
					update_option('newsman_code', ''); // for debug only
					$this->respond(false, __('Your license key is invalid. Please make sure you copied it in full and if this error still occurs, please write us at <a href="mailto:support@glocksoft.com">support@glocksoft.com</a>.', NEWSMANP));
				}				
			}
		}
	}

	public function ajTestBouncedConnection() {
		$params = array('type','host', 'port', 'secure', 'username', 'password');
		$req_params = array_keys($_REQUEST);
		foreach ($params as $param) {
			if ( !in_array($param, $req_params) ) {
				$this->respond(false, sprintf( __('Required parameter "%s" is not defined.', NEWSMANP), $param ));
			}
		}

		$bh = new newsmanBouncedHandler($_REQUEST);

		if ( @$bh->connect() ) {
			$this->respond(true, __('Your connection settings are correct', NEWSMANP));
		} else {
			$errStr = !empty($bh->errors) ? $bh->errors[count($bh->errors)-1] : '';
			$this->respond(false, $errStr);
		}
	}

	public function ajGetBHStatus() {
		$r = array();
		$bh = new newsmanBouncedHandler();
		$r['status']  = newsmanBouncedHandler::getStatus();
		$r['stats']   = newsmanBouncedHandler::getLastStats();
		$r['nextrun'] = wp_next_scheduled('newsman_bh_event');

		$this->respond(true, $r);
	}	

	public function ajRunBH() {
		if ( !$this->o->get('pokebackMode') ) {
			if ( wp_next_scheduled('newsman_bh_event') ) {
				wp_clear_scheduled_hook('newsman_bh_event');
				wp_schedule_event( time() + 3600, 'hourly', 'newsman_bh_event' );
			}			
		} else {
			// re-scheduling job
			$this->n->pbUnschedule();
			$this->n->pbSchedule();
		}

		$this->n->runBouncedHandler();
		
		$r = array();
		$r['status']  = newsmanBouncedHandler::getStatus();
		$r['stats']   = newsmanBouncedHandler::getLastStats();
		$r['nextrun'] = wp_next_scheduled('newsman_bh_event');

		$this->respond(true, __('Starting bounce handler', NEWSMANP), $r);
	}

	public function ajStopBH() {

		$u = newsmanUtils::getInstance();
		$u->log('ajStopBH');

		$wrs = newsmanWorkerRecord::findAll('workerClass = "newsmanBounceHandlerWorker"');

		// stopping workers
		foreach ($wrs as $wr) {
			if ( $wr->workerId ) {
				$w = new newsmanWorkerAvatar($wr->workerId, 5);
				$u->log('ajStopBH %s', $wr->workerId);
				$w->stop();				
			}
			$wr->remove();
		}

		$this->respond(true, __('Bounce handler stopped', NEWSMANP));
	}	

	public function ajIsGeoIpDBUpdateAvailable() {
		$reader = new newsmanGeoLiteDbReader();

		$this->respond(true, 'success', array(
			'updateAvailable' => $reader->isNewVersionAvailable()
		));
	}

	public function ajHideDownloadGeoIPDBNotification() {
		$this->o->set('hideDownloadGeoIPDBNotification', true);

		$this->respond(true, 'success');
	}

	public function ajDownloadGeoipDB() {
		$n = newsman::getInstance();
		$u = newsmanUtils::getInstance();

		$start = microtime(true);
		$u->log('[ajDownloadGeoipDB] download started');

		$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-City.mmdb.gz';

		$res = wp_remote_get($url, array(
			'timeout' => 0,
			'haeders' => array(
				'Pragma' => 'no-cache',
				'Cache-Control' => 'no-cache'
			)
		));

		if ( is_wp_error( $res ) ) {
			$this->respond(false, $res->get_error_message());
		} else {
			$filePath = $n->ensureUploadDir('geoip').DIRECTORY_SEPARATOR.basename($url);

			if ( $res['body'] ) {
				$length = file_put_contents($filePath, $res['body']);
				$dbFilePath = $this->n->unpackGeoIpDB($filePath);

				$u->log('[ajDownloadGeoipDB] download took '.(microtime(true) - $start).'s');

				if ( !$dbFilePath ) {
					$this->respond(false, 'Some error occured while unpacking geoip database');
				} else if ( is_wp_error($dbFilePath) ) {
					$this->respond(false, $dbFilePath->get_error_message());
				} else {
					$this->respond(true, $dbFilePath);
				}
			} else {
				$this->respond(false, 'Some error occured. Downloaded file size is 0.');
			}
		}
	}	

	public function ajGetBlockedDomains() {

		$start = isset($_REQUEST['start']) ? intval($_REQUEST['start']) : 0;
		$limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 25;
		$search = isset($_REQUEST['search']) ? stripslashes($_REQUEST['search']) : '';

		if ( $search ) {
			$query = 'domain LIKE %s';
			$queryArgs = array("%$search%");
			$domains = newsmanBlockedDomain::findRange($start, $limit, $query, $queryArgs);			
		} else {
			$domains = newsmanBlockedDomain::findRange($start, $limit);
		}		

		$blockedDomains = array();
		foreach ($domains as $d) {
			$blockedDomains[] = $d->toJSON();
		}

		$this->respond(true, __('Success', NEWSMANP), array(
			'domains' => $blockedDomains
		));
	}	

	public function ajRemoveBlockedDomain() {
		$domainId = isset($_REQUEST['domainId']) ? intval($_REQUEST['domainId']) : null;

		if ( $domainId === null ) {
			$this->respond(false, __('Parameter "domainId" could not be empty', NEWSMANP));			
		} else {
			if ( newsmanBlockedDomain::removeAll('id = %s', array($domainId)) !== false ) {
				$this->respond(true, __('Success', NEWSMANP));
			} else {				
				$this->respond(false, sprintf(__('Some error occured: %s', NEWSMANP), newsmanBlockedDomain::$lastError));
			}
		}
	}		

	public function ajRemoveAllBlockedDomains() {
		if ( newsmanBlockedDomain::removeAll() !== false ) {
			$this->respond(true, __('Success', NEWSMANP));
		} else {				
			$this->respond(false, sprintf(__('Some error occured: %s', NEWSMANP), newsmanBlockedDomain::$lastError));
		}
	}	




}