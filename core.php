<?php

require_once('class.newsman-worker.php');

require_once('class.form.php');
require_once('class.subscriber.php');
require_once('class.list.php');
require_once('class.options.php');
require_once('class.utils.php');
require_once('class.emails.php');
require_once('class.emailtemplates.php');
require_once('class.mailman.php');
require_once('class.sentlog.php');
require_once('ajaxbackend.php');

require_once('workers/class.mailer.php');

require_once('class.zip.php');

require_once('lib/emogrifier.php');

global $newsman_checklist;
global $newsman_error_message;
global $newsman_success_message;

function wpnewsmanFilterSchedules($schedules) {
	$schedules['1min'] = array('interval' => 60, 'display' => __('Every minute', NEWSMAN) );

	return $schedules;
}

add_filter('cron_schedules', 'wpnewsmanFilterSchedules');


function wpnewsman_loadstring() {
	$domain = NEWSMAN;
	// The "plugin_locale" filter is also used in load_plugin_textdomain()
	$locale = apply_filters('plugin_locale', get_locale(), $domain);

	load_textdomain($domain, WP_LANG_DIR.'/wpnewsman/'.$domain.'-'.$locale.'.mo');
	load_plugin_textdomain($domain, FALSE, dirname(plugin_basename(NEWSMAN_PLUGIN_MAINFILE)).'/languages/');	
}
add_action('plugins_loaded', 'wpnewsman_loadstring');

if ( function_exists('mb_internal_encoding') ) {
	mb_internal_encoding("UTF-8");
}

$loc = "UTF-8";
putenv("LANG=$loc");
$loc = setlocale(LC_ALL, $loc);

class newsman {

	var $pluginName = 'G-Lock WPNewsman';
	var $version = NEWSMAN_VERSION;

	var $options;
	var $utils;
	var $mailman;
	var $activation = false;

	var $registeredWorkers = array();

	var $linkTypes = array('confirmation', 'resend-confirmation', 'unsubscribe', 'update-subscription', 'email');

	// singleton instance 
	private static $instance; 

	// getInstance method 
	public static function getInstance() { 
		if ( !self::$instance ) { 
			self::$instance = new self();

			
		} 
		return self::$instance; 
	} 

	public function __construct() {

		$newsman_error_message = '';
		$newsman_success_message = '';

		add_action('init', array($this, 'onInit'));

		add_action('newsman_admin_page_header', array($this, 'showAdminNotifications'));

		//add_filter('cron_schedules', array($this, 'addRecurrences'));		

		$this->options = newsmanOptions::getInstance();
		$this->utils = newsmanUtils::getInstance();

		$u = newsmanUtils::getInstance();

		$this->mailman = newsmanMailMan::getInstance();

		switch ( strtolower($this->options->get('dashboardStats')) ) {
			case 'abox':
				// activity box
				add_action('activity_box_end', array($this, 'showStatsInLatestActivity'));
			break;
			case 'widget':
				// separate widget  
				add_action('wp_dashboard_setup', array($this, 'registerDashboardWidget') );
			break;
		}
		
		add_action('admin_menu', array($this, 'onAdminMenu'));
		add_action('admin_init', array($this, 'onAdminInit'));
		add_action('admin_head', array($this, 'onAdminHead'));

		add_action('template_redirect', array($this, 'onTemplateRedirect'), 1);

		add_action('wpnewsman_update', array($this, 'onActivate'));

		if ( !defined('NEWSMAN_WORKER') ) {
			add_action('widgets_init', array($this, 'onWidgetsInit') );	
		}	

		// schedule event
		add_action('newsman_mailman_event', array($this, 'mailman'));

		add_action("wp_insert_post", array($this, "onInsertPost"), 10, 2);

		add_action('wp_head', array($this, 'onWPHead'));

		add_action('plugins_loaded', array($this, 'setLocale'));

		// -----

		add_action('newsman_put_list_select', array($this, 'putListSelect'));

		add_filter( 'enter_title_here', array($this, 'filterEnterTitleHere') );	
		add_filter('gettext', array($this, 'customLabels'));
		add_filter( 'wp_insert_post_data', array($this, 'actionPagesCommentsOff') );

		add_filter('the_content', array($this, 'pre_content_message'));

		if ( function_exists('add_shortcode') ) {
			add_shortcode('newsman-form', array($this, 'shortCode'));
			add_shortcode('newsman', array($this, 'newsmanShortCode'));
			add_shortcode('newsman_loop', array($this, 'newsmanShortcodeLoop'));

			add_shortcode('newsman_change_email_form', array($this, 'formChangeEmail'));

			add_shortcode('newsman_unsubscribe_form', array($this, 'formUnsubscribeEmail'));

			add_shortcode('newsman_on_web', array($this, 'newsmanShortCodeOnWeb'));
			add_shortcode('newsman_not_on_web', array($this, 'newsmanShortCodeNotOnWeb'));
		}		

		add_action('do_meta_boxes', array($this, 'reAddExcerptMetabox'), 10, 3);

		add_action('save_post', array($this, 'save_ap_template'), 10, 2);

		add_filter('single_template', array($this, 'set_custom_template_for_action_page'));

	}

	public function onTemplateRedirect() {
		global $post;
		if( $post->post_type == 'newsman_ap' ){
			$categories = get_the_category();
			if ( count($categories) == 0 ) {
				//assign a category
				wp_set_object_terms($post->ID, 'uncategorized','category');
			}
		}
	}

	public function setLocale() {

		$this->wplang = get_locale();

		$this->lang = $this->options->get('lang');
		if ( !$this->lang ) {
			$this->lang = $this->wplang;
			$this->options->set('lang', $this->lang);
		}
	}

	public function register_worker($className) {
		if ( !in_array($className, $this->registeredWorkers) ) {
			$registeredWorkers[] = $className;
		}
	}

	public function runWorker($className, $params) {
		if ( !in_array($className, $this->registeredWorkers) ) {
			return null;
		} else {

		}
	}

	public function isInstalled() {
		return get_option('newsman_options') !== false;
	}

	public function mailman() {
		$this->cleanOldUnconfirmed();
		$this->mailman->pokeWorkers();		
		$this->mailman->checkEmailsQueue();
	}

	// ------------------------------------------------

	public function getDefaultForm() {

		$s = array(
			/* translators: Default subscription form */
			'dfTitle' => __('Subscription', NEWSMAN),
			/* translators: Default subscription form */
			'dfHeader' => __('Enter your primary email address to get our free newsletter.', NEWSMAN),
			/* translators: Default subscription form */
			'dfFirstName' => __('First Name', NEWSMAN),
			/* translators: Default subscription form */
			'dfLastName' => __('Last Name', NEWSMAN),
			/* translators: Default subscription form */
			'dfEmail' => __('Email', NEWSMAN),
			/* translators: Default subscription form */
			'dfSubmit' => __('Subscribe', NEWSMAN),
			/* translators: Default subscription form */
			'dfFooter' => __('You can leave the list at any time. Removal instructions are included in each message.', NEWSMAN)
		);

		return '{"useInlineLabels":true,"elements":[{"type":"title","value":"'.$s['dfTitle'].'"},{"type":"html","value":"'.$s['dfHeader'].'"},{"type":"text","label":"'.$s['dfFirstName'].'","name":"first-name","value":""},{"type":"text","label":"'.$s['dfLastName'].'","name":"last-name","value":""},{"type":"email","label":"'.$s['dfEmail'].'","name":"email","value":"","required":true},{"type":"submit","value":"'.$s['dfSubmit'].'","size":"small","color":"gray","style":"rounded"},{"type":"html","value":"'.$s['dfFooter'].'"}]}';
	}



	public function actionPagesCommentsOff( $data ) {
		if ( $data['post_type'] == 'newsman_ap' ) {
			$data['comment_status'] = 'close';
		}
		return $data;
	}


	public function prepareTransmission($t, $email) {
		$l = 03720;

		$upgradeLink = '<a href="'.NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-pro">'.__('Upgrade to Pro', NEWSMAN).'</a>';
		
		if ( $email->sent >= $l ) {
			$email->msg = sprintf(__('Sent %d of %d emails.', NEWSMAN), $email->sent, $email->recipients);
			$email->msg .= '<br>'.sprintf( __('You reached the subscribers limit of the Lite version. %s to resume sending.', NEWSMAN), $upgradeLink);
			$email->status = 'stopped';
			$email->save();
			return null;
		}

		return $t;
	}

	private function redirect($link){
		wp_redirect($link);
		echo " ";
		exit();
	}

    public function newsmanShortcodeLoop($attr) {
		global $newsman_loop_post;
		global $newsman_post_tpl;
		global $newsman_loop_post_nr;

    	if ( isset($attr['query']) && !empty($attr['query']) ) {
    		$q = new WP_Query($attr['query']);
    	} else {
    		$args = $attr;

    		if ( isset($args['post__in']) ) {
    			$args['post__in'] = explode( ',', $args['post__in'] );
    		}    		

    		if ( isset($args['post__not_in']) ) {
    			$args['post__not_in'] = explode( ',', $args['post__not_in'] );
    		}    		

    		if ( isset($args['nopaging']) ) {
    			$args['nopaging'] = ( $args['nopaging'] === '1' ) ? true : false;
    		}

    		$q = new WP_Query($args);
    	}

    	// default post loop template
    	if ( !isset($newsman_post_tpl) || empty($newsman_post_tpl) ) {
    		$newsman_post_tpl = '[newsman post="post_title"]';
    	}


    	$out = '';
    	$n = 0;
    	while ( $q->have_posts() ) {
    		$n += 1;
    		$newsman_loop_post_nr = $n;
    		$newsman_loop_post = $q->next_post();
    		$out .= do_shortcode($newsman_post_tpl);
    	}
    	return $out;
    	
    }


