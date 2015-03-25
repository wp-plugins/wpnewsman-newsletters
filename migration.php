<?php

/*******************************/
/*      Registration code      */
/*******************************/

global $newsman_changes;

$newsman_changes = array();

function newsman_do_migration() {

	global $newsman_changes;
	$u = newsmanUtils::getInstance();
	$oldVersion = $u->versionToNum( get_option('newsman_version') );
	$completed = get_option('newsman_completed_migrations');
	if ( !$completed || !is_array($completed) ) {
		$completed = array();
	}

	// after this version migrations are checked only using completed array.
	$migration2version = $u->versionToNum('1.6.0');

	$u->log('[migration] completed: %s', print_r($completed, true));

	foreach ($newsman_changes as $change) {
		$u->log('[migration] CHANGE: %s. introduced_in: %s', $change['func'], $change['introduced_in']);
		if ( $change['introduced_in'] >= $migration2version && ( !in_array($change['func'], $completed) || ( isset($change['repeat']) && $change['repeat'] ) ) ) {
			$u->log('[migration] executing...');
			call_user_func($change['func']);
			$completed[] = $change['func'];
			update_option('newsman_completed_migrations', $completed);
		}
	}
}

/*******************************/
/*      Migration functions    */
/*******************************/

$u = newsmanUtils::getInstance();


$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.2.0'),
	'func' => 'newsman_move_title_and_texts_from_list_params_to_form_els'
);

