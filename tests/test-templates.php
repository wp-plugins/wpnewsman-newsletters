<?php

require_once('inc.env.php');
require_once('../class.utils.php');
require_once('../lib/emogrifier.php');

error_reporting(E_ALL);

$u = newsmanUtils::getInstance();

$eml = $u->emailFromFile('welcome.txt');



$tplFileName = NEWSMAN_PLUGIN_PATH."/email-templates/newsman-system/newsman-system.html";
$baseTpl = file_get_contents($tplFileName);

//echo $eml['html'];
//echo $baseTpl;
$html = $u->replaceSectionContent($baseTpl, 'std_content', $eml['html']);

$emo = new Emogrifier($html);

$p_html = $u->normalizeShortcodesInLinks( $emo->emogrify() );

echo $html;

// $tpl = new newsmanEmailTemplate();

// $tpl->name = $name;
// $tpl->subject = $eml['subject'];
// $tpl->html = $this->utils->replaceSectionContent($baseTpl, 'std_content', $eml['html']);
// $tpl->plain = $eml['plain'];
// $tpl->system = true;

// $emlId = $tpl->save();



	