    public function newsmanShortCodeOnWeb($attr, $content = null) {
    	if ( defined('EMAIL_WEB_VIEW') ) {
    		return do_shortcode($content);
    	} else {
    		return '';
    	}
    }

    public function newsmanShortCodeNotOnWeb($attr, $content = null) {
    	if ( !defined('EMAIL_WEB_VIEW') ) {
    		return do_shortcode($content);
    	} else {
    		return '';
    	}
    }    


    public function loopReplaceCallback($matches) {
		global $newsman_post_tpl;
		if ( isset($matches[1]) ) {
			$newsman_post_tpl = $matches[1];
		}
		return '';
    }

    private function defineLoop($content) {
		global $newsman_post_tpl;

		$ids = ''; $del = '';
		if ( $pos ) {
			foreach ($pos as $p) {
				$ids .= $del.$p->id;
				$del = ',';
			}
		}
		$query = '[newsman_loop post__in="'.$ids.'"]';

		$newsman_post_tpl = $this->utils->cutPostBlock($content, $query);

		return $content;
	}

	public function newsmanShortCode($attr, $content = null) {
		global $newsman_current_subscriber;
		global $newsman_post_tpl;
		global $newsman_loop_post;
		global $newsman_loop_post_nr;

		global $newsman_current_email;

		extract($attr);

		if ( isset($profileurl) ) {
			switch ( $profileurl ) {
				case 'twitter':
					return $this->options->get('social.twitter');
					break;
				case 'facebook':
					return $this->options->get('social.facebook');
					break;
				case 'googleplus':
					return $this->options->get('social.googleplus');
					break;
				case 'linkedin':
					return $this->options->get('social.linkedin');
					break;
			}
		}

		if ( isset($badge) || in_array('badge', $attr) ) {
			return '<img src="'.NEWSMAN_PLUGIN_URL.'/img/newsman-badge.png" />';
		}

		if ( isset($if_not_on_web) || in_array('if_not_on_web', $attr) ) {
			if ( !defined('EMAIL_WEB_VIEW') ) {
				return do_shortcode($content);
			} else {
				return '';
			}
		}

		if ( isset($if_on_web) || in_array('if_on_web', $attr) ) {
			if ( defined('EMAIL_WEB_VIEW') ) {
				return do_shortcode($content);
			} else {
				return '';
			}
		}

		if ( isset($subject) || in_array('subject', $attr) ) {
			return $newsman_current_email->subject;
		}

		if ( !empty($post) ) {
			if ( $post === 'post_excerpt' ) {
				if ( trim($newsman_loop_post->post_excerpt) === '' ) {
					return __('"Post has no excerpt. Write something yourself or use fancy_excerpt option"', NEWSMAN);
				} else {
					return $newsman_loop_post->post_excerpt;
				}
			} else if ( $post == "number" ) {
				return $newsman_loop_post_nr;
			} else if ( $post == "permalink" ) {
				return get_permalink( $newsman_loop_post->ID );
			} else if ( $post == "fancy_excerpt" ) {
				if ( !isset($words) ) {
					$words = 350;
				}
				return $this->utils->fancyExcerpt($newsman_loop_post->post_content, $words);
			} else if ( $post == 'thumbnail' ) {
				$post_thumbnail_id = get_post_thumbnail_id( $newsman_loop_post->ID );
				$size = 'thumbnail';
				$attrs = wp_get_attachment_image_src( $post_thumbnail_id, $size, false );
				$url = $attrs[0];

				if ( !$url ) {
					$url = $this->utils->getFirstImage($newsman_loop_post);	
				}
				return $url;
			} else if ( $post == 'firstimage' ) {
				$url = $this->utils->getFirstImage($newsman_loop_post);
				return $url ? $url : '';
			} else if ( property_exists($newsman_loop_post, $post) ) {
				return $newsman_loop_post->$post;
			}
		}

		if ( !empty($sub) ) {
			if ( $sub == '*' ) {

				$r = '';

				foreach ($newsman_current_subscriber as $key => $value) {
					if ( $tag ) {
						$r .= "<$tag>$key : $value</$tag>\n";
					} else {
						$r .= "$key : $value\n";
					}
				}

				return $r;

			} else {
				if ( isset($newsman_current_subscriber[$sub]) ) {
					return $newsman_current_subscriber[$sub];
				} else {
					return '';
				}
			}
		}

		if ( isset($date) ) {
			return date($date);
		}

		if ( !empty($wp) ) {
			switch ($wp) {
				case 'blogname': return get_option('blogname');
				case 'url': return $this->utils->addTrSlash( get_bloginfo('url') );
				case 'wpurl': return $this->utils->addTrSlash( get_bloginfo('wpurl') );
				case 'blogdescription': return get_option('blogdescription');
				default: return '';
			}
		}

		/**
		 *	Gravatar
		 */

		if ( isset($gravatar) ) {
			
			if ( $gravatar == 'me' ) {
				$grEmailHash = md5( strtolower( trim( $this->options->get('sender.email') ) ) );
			} else {
				$grEmailHash = md5( strtolower( trim( $gravatar ) ) );
			}

			$grURL = 'http://www.gravatar.com/avatar/'.$grEmailHash;

			if ( isset($size) ) {
				$grURL .= '?s='.$size;
			}

			return $grURL;
		}		


		if ( $me == 'email' ) {
			return $this->options->get('sender.email');
		} elseif ( $me == 'name' ) {
			return $this->options->get('sender.name');
		}

		if ( isset($link) ) {
			return $this->getActionLink($link);
		}

		return $content ? $content : '';
	}

	public function shortCode($attrs) {

		$horizontal = in_array('horizontal', array_values($attrs));

		return $this->putForm(false, $attrs['id'], $horizontal);
	}

	public function formChangeEmail($attr) {
		global $newsman_current_subscriber;

		extract($attr);

		$new_email = isset($title) ? $title : __('New Email', NEWSMAN);
		$btn = isset($button) ? $button : __('Change my email', NEWSMAN);

		$form = '
		<form style="text-align: left;" action="" method="POST">
		<label for="nwsmn-chsub-newemail">'.$new_email.':</label>
		<input type="text" name="nwsmn-chsub-newemail" />
		<input type="submit" name="nwsmn-chsub" value="'.$btn.'" />
		</form>';

		return $form;		
	}

	public function formUnsubscribeEmail($attr) {
		global $newsman_current_subscriber;

		extract($attr);
		$btn = isset($button) ? $button : __('Change my email', NEWSMAN);

		$form = '
		<form style="text-align: left;" action="" method="post">
		<input type="submit" name="nwsmn-unsubscribe" value="'.$btn.'" />
		</form>';

		return $form;
	}

	// links

	public function getActionLink($type, $only_code = false) {
		global $newsman_current_subscriber;
		global $newsman_current_email;
		global $newsman_current_ucode;
		global $newsman_current_list;

		$ucode = $newsman_current_subscriber['ucode'];

		$blogurl = get_bloginfo('wpurl');

		if ( in_array($type, $this->linkTypes) ) {

			if ( $only_code ) {
				return $newsman_current_list->uid.':'.$ucode;
			}

			$link = "$blogurl/?newsman=$type&code=".$newsman_current_list->uid.':'.$ucode;

			if ( $type == 'email' ) {
				$link.='&email='.$newsman_current_email->ucode;
			}

			$link = apply_filters('newsman_apply_analytics', $link);
			//$link = $this->utils->linkAddAnalytics($link);

			return $link;
		} else {
			return "#link_type_$type_not_implemented";
		}
	}

	public function processActionLink() {
		global $newsman_current_subscriber;		
		global $newsman_current_email;
		global $newsman_current_list;
		global $newsman_current_ucode;

		if ( isset($_REQUEST['newsman']) && in_array($_REQUEST['newsman'], $this->linkTypes) ) {
			$type = $_REQUEST['newsman'];

			if ( strpos($_REQUEST['code'], ':') === false ) {
				wp_die( __('Wrong "code" parameter format', NEWSMAN) );
			}	

			$uArr = explode(':', $_REQUEST['code']);

			if ( $uArr[0] == '' || $uArr[1] == '' ) {
				wp_die( __('Your link seems to be broken.', NEWSMAN) );
			}

			$list = newsmanList::findOne('uid = %s', array($uArr[0]));

			if ( !$list ) {
				wp_die( sprintf( __('List with the unique code "%s" is not found', NEWSMAN), $uArr[0]) );
			}

			$s = $list->findSubscriber("ucode = %s", $uArr[1]);

			if ( !$s ) {
				wp_die(  sprintf( __('Subscriber with the unique code "%s" is not found', NEWSMAN), $uArr[1]) );
			}

			$newsman_current_ucode = $_REQUEST['code'];
			$newsman_current_list = $list;
			$newsman_current_subscriber = $s->toJSON();

			switch ( $type ) {

				case 'confirmation': 
					if ( $s->is_confirmed() ) {
						$this->redirect( $this->getLink('alreadySubscribedAndVerified', array('u' => $_REQUEST['code'] ) ) );
					} else {
						$s->confirm();
						$s->save();
						if ( $this->options->get('sendWelcome') ) {
							$this->sendEmail('welcome');	
						}
						if ( $this->options->get('notifyAdmin') ) {
							$this->notifyAdmin('adminSubscriptionEvent');	
						}						
						$this->redirect( $this->getLink('confirmationSucceed', array('u' => $_REQUEST['code'] ) ) );
					}
					break;					
				case 'resend-confirmation':					
					$this->sendEmail('confirmation');
					$this->redirect( $this->getLink('confirmationRequired', array('u' => $_REQUEST['code'] ) ) );
					break;
				case 'unsubscribe':
					$s->unsubscribe();
					$s->save();
					if ( $this->options->get('notifyAdmin') ) {
						$this->notifyAdmin('adminUnsubscribeEvent');
					}

					if ( $this->options->get('sendUnsubscribed') ) {
						$this->sendEmail('unsubscribe');
					}

					$this->redirect( $this->getLink('unsubscribeSucceed', array('u' => $_REQUEST['code'] ) ) );					
					break;
				case 'update-subscription':
					$this->redirect( $this->getLink('changeSubscription', array('u' => $_REQUEST['code'] ) ) );
					break;

				case 'email':
					if ( !isset($_REQUEST['email']) ) {
						wp_die( __('Unique email id is missing in request.', NEWSMAN) );
					} else {
						$eml = newsmanEmail::findOne('ucode = %s', array( $_REQUEST['email'] ) );

						if ( !$eml ) {
							wp_die( sprintf( __('Email with unique code %s is not found.', NEWSMAN), htmlentities($_REQUEST['email']) ) );
						} else {							
							define('EMAIL_WEB_VIEW', true);
							$r = $eml->renderMessage($newsman_current_subscriber);
							echo $this->utils->expandAssetsURLs($r['html'], $eml->assets);
							exit();
						}
					}
					$newsman_current_email;
					break;

			}

		}
	}