function newsman_move_title_and_texts_from_list_params_to_form_els() {
	// 1.2.0 changes. converting the form title, top and bottom texts into form elements
	$lists = newsmanList::findAll();
	$u = newsmanUtils::getInstance();

	if ( $lists ) {
		foreach ($lists as $list) {
			$tmpForm = json_decode($list->form, true);
			$foundTitle = false;
			$foundHTML = false;
			if ( $tmpForm !== NULL ) {
				foreach ($tmpForm['elements'] as $el) {
					if ( $el['type'] === 'title' ) {
						$foundTitle = true;
					}
					if ( $el['type'] === 'html' ) {
						$foundHTML = true;
					}
				}
				if ( !$foundTitle && !$foundHTML ) {
					if ( trim($list->header) ) {
						array_unshift($tmpForm['elements'], array( 'type' => 'html', 'value' => $list->header ));	
					}
					if ( trim($list->title) ) {
						array_unshift($tmpForm['elements'], array( 'type' => 'title', 'value' => $list->title ));
					}
					if ( trim($list->footer) ) {
						array_push($tmpForm['elements'], array( 'type' => 'html', 'value' => $list->footer ));
					}
					$list->form = json_encode( $u->utf8_encode_all($tmpForm) );
					$list->save();
				}
			}
		}
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.4.1'),
	'func' => 'newsman_migrate_emails_and_templates_tables'
);

function newsman_migrate_emails_and_templates_tables() {
	newsmanEmail::renameColumn('assets', 'assetsURL');
	newsmanEmailTemplate::renameColumn('assets', 'assetsURL');

	newsmanEmail::ensureDefinition();
	newsmanEmailTemplate::ensureDefinition();
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.4.1'),
	'func' => 'newsman_install_stock_templates'
);

function newsman_install_stock_templates() {
	$u = newsmanUtils::getInstance();
	$u->installStockTemplates();
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.0'),
	'func' => 'newsman_add_api_key'
);

function newsman_add_api_key() {
	$o = newsmanOptions::getInstance();
	$key = $o->get('apiKey');
	if ( !$key ) {
		$o->set('apiKey', sha1(sha1(microtime()).'newsman_api_key_salt'.get_option('admin_email')));
	}
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.0-pre-alpha-9'),
	'func' => 'newsman_migrating_eml_tpl'
);

function newsman_migrating_eml_tpl() {


	// updating Email Template DB structure
	newsmanEmailTemplate::ensureDefinition();

	// defining email template type for each system email template
	$tplsTypeMap = array(
		'addressChanged' 		 => NEWSMAN_ET_ADDRESS_CHANGED,
		'adminSubscriptionEvent' => NEWSMAN_ET_ADMIN_SUB_NOTIFICATION,
		'adminUnsubscribeEvent'  => NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION,
		'confirmation' 			 => NEWSMAN_ET_CONFIRMATION,
		'unsubscribe' 			 => NEWSMAN_ET_UNSUBSCRIBE,
		'welcome' 				 => NEWSMAN_ET_WELCOME
	);

	$o = newsmanOptions::getInstance();
	$templates = $o->get('emailTemplates');
	if ( $templates ) {
		foreach ($templates as $tplName => $tplId) {
			$tpl = newsmanEmailTemplate::findOne('`id` = %d', array($tplId));
			if ( $tpl ) {
				$tpl->system_type = $tplsTypeMap[$tplName];
				$tpl->save();
			}
		}
	}

	// copying system email templates for each list

	$u = newsmanUtils::getInstance();

	$lists = newsmanList::findAll();
	if ( $lists ) {
		foreach ($lists as $list) {
			$u->copySystemTemplatesForList($list->id);
		}
	}
}

// ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

//*
$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.0-pre-alpha-25'),
	'func' => 'newsman_migration_add_double_opt_out'
);
//*/
function newsman_migration_add_double_opt_out() {
	$data = array(
		'slug' => 'unsubscribe-confirmation-required',
		'title' => __('Please confirm your unsubscribe decision', NEWSMAN),
		'template' => 'unsubscribe-confirmation-required.html',
		'excerpt' => 'unsubscribe-confirmation-required-ex.html'
	);

	$o = newsmanOptions::getInstance();
	$u = newsmanUtils::getInstance();

	$o->set('useDoubleOptout', false);

	$pageId = $o->get('activePages.unsubscribeConfirmation');
	

	if ( !$pageId ) {
		$new_page = array(
			'post_type' => 'newsman_ap',
			'post_title' => $data['title'],
			'post_name' => $data['slug'],
			'post_content' => $u->loadTpl($data['template']),
			'post_excerpt' => $u->loadTpl($data['excerpt']),
			'post_status' => 'publish',
			'post_author' => 1
		);
		$pageId = wp_insert_post($new_page);

		$o->set('activePages.unsubscribeConfirmation', $pageId);
	}

	// -- email template

	$tplDef = array(
		'type' => NEWSMAN_ET_UNSUBSCRIBE_CONFIRMATION,
		'name' => __('Unsubscribe confirmation', NEWSMAN),
		'file' => 'unsubscribe-confirmation.txt'
	);

	$tplFileName = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR."email-templates".DIRECTORY_SEPARATOR."newsman-system".DIRECTORY_SEPARATOR."newsman-system.html";
	$baseTpl = file_get_contents($tplFileName);

	$eml = $u->emailFromFile($tplDef['file']);

	$tpl = newsmanEmailTemplate::findOne('`system` = 1 AND `assigned_list` = %d AND `system_type` = %d', array(0, $tplDef['type']));

	if ( !$tpl ) {
		$tpl = new newsmanEmailTemplate();

		$tpl->name = $tplDef['name'];
		$tpl->subject = $eml['subject'];
		$tpl->html = $u->replaceSectionContent($baseTpl, 'std_content', $eml['html']);
		$tpl->plain = $eml['plain'];
		$tpl->system = true;
		$tpl->system_type = $tplDef['type'];

		$tpl->save();			
	}

	$lists = newsmanList::findAll();

	foreach ($lists as $list) {
		$u->duplicateTemplate($tpl->id, false, $list->id);
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.0-pre-alpha-29'),
	'func' => 'newsman_migration_add_double_opt_out_2'
);


function newsman_migration_add_double_opt_out_2() {
	$o = newsmanOptions::getInstance();
	$u = newsmanUtils::getInstance();

	$pageId = $o->get('activePages.changeSubscription');

	if ( $pageId ) {
		$r = wp_delete_post(intval($pageId), true);
		$o->set('activePages.changeSubscription', NULL);
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.0-beta-4'),
	'func' => 'newsman_migration_add_index_to_sentlog2'
);

function newsman_migration_add_index_to_sentlog2() {
	global $wpdb;

	// add status key to all lists tables
	$lists = newsmanList::findAll();

	foreach ($lists as $list) {
		$sql = 'ALTER TABLE `'.$list->tblSubscribers.'` ADD INDEX status ( `status` )';
		$wpdb->query($sql);
	}
	
	$sql = 'ALTER TABLE `'.$wpdb->prefix.'newsman_sentlog` ADD INDEX recipientIdIdx ( `recipientId`, `emailId`, `listId` )';
	$wpdb->query($sql);
}


$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.2'),
	'func' => 'newsman_migration_add_re_confirm_email'
);

function newsman_migration_add_re_confirm_email() {
	$o = newsmanOptions::getInstance();
	$u = newsmanUtils::getInstance();

	// -- email template

	$tplDef = array(
		'type' => NEWSMAN_ET_RECONFIRM,
		'name' => __('Re-subscription confirmation', NEWSMAN),
		'file' => 'reconfirm.txt'
	);

	$tplFileName = NEWSMAN_PLUGIN_PATH.DIRECTORY_SEPARATOR."email-templates".DIRECTORY_SEPARATOR."newsman-system".DIRECTORY_SEPARATOR."newsman-system.html";
	$baseTpl = file_get_contents($tplFileName);

	$eml = $u->emailFromFile($tplDef['file']);

	$tpl = newsmanEmailTemplate::findOne('`system` = 1 AND `assigned_list` = %d AND `system_type` = %d', array(0, $tplDef['type']));

	if ( !$tpl ) {
		$tpl = new newsmanEmailTemplate();

		$tpl->name = $tplDef['name'];
		$tpl->subject = $eml['subject'];
		$tpl->html = $u->replaceSectionContent($baseTpl, 'std_content', $eml['html']);
		$tpl->plain = $eml['plain'];
		$tpl->system = true;
		$tpl->system_type = $tplDef['type'];

		$tpl->save();			
	}


	$doneListIds = array();
	$tpls = newsmanEmailTemplate::findOne('`system` = 1 AND `assigned_list` != 0 AND `system_type` = %d', array($tplDef['type']));

	if ( is_array($tpls) ) {
		foreach ($tpls as $tpl) {
			$doneListIds[] = $tpl->id;
		}		
	}

	$lists = newsmanList::findAll();

	foreach ($lists as $list) {
		if ( !in_array($list->id, $doneListIds) ) {
			$u->duplicateTemplate($tpl->id, false, $list->id);	
		}		
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.2'),
	'func' => 'newsman_migration_remove_address_changed_tpl'
);

function newsman_migration_remove_address_changed_tpl() {
	newsmanEmailTemplate::removeAll('`system_type` = %d', array(NEWSMAN_ET_ADDRESS_CHANGED));
}


$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.8'),
	'func' => 'newsman_migration_ensure_system_templates'
);

function newsman_migration_ensure_system_templates() {
	$u = newsmanUtils::getInstance();
	$lists = newsmanList::findAll();
	foreach ($lists as $list) {
		$tpls = newsmanEmailTemplate::findAll('`system` = 1 AND `assigned_list` = %d', array($list->id));
		if ( !$tpls || count($tpls) === 0 ) {
			$u->copySystemTemplatesForList($list->id);
		}
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.5.10'),
	'func' => 'newsman_migration_cleanup_system_eml_template_dups'
);

function newsman_migration_cleanup_system_eml_template_dups() {
	global $wpdb;
	$tbl = newsmanEmailTemplate::getTableName();
	$sql1 = "
		CREATE TEMPORARY TABLE newsman_tmp_tbl SELECT id
		  FROM `$tbl` WHERE assigned_list > 0
		  GROUP BY assigned_list, system_type;";

	$sql2 = "DELETE FROM `$tbl` WHERE id NOT IN (
		select id from newsman_tmp_tbl
		) and `assigned_list` > 0";	

	$wpdb->query($sql1);
	$wpdb->query($sql2);
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.6.0'),
	'func' => 'newsman_migration_alter_emails_table'
);

function newsman_migration_alter_emails_table() {
	global $wpdb;
	$tbl = newsmanEmail::getTableName();	
	$sql = "ALTER TABLE $tbl MODIFY COLUMN workerPid varchar(255) NOT NULL DEFAULT ''";
	$wpdb->query($sql);
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.6.4-beta-5'),
	'func' => 'newsman_migration_add_admin_notification_email_addr'
);

function newsman_migration_add_admin_notification_email_addr() {
	$o = newsmanOptions::getInstance();
	$o->set('notificationEmail', get_option('admin_email'));
}


$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.6.4-beta-7'),
	'func' => 'newsman_migration_alter_emails_table_apply_longtext_type'
);

