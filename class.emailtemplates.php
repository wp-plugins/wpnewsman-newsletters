<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."class.utils.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."class.storable.php");
require_once(__DIR__.DIRECTORY_SEPARATOR."lib/emogrifier.php");

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

		'assetsURL' => 'string', // URL of the theme's assets directory
		'assetsPath' => 'string', // Path of the theme's assets directory
		'particles' => 'text' // addition email parts like repeatable post_blocks
	);


	function save() {

		$u = newsmanUtils::getInstance();

		if ( trim($this->html) === '' ) {
			$this->p_html = $this->html;
		} else {
			$emo = new Emogrifier($this->html);
			$this->p_html = $u->normalizeShortcodesInLinks( $emo->emogrify() );
		}
		
		return parent::save();
	}

}

