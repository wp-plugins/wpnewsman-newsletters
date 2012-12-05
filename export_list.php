<?php

//ignore_user_abort(true);

//require_once('../../../wp-config.php');
require_once('../../../wp-load.php');
require_once('class.utils.php');
require_once('class.list.php');

define('NEWSMAN_WORKER', 1);

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


	$list->exportToCSV($fileName, $type);

	// header("Content-disposition: attachment; filename=$fileName.zip");
	// header('Content-type: application/zip');

	// $zip = new zipfile();
	// $zip->addFile($csv, $fileName.'.csv');

	// echo $zip->file();	

	//die();
}

export_newsman_list();