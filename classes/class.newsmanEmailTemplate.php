<?php

require_once(__DIR__.DIRECTORY_SEPARATOR."../lib/emogrifier.php");

class newsmanEmailTemplate extends newsmanStorable {
	static $table = 'newsman_email_templates';
	static $props = array(
		'id' => 'autoinc',
		'system' => 'int', // system template cannot be deleted

		'system_type' => 'int',
		
		'assigned_list' => 'int',

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
			if ( $u->isResponsive($this->html) ) {
				$this->p_html = $u->normalizeShortcodesInLinks( $this->html );
			} else {
				$emo = new Emogrifier($this->html);
				$this->p_html = $u->normalizeShortcodesInLinks( $emo->emogrify() );				
			}
		}
		
		return parent::save();
	}

}

