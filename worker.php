<?php

ignore_user_abort(true);
set_time_limit(0);

define('NEWSMAN_WORKER', 1);

require_once('../../../wp-load.php');

if ( isset( $_REQUEST['newsman_worker_fork'] ) && !empty($_REQUEST['newsman_worker_fork']) ) {
	$workerClass = $_REQUEST['newsman_worker_fork'];

	if ( !class_exists($workerClass) ) {
		die("requested worker class ".htmlentities($workerClass)." does not exist");
	}

	$worker = new $workerClass();
	$worker->worker();
	$worker->clearStopFlag(getmypid());
}