	// -----

	public function install() {		

		error_reporting(0);

		wpnewsman_loadstring();

		$options = array(			
			'sender' => array(
				'name' => html_entity_decode(get_option('blogname'), ENT_QUOTES, 'UTF-8'),
				'email' => get_option('admin_email'),
				'returnEmail' => get_option('admin_email')
			),
			'sendWelcome' => true,
			'sendUnsubscribed' => true,
			'form' => array(
				'json' => $this->getDefaultForm()
			),
			'notifyAdmin' => true,
			'mailer' => array(
				'mdo' => 'phpmail', // sendmail, smtp
				'smtp' => array(
					'host' => '',
					'user' => '',
					'pass' => '',
					'port' => 25,
					'secure' => 'off', // tls, ssl
				),
				'throttling' => array(
					'limit' => 0,
					'on' => false,
					'period' => 'day'
				)
			),
			'social' => array(
				'twitter' => '',
				'facebook'=> '',
				'googleplus' => '',
				'linkedin'=> ''
			),
			'dashboardStats' => 'off', // abox, widget

			'secret' => md5(get_option('blogname').get_option('admin_email').time()),

			'activePages' => array(
				'alreadySubscribedAndVerified' => 0,
				'badEmail' => 0,
				'changeSubscription' => 0,
				'confirmationRequired' => 0,
				'confirmationSucceed' => 0,
				'emailSubscribedNotConfirmed' => 0,
				'unsubscribeSucceed' => 0
			),

			'emailTemplates' => array(
				'addressChanged' => 0,
				'adminSubscriptionEvent' => 0,
				'adminUnsubscribeEvent' => 0,
				'confirmation' => 0,
				'unsubscribe' => 0,
				'welcome' => 0
			),

			'bounced' => array(
				'enabled' => 'off',
				'type' => 'imap', // imap, pop3
				'secure' => 'no', // no, ssl, tls
				'host' => '',
				'port' => '143',
				'username' => '',
				'password' => '',
				'skipLargeMessages' => true,
				'skipThreshold' => 1048576,  // 1Mb
				'removeFromServer' => true,
				'removeAutoreplies' => false
			),

			'base64TrMap' => $this->utils->genTranslationMap(),

			'debug' => true,

			'cleanUnconfirmed' => true,

			'uninstall' => array(
				'deleteSubscribers' => false
			),

			'lang' => $this->lang
		);	
		
		$this->options->load($options);	

		$this->utils->installActionPages($this->lang);
		$this->utils->installSystemEmailTemplates($this->lang);

		$list = newsmanList::findOne('name = %s', array('default'));

		if ( !$list ) {
			$defaultList = new newsmanList();
			$defaultList->save();			
		}

		$this->importWPUsers();
	}

	public function uninstall() {
		global $wpdb;

		$removeSubs = $this->options->get('uninstall.deleteSubscribers');

		if ( $removeSubs ) {
			$lists = newsmanList::findAll();

			foreach ($lists as $list) {
				$wpdb->query("DROP TABLE IF EXISTS $list->tblSubscribers");
			}
			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.newsmanList::$table);
		}

