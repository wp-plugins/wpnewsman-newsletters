<?php

class NEWSMAN_Widget_Form extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'newsman-widget', 'description' => __('WPNewsman Subscription form for your newsletters'));
		$control_ops = array('width' => 400, 'height' => 350);
		parent::__construct('newsman-form', __('WPNewsman Subscription Form'), $widget_ops);
	}

	function widget( $args, $instance ) {

		$g = newsman::getInstance();

		extract($args);
		$listId = $instance['list'];

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
		$instance = $old_instance;
		$instance['list'] = $new_instance['list'];
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'list' => '' ) );
		$list = $instance['list'] ? $instance['list'] : '1';
		$u = newsmanUtils::getInstance();

		$widget_options_form = '
		<p><label for="'.$this->get_field_id('list').'">'.__('List:', NEWSMAN).'</label>
		<select name="'.$this->get_field_name('list').'" id="'.$this->get_field_id('list').'">
			'.$u->getListsSelectOptions($list, false).'
		</select></p>';

		echo $widget_options_form;
	}
}
register_widget('NEWSMAN_Widget_Form');