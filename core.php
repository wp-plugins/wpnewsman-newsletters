<?php

require_once(__DIR__.DIRECTORY_SEPARATOR.'autoloader.php');

require_once(__DIR__.DIRECTORY_SEPARATOR.'ajaxbackend.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'lib/emogrifier.php');

global $newsman_checklist;
global $newsman_error_message;
global $newsman_success_message;

do_action('wpnewsman_add_ajax_handlers');

function wpnewsmanFilterSchedules($schedules) {
	$schedules['1min'] = array('interval' => 60, 'display' => __('Every minute', NEWSMAN) );

	return $schedules;
}

add_filter('cron_schedules', 'wpnewsmanFilterSchedules');

function wpnewsman_redefine_locale($locale) {
	$localeSwitchingMethods = array( 'newsmanAjGetActivePageTranslation', 'newsmanAjInstallSystemEmailTemplatesForList' );
	if (
		isset($_REQUEST['action'])
		&& in_array($_REQUEST['action'], $localeSwitchingMethods)
		&& isset($_REQUEST['loc'])
		) {
		$locale = $_REQUEST['loc'];
	}	
    return $locale;
}
add_filter('plugin_locale','wpnewsman_redefine_locale',10);  

function wpnewsman_loadstring() {
	// The "plugin_locale" filter is also used in load_plugin_textdomain()
	$domain = NEWSMAN;
	$loc = apply_filters('plugin_locale', get_locale(), $domain);

	load_textdomain($domain, WP_LANG_DIR.'/wpnewsman/'.$domain.'-'.$loc.'.mo');
	load_plugin_textdomain($domain, FALSE, dirname(plugin_basename(NEWSMAN_PLUGIN_MAINFILE)).'/languages/');	

	do_action('wpnewsman_load_strings');
}
add_action('plugins_loaded', 'wpnewsman_loadstring');

if ( function_exists('mb_internal_encoding') ) {
	mb_internal_encoding("UTF-8");
}

// $loc = get_locale()."UTF-8";
// putenv("LANG=$loc");
// $loc = setlocale(LC_ALL, $loc);

class newsman {

	var $pluginName = 'G-Lock WPNewsman';
	var $version = NEWSMAN_VERSION;

	var $options;
	var $utils;
	var $mailman;
	var $activation = false;

	var $analytics;

	var $registeredWorkers = array();

	var $linkTypes = array('confirmation', 'resend-confirmation', 'unsubscribe', 'email', 'unsubscribe-confirmation');

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

		$this->analytics = newsmanAnalytics::getInstance();
		$this->options = newsmanOptions::getInstance();

		if ( !defined('NEWSMAN_DEBUG') ) {
			define('NEWSMAN_DEBUG', $this->options->get('debug'));
		}

		$this->utils = newsmanUtils::getInstance();
		$this->mailman = newsmanMailMan::getInstance();
		$this->wm = newsmanWorkerManager::getInstance();

		$u = newsmanUtils::getInstance();

		$newsman_error_message = '';
		$newsman_success_message = '';

		add_action('init', array($this, 'onInit'));

		add_action('admin_enqueue_scripts', array($this, 'onAdminEnqueueScripts'), 99);

		add_action('newsman_admin_page_header', array($this, 'showAdminNotifications'));
		add_action('newsman_events_mode_changed', array($this, 'eventsModeChanged'));

		//add_filter('cron_schedules', array($this, 'addRecurrences'));


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

			add_shortcode('newsman_on_web', array($this, 'newsmanShortCodeOnWeb'));
			add_shortcode('newsman_not_on_web', array($this, 'newsmanShortCodeNotOnWeb'));
			add_shortcode('newsman_in_email', array($this, 'newsmanShortCodeNotOnWeb'));
			add_shortcode('newsman_has_thumbnail', array($this, 'newsmanShortCodeHasThumbnail'));
			add_shortcode('newsman_no_thumbnail', array($this, 'newsmanShortCodeNoThumbnail'));
		}

		add_action('do_meta_boxes', array($this, 'reAddExcerptMetabox'), 10, 3);

		add_action('save_post', array($this, 'save_ap_template'), 10, 2);

		add_filter('single_template', array($this, 'set_custom_template_for_action_page'));

		do_action('wpnewsman_add_actions');

	}

	public function onTemplateRedirect() {
		global $post;
		if ( isset($post) && $post->post_type == 'newsman_ap' ){
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
		if ( $this->options->get('cleanUnconfirmed') ) {
			$this->cleanOldUnconfirmed();
		}

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
			return false;
		}

		return $t;
	}

	private function redirect($link){
		header('Cache-Control: no-cache'); // workaround for Chrome redirect caching bug
		wp_redirect($link, 303);
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

    	if ( is_null($content) ) { return ''; }

    	if ( !defined('EMAIL_WEB_VIEW') ) {
    		return do_shortcode($content);
    	} else {
    		return '';
    	}
    }    

	public function newsmanShortCode($attr, $content = null) {
		global $newsman_current_subscriber;
		global $newsman_post_tpl;
		global $newsman_loop_post;
		global $newsman_loop_post_nr;

		global $newsman_current_email;

		extract($attr);

		//newsmanShortCode

		if ( isset($fn) ) {
			$fn_args = ( isset($args) ) ? explode(',', $args) : array();
			var_dump($fn);
			return call_user_func_array($fn, $fn_args);
		}

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
			return ( !!get_option('newsman_code') && defined('NEWSMANP') ) ? '' : '<a href="http://wpnewsman.com/?pk_campaign=freemium-plugin"><img border="0" src="'.NEWSMAN_PLUGIN_URL.'/img/newsman-badge.png" /></a>';
		}

		if ( isset($badge2) || in_array('badge2', $attr) ) {
			return ( !!get_option('newsman_code') && defined('NEWSMANP') ) ? '' : '<a style="display:block; text-align: center;" href="http://wpnewsman.com/?pk_campaign=freemium-plugin"><img border="0" src="'.NEWSMAN_PLUGIN_URL.'/img/newsman-badge2.png" /></a>';
		}


		if ( isset($newsman_current_email) && ( isset($subject) || in_array('subject', $attr) ) ) {
			return $newsman_current_email->subject;
		}

		if ( !empty($post) ) {
			if ( !is_object($newsman_loop_post) ) {
				return '';
			}
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
				$c = $this->utils->cutScripts($newsman_loop_post->post_content);
				return $this->utils->fancyExcerpt($c, $words);
			} else if ( $post == 'thumbnail' ) {
				$s = isset($size) ? $size : null;
				$url = $this->utils->getPostThumbnail($newsman_loop_post, $s);

				if ( !$url && isset($placeholder) ) {
					$url = $placeholder;
				}

				return $url;
			} else if ( $post == 'firstimage' ) {
				$url = $this->utils->getFirstImage($newsman_loop_post);
				return $url ? $url : '';
			} else if ( $post == 'post_content' ) {
				return $this->utils->cutScripts($newsman_loop_post->post_content);
			} else if ( property_exists($newsman_loop_post, $post) ) {
				return $newsman_loop_post->$post;
			}
		}

		if ( !empty($sub) ) {
			if ( $sub == '*' ) {

				$r = '';

				foreach ($newsman_current_subscriber as $key => $value) {
					if ( isset($tag) ) {
						$r .= "<$tag>$key : $value</$tag>\n";
					} else {
						$r .= "$key : $value\n";
					}
				}

				return $r;

			} else {
				if ( isset($newsman_current_subscriber[$sub]) ) {
					return $newsman_current_subscriber[$sub];
				} else if ( $sub == 'subscribed' ) {
					$subscr_time = strtotime($newsman_current_subscriber['ts']);				
					return isset($format) ? date($format, $subscr_time) : date('D, d M Y H:i:s', $subscr_time);
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
				case 'blogname': return html_entity_decode(get_option('blogname'), ENT_QUOTES);
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


		if ( isset($me) ) {
			if ( $me == 'email' ) {
				return $this->options->get('sender.email');
			} elseif ( $me == 'name' ) {
				return $this->options->get('sender.name');
			}			
		}

		if ( isset($link) ) {
			return $this->getActionLink($link, false, isset($ext));
		}

		return $content ? $content : '';
	}

	public function newsmanShortCodeHasThumbnail($attr, $content = null) {
		global $newsman_show_thumbnail_placeholders;
		global $newsman_loop_post;

		if ( is_null($content) ) { return ''; }

		// if we have newsman_show_thumbnail_placeholders option enabled
		// we show the image block anyway
		if ( isset($newsman_show_thumbnail_placeholders) && $newsman_show_thumbnail_placeholders ) {
			return do_shortcode($content);
		} else {
			// we check if the post has an image
			// and show the image block accordingly
			$thumbnailUrl = $this->utils->getPostThumbnail($newsman_loop_post);
			return $thumbnailUrl ? do_shortcode($content) : '';
		}
	}

	public function newsmanShortCodeNoThumbnail($attr, $content = null) {
		global $newsman_show_thumbnail_placeholders;
		global $newsman_loop_post;

		if ( is_null($content) ) { return ''; }

		// we check if the post has an image
		// and show the image block accordingly
		$thumbnailUrl = $this->utils->getPostThumbnail($newsman_loop_post);
		return $thumbnailUrl ? '' : do_shortcode($content);

	}	

	public function shortCode($attrs) {

		$horizontal = in_array('horizontal', array_values($attrs));

		return $this->putForm(false, $attrs['id'], $horizontal);
	}

	// links

	public function getActionLink($type, $only_code = false, $externalForm = false, $dropEmailPart = false) {
		global $newsman_current_subscriber;
		global $newsman_current_email;
		global $newsman_current_list;

		$ucode = $newsman_current_subscriber['ucode'];

		$blogurl = get_bloginfo('wpurl');

		if ( in_array($type, $this->linkTypes) ) {

			if ( $only_code ) {
				return $newsman_current_list->uid.':'.$ucode;
			}

			$link = "$blogurl/?newsman=$type&code=".$newsman_current_list->uid.':'.$ucode;

			if ( isset($newsman_current_email) && in_array($type, array('email', 'unsubscribe', 'unsubscribe-confirmation')) && !$dropEmailPart ) {
				$link.='&email='.$newsman_current_email->ucode;
			}

			if ( $externalForm ) {
				$link .= '&extForm=1';
			}

			if ( isset($_REQUEST['_form_lang']) ) {
				$link .= '&_form_lang='.$_REQUEST['_form_lang'];
			}

			$link = apply_filters('newsman_apply_analytics', $link);

			return $link;
		} else {
			return "#link_type_".$type."_not_implemented";
		}
	}

	public function trackUnsubscribe() {

		global $newsman_current_subscriber;		
		global $newsman_current_email;
		global $newsman_current_list;

		if ( !isset($newsman_current_email) || !is_object($newsman_current_email) ) { return; }

		$sd = new newsmanAnSubDetails();
		$sd->emailId = $newsman_current_email->id;
		$sd->listId = $newsman_current_list->id;
		$sd->subId = $newsman_current_subscriber['id'];
		$sd->emailAddr = $newsman_current_subscriber['email'];
		$sd->ip = $this->utils->peerip();

		$res = $sd->unsubscribe();

		$this->utils->log('[trackUnsubscribe] newsmanAnSubDetails->unsubscribe res %d', $res);

		if ( $res === NEWSMAN_UNIQUE_OPEN ) { // unique operation
			$tl = new newsmanAnTimeline();
			$tl->emailId = $newsman_current_email->id;
			$tl->unsubscribe();
			$tl->open();
			$newsman_current_email->incOpens();
		}


		$newsman_current_email->incUnsubscribes();
	}

	public function processActionLink() {
		global $newsman_current_subscriber;		
		global $newsman_current_email;
		global $newsman_current_list;
		global $newsman_current_ucode;

		if ( isset($_REQUEST['newsman']) && in_array($_REQUEST['newsman'], $this->linkTypes) ) {
			$type = $_REQUEST['newsman'];

			if ( isset($_REQUEST['email']) ) {
				$newsman_current_email = newsmanEmail::findOne('ucode = %s', array($_REQUEST['email']));
			}

			if ( isset($_REQUEST['code']) && strpos($_REQUEST['code'], ':') !== false ) {

				// MSHTML lib bad behaviour workaround
				$c = $_REQUEST['code'];
				$cLastChar = strlen($c)-1;
				if ( $c[$cLastChar] === '/' ) {
					$_REQUEST['code'] = substr($c, 0, $cLastChar);
				}
				// ----

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
			} else {
				$newsman_current_list = new newsmanList('Temporary');
				$newsman_current_subscriber = $newsman_current_list->newSub()->toJSON();
			}

			$use_excerpts = isset($_REQUEST['extForm']);

			switch ( $type ) {

				case 'confirmation': 
					if ( $s->is_confirmed() ) {
						$this->redirect( $this->getLink('alreadySubscribedAndVerified', array('u' => $_REQUEST['code'] ) ) );
					} else {
						$s->confirm();
						$s->save();
						if ( $this->options->get('sendWelcome') ) {
							$this->sendEmail($list->id, NEWSMAN_ET_WELCOME);
						}
						if ( $this->options->get('notifyAdmin') ) {
							$this->notifyAdmin($list->id, NEWSMAN_ET_ADMIN_SUB_NOTIFICATION);
						}	

						if ( $use_excerpts ) {
							$this->showActionExcerpt('confirmationSucceed');
						} else {
							$this->redirect( $this->getLink('confirmationSucceed', array('u' => $_REQUEST['code'] ) ) );	
						}						
					}
					break;					
				case 'resend-confirmation':
					$this->sendEmail($list->id, NEWSMAN_ET_CONFIRMATION);

					if ( $use_excerpts ) {
						$this->showActionExcerpt('confirmationRequired');
					} else {
						$this->redirect( $this->getLink('confirmationRequired', array('u' => $_REQUEST['code'] ) ) );
					}
					break;
				case 'unsubscribe':

					if ( $this->options->get('useDoubleOptout') ) {

						$this->sendEmail($list->id, NEWSMAN_ET_UNSUBSCRIBE_CONFIRMATION);

						if ( $use_excerpts ) {
							$this->showActionExcerpt('unsubscribeConfirmation');
						} else {
							$this->redirect( $this->getLink('unsubscribeConfirmation', array('u' => $_REQUEST['code'] ) ) );	
						}

					} else {
						$s->unsubscribe();
						$s->save();						

						if ( $this->options->get('notifyAdmin') ) {
							$this->notifyAdmin($list->id, NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION);
						}

						if ( $this->options->get('sendUnsubscribed') ) {
							$this->sendEmail($list->id, NEWSMAN_ET_UNSUBSCRIBE);
						}

						$this->trackUnsubscribe();

						if ( $use_excerpts ) {
							$this->showActionExcerpt('unsubscribeSucceed');
						} else {
							$this->redirect( $this->getLink('unsubscribeSucceed', array('u' => $_REQUEST['code'] ) ) );	
						}						
					}
					break;

				case 'unsubscribe-confirmation':
						$s->unsubscribe();
						$s->save();						

						if ( $this->options->get('notifyAdmin') ) {
							$this->notifyAdmin($list->id, NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION);
						}

						if ( $this->options->get('sendUnsubscribed') ) {
							$this->sendEmail($list->id, NEWSMAN_ET_UNSUBSCRIBE);
						}

						$this->trackUnsubscribe();

						if ( $use_excerpts ) {
							$this->showActionExcerpt('unsubscribeSucceed');
						} else {
							$this->redirect( $this->getLink('unsubscribeSucceed', array('u' => $_REQUEST['code'] ) ) );
						}
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
							header("Content-type: text/html; charset=UTF-8");
							
							// if dummy subscriber
							if ( !$newsman_current_subscriber['email'] ) {
								$eml->emailAnalytics = false;	
							}

							$r = $eml->renderMessage($newsman_current_subscriber);
							echo $this->utils->processAssetsURLs($r['html'], $eml->assetsURL);
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
			'notificationEmail' => get_option('admin_email'),
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

			'useDoubleOptout' => false,

			'secret' => md5(get_option('blogname').get_option('admin_email').time()),
			'pokebackKey' => md5(get_bloginfo('wpurl').get_option('admin_email').time()),
			'apiKey' => sha1(sha1(microtime()).'newsman_api_key_salt'.get_option('admin_email')),

			'activePages' => array(
				'alreadySubscribedAndVerified' => 0,
				'badEmail' => 0,
				'confirmationRequired' => 0,
				'confirmationSucceed' => 0,
				'emailSubscribedNotConfirmed' => 0,
				'unsubscribeSucceed' => 0,
				'unsubscribeConfirmation' => 0
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

			'debug' => false,

			'cleanUnconfirmed' => false,

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

		$this->utils->installStockTemplates();

		$this->importWPUsers();
	}

	public function uninstall($removeSubs = false) {
		global $wpdb;

		if ( $removeSubs ) {
			$lists = newsmanList::findAll();

			foreach ($lists as $list) {
				$wpdb->query("DROP TABLE IF EXISTS $list->tblSubscribers");
			}
			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.newsmanList::$table);
		}

		newsmanEmail::dropTable();
		newsmanEmailTemplate::dropTable();

		newsmanAnClicks::dropTable();
		newsmanAnLink::dropTable();
		newsmanAnSubDetails::dropTable();
		newsmanAnTimeline::dropTable();

		newsmanLocks::dropTable();
		newsmanWorkerBase::dropTable();

		$sl = newsmanSentlog::getInstance();
		$wpdb->query("DROP TABLE IF EXISTS ".$sl->tableName);

		$pages = $this->options->get('activePages');
		foreach ($pages as $pageId) {
			wp_delete_post( $pageId, true );
		}

		$this->removeUploadDir();
		$this->utils->uninstallNewsmanMuPlugin();

		delete_option('newsman_options');
		delete_option('newsman_version');
		delete_option('newsman_old_version');
		delete_option('newsman_completed_migrations');
		delete_option('newsman_bh_pid');
		delete_option('newsman_bh_pid');
		delete_option('newsman_bh_last_stats');
		delete_option('NEWSMAN_DOING_UPDATE');
	}


	public function getTemplate($listId, $tplType) {
		$email = array();

		$tpl = newsmanEmailTemplate::findOne('`assigned_list` = %d AND `system_type` = %d', array($listId, $tplType));

		if ( $tpl ) {

			$email['subject'] = do_shortcode( $tpl->subject );
			$email['html']    = do_shortcode( $tpl->p_html );
			$email['plain']   = do_shortcode( $tpl->plain );

			return $email;
		}

		return null;
	}

	public function sendEmail($listId, $tplType) {
		global $newsman_current_subscriber;

		$eml = $this->getTemplate($listId, $tplType);

		$this->utils->mail($eml, array(
			'vars' => $newsman_current_subscriber
		));

		return $eml;
	}

	public function createReConfirmEmail($listId) {
		$list = newsmanList::findOne('id = %d', array($listId));

		$tpl = newsmanEmailTemplate::findOne('`assigned_list` = %d AND `system_type` = %d', array($listId, NEWSMAN_ET_RECONFIRM));

		if ( $tpl ) {
			$email = new newsmanEmail();
			$email->emailAnalytics = false;

			$email->type = 'email';	

			$email->to = array($list->name.'/UNCONFIRMED');

			$email->subject = $tpl->subject;
			$email->html = $tpl->html;
			$email->plain = $tpl->plain;
			$email->assetsURL = $tpl->assetsURL;
			$email->assetsPath = $tpl->assetsPath;
			$email->particles = $tpl->particles;

			$email->status = 'pending'; // $eml->status = 'pending';

			$email->save();

			// starting sender for pending emails
			$this->mailman();

			return $email;
		}
		return false;
	}

	public function notifyAdmin($listId, $tplType) {
		global $newsman_current_subscriber;

		$eml = $this->getTemplate($listId, $tplType);

		$this->utils->mail($eml, array(
			'to' => $this->options->get('notificationEmail'),
			'vars' => $newsman_current_subscriber
		));

		return $eml;
	}

	public function loadScrtipsAndStyles() {

		global $wp_version;

		$isNew38Style = (intval(preg_replace('/[^\d]+/i', '', $wp_version)) >= 38);

		$page   = isset($_REQUEST['page']) ? $_REQUEST['page'] : '';
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
		$type   = isset($_REQUEST['type']) ? $_REQUEST['type'] : '';

		wp_enqueue_style('newsman_menu_icon', NEWSMAN_PLUGIN_URL.'/css/menuicon.css');

		// this scripts should be loaded on all pages.
		wp_register_script('newsmanform', NEWSMAN_PLUGIN_URL.'/js/newsmanform.js', array('jquery'), NEWSMAN_VERSION);
		wp_enqueue_script('newsman-jquery-placeholder', NEWSMAN_PLUGIN_URL.'/js/jquery.placeholder.js', array('jquery'), NEWSMAN_VERSION);		

		wp_localize_script( 'newsmanform', 'newsmanformL10n', array(
			'pleaseFillAllTheRequiredFields' => __('Please fill all the required fields.', NEWSMAN),
			'pleaseCheckYourEmailAddress' => __('Please check your email address.', NEWSMAN)
		) );

		wp_enqueue_script('newsman-jquery-1.9.1-ie-fix', NEWSMAN_PLUGIN_URL.'/js/jquery-1.9.1.mod.js', array('jquery'), NEWSMAN_VERSION);

		if ( function_exists('wp_enqueue_script') ) {

			if ( defined('WP_ADMIN') ) {
		
				global $wp_scripts;

				if ( defined('NEWSMAN_PAGE') ) {
					do_action('newsman_get_ext_script');

					wp_enqueue_script('jquery-ui-core', array('jquery'));
					wp_enqueue_script('jquery-ui-datepicker', array('jquery'));					
					//wp_enqueue_script('jquery-ui-draggable', array('jquery'));

					wp_enqueue_script('newsman_momentjs', NEWSMAN_PLUGIN_URL.'/js/moment-with-locales.min.js', array(), NEWSMAN_VERSION);

					wp_enqueue_style('jquery-tipsy-css', NEWSMAN_PLUGIN_URL.'/css/tipsy.css');
					wp_enqueue_script('jquery-tipsy', NEWSMAN_PLUGIN_URL.'/js/jquery.tipsy.js', array('jquery'));

					wp_enqueue_script('newsman-zeroclipoard', NEWSMAN_PLUGIN_URL.'/js/zeroclipboard/ZeroClipboard.min.js', array(), NEWSMAN_VERSION);
					wp_enqueue_script('newsman-fold-to-ascii', NEWSMAN_PLUGIN_URL.'/js/fold-to-ascii.min.js', array(), NEWSMAN_VERSION);

					wp_enqueue_script('newsman-jquery-easy-pie-chart', NEWSMAN_PLUGIN_URL.'/js/jquery.easypiechart.js', array('jquery'));
					wp_enqueue_script('newsman-chart-js', NEWSMAN_PLUGIN_URL.'/js/Chart.js', array('jquery'));

					wp_register_style('fileuploader-css', NEWSMAN_PLUGIN_URL.'/css/fileuploader.css', array(), NEWSMAN_VERSION);
					wp_register_script('fileuploader-iframe-transport-js', NEWSMAN_PLUGIN_URL.'/js/uploader/jquery.iframe-transport.js', array('jquery'), NEWSMAN_VERSION);	
					wp_register_script('fileuploader-js', NEWSMAN_PLUGIN_URL.'/js/uploader/jquery.fileupload.js', array('jquery', 'jquery-ui-widget', 'fileuploader-iframe-transport-js'), NEWSMAN_VERSION);

					wp_register_style('bootstrap', NEWSMAN_PLUGIN_URL.'/css/bootstrap.css', array(), NEWSMAN_VERSION);

					wp_enqueue_style('newsman-font-css', NEWSMAN_PLUGIN_URL.'/css/newsman-font/style.css', array(), NEWSMAN_VERSION);

					if ( $isNew38Style ) {
						wp_enqueue_style('wp38-bootstrap-fixes', NEWSMAN_PLUGIN_URL.'/css/wp38.css', array(), NEWSMAN_VERSION);
					}

					wp_register_script('bootstrapjs', NEWSMAN_PLUGIN_URL.'/js/bootstrap.min.js', array('jquery'));
					
					wp_register_script('director', NEWSMAN_PLUGIN_URL.'/js/director.js');
					wp_register_script('hashroute', NEWSMAN_PLUGIN_URL.'/js/hashroute.js');

					if ( (isset($_REQUEST['action']) && $_REQUEST['action'] === 'newsman-frame-get-posts') || 
						 (isset($_REQUEST['page']) && $_REQUEST['page'] === 'newsman-mailbox' ) ||
						 (isset($_REQUEST['page']) && $_REQUEST['page'] === 'newsman-templates' )
					   ) {
						wp_enqueue_script('jquery-cookie', NEWSMAN_PLUGIN_URL.'/js/jquery.cookie.js', array('jquery'));
					}
				}

				if ( $page == 'newsman-forms' ) {
					wp_enqueue_style('fileuploader-css');
					wp_enqueue_script('fileuploader-js');

					if ( $action === 'editlist' ) {	
						wp_enqueue_script('newsman-formbuilder', NEWSMAN_PLUGIN_URL.'/js/newsman-formbuilder.js', array('jquery', 'jquery-ui-widget'), NEWSMAN_VERSION);
					}
				}

				if ( isset($_GET['action']) && $_GET['action'] === 'editlist' ) {
					wp_enqueue_script('newsman-ko', NEWSMAN_PLUGIN_URL.'/js/knockout-2.2.1.js', array('jquery'));
					wp_enqueue_script('newsman-ko-mapping', NEWSMAN_PLUGIN_URL.'/js/knockout.mapping.js', array('newsman-ko'));
					wp_enqueue_script('newsman-ko-sortable', NEWSMAN_PLUGIN_URL.'/js/knockout-sortable.min.js', array('newsman-ko'));
				}

				if ( defined('NEWSMAN_PAGE') || defined('INSERT_POSTS_FRAME') ) {


					//wp_dequeue_script

					// bootstrap
					wp_enqueue_style('bootstrap');				
					wp_enqueue_script('bootstrapjs');
					wp_enqueue_script('director');		
					wp_enqueue_script('hashroute');		
					// ------ 

					wp_register_style('jq-multiselect', NEWSMAN_PLUGIN_URL.'/js/css/jquery.multiselect.css', array(), NEWSMAN_VERSION);
					wp_enqueue_style('jq-multiselect');

					wp_register_style('jquery-ui-timepicker', NEWSMAN_PLUGIN_URL.'/css/jquery-ui-timepicker-addon.css');
					wp_register_script('jquery-ui-timepicker-js', NEWSMAN_PLUGIN_URL.'/js/jquery-ui-timepicker-addon.js', array('jquery-ui-datepicker'));

					wp_enqueue_style('multis-css', NEWSMAN_PLUGIN_URL.'/js/css/multis.css', array(), NEWSMAN_VERSION);
					wp_enqueue_script('multis-js', NEWSMAN_PLUGIN_URL.'/js/jquery.multis.js', array('jquery', 'jquery-ui-widget'), NEWSMAN_VERSION );

					wp_enqueue_script('neouploader-js', NEWSMAN_PLUGIN_URL.'/js/neoUploader.js', array('jquery', 'jquery-ui-widget') );

					//wp_enqueue_script('newsman-holder-js', NEWSMAN_PLUGIN_URL.'/js/holder.js');

					wp_register_script('bootstrap-editable', NEWSMAN_PLUGIN_URL.'/js/bootstrap-editable.js', array('bootstrapjs'));
					wp_register_style('bootstrap-editable-css', NEWSMAN_PLUGIN_URL.'/css/bootstrap-editable.css', array('bootstrap'));

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

					wp_register_script('newsmanwidgets', NEWSMAN_PLUGIN_URL.'/js/newsmanwidgets.js', array('jquery', 'jquery-ui-widget', 'jquery-ui-draggable', 'newsman-admin'));

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

					wp_enqueue_script('newsmanwidgets');

					if ( defined('NEWSMANP') ) {
						$newsmanIsProVersion = 'newsman-pro-version';
						$viewStatsHint = '';
					} else {
						$wpnewsmanLP = 'http://wpnewsman.com/documentation/track-opens-clicks-unsubscribes/';
						$viewStatsHint = sprintf(__('Full Email Statistics & Click Tracking available in <a href="%s">the Pro version</a>', NEWSMAN), $wpnewsmanLP);
						$newsmanIsProVersion = 'newsman-lite-version';
					}

					wp_localize_script( 'newsman-admin', 'newsmanL10n', array(
						'newsmanVersionClass' => $newsmanIsProVersion,
						/* translators: subscriber type */
						'unconfirmed' => __('Unconfirmed', NEWSMAN),
						/* translators: subscriber type */
						'confirmed' => __('Confirmed', NEWSMAN),
						/* translators: subscriber type */
						'unsubscribed' => __('Unsubscribed', NEWSMAN),
						'error' => __('Error: ', NEWSMAN),
						'error2' => __('Error', NEWSMAN),
						'done' => __('Done', NEWSMAN),
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
						'stInprogress' => __('Sending...', NEWSMAN),
						'stStopped' => __('Stopped', NEWSMAN),
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
						'warnExceededLimit' => __('Warning! You exceeded the limit of %d subscribers you can send emails to in the Lite version of the plugin. Please, <a href="%s">upgrade to the Pro version</a> to send emails without limitations.', NEWSMAN),
						'name' => __('Name', NEWSMAN),
						'editForm' => __('Edit Form', NEWSMAN),
						'viewSubscribers' => __('View Subscribers', NEWSMAN),
						'copy' => __('Copy', NEWSMAN),
						'sEdit' => __('Edit', NEWSMAN),
						'sDelete' => __('Delete', NEWSMAN),
						'sExport' => __('Export', NEWSMAN),
						'sRestore' => __('Restore', NEWSMAN),
						'defaultSystemTemplates' => __('Default System Templates', NEWSMAN),
						'systemTemplateCannotBeDeleted' => __('System Template. Cannot be deleted.', NEWSMAN),
						'insertPosts' => __('Insert Posts', NEWSMAN),
						'postsSelected' => __('Post(s) selected: %d', NEWSMAN),
						'selectCategories' => __('Select categories', NEWSMAN),
						'nOfNCategoriesSelected' => __('# of # categories selected', NEWSMAN),
						'selectAuthors' => __('Select author(s)', NEWSMAN),
						'nOFNAuthorsSelected' => __('# of # authors selected', NEWSMAN),
						'save' => __('Save', NEWSMAN),
						'savedAt' => __('Saved at', NEWSMAN),

						'someRecipientsMightBeFiltered' => __('Bounce Handler has %d blocked domains. Some of recipients might be skipped during sending.', NEWSMAN),

						'viewInBrowser' => __('View in browser', NEWSMAN),

						'viewStats' => __('View Stats', NEWSMAN),
						'viewStatsHint' => $viewStatsHint,

						'pleaseFillAllTheRequiredFields' => __('Please fill all the required fields.', NEWSMAN),
						'pleaseCheckYourEmailAddress' => __('Please check your email address.', NEWSMAN),
						'areYouSureYouWantToRestoreStockTemplate' => __('Are you sure you want to restore stock template?', NEWSMAN),

						// mailbox table strings

						'mbNoSubject' => __('No subject', NEWSMAN),
						'mbStart' => __('Start', NEWSMAN),
						'mbStop' => __('Stop', NEWSMAN),
						'mbEdit' => __('Edit', NEWSMAN),
						'mbDuplicate' => __('Duplicate', NEWSMAN),
						'mbDelete' => __('Delete', NEWSMAN),
						'mbSent' => __('sent', NEWSMAN),
						'mbOpens' => __('opens', NEWSMAN),
						'mbClicks' => __('clicks', NEWSMAN),
						'mbUnsubscribes' => __('unsubscribes', NEWSMAN),

						'NEWSMAN_ET_ADMIN_SUB_NOTIFICATION' => __('Subscribe notification email sent to the administrator.', NEWSMAN),
						'NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION' => __('Unsubscribe notification email sent to the administrator.', NEWSMAN),
						'NEWSMAN_ET_CONFIRMATION' => __('Email with the confirmation link sent to the user upon subscription.', NEWSMAN),
						'NEWSMAN_ET_UNSUBSCRIBE' => __('Email sent to the user that confirms that his email address was unsubscribed.', NEWSMAN),
						'NEWSMAN_ET_WELCOME' => __('Welcome message sent after the subscriber confirms his subscription.', NEWSMAN),
						'NEWSMAN_ET_UNSUBSCRIBE_CONFIRMATION' => __('Email with a link to confirm the subscriber’s decision to unsubscribe.', NEWSMAN),
						'NEWSMAN_ET_RECONFIRM' => __('Email with a link to re-confirm the subscription that is sent to ALL "unconfirmed" subscribers on the list.', NEWSMAN)
					) );
				}

				if ( in_array($page, array('newsman-templates', 'newsman-mailbox', 'newsman-forms') ) ) {
					wp_enqueue_script('newsman-ckeditor');
					wp_enqueue_script('newsman-ckeditor-jq');
					wp_enqueue_script('newsman-ck-max-patch');

					wp_enqueue_script('newsman-jqMiniColors');
					wp_enqueue_style('newsman-jqMiniColorsCSS');

					wp_enqueue_style('jquery-ui-timepicker');
					wp_enqueue_script('jquery-ui-timepicker-js');
				}

				if  ( (strpos($page, 'newsman') !== false) || defined('INSERT_POSTS_FRAME') ) {
					wp_enqueue_script('jquery');
					wp_enqueue_script('editor');
					wp_enqueue_script('thickbox');
					wp_enqueue_script('media-upload');
					
					wp_enqueue_style('thickbox');

					$url = NEWSMAN_PLUGIN_URL."/css/smoothness/jquery-ui-1.8.20.custom.css";
					wp_enqueue_style('newsman-jquery-ui-smoothness', $url, false, $ui->ver);
				  
				  	wp_enqueue_script('jquery-multiselect');									

				  	wp_enqueue_script('bootstrap-editable');
				  	wp_enqueue_style('bootstrap-editable-css');
				}
			}
		}

		wp_register_style('newsman', NEWSMAN_PLUGIN_URL.'/css/newsman.css', array(), NEWSMAN_VERSION);
		wp_enqueue_style('newsman');

		wp_register_style('newsman-ie9', NEWSMAN_PLUGIN_URL.'/css/newsman-ie9.css', array(), NEWSMAN_VERSION);
		$GLOBALS['wp_styles']->add_data( 'newsman-ie9', 'conditional', 'gte IE 9' );
		wp_enqueue_style('newsman-ie9');

	}

	public function onAdminEnqueueScripts() {
		// This is a hack for a Classipress 3.1.9 
		// which loads it's dependent sctips on all admin pages
		
		if ( defined('NEWSMAN_PAGE') && function_exists( 'scb_init' ) ) {
			if ( wp_script_is('datepicker') )   { 
				wp_dequeue_script('datepicker'); 
			}
			if ( wp_script_is('jqueryslider') ) { 
				wp_dequeue_script('jqueryslider'); 
			}
			if ( wp_script_is('timepicker') )   { 
				wp_dequeue_script('timepicker'); 
			}

			if ( wp_style_is('admin-style') ) {
				wp_dequeue_style('admin-style');
			}
		}
	}

	public function cleanOldUnconfirmed() {
		global $wpdb;

		$lists = newsmanList::findAll();
		foreach ($lists as $list) {
			if ( $list->name == '_Test' ) { continue; }
			if ( $this->utils->tableExists($list->tblSubscribers) ) {
				$sql = "DELETE FROM $list->tblSubscribers WHERE status = ".NEWSMAN_SS_UNCONFIRMED." and DATE_ADD(`ts`, INTERVAL 7 DAY) <= CURDATE()";	
				$result = $wpdb->query($sql);				
			} else {
				$list->remove();
			}
		}	
		
	}

	public function processPageRequest() {
		global $newsman_current_subscriber;
		global $newsman_current_ucode;
		global $newsman_current_list;

		global $newsman_error_message;
		global $newsman_success_message;

		$use_excerpts = isset($_REQUEST['newsman_use_excerpts']);

		if ( isset($_REQUEST['u']) && strpos($_REQUEST['u'], ':') !== false ) {
			$uArr = explode(':', $_REQUEST['u']);

			if ( count($uArr) === 2 ) {
				$newsman_current_ucode = $_REQUEST['u'];				
				$list = newsmanList::findOne('uid = %s', array($uArr[0]));

				if ( $list ) {
					$newsman_current_list = $list;

					$s = $list->findSubscriber("ucode = %s", $uArr[1]);
					if ( $s ) {
						$newsman_current_subscriber = $s->toJSON();
					}
				}					
			}
		}
				
		if ( isset($_REQUEST['nwsmn-unsubscribe']) ) {
			if ( !isset($s) ) {
				wp_die( sprintf( __('Cannot unsubscribe email. Subscriber with unique code %s is not found.', NEWSMAN ), htmlentities($_REQUEST['u'])) );
			} else {

				$s->unsubscribe();
				$s->save();

				if ( $this->options->get('notifyAdmin') ) {
					$this->notifyAdmin($list->id, NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION);
				}

				if ( $this->options->get('sendUnsubscribed') ) {
					$this->sendEmail($list->id, NEWSMAN_ET_UNSUBSCRIBE);
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
			if ( !$this->utils->emailValid($res['email']) ) {
				if ( $use_excerpts ) {
					$this->showActionExcerpt('badEmail');
				} else {
					$this->redirect( $this->getLink('badEmail') );
				}
			}

			$list = $frm->list;
			$this->subscribe($list, $res, $use_excerpts);
		}
	}

	/**
	 * Subscribe user
	 */
	public function subscribe($list, $userData, $use_excerpts, $returnResult = false, &$sub = false) {
		global $newsman_current_list;
		global $newsman_current_subscriber;
		global $newsman_current_ucode;

		$newsman_current_list = $list;

		$s = $list->findSubscriber("email = '%s'", $userData['email']);

		if ( isset($_REQUEST['newsman-form-url']) ) {
			$userData['newsman-form-url'] = $_REQUEST['newsman-form-url'];
		}

		$subscriberStatus = -1; // -1 - newly created

		if ( !$s ) {
			$s = $list->newSub();
			$s->fill($userData);
			$s->save();
			$subscriberStatus = -1;
		} else {
			$subscriberStatus = $s->meta('status');
		}

		$sub = $s;

		$newsman_current_subscriber = $s->toJSON();

		$userUID = $list->uid.':'.$s->meta('ucode');

		$newsman_current_ucode = $userUID;

		switch ( $subscriberStatus ) {
			case -1:
				$this->sendEmail($list->id, NEWSMAN_ET_CONFIRMATION);
				if ( $use_excerpts ) {
					return $returnResult ? $this->getActionExcerpt('confirmationRequired') : $this->showActionExcerpt('confirmationRequired');
				} else {
					$url = $this->getLink('confirmationRequired', array('u' => $userUID ) );
					return $returnResult ? $url : $this->redirect( $url );
				}					
			break;
			case NEWSMAN_SS_UNCONFIRMED:
				// subscribed but not confirmed
				if ( $use_excerpts ) {
					return $returnResult ? $this->getActionExcerpt('emailSubscribedNotConfirmed') : $this->showActionExcerpt('emailSubscribedNotConfirmed');
				} else {
					$url = $this->getLink('emailSubscribedNotConfirmed', array('u' => $userUID ) );
					return $returnResult ? $url : $this->redirect( $url );
				}					
			break;                
			case NEWSMAN_SS_CONFIRMED:
				// subscribed and confirmed
				if ( $use_excerpts ) {
					return $returnResult ? $this->getActionExcerpt('alreadySubscribedAndVerified') : $this->showActionExcerpt('alreadySubscribedAndVerified');
				} else {
					$url = $this->getLink('alreadySubscribedAndVerified', array('u' => $userUID ) );
					return $returnResult ? $url : $this->redirect( $url );	
				}					
			break;
			case NEWSMAN_SS_UNSUBSCRIBED:
				// unsubscribed
				$s->subscribe();
				$s->save();

				$this->sendEmail($list->id, NEWSMAN_ET_CONFIRMATION);

				if ( $use_excerpts ) {
					return $returnResult ? $this->getActionExcerpt('confirmationRequired') : $this->showActionExcerpt('confirmationRequired');
				} else {
					$url = $this->getLink('confirmationRequired', array('u' => $userUID ) );
					return $returnResult ? $url : $this->redirect( $url );
				}					
			break;
		}
	}

	public function getActionExcerpt($pageName) {
		$post = get_post( $this->options->get('activePages.'.$pageName) );
		return do_shortcode($post->post_excerpt);
	}


	public function showActionExcerpt($pageName) {
		
		$post = get_post( $this->options->get('activePages.'.$pageName) );
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

	public function putForm($print = true, $listId = false, $horizontal = false, $adminSubmissionMode = false, $subId = false) {
		$list = newsmanList::findOne('id = %d', array($listId));
		$frm = new newsmanForm($listId);

		$frm->horizontal = $horizontal;

		$frm->adminSubmissionMode = $adminSubmissionMode;

		if ( !wp_script_is('newsmanform') ) {
			wp_register_script('newsmanform', NEWSMAN_PLUGIN_URL.'/js/newsmanform.js', array('jquery'), NEWSMAN_VERSION);
		}

		$data = '';
																	   
		if ( !$print ) {
			$clsa_form = 'class="newsman-sa-from"';
			$clsa_placehldr = ' newsman-sa-placeholder';
		} else {
			$clsa_form = '';
			$clsa_placehldr = '';
		}
		
		wp_enqueue_script('newsmanform');
		if ( !get_option('newsman_code') ) {
			$data .= '<!-- Powered by WPNewsman '.NEWSMAN_VERSION.' - http://wpnewsman.com/ -->';			
		}

		$id = "newsman-form-".$list->uid;

		$data .= "<form id=\"$id\" ".$clsa_form.' name="newsman-nsltr" action="'.$this->utils->addTrSlash( get_bloginfo('wpurl') ).'" method="post">';

		$data.= $frm->getForm(false, $list, $subId);

		$data .= '</form>';
		
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

		$blockedDomainsCount = 'null';

		if ( class_exists('newsmanBlockedDomain') ) {
			$blockedDomainsCount = newsmanBlockedDomain::count();
		}

		//newsmanBlockedDomain::count();

		echo "<script>
			var attr = document.head.parentNode.attributes.getNamedItem('dir'),
				textDir = attr && attr.value === 'rtl' ? 'rtl' : 'ltr';

			document.head.parentNode.className += ' newsman-dir-'+textDir;
		</script>";

		echo '<script>			
			NEWSMAN_LOCALE = "'.get_locale().'";
			NEWSMAN_BLOCKED_DOMAINS = '.$blockedDomainsCount.';
			NEWSMAN_PLUGIN_URL = "'.NEWSMAN_PLUGIN_URL.'";
			NEWSMAN_BLOG_ADMIN_URL = "'.NEWSMAN_BLOG_ADMIN_URL.'";
			NEWSMAN_WPML_MODE = '.( (defined('NEWSMAN_WPML_MODE') && NEWSMAN_WPML_MODE) ? 'true' : 'false' ).';
			</script>';

		$mode = '<script>NEWSMAN_LITE_MODE = true;</script>';
		echo apply_filters('newsman_amend_mode', $mode);
	}

	public function tryUpdate() {

		if ( $this->utils->canUpdate() && !get_option('NEWSMAN_DOING_UPDATE') ) {
			update_option('NEWSMAN_DOING_UPDATE', true);

			$doRedirect = !defined('DOING_AJAX') && !defined('DOING_CRON');

			// Update plugin iframe URL: 
			// update.php?action=activate-plugin&networkwide&plugin=wpnewsman-newsletters%2Fwpnewsman.php&_wpnonce=8f9c8f4c6d

			if ( preg_match('/update\.php/', $_SERVER['SCRIPT_NAME']) && isset($_REQUEST['action']) && $_REQUEST['action']=='activate-plugin' ) {
				$doRedirect = false;
			}

			$this->utils->runUpdate();
			
			delete_option('NEWSMAN_DOING_UPDATE');
			if ( $doRedirect ) {
				$url = NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-mailbox&welcome=1';
				if ( strpos($_SERVER['REQUEST_URI'], 'wpnewsman') !== false ) {
					$url .= '&return='.$_SERVER['REQUEST_URI'];
				}
				wp_redirect($url);	
			} else {
				$this->options->set('showWelcomeScreen', true);
			}			
		}
	}

	public function onAdminInit() {

		if ( defined('NEWSMAN_PAGE') && 
			 $this->options->get('showWelcomeScreen') &&
			 !defined('DOING_AJAX') &&
			 !defined('DOING_CRON') 
		   ) {
			$this->options->set('showWelcomeScreen', false);
			$url = NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-mailbox&welcome=1';
			if ( strpos($_SERVER['REQUEST_URI'], 'wpnewsman') !== false ) {
				$url .= '&return='.$_SERVER['REQUEST_URI'];
			}		
			wp_redirect($url);	
		}

		$this->tryUpdate();

		$this->configureW3TC();

		// page=newsman-mailbox&action=compose
		$page = isset($_REQUEST['page']) ? $_REQUEST['page'] : null;
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : null;
		$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

		if ( $page === 'newsman-settings' && $action === 'newsman-frame-get-posts' ) {
			include(__DIR__.DIRECTORY_SEPARATOR.'frmGetPosts.php');
			exit();
		}

		if ( $page === 'newsman-mailbox' && $action === 'compose' ) {
			$ent = new newsmanEmail();

			$ent->subject = __('Enter Subject Here', NEWSMAN);

			$defParticles = $this->utils->getDefaultTemplateParticles();
			$ent->particles = $defParticles ? $defParticles : '';

			$ent->save();
			wp_redirect(NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-mailbox&action=edit&id='.$ent->id);
		}

		if ( $page === 'newsman-templates' && $action === 'create-template' ) {
			$tpl = new newsmanEmailTemplate();
			$tpl->system = false;
			$tpl->name = __('Untitled templates', NEWSMAN);
			$tpl->subject = __('Enter Subject Here', NEWSMAN);

			$defParticles = $this->utils->getDefaultTemplateParticles();
			$tpl->particles = $defParticles ? $defParticles : '';

			$tpl->save();

			wp_redirect(NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-templates&action=edit&id='.$tpl->id);
		}

		add_meta_box("newsman-et-meta", __('Alternative Plain Text Body', NEWSMAN), array($this, "metaPlainBody"), "newsman_et", "normal", "default");
	}

	public function onWidgetsInit() {
		require_once(NEWSMAN_PLUGIN_PATH.'/newsman-widget.php');

		if ( defined('NEWSMAN_WPML_MODE') && NEWSMAN_WPML_MODE ) {
			require_once(NEWSMAN_PLUGIN_PATH.'/widget-wpml.php');
		}
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

		if ( defined('NEWSMAN_DEBUG') && NEWSMAN_DEBUG === true ) {
			add_submenu_page(
				'newsman-mailbox',
				__('Debug Log', NEWSMAN),
				__('Debug Log', NEWSMAN),
				'publish_pages',
				'newsman-debuglog',
				array($this, 'pageDebugLog')
			);			
		}


		$label = apply_filters('newsman_upgrade_to_pro', __('Upgrade to Pro', NEWSMAN));
		
		add_submenu_page(
			'newsman-mailbox',
			$label,
			$label,
			'publish_pages', 
			'newsman-pro', 
			array($this, 'pagePro')
		);

		do_action('newsman_admin_menu');

		//var_dump($submenu['newsman-mailbox']);

		$posSettings = null;
		$posActionPages = null;

		$this->moveMenuItem('newsman_ap', 'newsman-settings');
		$this->moveMenuItem('newsman-bh', 'newsman-settings');
	}

	/**
	 * $menuId is a menu id or custom page id
	 */
	private function moveMenuItem($menuId, $above) {		
		global $submenu;

		// getting menu item position
		$menuItemPos = $this->getMenuPos($menuId);

		if ( $menuItemPos !== null ) {
			// cutting out menu item
			$menuItem = array_splice($submenu['newsman-mailbox'], $menuItemPos, 1);

			$aboveMenuItemPos = $this->getMenuPos($above);

			// inserting above
			array_splice($submenu['newsman-mailbox'], $aboveMenuItemPos, 0, $menuItem);			
		}
	}

	private function getMenuPos($menuId) {
		global $submenu;
		for ($i=0, $c = count($submenu['newsman-mailbox']); $i < $c; $i++) { 
			if ( strpos($submenu['newsman-mailbox'][$i][2], $menuId) !== false ) {
				return $i;
			}
		}
		return null;
	}

	public function reAddExcerptMetabox($post_type, $position, $post) {
		if ( $position == 'normal' ) {
			remove_meta_box( 'postexcerpt' , 'newsman_ap' , 'normal' );

			add_meta_box('postexcerpt', __('Excerpt for external forms', NEWSMAN), 'post_excerpt_meta_box', 'newsman_ap', 'normal');
		}
	}

	public function removeUploadDir() {
		$dirs = wp_upload_dir();
		$ud = $dirs['basedir'].DIRECTORY_SEPARATOR.'wpnewsman';
		if ( is_dir($ud) ) {
			$this->utils->delTree($ud);
		}
	}

	static function ensureUploadDir($subdir = false) {

		$dirs = wp_upload_dir();
		$ud = $dirs['basedir'].DIRECTORY_SEPARATOR.'wpnewsman';
		$rootUD = $ud;

		if ( $subdir ) {
			$ud .= DIRECTORY_SEPARATOR.$subdir;
		}

		if ( !is_dir($ud) ) {
			mkdir($ud, 0777, true);
		}

		if ( !file_exists($rootUD.DIRECTORY_SEPARATOR.'index.html') ) {
			file_put_contents($rootUD.DIRECTORY_SEPARATOR.'index.html', '');
		}		

		return $ud;
	}

	public function onActivate() {
		$this->activation = true;

		$this->securityCleanup();

		if ( !isset($this->wplang) ) {
			$this->setLocale();
		}

		$locks = newsmanLocks::getInstance();
		$locks->clearLocks();

		$this->wm->clearWorkers();

		// clenup message queue table
		newsmanWorkerBase::cleanupTable();

		$this->utils->log('[onActivate] ensureEnvironment');
		$this->ensureEnvironment();
		$this->utils->log('[onActivate] install');
		$this->install();

		// adding capability
		$role = get_role('administrator');
		if ( $role ) {
			$role->add_cap('newsman_wpNewsman');
		}

		$this->utils->log('[onActivate] onInit');
		$this->onInit(true);
		flush_rewrite_rules();
	}

	public function ensureEnvironment() {
		global $wpdb;

		$this->utils->log('[ensureEnvironment] ------------ ');

		newsmanEmail::ensureTable();
		newsmanEmail::ensureDefinition();

		newsmanEmailTemplate::ensureTable();
		newsmanEmailTemplate::ensureDefinition();

		newsmanAnClicks::ensureTable();
		newsmanAnClicks::ensureDefinition();

		newsmanAnLink::ensureTable();
		newsmanAnLink::ensureDefinition();

		newsmanAnSubDetails::ensureTable();
		newsmanAnSubDetails::ensureDefinition();

		newsmanAnTimeline::ensureTable();
		newsmanAnTimeline::ensureDefinition();	

		newsmanList::ensureTable();
		newsmanList::ensureDefinition();	

		newsmanBlockedDomain::ensureTable();
		newsmanBlockedDomain::ensureDefinition();	

		newsmanWorkerRecord::ensureTable();
		newsmanWorkerRecord::ensureDefinition();

		// modify lists tables
		$nsTable = newsmanStorable::$table;
		$nsProps = newsmanStorable::$props;

		$lists = newsmanList::findAll();

		foreach ($lists as $list) {
			newsmanStorable::$table = preg_replace('/^'.$this->utils->preg_quote($wpdb->prefix).'/i', '', $list->tblSubscribers);
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

		newsmanStorable::$table = $nsTable;
		newsmanStorable::$props = $nsProps;
	}

	public function onDeactivate() {

		$this->unscheduleEvents();
		$this->wm->disableWorkersCheck();


		$locks = newsmanLocks::getInstance();
		$locks->clearLocks();

		$this->wm->clearWorkers();

		$emails = newsmanEmail::findAll('status = "pending" OR status = "inprogress"');

		foreach ($emails as $email) {
			$email->status = 'stopped';
			$email->save();
		}

		// removing capability
		$role = get_role('administrator');
		if ( $role ) {
			$role->remove_cap('newsman_wpNewsman');
		}
	}

	public function getLink() {
		
		$pageName = func_get_arg(0);
		
		$params = ( func_num_args() == 2 ) ? func_get_arg(1) : array();

 		$post_id = $this->options->get('activePages.'.$pageName);

 		if ( function_exists('icl_object_id') ) {

 			$lang = ICL_LANGUAGE_CODE;

			if ( isset($_REQUEST['_form_lang']) ) {
				$lang = $_REQUEST['_form_lang'];
			}

 			$post_id = icl_object_id( $post_id , 'newsman_ap', true, $lang );
 		}    	

		$link = get_permalink( $post_id );

		$del = ( strpos($link, '?') === false ) ? '?' : '&';

		if ( isset($_REQUEST['extForm']) ) {
			$params['extForm'] = $_REQUEST['extForm'];
		}

		if ( count($params) ) {
			foreach ( $params as $key => $value ) {
				$link .= $del.$key.'='.$value;
				$del = '&';
			}			
		}


		return $link;
	}



	public function securityCleanup() {
		$uploadDir = $this->ensureUploadDir();

		$Directory = new RecursiveDirectoryIterator($uploadDir);
		$Iterator = new RecursiveIteratorIterator($Directory);
		$Regex = new RegexIterator($Iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);		

		foreach($Regex as $filename => $object){
			unlink($filename);
		}
	}

	public function onInit($activation = false) {
		new newsmanAJAX();
		do_action('wpnewsman_init_ajax');

		if ( isset($_REQUEST['NEWSMAN_FORCE_UPDATE']) ) {
			if ( current_user_can('manage_options') && current_user_can('newsman_wpNewsman') ) {
				$this->utils->runUpdate(false);			
				wp_die('Newsman Forced Update Done');
			}
		}

		// if ( !empty($_POST) ) {
		// 	echo "POST REQUEST";
		// 	print_r($_POST);
		// 	exit();
		// }

		if ( isset( $_REQUEST['newsman_worker_fork'] ) && !empty($_REQUEST['newsman_worker_fork']) ) {
			$this->utils->log('[onProWorkersReady] $_REQUEST["newsman_worker_fork"] %s', isset($_REQUEST['newsman_worker_fork']) ? $_REQUEST['newsman_worker_fork'] : '');			

			define('NEWSMAN_WORKER', true);
			
			$workerClass = $_REQUEST['newsman_worker_fork'];

			if ( !class_exists($workerClass) ) {
				$this->utils->log("requested worker class ".htmlentities($workerClass)." does not exist");
				die("requested worker class ".htmlentities($workerClass)." does not exist");
			}

			if ( !isset($_REQUEST['workerId']) ) {
				$this->utils->log('workerId parameter is not defiend in the query');
				die('workerId parameter is not defiend in the query');
			}

			$worker = new $workerClass($_REQUEST['workerId']);
			$worker->run();
			exit();
		}		

		if ( preg_match('/'.NEWSMAN_PLUGIN_DIRNAME.'\/api.php/i', $_SERVER['REQUEST_URI'] ) ) {
			new newsmanAPI();
			exit();
		}

		if ( preg_match('/wpnewsman-upload/i', $_SERVER['REQUEST_URI'] ) ) {
			if ( current_user_can('manage_options') && current_user_can('newsman_wpNewsman') ) {
				include_once(__DIR__.DIRECTORY_SEPARATOR.'upload.php');
				nuHandleUpload();
				$this->securityCleanup();
				exit();
			} else {
				wp_die( __('You are not authorized to access this resource.', NEWSMAN) , 'Not authorized', array( 'response' => 401 ));
			}
		}

		if ( preg_match('/wpnewsman-pokeback\/(run-scheduled|run-bounced-handler|mailman|check-workers)/i', $_SERVER['REQUEST_URI'], $matches) ) {
			switch ( $matches[1] ) {
				case 'run-scheduled':
					if ( !isset($_REQUEST['emlucode']) || !$_REQUEST['emlucode'] ) {
						wp_die('Required parameter "emlucode" is not defined.');
					} else {
						$eml = newsmanEmail::findOne('ucode = %s', array( $_REQUEST['emlucode'] ));
						$eml->status = 'pending';
						$eml->save();

						$this->mailman();
					}
					break;

				case 'run-bounced-handler':
					do_action('wpnewsman-pro-run-bh');
					break;
				case 'mailman':
					$this->mailman();
					break;
				case 'check-workers':
					$this->wm->pokeWorkers();
					break;
			}
			exit();
		}		

		// checking for external form request

		if ( isset($_REQUEST['uid']) && preg_match('/wpnewsman(\-newsletters|)\/form/', $_SERVER['REQUEST_URI']) ) {
			include('views/ext-form.php');
			exit();
		}

		if ( isset( $_REQUEST['page'] ) && strpos($_REQUEST['page'], 'newsman') !== false && !defined('NEWSMAN_PAGE') ) {
			define('NEWSMAN_PAGE', true);
			header('Cache-Control: no-cache'); // workaround for Chrome redirect caching bug
		}

		$page   = isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		$id     = isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

		if ( $page == 'newsman-templates') {
			if ( $action == 'source') {
				define('EMAIL_WEB_VIEW', true);
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
				$email->assetsURL = $tpl->assetsURL;
				$email->assetsPath = $tpl->assetsPath;
				$email->particles = $tpl->particles;

				$email->status = 'draft';

				$r = $email->save();

				if ( $r ) {					
					$url = get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox&action=edit&id='.$email->id;
					$this->redirect( $url );
				} else {
					wp_die($r);
				}				
			}
		}

		if ( $action == 'compose-from-email' && $id ) {

			$eml = newsmanEmail::findOne('id = %d', array( $id ));

			if ( $eml ) {

				$email = new newsmanEmail();

				$email->type = 'email';	

				$email->subject = $eml->subject.' '.__('Copy', NEWSMAN);
				$email->html = $eml->html;
				$email->plain = $eml->plain;
				$email->assetsURL = $eml->assetsURL;
				$email->assetsPath = $eml->assetsPath;
				$email->particles = $eml->particles;

				$email->status = 'draft';

				$r = $email->save();

				if ( $r ) {					
					$url = get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox&action=edit&id='.$email->id;
					$this->redirect( $url );
				} else {
					wp_die($r);
				}				
			} else {
				wp_die("Email with id $id is not found.");
			}
		}

		if ( $page == 'newsman-forms' && $action == 'export_newsman_list' ) {
			$this->export_newsman_list();
			exit();
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
			'register_meta_box_cb' => array($this, 'add_shortcode_metabox'),
			'exclude_from_search' => true
		));

		register_taxonomy_for_object_type('category', 'newsman_ap');

		// these line should stay last 
		$this->loadScrtipsAndStyles();
		$this->processActionLink();
		$this->processPageRequest();

		do_action('newsman_core_loaded');

		if ( $activation ) {
			$this->scheduleEvents();
		}
	}

	/**
	 * This function is called when events mode is changed from pokeback to wpcron and vice versa
	 */	
	public function eventsModeChanged() {
		$this->unscheduleEvents();
		$this->scheduleEvents();
	}

	public function scheduleEvents() {
		// Schedule events		
		if ( $this->options->get('pokebackMode') ) {
			$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/schedule/?'.http_build_query(array(
				'key' => $this->options->get('pokebackKey'),
				'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/mailman/',
				'time' => 'every 1 minute'
			));

			$r = wp_remote_get(
				$pokebackSrvUrl,
				array(
					'timeout' => 0.01,
					'blocking' => false
				)
			);
		} else {
			if ( !wp_next_scheduled('newsman_mailman_event') ) {
				wp_schedule_event( time(), '1min', 'newsman_mailman_event');
			}
		}
	}

	public function unscheduleEvents() {
		$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/unschedule/?'.http_build_query(array(
			'key' => $this->options->get('pokebackKey'),
			'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/mailman/',
			'time' => 'every 1 minute'
		));

		$r = wp_remote_get(
			$pokebackSrvUrl,
			array(
				'timeout' => 0.01,
				'blocking' => false
			)
		);
		wp_clear_scheduled_hook('newsman_mailman_event');
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

		if ( isset($_GET['welcome']) ) {
			$hideVideo = true;
			if ( !$this->options->get('hideInitialVideo') ) {
				$hideVideo = false;
				$this->options->set('hideInitialVideo', true);
			}

			include('views/welcome.php');
			return;
		}

		if ( isset($_GET['thanks']) ) {
			include('views/thanks.php');
			return;
		}		

		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		$page 	= isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
		$type 	= isset($_REQUEST['type']) ? $_REQUEST['type'] : false;
		$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

		if ( isset($_GET['action']) && $_GET['action'] == 'stats' ) {
			if ( defined('NEWSMANP') ) {
				$np = newsmanPro::getInstance();
				$np->showStatsPage();
				return;
			}
		}


		if ( $action == 'view' ) {

			if ( $id !== false ) {
				$ent = newsmanEmail::findOne('id = %d', array( $id ) );

				define('NEWSMAN_EDIT_ENTITY', 'email');
				include 'views/mailbox-email.php';	
			}			
		} elseif ( $action == 'edit' ) {

			define('NEWSMAN_EDIT_ENTITY', 'email');

			$ent = newsmanEmail::findOne('id = %d', array( $id ) );
			include 'views/mailbox-email.php';	

		} else {
			include 'views/mailbox.php';	
		}	
	}

	public function pageTemplates() {
		$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : false;
		$page 	= isset($_REQUEST['page']) ? $_REQUEST['page'] : false;
		$id 	= isset($_REQUEST['id']) ? $_REQUEST['id'] : false;

		if ( $action == 'new' || $action == 'edit' ) {

			define('NEWSMAN_EDIT_ENTITY', 'template');
			$ent = newsmanEmailTemplate::findOne('id = %d', array($id));
			include 'views/mailbox-email.php';
		} else {
			include 'views/templates.php';
		}	
	}

	public function pageForms() {
		if ( isset($_REQUEST['sub']) && $_REQUEST['sub'] === 'subscribers' ) {
			$this->pageSubscribers();
		} else {
			include 'views/forms.php';
		}		
	}

	public function pagePro() {

		if ( has_action('newsman_upgrade_form') ) {
			do_action('newsman_upgrade_form');
		} else {
			$domain = '';
			if ( preg_match('/\w+:\/\/(?:www\.|)([^\/\:]+)/i', get_bloginfo('url'), $matches) ) {
				$domain = $matches[1];
			}			
			include 'views/pro.php';
		}
	}

	public function pageDebugLog() {
		include 'views/debug-log.php';
	}

	public function echoTemplate() {
		global $newsman_current_subscriber;
		global $newsman_current_list;

		header("Content-Type: text/html; charset=utf-8");
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Pragma: no-cache"); 
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past		

		$list = newsmanList::findOne('id = %s', array($_REQUEST['list']));

		if ( $list ) {
			$s = $list->findSubscriber("id = %s", array($_REQUEST['sub']));
			$newsman_current_list = $list;
			$newsman_current_subscriber = $s->toJSON();		
		}			

		$id = $_REQUEST['id'];
		$tpl = newsmanEmailTemplate::findOne('id=%d', array($id));
		if ( !$tpl ) {
			echo '<span style="color: red;">'.__('Error: Email template not found', NEWSMAN).'</span>';
		} else {
			$c = isset($_REQUEST['processed']) ? do_shortcode($tpl->p_html) : $tpl->html;
			echo $c;
		}
		die();
	}

	public function echoEmail() {
		global $newsman_current_subscriber;
		global $newsman_current_email;
		global $newsman_current_list;

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

				if ( !isset($_REQUEST['list']) || $_REQUEST['list'] === '' ) {
					wp_die('Required parameter "list" is missing or empty');
				}

				if ( !isset($_REQUEST['sub']) || $_REQUEST['sub'] === '' ) {
					wp_die('Required parameter "sub" is missing or empty');
				}				

				$list = newsmanList::findOne('id = %s', array($_REQUEST['list']));
				$newsman_current_list = $list;

				$sub = $list->findSubscriber("id = %s", array($_REQUEST['sub']));
				$newsman_current_subscriber = $sub->toJSON();

				$c = $eml->renderMessage($sub->toJSON());
				echo $c['html'];

			} elseif ( isset($_REQUEST['processed2']) ) {
				echo $eml->p_html;
			} else {
				echo $eml->html;
			}
			//echo $eml->html; //echo $this->utils->processAssetsURLs($c, $eml->assetsURL);
		}
		die();
	}	

	private function zipDirectory($zip, $path, $archPath, $excludeFilename = false) {

		if ( $handle = opendir($path) ) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					$entPath = $path.DIRECTORY_SEPARATOR.$entry;
					if ( is_dir( $entPath ) ) {
						$this->zipDirectory($zip, $entPath, $archPath.DIRECTORY_SEPARATOR.$entry, $excludeFilename);
					} else {
						if ( $excludeFilename && $entry == $excludeFilename ) {
							continue;
						}
						$zip->addFile( file_get_contents($entPath), $archPath.DIRECTORY_SEPARATOR.$entry );	
					}					
				}
			}
			closedir($handle);
		}		
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

			$zip = new newsmanZipFile();
			//*
			if ( $tpl->assetsPath ) {
				$dir = $tpl->assetsPath;

				$this->zipDirectory($zip, $dir, $fileName, $fileName.'.html');
			}
			//*/

			$html = $this->utils->processAssetsURLs($tpl->html, $tpl->assetsURL, 'shrink');

			$zip->addFile($html, $fileName.'/'.$fileName.'.html');
			echo $zip->file();	
		}
		die();
	}

	public function getEmailToAsTags($tag = 'option') {
		$options = '';

		$eml = newsmanEmail::findOne('id = %d', array($_REQUEST['id']));

		if ( $eml ) {
			if ( is_array($eml->to) ) {
				foreach ($eml->to as $to) {
					$options .= "<$tag data-value=\"$to\">$to</$tag>";
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
		if ( !current_user_can( 'newsman_wpNewsman' ) ) {
			return;
		}
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
				    echo '<p class="glock_sub" style="margin: 0; float:left;">'.__('G-Lock WPNewsman subscription summary', NEWSMAN).'</p>';
				    echo '<a style="line-height:140%; float:right" href="admin.php?page=newsman-forms">'.__('Manage Forms and Lists', NEWSMAN).'</a>';
			    echo '</div>';
		    
		    $tod_subs = 0;
		    $ytd_subs = 0;
		    $total_subs = 0;
		    $total_unsubs = 0;
		    $total_unconf = 0;

		    echo '
		    <div style="overflow-y: auto;">     
		    <table style="width:99%;" class="newsman-dboard-summary">
		        <thead>
		            <tr>
		            	<th>'.__('List name', NEWSMAN).'</th>
		                <th style="width: 90px;">'.__('Total confirmed', NEWSMAN).'</th>
		                <th style="width: 105px;">'.__('Total unconfirmed', NEWSMAN).'</th>
		                <th style="width: 110px;">'.__('Total unsubscribed', NEWSMAN).'</th>
		            </tr>
		        </thead>
		        <tbody>';


			$lists = newsmanList::findAll();
			$listsNames = array();

			foreach ($lists as $list) {
				$s = $list->getStats(false, 'extended');
				echo '<tr>
					<th><a href="'.NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-forms&sub=subscribers#/'.$list->id.'/all">'.$list->name.'</a></th>
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

	public function showStatsAsWidget() {
		$this->putStats();
	}

	public function add_shortcode_metabox() {
		$title = 'WPNewsman <a style="font-size: inherit;" href="http://codex.wordpress.org/Shortcode_API">shortcodes</a>';
		add_meta_box('newsman_ap_shortcodes', $title, array($this, 'putApShortcodesMetabox'), 'newsman_ap', 'side', 'default');

		if ( defined('NEWSMAN_WPML_MODE') && NEWSMAN_WPML_MODE ) {
			$title = 'WPNewsman Translations';
			add_meta_box('newsman_mb_translations', $title, array($this, 'putApTranslationsMetabox'), 'newsman_ap', 'side', 'high');
		}

		// page_attributes_meta_box
	    add_meta_box('newsman_ap_template', __('Post Template'), array($this, 'post_template_meta_box'), 'newsman_ap', 'side', 'core');
	    //add_meta_box('newsman_ap_template', __('Post Template'), 'page_attributes_meta_box', 'newsman_ap', 'side', 'core');
	}

	public function putApShortcodesMetabox() {

		$link = '<a href="http://wpnewsman.com/documentation/short-codes-for-email-messages/">'.__('Click here', NEWSMAN).'</a>';

		?>	
		<p><?php _e('You can use the "Newsman" menu button on the editor\'s toolbar to insert the unsubscribe link and social profile links into the message.', NEWSMAN); ?></p>		
		<p><?php echo sprintf( __('%s for more shortcode macros supported by WPNewsman.', NEWSMAN), $link ); ?></p>
		<?php
	}

	public function putApTranslationsMetabox() {
		$translations = $this->utils->getAvailableTranslationLocales();
		?>
		<p><label for="wp-load-lang">Load default translation of this page:</label></p>
		<select name="wp-load-lang" id="wp-load-lang">
			<?php
			foreach ($translations as $t) {
				echo '<option value="'.$t['locale'].'">'.$t['native'].'</option>';
			}
			?>
		</select>
		<button type="button" id="btn-load-translation" class="button">Load</button>
		<script>
			jQuery(function($){
				$('#btn-load-translation').click(function(){
					var btn = $(this);
					btn.prop('disabled', true).text('Loadin...');

					var originalPageId;

					var origPageSel = $('#icl_translation_of')[0];
					if ( origPageSel ) {
						originalPageId = $(origPageSel).val();
					} else {
						originalPageId = $('#post_ID').val();
					}

					$.ajax(ajaxurl, {
						type: 'post',
						data: {
							action: 'newsmanAjGetActivePageTranslation',
							pageId: originalPageId,
							loc: $('#wp-load-lang').val()
						}
					}).done(function(data){
						if ( data.state ) {
							$('#title').val( data.title );

							if ( tinyMCE && tinyMCE.editors && tinyMCE.editors[0] ) {
								var e = tinyMCE.editors[0];
								if ( e ) {
									setTimeout(function() {
										e.setContent(data.content);
									}, 10);
								}
							} else if ( CKEDITOR && CKEDITOR.instances && CKEDITOR.instances.content ) {
								CKEDITOR.instances.content.setData(data.content);
							}

						}
					}).always(function(){
						btn.prop('disabled', false).text('Load');
					});
				});
			});
		</script>
		<?php
	}

	public function onWPHead() {
		global $post_type;

		if ( $post_type === 'newsman_ap' ) {
			echo '<meta name="robots" content="noindex,nofollow">';
		}
	}

	public function listNamesAsJSArr() {
		$listsArr = array();

		$lists = newsmanList::findAll();

		foreach ($lists as $list) {
			$listsArr[] = array(
				'name' => $list->name,
				'count' => $list->getTotal()
			);
		}

		return json_encode($listsArr);
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
		global $wpdb;
		
		$hideForLang = $this->options->get('hideLangNotice');

		if ( $this->lang !== $this->wplang && $this->wplang != $hideForLang ) {
			include('views/_an_locale_changed.php');	
		}

		if ( $this->options->get('showW3TCConfigNotice') ) {
			$this->options->set('showW3TCConfigNotice', false);
			$ignoreStem = $wpdb->prefix.'newsman_';
			include('views/_an_w3tc_configured.php');
		}

		if ( $hideForLang != $this->wplang ) {
			$this->options->set('hideLangNotice', false);			
		}

		$this->showWPCronStatus();

		if ( defined('FS_METHOD') && FS_METHOD !== '' && FS_METHOD !== 'direct' ) {
			include('views/_an_fs_method.php');
		}
	}

	public function configureW3TC() {
		global $wpdb;

		$ignoreStem = $wpdb->prefix.'newsman_';

		if ( defined('W3TC_DIR') && is_plugin_active('w3-total-cache/w3-total-cache.php') ) {
			require_once W3TC_DIR . '/inc/define.php';

			$config = w3_instance('W3_Config');

			$arr = $config->get_array('dbcache.reject.sql');
			if ( is_array($arr) && !in_array($ignoreStem, $arr) ) {
				$arr[] = $ignoreStem;
				$config->set('dbcache.reject.sql', $arr);
				$config->save();
				$this->options->set('showW3TCConfigNotice', true);
			}		
		}
	}

	private function getWPCronStatus() {

		if ( $this->options->get('pokebackMode') ) {
			return 'alternative';
		}

		$doing_wp_cron = sprintf( '%.22F', microtime( true ) );
		$cron_url = site_url( 'wp-cron.php?doing_wp_cron=' . $doing_wp_cron );

		$result = wp_remote_post( $cron_url, array(
			'timeout'   => 5,
			'blocking'  => true
		) );

		return is_wp_error($result) ? $result : 'normal';
	} 

	public function showWPCronStatus() {
		global $wpdb;

		$st = $this->getWPCronStatus();

		if ( $st === 'alternative' ) {
			if ( !$this->options->get('hideAlternateCronWarning') ) {
				include('views/_an_wpcron_alternative_mode.php');
			}			
		} else if ( is_wp_error($st) && !$this->options->get('hideCronFailWarning') ) {
			$error = esc_html($st->get_error_message());
			include('views/_an_wp_cron_error.php');
			// show error message
		}

	}

	public function putListSelect($showAddNew = false) {
		$u = newsmanUtils::getInstance();

		$selectedId = false;

		if ( isset($_GET['action']) && $_GET['action'] === 'editlist' && isset($_GET['id']) ) {
			$selectedId = intval($_GET['id']);
		}
		$lists = $u->getListsSelectOptions($selectedId, $showAddNew);

		echo ' <label style="display: inline;" for="newsman-lists">'.__('List: ', NEWSMAN).'</label> <select id="newsman-lists" name="list">'.$lists.'</select>';
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

	function export_newsman_list() {
		if ( !current_user_can('newsman_wpNewsman') ) {
			wp_die( __('You are not authorized to access this resource.', NEWSMAN) , 'Not authorized', array( 'response' => 401 ));
		}

		$listId = intval($_GET['listId']);

		if ( isset($_GET['type']) ) {
			$type = strtolower($_GET['type']);
			if ( !in_array($type, array('all', 'confirmed', 'unconfirmed', 'unsubscribed')) ) {
				$type = 'all';
			}
		} else {
			$type = 'all';
		}

		if ( !$listId ) {
			wp_die( __('Please, provide correct "listId" parameter.', NEWSMAN) , 'Bad request', array( 'response' => 400 ));
		}

		$list = newsmanList::findOne('id = %d', array($listId));
		if ( !$list ) {
			wp_die( sprintf( __( 'List with id "%s" is not found.', NEWSMAN), $listId) , 'Not found', array( 'response' => 404 ));
		}		

		$u = newsmanUtils::getInstance();

		$fileName = date("Y-m-d").'-'.$list->name;
		$fileName = $u->sanitizeFileName($fileName).'.csv';


		$list->export(array(
			'fileName' => $fileName,
			'type' => $type
		));
	}	

	/*		DEBUG 		*/

	public function echoDebugLog() {		
		echo htmlentities($this->utils->readLog());
	}

	/* 		P 		*/

	public function getSelfDomain() {
		if ( preg_match('/\w+:\/\/(?:www\.|)([^\/\:]+)/i', get_bloginfo('url'), $matches) ) {
			$domain = $matches[1];
		}
		return $domain;
	}

	public function pbSchedule() {
		$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/schedule/?'.http_build_query(array(
			'key' => $this->options->get('pokebackKey'),
			'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/run-bounced-handler/',
			'time' => 'every 1 hour'
		));

		$r = wp_remote_get(
			$pokebackSrvUrl,
			array(
				'timeout' => 0.01,
				'blocking' => false
			)
		);
	}

	public function pbUnschedule() {
		// unschedlue
		$pokebackSrvUrl = WPNEWSMAN_POKEBACK_URL.'/unschedule/?'.http_build_query(array(
			'key' => $this->options->get('pokebackKey'),
			'url' => get_bloginfo('wpurl').'/wpnewsman-pokeback/run-bounced-handler/',
			'time' => 'every 1 hour'
		));

		$r = wp_remote_get(
			$pokebackSrvUrl,
			array(
				'timeout' => 0.01,
				'blocking' => false
			)
		);		
	}	

	public function runBouncedHandler() {
		newsmanBounceHandlerWorker::fork(array(
			'worker_lock' => 'bounce_handler'
		));
	}

	public function unpackGeoIpDB($filePath) {
		$u = newsmanUtils::getInstance();

		$extractDir = dirname($filePath); // .../wp-content/uploads/newsman/geoip

		$dbFilename = basename($filePath, '.gz');
		$dbFilePath = $extractDir.DIRECTORY_SEPARATOR.$dbFilename;

		if ( function_exists('request_filesystem_credentials') ) {
			$newsman_wpfs_creds = request_filesystem_credentials(NEWSMAN_BLOG_ADMIN_URL, '', false, false, null);	
			WP_Filesystem($newsman_wpfs_creds);

			if ( $newsman_wpfs_creds['connection_type'] == 'ftp' ) {
				$extractDir = $u->getRelativePath(ABSPATH, $extractDir);
			}
		}

		$res = $this->ungzipFile($filePath, $extractDir);

		if ( is_wp_error($res) ) {
			$u->log('[unpackGeoIpDB] Error: %s', $res->get_error_message());
			return $res;
		}

		if ( $res === true ) {
			unlink($filePath);
			return $dbFilePath;
		}

		return $res;
	}	

	private function ungzipFile($filePath, $extractDir) {
		$extractedFilename = basename($filePath, '.gz');
		$extractedFilePath = $extractDir.DIRECTORY_SEPARATOR.$extractedFilename;

		$buffReadSize = 256*1024; // 256kb

		$gz = gzopen ( $filePath, 'r' );
		$fn = fopen($extractedFilePath, 'w+');
		while ( !gzeof($gz) ) {
			fwrite($fn, gzread($gz, $buffReadSize));
		}
		fclose($fn);    	
    	gzclose($gz);		
    	return true;
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
if ( !function_exists('newsmanAdminHeader') ) {
	function newsmanAdminHeader() {
		include(__DIR__.DIRECTORY_SEPARATOR."views/_header.php");
	}	
}

if ( !function_exists('newsmanAdminFooter') ) {
	function newsmanAdminFooter() {
		include(__DIR__.DIRECTORY_SEPARATOR."views/_footer.php");
	}	
}


function newsmanCheckedValue($htmlVal, $checked, $applyFalsy = false) {
	if ( !$checked && $applyFalsy ) {		
		echo 'checked="checked"';
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

function newsmanEnt($str) {
	return htmlentities($str, ENT_COMPAT, 'UTF-8');
}

function newsmanEEnt($str) {
	echo newsmanEnt($str);
}

