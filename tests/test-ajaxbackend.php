<?php

require_once('inc.env.php');
require_once('../ajaxbackend.php');

define('NEWSMAN_TESTS', 1); // defines test specific output methods
//define('NEWSMAN_TESTS_ENABLE_MAIL', 1); // if defined test code will actually send emails

global $AJ_PARAMS;

$aj = new newsmanAJAX();

// -----
$AJ_PARAMS = array(
	'ids' => '1'
);

echo "Testing ajResendConfirmation function:\n";
$aj->ajResendConfirmation();