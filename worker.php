<?php

$checkpoint = time();

ignore_user_abort(true);
set_time_limit(0);

define('NEWSMAN_WORKER', 1);

require_once('../../../wp-load.php');
require_once('class.mailman.php');

$mm = newsmanMailMan::getInstance();
$mm->runWorker();