		$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.newsmanEmail::$table);
		$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.newsmanEmailTemplate::$table);		
		$sl = newsmanSentlog::getInstance();
		$wpdb->query("DROP TABLE IF EXISTS ".$sl->tableName);

		$pages = $this->options->get('activePages');
		foreach ($pages as $pageId) {
			wp_delete_post( $pageId, true );
		}

		delete_option('newsman_options');
		delete_option('newsman_version');
		delete_option('newsman_bh_pid');
		delete_option('newsman_bh_pid');
		delete_option('newsman_bh_last_stats');
	}


	public function getEmail($name) {
		$email = array();
		$emlId = $this->options->get('emailTemplates.'.$name);

		$tpl = newsmanEmailTemplate::findOne('id=%d', array($emlId));

		$email['subject'] = do_shortcode( $tpl->subject );

		$content = $this->defineLoop($tpl->p_html);

		$email['html'] = do_shortcode( $content );


		$content = $tpl->plain;
		$email['plain'] = $this->defineLoop($content);
		$email['plain'] = do_shortcode( $email['plain'] );

		return $email;
	}

	public function sendEmail($name) {
		global $newsman_current_subscriber;

		$eml = $this->getEmail($name);

		$this->utils->mail($eml, array(
			'vars' => $newsman_current_subscriber
		));

		return $eml;
	}

	public function notifyAdmin($tplName) {
		global $newsman_current_subscriber;

		$eml = $this->getEmail($tplName);

		$this->utils->mail($eml, array(
			'to' => $this->options->get('sender.email'),
			'vars' => $newsman_current_subscriber
		));

		return $eml;
	}

	public function loadScrtipsAndStyles() {

		$page   = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$type   = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';

		wp_enqueue_style('newsman_menu_icon', NEWSMAN_PLUGIN_URL.'/css/menuicon.css');
		
		$NEWSMAN_PAGE = strpos($page, 'newsman') !== false;

		wp_register_script('newsmanform', NEWSMAN_PLUGIN_URL.'/js/newsmanform.js', array('jquery'), NEWSMAN_VERSION);

		if ( function_exists('wp_enqueue_script') ) {

			if ( defined('WP_ADMIN') ) {
		
				global $wp_scripts;

				do_action('newsman_get_ext_script');

				wp_enqueue_script('jquery-ui-core ');

				wp_enqueue_script('jquery-ui-datepicker');

				wp_enqueue_style('jquery-tipsy-css', NEWSMAN_PLUGIN_URL.'/css/tipsy.css');
				wp_enqueue_script('jquery-tipsy', NEWSMAN_PLUGIN_URL.'/js/jquery.tipsy.js', array('jquery'));

				wp_register_style('newsman-html-editor', NEWSMAN_PLUGIN_URL.'/css/html-editor.css', array(), NEWSMAN_VERSION);
				wp_register_script('newsman-html-editor-js', NEWSMAN_PLUGIN_URL.'/js/newsman-html-editor.js', array('jquery','jquery-ui-core', 'jquery-ui-dialog', 'jquery-tipsy'), NEWSMAN_VERSION);

				wp_register_style('fileuploader-css', NEWSMAN_PLUGIN_URL.'/css/fileuploader.css', array(), NEWSMAN_VERSION);
				wp_register_script('fileuploader-iframe-transport-js', NEWSMAN_PLUGIN_URL.'/js/uploader/jquery.iframe-transport.js', array('jquery'), NEWSMAN_VERSION);	
				wp_register_script('fileuploader-js', NEWSMAN_PLUGIN_URL.'/js/uploader/jquery.fileupload.js', array('jquery', 'jquery-ui-widget', 'fileuploader-iframe-transport-js'), NEWSMAN_VERSION);

				if ( in_array($page, array('newsman-mailbox', 'newsman-templates') ) && $action == 'edit' && $type != 'wp' ) {
					wp_enqueue_style('newsman-html-editor');
					wp_enqueue_script('newsman-html-editor-js');
				}

				wp_register_style('bootstrap', NEWSMAN_PLUGIN_URL.'/css/bootstrap.css');
				wp_register_script('bootstrapjs', NEWSMAN_PLUGIN_URL.'/js/bootstrap.min.js', array('jquery'));
				wp_register_script('director', NEWSMAN_PLUGIN_URL.'/js/director.js');

				if ( $page == 'newsman-subs' ) {
					wp_enqueue_style('fileuploader-css');
					wp_enqueue_script('fileuploader-js');

					if ( $action === 'editlist' ) {
						wp_enqueue_script('newsman-jquery-placeholder', NEWSMAN_PLUGIN_URL.'/js/jquery.placeholder.js', array('jquery'), NEWSMAN_VERSION);
						wp_enqueue_script('newsman-formbuilder', NEWSMAN_PLUGIN_URL.'/js/newsman-formbuilder.js', array('jquery', 'jquery-ui-widget'), NEWSMAN_VERSION);
					}
				}

				if ( isset($_GET['action']) && $_GET['action'] === 'editlist' ) {
					wp_enqueue_script('newsman-ko', NEWSMAN_PLUGIN_URL.'/js/knockout-2.2.0.js', array('jquery'));
					wp_enqueue_script('newsman-ko-mapping', NEWSMAN_PLUGIN_URL.'/js/knockout.mapping.js', array('newsman-ko'));
					wp_enqueue_script('newsman-ko-sortable', NEWSMAN_PLUGIN_URL.'/js/knockout-sortable.min.js', array('newsman-ko'));
				}

				if ( $NEWSMAN_PAGE || defined('INSER_POSTS_FRAME') ) {
					// bootstrap
					wp_enqueue_style('bootstrap');				
					wp_enqueue_script('bootstrapjs');

					wp_enqueue_script('director');		
					// ------ 

					wp_register_style('jq-multiselect', NEWSMAN_PLUGIN_URL.'/js/css/jquery.multiselect.css', array(), NEWSMAN_VERSION);
					wp_enqueue_style('jq-multiselect');

					wp_register_style('jquery-ui-timepicker', NEWSMAN_PLUGIN_URL.'/css/jquery-ui-timepicker-addon.css');
					wp_register_script('jquery-ui-timepicker-js', NEWSMAN_PLUGIN_URL.'/js/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker'));

					wp_enqueue_style('multis-css', NEWSMAN_PLUGIN_URL.'/js/css/multis.css', array(), NEWSMAN_VERSION);
					wp_enqueue_script('multis-js', NEWSMAN_PLUGIN_URL.'/js/jquery.multis.js', array('jquery', 'jquery-ui-widget'), NEWSMAN_VERSION );

					wp_enqueue_script('neouploader-js', NEWSMAN_PLUGIN_URL.'/js/neoUploader.js', array('jquery', 'jquery-ui-widget') );

					// ----
		
					wp_register_script('newsman-jqMiniColors', NEWSMAN_PLUGIN_URL.'/js/jqminicolors/jquery.miniColors.js');
					wp_register_style('newsman-jqMiniColorsCSS', NEWSMAN_PLUGIN_URL.'/js/jqminicolors/jquery.miniColors.css');

					wp_register_script('newsman-ckeditor', NEWSMAN_PLUGIN_URL.'/js/ckeditor/ckeditor.js');
					wp_register_script('newsman-ckeditor-jq', NEWSMAN_PLUGIN_URL.'/js/ckeditor/adapters/jquery.js', array('jquery', 'newsman-ckeditor'));
					wp_register_script('newsman-ck-max-patch', NEWSMAN_PLUGIN_URL.'/js/ck-max-patch.js', array('jquery', 'newsman-ckeditor'));

					wp_register_style('newsman', NEWSMAN_PLUGIN_URL.'/css/newsman.css', array(), NEWSMAN_VERSION);
					

					wp_register_style('newsman-ie9', NEWSMAN_PLUGIN_URL.'/css/newsman-ie9.css', array(), NEWSMAN_VERSION);
					$GLOBALS['wp_styles']->add_data( 'newsman-ie9', 'conditional', 'gte IE 9' );

					wp_register_style('newsman_admin', NEWSMAN_PLUGIN_URL.'/css/newsman_admin.css', array(), NEWSMAN_VERSION);

					wp_register_script( 'jquery-multiselect', NEWSMAN_PLUGIN_URL.'/js/jquery.multiselect.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget'), NEWSMAN_VERSION);
					wp_register_script('newsman-admin', NEWSMAN_PLUGIN_URL.'/js/admin.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-tabs'), NEWSMAN_VERSION);

					wp_enqueue_style('newsman');
					wp_enqueue_style('newsman-ie9');
					wp_enqueue_style('newsman_admin');

					wp_enqueue_script('jquery');
					$ui = $wp_scripts->query('jquery-ui-core');

					wp_enqueue_script("jquery-effects-core");
					wp_enqueue_script('jquery-ui-widget');
					wp_enqueue_script('jquery-ui-tabs');
					wp_enqueue_script('jquery-ui-datepicker');
					wp_enqueue_script('jquery-ui-slider');

					wp_enqueue_script('jquery-ui-sortable');				
					wp_enqueue_script('newsman-admin');

					wp_localize_script( 'newsman-admin', 'newsmanL10n', array(
						/* translators: subscriber type */
						'unconfirmed' => __('Unconfirmed', NEWSMAN),
						/* translators: subscriber type */
						'confirmed' => __('Confirmed', NEWSMAN),
						/* translators: subscriber type */
						'unsubscribed' => __('Unsubscribed', NEWSMAN),
						'error' => __('Error: ', NEWSMAN),
						'error2' => __('Error', NEWSMAN),
						'pleaseSelectEmailsToStop' => __('Please select emails which you want to stop sending of.', NEWSMAN),
						'youHaveSuccessfullyStoppedSending' => __('You have successfully stopped sending of selected emails.', NEWSMAN),
						'pleaseMarkSubsWhichYouWantToUnsub' => __('Please mark subscribers which you want to unsubscribe.', NEWSMAN),
						'youHaveSuccessfullyUnsubscribedSelectedSubs' => __('You have successfully unsubscribed selected subscribers.', NEWSMAN),
						'pleaseMarkSubsWhichYouWantToDelete' => __('Please mark subscribers which you want to delete.', NEWSMAN),
						'youHaveSucessfullyDeletedSelSubs' => __('You have successfully deleted selected subscribers.', NEWSMAN),
						'pleaseMarkSubscribersToChange' => __('Please mark subscribers to change.', NEWSMAN),
						'youHaveSuccessfullyChangedSelSubs' => __('You have successfully changed status of selected subscribers.', NEWSMAN),
						'pleaseMarkSubsToSendConfirmationTo' => __('Please mark subscribers which you want to send confirmation to.', NEWSMAN),
						'youHaveSuccessfullySentConfirmation' => __('You have successfully send confirmation emails.', NEWSMAN),
						'allSubs' => __('All subscribers (#)', NEWSMAN),
						'confirmedSubs' => __('Confirmed (#)', NEWSMAN),
						'unconfirmedSubs' => __('Unconfirmed (#)', NEWSMAN),
						'unsubscribedSubs' => __('Unsubscribed (#)', NEWSMAN),
						'noSubsYet' => __('You have no subscribers yet.', NEWSMAN),
						'sending' => __('Sending', NEWSMAN),
						'checkMe' => __('Check me', NEWSMAN),
						'required' => __('Required', NEWSMAN),
						'chooseAnOption' => __('Choose an option', NEWSMAN),
						'optionOne' => __('new option 1', NEWSMAN),
						'untitled' => __('Untitled', NEWSMAN),
						'youHaveNoEmailsYet' => __('You have no emails yet.', NEWSMAN),
						'youHaveNoFormsYet' => __('You have no forms, yet', NEWSMAN),
						'stSent' => __('Sent', NEWSMAN),
						'stPending' => __('Pending', NEWSMAN),
						'stDraft' => __('Draft', NEWSMAN),
						'stError' => __('Error', NEWSMAN),
						'stInprogress' => __('Sending...'),
						'stStopped' => __('Stopped'),
						'stScheduledOn' => __('Scheduled on', NEWSMAN),
						'youDontHaveAnyTemplates' => __('You don\'t have any templates yet.', NEWSMAN),
						'createOne' => __('Create one?', NEWSMAN),
						'pleaseMarkEmailsForDeletion' => __('Please mark the emails which you want to delete.', NEWSMAN),
						'youHaveSuccessfullyDeletedSelectedEmails' => __('You have successfully deleted selected emails.', NEWSMAN),
						'pleaseMarkTemplatesForDeletion' => __('Please mark the templates which you want to delete.', NEWSMAN),
						'pleaseMarkFormsForDeletion' => __('Please mark the forms which you want to delete.', NEWSMAN),
						'youHaveNoTemplatesYet' => __('You have no templates yet.', NEWSMAN),
						'emlsAll' => __('All emails (#)', NEWSMAN),
						'emlsInProgress' => __('In progress (#)', NEWSMAN),
						'emlsPending' => __('Pending (#)', NEWSMAN),
						'emlsSent' => __('Sent (#)', NEWSMAN),
						'resume' => __('Resume', NEWSMAN),
						'pleaseChooseSubscribersList' => __('Please fill the "To:" field.', NEWSMAN),
						'pleaseSelectEmailsFist' => __('Please select emails first.', NEWSMAN),
						'pleaseSelect' => __('Please select', NEWSMAN),
						/* translators: mailbox view link */
						'vAllEmails' => __('All emails', NEWSMAN),
						/* translators: mailbox view link */
						'vInProgress' => __('In progress', NEWSMAN),
						/* translators: mailbox view link */
						'vDrafts' => __('Drafts', NEWSMAN),
						/* translators: mailbox view link */
						'vPending' => __('Pending', NEWSMAN),
						/* translators: mailbox view link */
						'vSent' => __('Sent', NEWSMAN),
						'sentXofXemails' => __('Sent %d of %d emails', NEWSMAN),
						'status' => __('Status: ', NEWSMAN),
						'emailErrors' => __('Email Errors', NEWSMAN),
						'emailAddress' => __('Email Address', NEWSMAN),
						'errorMessage' => __('Error Message', NEWSMAN),
						'loading' => __('Loading...', NEWSMAN),
						'list' => __('List:', NEWSMAN),
						'bugReport' => __('Bug report', NEWSMAN),
						'loadMore' => __('Load more...', NEWSMAN),
						'sorryNoPostsMathedCriteria' => __('Sorry, no posts matched your criteria.', NEWSMAN),
						'warnCloseToLimit' => __('Warning! You are close to the limit of %d subscribers you can send emails to in the Lite version of the plugin. Please, <a href="%s">upgrade to the Pro version</a> to send emails without limitations.', NEWSMAN),
						'warnExceededLimit' => __('Warning! You exceeded the limit of %d subscribers you can send emails to in the Lite version of the plugin. Please, <a href="%s">upgrade to the Pro version</a> to send emails without limitations.', NEWSMAN)
					) );
				}

				if ( in_array($page, array('newsman-templates', 'newsman-mailbox') ) ) {
					wp_enqueue_script('newsman-ckeditor');
					wp_enqueue_script('newsman-ckeditor-jq');
					wp_enqueue_script('newsman-ck-max-patch');

					wp_enqueue_script('newsman-jqMiniColors');
					wp_enqueue_style('newsman-jqMiniColorsCSS');

					wp_enqueue_style('jquery-ui-timepicker');
					wp_enqueue_script('jquery-ui-timepicker-js');
				}

			
				if  ( (strpos($page, 'newsman') !== false) || defined('INSER_POSTS_FRAME') ) {
					wp_enqueue_script(array('jquery', 'editor', 'thickbox', 'media-upload'));
					wp_enqueue_style('thickbox');

					$url = NEWSMAN_PLUGIN_URL."/css/smoothness/jquery-ui-1.8.20.custom.css";
					wp_enqueue_style('newsman-jquery-ui-smoothness', $url, false, $ui->ver);
				  
				  	wp_enqueue_script('jquery-multiselect');									
				}
			}
		}

		wp_register_style('newsman', NEWSMAN_PLUGIN_URL.'/css/newsman.css', array(), NEWSMAN_VERSION);
		wp_enqueue_style('newsman');

		wp_register_style('newsman-ie9', NEWSMAN_PLUGIN_URL.'/css/newsman-ie9.css', array(), NEWSMAN_VERSION);
		$GLOBALS['wp_styles']->add_data( 'newsman-ie9', 'conditional', 'gte IE 9' );
		wp_enqueue_style('newsman-ie9');

	}

	public function cleanOldUnconfirmed() {
		global $wpdb;

		$lists = newsmanList::findAll();
		foreach ($lists as $list) {
			$sql = "DELETE FROM $list->tblSubscribers WHERE status = ".NEWSMAN_SS_UNCONFIRMED." and DATE_ADD(`ts`, INTERVAL 7 DAY) <= CURDATE()";	
			$result = $wpdb->query($sql);
		}	
		
	}

	public function processPageRequest() {
		global $newsman_current_subscriber;
		global $newsman_current_ucode;
		global $newsman_current_list;

		global $newsman_error_message;
		global $newsman_success_message;

		$use_excerpts = isset($_REQUEST['newsman_use_excerpts']);

		if ( isset($_REQUEST['u']) && !empty($_REQUEST['u']) ) {
			if ( strpos($_REQUEST['u'], ':') === false ) {
				wp_die('Wrong "u" parameter format');
			}

			$newsman_current_ucode = $_REQUEST['u'];

			$uArr = explode(':', $_REQUEST['u']);
			$list = newsmanList::findOne('uid = %s', array($uArr[0]));
			$newsman_current_list = $list;

			$s = $list->findSubscriber("ucode = %s", $uArr[1]);

			if ( !$s ) {
				wp_die('Please, check your link. It seems to be broken.');
			}

			$newsman_current_subscriber = $s->toJSON();
		}
		
		if ( isset($_REQUEST['nwsmn-chsub']) ) {
			if ( !isset($s) ) {
				wp_die( sprintf( __('Cannot change subscriber email. Subscriber with unique code %s is not found.', NEWSMAN ), htmlentities($_REQUEST['u'])) );
			}

			$newEmail = strtolower($_REQUEST['nwsmn-chsub-newemail']);

			$this->utils->emailValid($newEmail, 'DIE_ON_FAILURE');

			if ( $list->findSubscriber("email = %s", $newEmail) ) {
				$newsman_error_message = sprintf(__('Email address %s is already subscribed to the list.', NEWSMAN), $newEmail);
			} else {
				$newsman_success_message = sprintf(__('Your email address %s was successfully changed.', NEWSMAN), $s->email);
				$s->email = $newEmail;
				$s->save();
				$newsman_current_subscriber = $s->toJSON();
			}

			if ( $use_excerpts ) {
				$this->showActionExcerpt('changeSubscription');
			} else {
				//$this->redirect( $this->getLink('changeSubscription', array('u' => $_REQUEST['u'] ) ) );
			}
		}    
		
		if ( isset($_REQUEST['nwsmn-unsubscribe']) ) {
			if ( !isset($s) ) {
				wp_die( sprintf( __('Cannot unsubscribe email. Subscriber with unique code %s is not found.', NEWSMAN ), htmlentities($_REQUEST['u'])) );
			} else {

				$s->unsubscribe();
				$s->save();

				if ( $this->options->get('notifyAdmin') ) {
					$this->notifyAdmin('adminUnsubscribeEvent');
				}

				if ( $this->options->get('sendUnsubscribed') ) {
					$this->sendEmail('unsubscribe');
				}				

				if ( $use_excerpts ) {
					$this->showActionExcerpt('unsubscribeSucceed');
				} else {
					$this->redirect( $this->getLink('unsubscribeSucceed', array('u' => $_REQUEST['u']) ) );
				}
			}

		}
		
		if ( isset($_POST['nwsmn-subscribe']) ) {

			$frm = new newsmanForm($_REQUEST['uid']);
			$res = $frm->parse();

			//Verify Email
			$this->utils->emailValid($res['email'], true);

			$list = $frm->list;
			$newsman_current_list = $frm->list;

			$s = $list->findSubscriber("email = '%s'", $res['email']);

			if ( !$s ) {
				$s = $list->newSub();
				$s->fill($res);
				$s->save();
				$res = -1;
			} else {
				$res = $s->meta('status');
			}


			$newsman_current_subscriber = $s->toJSON();

			$userUID = $list->uid.':'.$s->meta('ucode');

			$newsman_current_ucode = $userUID;

			switch ( $res ) {
				case -1:
					$this->sendEmail('confirmation');
					if ( $use_excerpts ) {
						$this->showActionExcerpt('confirmationRequired');
					} else {
						$this->redirect( $this->getLink('confirmationRequired', array('u' => $userUID ) ) );	
					}					
				break;
				case NEWSMAN_SS_UNCONFIRMED:
					// subscribed but not confirmed
					if ( $use_excerpts ) {
						$this->showActionExcerpt('emailSubscribedNotConfirmed');
					} else {
						$this->redirect( $this->getLink('emailSubscribedNotConfirmed', array('u' => $userUID ) ) );	
					}					
				break;                
				case NEWSMAN_SS_CONFIRMED:
					// subscribed and confirmed
					if ( $use_excerpts ) {
						$this->showActionExcerpt('alreadySubscribedAndVerified');
					} else {
						$this->redirect( $this->getLink('alreadySubscribedAndVerified', array('u' => $userUID ) ) );	
					}					
				break;
				case NEWSMAN_SS_UNSUBSCRIBED:
					// unsubscribed
					$s->subscribe();
					$s->save();

					$this->sendEmail('confirmation');

					if ( $use_excerpts ) {
						$this->showActionExcerpt('confirmationRequired');
					} else {
						$this->redirect( $this->getLink('confirmationRequired', array('u' => $userUID ) ) );	
					}					
				break;
			}
		}
	}

	public function showActionExcerpt($pageName) {
		
		$post = wp_get_single_post( $this->options->get('activePages.'.$pageName) );
		?>
<!DOCTYPE html>
<html>
  <head>
    <title>G-Lock Newsletter Subscription Form</title>
    <!-- Bootstrap -->
    <link href="<?php echo NEWSMAN_PLUGIN_URL; ?>/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo NEWSMAN_PLUGIN_URL; ?>/css/newsman.css" rel="stylesheet">
  </head>
  <body class="wp_bootstrap">
	<div class="form-container">
		<h3><?php echo $post->post_title; ?></h3><br>
		<?php echo do_shortcode($post->post_excerpt); ?>
    </div>
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="<?php echo NEWSMAN_PLUGIN_URL; ?>/js/bootstrap.min.js"></script>
    <script src="<?php echo NEWSMAN_PLUGIN_URL; ?>/js/newsmanform.js"></script>
    <script src="<?php echo NEWSMAN_PLUGIN_URL; ?>/js/jquery.placeholder.js"></script>    
  </body>
</html>	
		<?php		
		die();
	}

	public function putForm($print = true, $listId = false, $horizontal = false) {
		$list = newsmanList::findOne('id = %d', array($listId));
		$frm = new newsmanForm($listId);

		$frm->horizontal = $horizontal;

		$data = '';
																	   
		if ( !$print ) {
			$clsa_form = 'class="newsman-sa-from"';
			$clsa_placehldr = ' newsman-sa-placeholder';
		} else {
			$clsa_form = '';
			$clsa_placehldr = '';
		}

		//$data .= $list->header;
		
		wp_enqueue_script('newsmanform');
		if ( !get_option('newsman_code') ) {
			$data .= '<!-- Powered by WPNewsman '.NEWSMAN_VERSION.' - http://wpnewsman.com/ -->';			
		}

		$id = "newsman-form-".$list->uid;

		$data .= "<form id=\"$id\" ".$clsa_form.' name="newsman-nsltr" action="'.$this->utils->addTrSlash( get_bloginfo('wpurl') ).'" method="post">';

		$data.= $frm->getForm();

		$data .= '</form>';
		
		//$data .= $list->footer;
		
		/*
		* G-Lock WPNewsman has required a great deal of time and effort to develop.
		* We will be happy if you support this development by suggesting G-Lock WPNewsman to other
		* people who could be interested in it, e. g. your friends, colleagues or maybe even your website visitors.
		*
		* G-Lock Software : 2012
		*/
		 
		$nofollow = is_page() ? '' : 'rel="nofollow"';

		if ( !get_option('newsman_code') ) {
			$data .= '<p style="font-size:x-small; line-height:1.5em;">Powered by WPNewsman</p>';		
		}
		
		if ($print) {
			echo $data;
		} else {
			return $data;
		}		
	}

	// events

	public function onAdminHead() {
		//wp_tiny_mce( false ); // true gives you a stripped down version of the editor
		echo '<script>
			NEWSMAN_PLUGIN_URL = "'.NEWSMAN_PLUGIN_URL.'";
			NEWSMAN_BLOG_ADMIN_URL = "'.NEWSMAN_BLOG_ADMIN_URL.'";			
			</script>';

		$mode = '<script>NEWSMAN_LITE_MODE = true;</script>';
		echo apply_filters('newsman_amend_mode', $mode);
	}

	public function onAdminInit() {

		// check version change here

		$doRedirect = true;

		if ( isset($_REQUEST['action']) && $_REQUEST['action']=='activate-plugin' ) {
			$doRedirect = false;
		}
		if ( $this->utils->isUpdate() && $doRedirect ) {
			wp_redirect(NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-mailbox&welcome=1');
		}

		// page=newsman-mailbox&action=compose&type=wp
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
		$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

		if ( $page === 'newsman-mailbox' && $action === 'compose' && $type === 'wp' ) {
			$email = new newsmanEmail();
			if ( isset($type) && $type === 'wp' ) {
				$email->editor = 'wp';
			}
			$email->save();
			wp_redirect(NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-mailbox&action=edit&type=wp&id='.$email->id);
		}

		add_meta_box("newsman-et-meta", __('Alternative Plain Text Body', NEWSMAN), array($this, "metaPlainBody"), "newsman_et", "normal", "default");
	}

	public function onWidgetsInit() {
		require_once(NEWSMAN_PLUGIN_PATH.'/widget.php');
	}

	public function onAdminMenu() {
		global $submenu;

		if ( !current_user_can( 'newsman_wpNewsman' ) ) {
			return;
		}

		$mainLabel = apply_filters('newsman_main_menu_lable', __('WPNewsman Lite', NEWSMAN));

		add_menu_page(
			$mainLabel, 
			$mainLabel, 
			'publish_pages', 
			'newsman-mailbox', 
			array($this, 'pageMailbox'),
			'div'
		);

		add_submenu_page(
			'newsman-mailbox',
			__('Mailbox', NEWSMAN),
			__('Mailbox', NEWSMAN),
			'publish_pages',
			'newsman-mailbox',
			array($this, 'pageMailbox')
		);

		add_submenu_page(
			'newsman-mailbox',
			__('Subscribers', NEWSMAN),
			__('Subscribers', NEWSMAN), 
			'publish_pages', 
			'newsman-subs', 
			array($this, 'pageSubscribers')
		);

		add_submenu_page(
			'newsman-mailbox',
			__('Lists and Forms', NEWSMAN),
			__('Lists and Forms', NEWSMAN), 
			'publish_pages', 
			'newsman-forms', 
			array($this, 'pageForms')
		);

		add_submenu_page(
			'newsman-mailbox',
			__('Email Templates', NEWSMAN),
			__('Email Templates', NEWSMAN),
			'publish_pages',
			'newsman-templates',
			array($this, 'pageTemplates')
		);		

		add_submenu_page(
			'newsman-mailbox',
			__('Settings', NEWSMAN),
			__('Settings', NEWSMAN),
			'publish_pages',
			'newsman-settings',
			array($this, 'pageOptions')
		);

		$label = apply_filters('newsman_upgrade_to_pro', __('Upgrade to Pro', NEWSMAN));
		
		add_submenu_page(
			'newsman-mailbox',
			$label,
			$label,
			'publish_pages', 
			'newsman-pro', 
			array($this, 'pagePro')
		);

		// placing it first in the menu
		for ($i=0, $c = count($submenu['newsman-mailbox']); $i < $c; $i++) { 
			if ( $submenu['newsman-mailbox'][$i][2] === 'newsman-settings' ) {
				$prevPos = $i-1;

				$actionPagesSubmenu = array_splice($submenu['newsman-mailbox'], 0, 1);
				array_splice($submenu['newsman-mailbox'], $prevPos, 0, $actionPagesSubmenu);
				break;
			}
		}		

	}

	public function reAddExcerptMetabox($post_type, $position, $post) {
		if ( $position == 'normal' ) {
			remove_meta_box( 'postexcerpt' , 'newsman_ap' , 'normal' );

			add_meta_box('postexcerpt', __('Excerpt for external forms', NEWSMAN), 'post_excerpt_meta_box', 'newsman_ap', 'normal');
		}
	}

	public function ensureUploadDir() {
		$dirs = wp_upload_dir();
		$ud = $dirs['basedir'].'/wpnewsman';

		if ( !is_dir($ud) ) {
			mkdir($ud);
		}

		return $ud;
	}

	public function onActivate() {
		$this->activation = true;

		// Schedule events
		
		if ( !wp_next_scheduled('newsman_mailman_event') ) {
			wp_schedule_event( time(), '1min', 'newsman_mailman_event');
		}

		if ( !isset($this->wplang) ) {
			$this->setLocale();
		}

		$this->install();
		$this->ensureEnvironment();

		// adding capability
		$role = get_role('administrator');
		if ( $role ) {
			$role->add_cap('newsman_wpNewsman');
		}

		$this->onInit(true);
		flush_rewrite_rules();
	}

	public function ensureEnvironment() {
		global $wpdb;

		// modify lists tables
		$nsTable = newsmanStorable::$table;
		$nsProps = newsmanStorable::$props;

		$lists = newsmanList::findAll();

		foreach ($lists as $list) {
			newsmanStorable::$table = str_replace($wpdb->prefix, '', $list->tblSubscribers);
			newsmanStorable::$props = array(
				'id' => 'autoinc',
				'ts' => 'datetime',
				'ip' => 'string',
				'email'  => 'string',
				'status' => 'int',
				'ucode'  => 'string',
				'fields' => 'text',
				'bounceStatus' => 'text'
			);
			newsmanStorable::ensureDefinition();
		}		

		newsmanEmail::ensureDefinition();

		newsmanStorable::$table = $nsTable;
		newsmanStorable::$props = $nsProps;
	}

	public function onDeactivate() {
	
		wp_clear_scheduled_hook('newsman_mailman_event');

		// removing capability
		$role = get_role('administrator');
		if ( $role ) {
			$role->remove_cap('newsman_wpNewsman');
		}
	}

	public function getLink() {
		
		$pageName = func_get_arg(0);
		
		$params = ( func_num_args() == 2 ) ? func_get_arg(1) : array();

		$link = get_permalink( $this->options->get('activePages.'.$pageName) );

		if ( count($params) ) {
			$link = $this->utils->addTrSlash($link);

			$del = ( strpos($link, '?') === false ) ? '?' : '&';		
			foreach ( $params as $key => $value ) {
				$link .= $del.$key.'='.$value;
				$del = '&';
			}			
		}
		return $link;
	}


	public function onInit($activation = false) {
		new newsmanAJAX();

		$page   = isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		$id     = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

		if ( $page == 'newsman-templates') {
			if ( $action == 'source') {
				$this->echoTemplate();
			} elseif ( $action == 'download' ) {
				$this->downloadTemplate();
			}			
		} elseif ( $page == 'newsman-mailbox' && $action == 'source' ) {
			$this->echoEmail();
		}

 		if ( $action == 'compose-from-tpl' && $id ) {

			$tpl = newsmanEmailTemplate::findOne('id = %d', array( $id ));

			if ( $tpl ) {

				$email = new newsmanEmail();

				$email->type = 'email';	

				$email->subject = $tpl->subject;
				$email->html = $tpl->html;
				$email->plain = $tpl->plain;
				$email->assets = $tpl->assets;
				$email->particles = $tpl->particles;

				$email->status = 'draft';
				$email->created = date('Y-m-d');

				$r = $email->save();

				if ( $r ) {					
					$url = get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox&action=edit&type=html&id='.$email->id;
					$this->redirect( $url );
				} else {
					wp_die($r);
				}				
			}
		}		



		// Action Pages

		$labels = array(
			'name' => _x('Action Pages', 'Action Page', NEWSMAN),
			'singular_name' => _x('Action Page', 'Action Page', NEWSMAN),
			'add_new' => _x('Add New', 'Action Page', NEWSMAN),
			'add_new_item' => _x('Add New Action Page', 'Action Page', NEWSMAN),
			'edit_item' => _x('Edit Action Page', 'Action Page', NEWSMAN),
			'new_item' => _x('New Action Page', 'Action Page', NEWSMAN),
			'view_item' => _x('View Action Page', 'Action Page', NEWSMAN),
			'search_items' => _x('Search Action Pages', 'Action Page', NEWSMAN),
			'not_found' =>  __('Nothing found', 'Action Page', NEWSMAN),
			'not_found_in_trash' => __('Nothing found in the Trash', NEWSMAN),
			'parent_item_colon' => ''
		);

		register_post_type('newsman_ap', array(
			'labels' => $labels,
			'public' => true,
			'show_ui' => true, // UI in admin panel
			'show_in_menu' => 'newsman-mailbox',
			// '_builtin' => false, // It's a custom post type, not built in
			// '_edit_link' => 'post.php?post=%d',
			'capability_type' => 'page',
			'hierarchical' => false,
			'rewrite' => array("slug" => "subscription"), // Permalinks
			'query_var' => "subscription", // This goes to the WP_Query schema
			'supports' => array('title', 'excerpt', 'editor' /*,'custom-fields'*/), // Let's use custom fields for debugging purposes only
			'register_meta_box_cb' => array($this, 'add_shortcode_metabox')
		));

		register_taxonomy_for_object_type('category', 'newsman_ap');

		// these line should stay last 
		$this->loadScrtipsAndStyles();
		$this->processActionLink();
		$this->processPageRequest();

		do_action('newsman_core_loaded');

	}

	public function onInsertPost($post_id, $post = null) {		
		if ( $post->post_type == "newsman_et" ) {

			$key = 'newsman-plain-body';

			$value = @$_POST[$key];
			if ( empty($value) ) {
				delete_post_meta($post_id, $key);
			} else {
				// Update meta
				if ( !update_post_meta($post_id, $key, $value) ) {
					// Or add the meta data
					add_post_meta($post_id, $key, $value);
				}			
			}
		}
	}

	// filters

	static function addRecurrences($schedules) {
		$schedules['1min'] = array('interval' => 60, 'display' => __('Every minute', NEWSMAN) );

		return $schedules;
	}

	public function filterEnterTitleHere( $title ) {
		$screen = get_current_screen();

		if ( 'newsman_ap' == $screen->post_type ) {
			$title = 'Page title';
		} elseif ( 'newsman_et' == $screen->post_type ) {
			$title = 'Subject template';
		} elseif ( 'newsman_email' == $screen->post_type ) {
			$title = 'Email subject';
		}

		return $title;
	}

	public function customLabels( $input ) {
		global $post_type;

		if( is_admin() && 'Publish' == $input && 'newsman_email' == $post_type )
		return 'Send';

		return $input;
	}

	// meta boxes

	public function metaPlainBody() {
		global $post;		
		$plainBody = get_post_meta($post->ID, 'newsman-plain-body', true);	
		include 'views/_meta_plain_body.php';
	}

	// pages

	public function pageOptions() {
		include 'views/options.php';	
	}

	public function pageSubscribers() {
		$id     = isset($_REQUEST['id'])     ? trim($_REQUEST['id'])     : false;
		$action = isset($_REQUEST['action']) ? trim($_REQUEST['action']) : false;

		if ( !$id ) {
			$id = '1';
		}

		if ( $action == 'editlist' ) {
			$list = newsmanList::findOne('id = %d', array($id));
			include 'views/list.php';
		} else {
			include 'views/subscribers.php';	
		}		
	}

	public function pageMailbox() {

		if ( isset($_GET['welcome']) || $this->utils->isUpdate() ) {
			$hideVideo = true;
			if ( !$this->options->get('hideInitialVideo') ) {
				$hideVideo = false;
				$this->options->set('hideInitialVideo', true);
			}

			include('views/welcome.php');
			return;
		}

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		$page 	= isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
		$type 	= isset($_REQUEST['type']) ? $_REQUEST['type'] : false;
		$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

		if ( $action == 'view' ) {

			if ( $id !== false ) {
				$email = newsmanEmail::findOne('id = %d', array( $id ) );
				include 'views/mailbox-email.php';	
			}			
		} elseif ( $action == 'edit' ) {			

			if ( $type == 'wp' ) {
				define('NEWSMAN_EDIT_ENTITY', 'email');

				$email = newsmanEmail::findOne('id = %d', array( $id ) );
				include 'views/mailbox-email.php';	

			} elseif ( $type == 'html' ) {
				$email = newsmanEmail::findOne('id = %d', array( $id ) );
				define('NEWSMAN_EDIT_ENTITY', 'email');
				include 'views/newsman-html-editor.php';
			}
		} else {
			include 'views/mailbox.php';	
		}	
	}

	public function pageTemplates() {
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		$page 	= isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
		$type 	= isset($_REQUEST['type']) ? $_REQUEST['type'] : false;
		$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

		if ( $action == 'new' || $action == 'edit' ) {

			define('NEWSMAN_EDIT_ENTITY', 'template');
			include 'views/newsman-html-editor.php';
		} else {
			include 'views/templates.php';
		}	
	}

	public function pageForms() {
		include 'views/forms.php';
	}

	public function pagePro() {

		if ( has_action('newsman_upgrade_form') ) {
			do_action('newsman_upgrade_form');
		} else {
			$domain = '';
			if ( preg_match('/\w+:\/\/(?:www\.|)([^\/\:]+)/i', get_option('siteurl'), $matches) ) {
				$domain = $matches[1];
			}			
			include 'views/pro.php';
		}
	}

	public function echoTemplate() {
		header("Content-Type: text/html; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Pragma: no-cache"); 
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past		

		$id = $_REQUEST['id'];
		$tpl = newsmanEmailTemplate::findOne('id=%d', array($id));
		if ( !$tpl ) {
			echo '<span style="color: red;">'.__('Error: Email template not found', NEWSMAN).'</span>';
		} else {
			$c = isset($_REQUEST['processed']) ? $tpl->p_html : $tpl->html;
			echo $this->utils->expandAssetsURLs($c, $tpl->assets);
		}
		die();
	}

	public function echoEmail() {
		header("Content-Type: text/html; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Pragma: no-cache"); 
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past		

		$id = $_REQUEST['id'];
		$eml = newsmanEmail::findOne('id=%d', array($id));
		if ( !$eml ) {
			echo '<span style="color: red;">'.__('Error: Email not found', NEWSMAN).'</span>';
		} else {			
			if ( isset($_REQUEST['processed']) ) {

				$list = new newsmanList();
				$sub = $list->findSubscriber("id = %s", 1);
				$c = $eml->renderMessage($sub->toJSON());
				$c = $c['html'];

			} else {
				$c = $eml->html;
			}
			echo $this->utils->expandAssetsURLs($c, $eml->assets);
		}
		die();
	}	

	public function downloadTemplate() {
		$id = $_REQUEST['id'];
		$tpl = newsmanEmailTemplate::findOne('id=%d', array($id));
		if ( !$tpl ) {
			echo '<span style="color: red;">'.__('Error: Email template not found', NEWSMAN).'</span>';
		} else {
			$fileName = $this->utils->sanitizeFileName($tpl->name);
			header("Content-disposition: attachment; filename=$fileName.zip");
			header('Content-type: application/zip');

			$zip = new zipfile();
			//*
			if ( $tpl->assets ) {
				$dir = NEWSMAN_PLUGIN_PATH.'/email-templates/'.$tpl->assets.'/';
				if ( $handle = opendir($dir) ) {
					while (false !== ($entry = readdir($handle))) {
						if ($entry != "." && $entry != "..") {
							$zip->addFile( file_get_contents($dir.$entry), $fileName.'/'.$entry );
						}
					}
					closedir($handle);
				}
			}
			//*/

			$zip->addFile($tpl->html, $fileName.'/'.$fileName.'.html');

			echo $zip->file();	
		}
		die();
	}

	public function getEmailToAsOptions() {
		$options = '';

		$eml = newsmanEmail::findOne('id = %d', array($_REQUEST['id']));

		if ( $eml ) {
			if ( is_array($eml->to) ) {
				foreach ($eml->to as $to) {
					$options .= "<option selected=\"selected\" value=\"$to\">$to</option>";
				}
			}
		}
		return $options;
	}


	// Dashboard stats

	public function addDashboardWidget() {
		global $wp_registered_widgets;
		if ( !isset($wp_registered_widgets['dashboard_glnt']) ) {
			return $widgets;
		}
		$w1 = array_slice($widgets, 0, 1);
		$w2 = array_slice($widgets, 1);
		return array_merge($w1, array('dashboard_glnt'), $w2);
	}

	public function registerDashboardWidget() {
		global $newsman_full_plugin_name;
		wp_add_dashboard_widget('dashboard_glnt', __('G-Lock WPNewsman', NEWSMAN), array($this, 'showStatsAsWidget'));
	}

	private function putStats($abox = false) {
		global $wpdb;

		echo '<style>
		        table.newsman-dboard-summary {    
		            border: 1px solid #eeeeee;
		            
		            margin: 10px 1px 1px 1px;                    
		        }
		        
		        table.newsman-dboard-summary td, table.newsman-dboard-summary th {
		            padding: 3px;
		            font-weight: normal;
		        }                       
		        table.newsman-dboard-summary thead tr {
		            background: #EBEBEB;
		            font-size: 12px;                    
		        }        
		        table.newsman-dboard-summary tbody td {
		            text-align: center;
		        }
		        p.glock_sub {
		            color:#777777;
		            font-style:italic;
		            font-family:Georgia,serif;
		            margin:-10px;                
		        }
		      </style>';         

		    if ( $abox ) {
		    	echo '<div style="margin-top: 1em;">';
		    } else {
		    	echo '<div>';	
		    }		    
			    echo '<div class="youhave" style="clear:both; overflow:hidden;">';        
				    echo '<p class="glock_sub" style="margin: 0; float:left;">G-Lock WPNewsman subscription summary</p>';
				    echo '<a style="line-height:140%; float:right" href="admin.php?page=newsman-subs">Manage subscribers</a>';
			    echo '</div>';
		    
		    $tod_subs = 0;
		    $ytd_subs = 0;
		    $total_subs = 0;
		    $total_unsubs = 0;
		    $total_unconf = 0;

			$lists = newsmanList::findAll();
			$listsNames = array();

		    echo '
		    <div style="overflow-y: auto;">     
		    <table style="width:99%;" class="newsman-dboard-summary">
		        <thead>
		            <tr>
		            	<th>List name</th>
		                <th style="width: 100px;">Today confirmed</th>
		                <th style="width: 120px;">Yesterday confirmed</th>
		                <th style="width: 90px;">Total confirmed</th>
		                <th style="width: 105px;">Total unconfirmed</th>
		                <th style="width: 110px;">Total unsubscribed</th>
		            </tr>
		        </thead>
		        <tbody>';


			$lists = newsmanList::findAll();
			$listsNames = array();

			foreach ($lists as $list) {
				$s = $list->getStats(false, 'extended');
				echo '<tr>
					<th>'.$list->name.'</th>
					<td>'.$s['confirmedToday'].'</td>
					<td>'.$s['confirmedYesterday'].'</td>
					<td style="color: green;">'.$s['confirmed'].'</td>
					<td style="color: orange;">'.$s['unconfirmed'].'</td>
					<td style="color: red;">'.$s['unsubscribed'].'</td>					
				</tr>';
			}

		   	echo '</tbody>
		    </table></div></div>';		
	}

	public function showStatsInLatestActivity() {
		$this->putStats(true);
	}

	public function showStatsAsWidget($sidebar_args) {
		if ( is_array($sidebar_args) ) {
		    extract($sidebar_args, EXTR_SKIP);
		}

		echo $before_widget;
		echo $before_title;
		echo $widget_name;
		echo $after_title;

		$this->putStats();

		echo $after_widget;
	}

	public function add_shortcode_metabox() {
		$title = 'WPNewsman <a style="font-size: inherit;" href="http://codex.wordpress.org/Shortcode_API">shortcodes</a>';
		add_meta_box('newsman_ap_shortcodes', $title, array($this, 'putApShortcodesMetabox'), 'newsman_ap', 'side', 'default');

		// page_attributes_meta_box
	    add_meta_box('newsman_ap_template', __('Post Template'), array($this, 'post_template_meta_box'), 'newsman_ap', 'side', 'core');
	    //add_meta_box('newsman_ap_template', __('Post Template'), 'page_attributes_meta_box', 'newsman_ap', 'side', 'core');
	}

	public function putApShortcodesMetabox() {

		$link = '<a href="http://wpnewsman.com/documentation/short-codes-for-email-messages/">'.__('Click here', NEWSMAN).'</a>';

		?>	
		<p><?php _e('You can use these shortcode macros to add the unsubscribe and update subscription links to your message:', NEWSMAN); ?></p>
		<ul class="unstyled shortcodes-list">
		<li><code>[newsman link="unsubscribe"]</code></li>
		<li><code>[newsman link="update-subscription"]</code></li>
		</ul>
		<p><?php _e('and these shortcode macros to add links to your social profiles (enter the URLs of your social profiles in the plugin Settings):', NEWSMAN);  ?></p>
		<ul class="unstyled shortcodes-list">
			<li><code>[newsman profileurl="twitter"]</code></li>
			<li><code>[newsman profileurl="googleplus"]</code></li>
			<li><code>[newsman profileurl="linkedin"]</code></li>
			<li><code>[newsman profileurl="facebook"]</code></li>
		</ul>
		
		<p><?php echo sprintf( __('%s for more shortcode macros supported by WPNewsman.', NEWSMAN), $link ); ?></p>
		<?php
	}

	public function onWPHead() {
		global $post_type;

		if ( $post_type === 'newsman_ap' ) {
			echo '<meta name="robots" content="noindex,nofollow">';
		}
	}

	public function listNamesAsJSArr() {
		$names = '[';
			$lists = newsmanList::findAll();

			$c = '';

			foreach ($lists as $list) {
				$names .= $c.'"'.addslashes($list->name).'"';
				$c = ',';
			}

		$names .= ']';
		return $names;
	}

	public function pre_content_message($content) {
		global $newsman_error_message;
		global $newsman_success_message;
		$msg = '';

		if ( $newsman_error_message || $newsman_success_message ) {
			$class = $newsman_error_message ? 'newsman-msg-error' : 'newsman-msg-success';
			$str = $newsman_error_message ? $newsman_error_message : $newsman_success_message;
			$msg = '<div class="'.$class.'"><span>'.htmlentities($str).'</span></div>';
		}

		return $msg . $content;
	}

	public function showAdminNotifications() {

		$hideForLang = $this->options->get('hideLangNotice');

		if ( $this->lang !== $this->wplang && $this->wplang != $hideForLang ) {
			include('views/_an_locale_changed.php');	
		}

		if ( $hideForLang != $this->wplang ) {
			$this->options->set('hideLangNotice', false);			
		}
	}

	public function putListSelect($showAddNew = false) {
		$u = newsmanUtils::getInstance();

		$selectedId = false;

		if ( isset($_GET['action']) && $_GET['action'] === 'editlist' && isset($_GET['id']) ) {
			$selectedId = intval($_GET['id']);
		}
		$lists = $u->getListsSelectOptions($selectedId, $showAddNew);

		echo ' <label style="display: inline;" for="newsman-lists">'.__('List: ', NEWSMANP).'</label> <select id="newsman-lists" name="list">'.$lists.'</select>';
	}

	/************************************************/
	/*		Post template for action pages  		*/
	/************************************************/

	function post_template_meta_box($post) {
		if ( $post && $post->post_type === 'newsman_ap' ) {
			$template = get_post_meta($post->ID,'_post_template',true);
		}		
	    ?>
		<label class="screen-reader-text" for="post_template"><?php _e('Page Template') ?></label>
		<select name="post_template" id="post_template">
			<option value='default'><?php _e('Default Template'); ?></option>
			<?php page_template_dropdown($template); ?>
		</select>
		<?php
	}

	function save_ap_template($post_id, $post) {
		if ( $post->post_type == 'newsman_ap' && !empty($_POST['post_template']) ) {
			update_post_meta($post->ID,'_post_template',$_POST['post_template']);	
		}		
	}	

	function set_custom_template_for_action_page($template) {
		global $post;

		if ( $post && $post->post_type === 'newsman_ap' ) {
			$post_template = get_post_meta($post->ID,'_post_template',true);

			if ( !empty($post_template) && $post_template != 'default' ) {
				$template = get_stylesheet_directory() . "/{$post_template}";	
			}
		}

		return $template;
	}


	/************************************************/
	/*		    	Importing WP Users 				*/
	/************************************************/

	public function importWPUsers() {
		global $wpdb;

		$limit = 500;
		$offset = 0;

		$sql = "
			SELECT
				$wpdb->users.user_email as email,
				$wpdb->users.user_registered as registered,
				tbl_1.meta_value AS firstname,
				tbl_2.meta_value AS lastname
			FROM $wpdb->users
				LEFT JOIN $wpdb->usermeta AS tbl_1 ON ( $wpdb->users.ID = tbl_1.user_id	AND tbl_1.meta_key =  'first_name' ) 
				LEFT JOIN $wpdb->usermeta AS tbl_2 ON ( $wpdb->users.ID = tbl_2.user_id	AND tbl_2.meta_key =  'last_name' ) 
			LIMIT %d, %d
		";

		$list = newsmanList::findOne('name = %s', array('wp-users'));
		if ( !$list ) {
			$list = new newsmanList('wp-users');
			$list->save();

			while ( $users = $wpdb->get_results( $wpdb->prepare($sql, $offset, $limit) ) ) {

				foreach ($users as $user) {

					$s = $list->newSub();
					$s->fill(array(
						'email' 		=> $user->email,
						'first-name' 	=> $user->firstname,
						'last-name' 	=> $user->lastname
					));
					$s->setDate($user->registered);

					$s->confirm();
					$s->save();
				}
				$offset += $limit;
			}
		}
	}	

}

if ( !function_exists('nwsmn_echo_option') ) {
	function nwsmn_get_option($name) {
		$g = newsman::getInstance();
		return $g->options->get($name);
	}
}

if ( !function_exists('nwsmn_get_prop') ) {
	function nwsmn_get_prop($name) {
		$g = newsman::getInstance();
		return $g->$name;
	}
}

function newsmanCheckedValue($htmlVal, $checked, $applyFalsy = false) {
	if ( !$checked && $applyFalsy ) {		
		echo 'checked="Fchecked"';
	} else {
		echo ( $checked === $htmlVal ) ? 'checked="checked"' : '';	
	}	
}

/**
 * Outputs options tabs
 * @prop $tabDefs array( array( 'title' => 'Title', 'id' => 'title-id' ), ... )
 */
function newsmanOutputTabs($tabDefs) {
	$tabDefs = apply_filters('newsman_options_tabs', $tabDefs);

	$class = ' class="active"';
	foreach ($tabDefs as $tab) {
		echo '<li'.$class.'><a data-toggle="tab" href="#'.$tab['id'].'">'.$tab['title'].'</a></li>';
		$class = '';
	}
}

function newsman_register_worker($className) {
	$n = newsman::getInstance();
	$n->register_worker($className);
}

function newsmanGetDefaultForm() {
	$n = newsman::getInstance();
	return $n->getDefaultForm();
}