function newsman_migration_alter_emails_table_apply_longtext_type() {
	global $wpdb;

	$email_cols = array('html','p_html', 'plain');
	$tpl_cols = array('html','p_html', 'plain', 'p_plain');

	$tbl = newsmanEmail::getTableName();	
	foreach ($email_cols as $col) {
		$wpdb->query("ALTER TABLE $tbl MODIFY COLUMN $col LONGTEXT NOT NULL DEFAULT ''");
	}

	$tbl = newsmanEmailTemplate::getTableName();
	foreach ($tpl_cols as $col) {
		$wpdb->query("ALTER TABLE $tbl MODIFY COLUMN $col LONGTEXT NOT NULL DEFAULT ''");
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.7.0-alpha-1'),
	'func' => 'newsman_migration_add_analytics_columns_and_tables'
);

function newsman_migration_add_analytics_columns_and_tables() {
	global $wpdb;

	$newEmailColumns = array('opens', 'clicks', 'unsubscribes');

	$tbl = newsmanEmail::getTableName();	
	foreach ($newEmailColumns as $col) {
		$sql = "ALTER TABLE $tbl ADD COLUMN $col int(10) unsigned NOT NULL DEFAULT 0";
		$res = $wpdb->query($sql);
	}	
}


function newsman_migration_add_emailAnalytics_column() {
	global $wpdb;

	newsmanEmail::addColumn('emailAnalytics', array( 'type' => 'bool', 'default' => true ));
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.7.0-alpha-2'),
	'func' => 'newsman_migration_add_emailAnalytics_column'
);

function newsman_migration_recheck_table_definitions() {
	$u = newsmanUtils::getInstance();
	$u->log('[migration] newsman_migration_recheck_table_definitions');
	$g = newsman::getInstance()->ensureEnvironment();
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.7.0-alpha-5'),
	'func' => 'newsman_migration_recheck_table_definitions',
	'repeat' => true
);


function newsman_migration_add_pokeback_key() {
	$o = newsmanOptions::getInstance();
	$pokebackKey = md5(get_bloginfo('wpurl').get_option('admin_email').time());
	$o->set('pokebackKey', $pokebackKey);
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.7.7-alpha-1'),
	'func' => 'newsman_migration_add_pokeback_key'
);

function newsman_migration_drop_ajax_fork_table() {
	global $wpdb;
	$wpdb->query('DROP TABLE `'.$wpdb->prefix.'newsman_ajax_forks`');
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.7.7-alpha-1'),
	'func' => 'newsman_migration_drop_ajax_fork_table'
);

function newsman_migration_check_api_key() {
	$o = newsmanOptions::getInstance();
	if ( !$o->get('apiKey') ) {
		$o->set('apiKey', sha1(sha1(microtime()).'newsman_api_key_salt'.get_option('admin_email')));
	}
}
$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.7.7-alpha-1'),
	'func' => 'newsman_migration_check_api_key'
);


