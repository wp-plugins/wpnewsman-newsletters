<?php

class newsmanAnLink extends newsmanStorable {
	static $table = 'newsman_an_links';
	static $props = array(
		'id' => 'autoinc',

		'emailId' => 'int',
		'URL' => 'text',
		'uniqueClicks' => array( 'type' => 'int', 'readOnly' => true )
	);

	static $keys = array(
		'eml_url' => array( 'cols' => array( 'emailId', 'URL' ) )
	);

	public function uniqueClick() {
		global $wpdb;
		$tn = $this->getTableName();
		$this->data['uniqueClicks'] += 1;
		$sql = "UPDATE $tn SET `uniqueClicks` = `uniqueClicks`+1 WHERE id = %d";
		$res = $wpdb->query($wpdb->prepare($sql, $this->id));

		if ( is_wp_error($res) ){
			wp_die('[uniqueClick] Error: '.$res->get_error_message());
		} else {
			return $res;
		}
	}
}