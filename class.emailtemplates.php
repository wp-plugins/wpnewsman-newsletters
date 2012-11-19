<?php

require_once("class.utils.php");
require_once('class.storable.php');
require_once('lib/emogrifier.php');

class newsmanEmailTemplate extends newsmanStorable {
	static $table = 'newsman_email_templates';
	static $props = array(
		'id' => 'autoinc',
		'system' => 'bool', // system template could not be deleted
		
		'name' => 'text',

		'subject' => 'text',
		'html' => 'text',
		'plain' =>'text',

		// processed content
		'p_subject' => 'text',
		'p_html' => 'text',
		'p_plain' =>'text',

		'assets' => 'string', // assets directory name, for imported themes
		'particles' => 'text' // addition email parts like repeatable post_blocks
	);


	function save() {

		$u = newsmanUtils::getInstance();

		$emo = new Emogrifier($this->html);		

		$this->p_html = $u->normalizeShortcodesInLinks( $emo->emogrify() );

		return parent::save();
	}

}

