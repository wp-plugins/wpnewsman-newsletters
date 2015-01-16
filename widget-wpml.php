<?php

class NEWSMAN_Widget_Form_WPML extends WP_Widget {

	function __construct() {
		$widget_ops = array(
			'classname' => 'newsman-widget-wpml',
			'description' => __('WPNewsman multilanguage subscription form for your newsletters')
		);
		$control_ops = array('width' => 400, 'height' => 350);
		parent::__construct('newsman-form-wpml', __('WPNewsman Subscription Multilanguage Form'), $widget_ops);
	}

	function widget( $args, $instance ) {

		$g = newsman::getInstance();

		extract($args);
		$langMap = $instance;

		$currentLang = defined('ICL_LANGUAGE_CODE') ? ICL_LANGUAGE_CODE : 'en';

		$listId = $langMap[$currentLang];

		$list = newsmanList::findOne('id = %d', array($listId));

		$form = new newsmanForm($listId);

		echo $before_widget;

		$title = apply_filters( 'widget_title', $form->title );

		if ( ! empty( $title ) ) {
			echo $before_title ? $before_title : '<h3>';
			echo $title;
			echo $after_title ? $after_title : '</h3>';
		}

		$g->putForm(true, $listId);

		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$old_instance = $new_instance;
		return $old_instance;
	}

	function form( $instance ) {

		$languages = icl_get_languages('skip_missing=1');

		$args = array();

		foreach ($languages as $l) {
			$args[ $l['language_code'] ] = '';
		}

		$instance = wp_parse_args( (array) $instance, $args );

		$langMap = isset($instance) ? $instance : array();

		$u = newsmanUtils::getInstance();

		$widget_options_form = '';

		foreach ($languages as $l) {
			$widget_options_form .= 
			'<p>
				<label for="'.$this->get_field_id($l['language_code']).'">'.$l['native_name'].'</label>
				<select name="'.$this->get_field_name($l['language_code']).'" id="'.$this->get_field_id($l['language_code']).'">
					'.$u->getListsSelectOptions($langMap[$l['language_code']], false).'
				</select>
			</p>';
		}

		echo $widget_options_form;
	}
}
register_widget('NEWSMAN_Widget_Form_WPML');