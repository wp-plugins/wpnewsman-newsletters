<?php
/**
 * This file tests the class.utils.php file
 * you can run it from command line "php test-utils.php "
 */

require_once('inc.env.php');
require_once('../class.list.php');

error_reporting(E_ALL);

$u = newsmanUtils::getInstance();


//$list = new newsmanList('Another list');
//$list->save();

// $list = new newsmanList();
// $s = $list->newSub();
// print_r($s);

$list = newsmanList::findOne('name = %s', array('default'));

echo '<pre>';
print_r($list);
echo '</pre>';
echo '<hr>';

echo '<pre>';
print_r($list->findAllSubscribers());
echo '</pre>';

//$s->save();

// $lists = newsmanList::findAll();
// print_r($lists);
	

