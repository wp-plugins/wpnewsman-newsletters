<?php

/**
 * If you placed wp-content directory in non-classic location, plase define the server path to the wp-config.php here
 */
$path  = ''; // The path should end with a trailing slash

/** That's all, stop editing from here **/

if ( !defined('NEWSMAN_WP_LOAD_PATH') ) {

	/** trying to find the path to wp-load */
	$wp_root = dirname(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR ;
	
	if ( file_exists( $wp_root . 'wp-load.php') ) {
		define( 'NEWSMAN_WP_LOAD_PATH', $wp_root);
	} else {
		if ( file_exists( $path . 'wp-load.php') ) {
			define( 'NEWSMAN_WP_LOAD_PATH', $path);
		} else {
			exit("Could not find wp-load.php");
		}	
	}
}

require_once( NEWSMAN_WP_LOAD_PATH . 'wp-load.php');