function newsman_migration_add_columns_to_sentlog() {
	global $wpdb;
	$sl = newsmanSentlog::getInstance();
	$u = newsmanUtils::getInstance();
	$tbl = $sl->tableName;

	try {
		$sql = "ALTER IGNORE TABLE $tbl ENGINE=InnoDB";
		$wpdb->query($sql);
	} catch ( Exception $e ) {
	}

	try {	
		if ( !$u->hasColumn($tbl, 'status') ) {
			$sql = "ALTER IGNORE TABLE $tbl ADD COLUMN `status` TINYINT(1) unsigned NOT NULL DEFAULT 0";
			$res = $wpdb->query($sql);			
		}
	} catch ( Exception $e ) {
	}

	try {
		$sql = "ALTER IGNORE TABLE $tbl ADD UNIQUE( `emailId`, `listId`, `recipientId`);";
		$res = $wpdb->query($sql);
	} catch ( Exception $e ) {
	}

}
$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.9-beta-2'),
	'func' => 'newsman_migration_add_columns_to_sentlog',
	'repeat' => true
);

function newsman_stop_all_pending_emails() {
	$emails = newsmanEmail::findAll('status = "pending" OR status = "inprogress"');

	foreach ($emails as $email) {
		$email->status = 'stopped';
		$email->save();
	}		
}
$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.9-beta-5'),
	'func' => 'newsman_stop_all_pending_emails'
);


