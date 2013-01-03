<?php
/**
 * This file tests the class.utils.php file
 * you can run it from command line "php test-utils.php "
 */

require_once('inc.env.php');
require_once('../class.utils.php');

error_reporting(E_ALL);

$u = newsmanUtils::getInstance();

assert( $u->jsArrToMySQLSet('[1,2,3]') === '(1,2,3)' );
assert( $u->jsArrToMySQLSet('1,2,3') === '(1,2,3)' );
assert( $u->jsArrToMySQLSet('1') === '(1)' );

$content = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.';
$c = $u->fancyExcerpt($content, 12);
assert( $c  === 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. (...)');

$field = $u->sanitizeDBFieldName('php test+-utils.php');
assert( $field === 'php_test_utils_php' );


$pwd = 'This is some Secret password';

assert( $pwd === $u->decrypt_pwd($u->encrypt_pwd($pwd)) );

$fn =  $u->extractURLFilename('http://test.com:8888/wp-content/plugins/newsman2/emil-templates/big.gif');

assert( $fn === 'big.gif' );


$content = <<<THECONTENT
<img src="ribbon.png" gssource="ribbon.psd" gsdefault="ribbon.png" gsedit="digest_header_image" gsblock="image" placehold="46x94.gif" width="46" height="94" style="border: 0; background-color: #fff; line-height: 100%;border: 0;">
THECONTENT;

//echo htmlentities($u->expandAssetsURLs($content, 'digest'));	

