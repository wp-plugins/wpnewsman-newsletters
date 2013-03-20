<?php

require_once(__DIR__.DIRECTORY_SEPARATOR.'class.emails.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.emailtemplates.php');
require_once(__DIR__.DIRECTORY_SEPARATOR.'class.utils.php');

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

	foreach ($newsman_changes as $change) {
		if ( $oldVersion < $change['introduced_in'] && !in_array($change['func'], $completed) ) {
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