function newsman_add_index_to_blocked_domains() {
	global $wpdb;

	$tbl = newsmanBlockedDomain::getTableName();

	$sql = "ALTER TABLE `$tbl` ADD INDEX domain ( `domain` )";
	$wpdb->query($sql);
}
$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.9-beta-7'),
	'func' => 'newsman_add_index_to_blocked_domains'
);


function newsman_migration_cleanup_incorrect_sentlog_records() {
	global $wpdb;

	$u = newsmanUtils::getInstance();

	$sl = newsmanSentlog::getInstance();
	$tbl = $sl->tableName;

	$sql = "DELETE FROM $tbl WHERE recipientAddr = '' AND status = 0";
	$u->log('newsman_migration_cleanup_incorrect_sentlog_records: '.$sql);
	$wpdb->query($sql);
}
$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.9-beta-11'),
	'func' => 'newsman_migration_cleanup_incorrect_sentlog_records',
	'repeat' => true
);



function newsman_migration_remove_timestamps_table() {
	global $wpdb;
	$table = $wpdb->prefix.'newsman_timestamps';
	$wpdb->query("DROP TABLE `$table`");		
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.12-alpha-1'),
	'func' => 'newsman_migration_remove_timestamps_table'
);


function newsman_migration_convert_all_tables_to_innodb() {
	global $wpdb;
	$sql = 'SHOW TABLES LIKE "%_newsman_%"';
	$rows = $wpdb->get_col($sql);
	foreach ($rows as $tbl) {
		try {
			$sql = "ALTER IGNORE TABLE $tbl ENGINE=InnoDB";
			$wpdb->query($sql);
		} catch ( Exception $e ) {
		}
	}
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.12-alpha-1'),
	'func' => 'newsman_migration_convert_all_tables_to_innodb'
);


function newsman_migration_recreate_sentlog_indexes2() {
	global $wpdb;

	$wpdb->query('set session old_alter_table=1');

	$sql = 'SHOW INDEXES IN `'.$wpdb->prefix.'newsman_sentlog`';
	$res = $wpdb->get_results($sql, ARRAY_A);
	if ( is_array($res) ) {
		foreach ($res as $row) {
			if ( $row['Key_name'] !== 'PRIMARY' ) {
				$wpdb->query('DROP INDEX '.$row['Key_name'].' ON `'.$wpdb->prefix.'newsman_sentlog`');
			}
		}		
	}

	$indexes = array(
		'KEY emailIdIdx (`emailId`)',
		'KEY emailIdStatusIdx (`emailId`, `status`)',
		'KEY emailIdListIdIdx (`emailId`,`listId`)',
		'KEY recipientIdIdx (`recipientId`)',
		'UNIQUE KEY emailIdListIdRecIdIdx (`emailId`,`listId`, `recipientId`)'
	);

	foreach ($indexes as $idx) {
		$sql = 'ALTER IGNORE TABLE `'.$wpdb->prefix.'newsman_sentlog` ADD '.$idx;
		$wpdb->query($sql);
	}

	$wpdb->query('set session old_alter_table=0');
}

$newsman_changes[] = array(
	'introduced_in' => $u->versionToNum('1.8.13'),
	'func' => 'newsman_migration_recreate_sentlog_indexes2'
);

