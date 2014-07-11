<?php

define('NEWSMAN_WORKER', 1);

if ( ! defined('WP_ADMIN') )
	define('WP_ADMIN', true);

if ( ! defined('WP_NETWORK_ADMIN') )
	define('WP_NETWORK_ADMIN', false);

if ( ! defined('WP_USER_ADMIN') )
	define('WP_USER_ADMIN', false);

if ( ! WP_NETWORK_ADMIN && ! WP_USER_ADMIN ) {
	define('WP_BLOG_ADMIN', true);
}

$_SERVER['PHP_SELF'] = '/wp-admin/wpnewsman-worker.php';

require_once(ABSPATH.'wp-load.php');

ignore_user_abort(true);
set_time_limit(0);


if ( isset( $_REQUEST['newsman_worker_fork'] ) && !empty($_REQUEST['newsman_worker_fork']) ) {
	$workerClass = $_REQUEST['newsman_worker_fork'];

	if ( !class_exists($workerClass) ) {
		die("requested worker class ".htmlentities($workerClass)." does not exist");
	}

	if ( !isset($_REQUEST['workerId']) ) {
		die('workerId parameter is not defiend in the query');
	}

	$worker = new $workerClass($_REQUEST['workerId']);
	$worker_lock = isset($_REQUEST['worker_lock']) ? $_REQUEST['worker_lock'] : null;
	$worker->run($worker_lock);
}