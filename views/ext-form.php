<!DOCTYPE html>
<html>
  <head>
    <title>Glock Newsletter Subscription Form</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <!-- Bootstrap -->
    <link href="<?php echo NEWSMAN_PLUGIN_URL; ?>/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo NEWSMAN_PLUGIN_URL; ?>/css/newsman.css" rel="stylesheet">
    	
	<link rel='stylesheet' id='newsman-ie9-css'  href='<?php echo NEWSMAN_PLUGIN_URL; ?>/css/newsman-ie9.css?ver=1.3.1' type='text/css' media='all' />

	<?php wp_print_scripts(); ?>
  </head>
  <body class="wp_bootstrap">
	<div class="form-container">
<?php

if ( !isset($_REQUEST['uid']) || empty($_REQUEST['uid']) ) {
	echo 'Required parameter "uid" is missing in request.';
} else {
	$uid = $_REQUEST['uid'];

	$list = newsmanList::findOne('uid = %s', array($uid));
	$frm = new newsmanForm($uid);

	echo '<style>'.$list->extcss.'</style>';


	if ( !$list ) {
		die("Form with id \"$uid\" is not found");
	}	

	$data = '';
																   
	//$data .= $list->header;
	if ( !get_option('newsman_code') ) {
		$data .= '<!-- Powered by WPNewsman '.NEWSMAN_VERSION.' - http://wpnewsman.com/ -->';
	}

	$data .= '<form name="newsman-nsltr" action="'.get_bloginfo('url').'/" method="post">';

	$data.= $frm->getForm(true);

	$data .= '</form>';
	
	//$data .= $list->footer;
	
	/*
	* G-Lock Newsletter Plugin has required a great deal of time and effort to develop.
	* We will be happy if you support this development by suggesting G-Lock Newsletter Plugin to other
	* people who could be interested in it, e. g. your friends, colleagues or maybe even your website visitors.
	*
	* G-Lock Software : 2012
	*/
	if ( !get_option('newsman_code') ) { 
		$data .= '<p style="font-size:x-small; line-height:1.5em;">Powered by G-Lock Newsletter plugin</p>';
	}

	echo $data;
}
?>
    </div>
    <script src="http://code.jquery.com/jquery-latest.js"></script>
    <script src="<?php echo NEWSMAN_PLUGIN_URL; ?>/js/bootstrap.min.js"></script>
    <script src="<?php echo NEWSMAN_PLUGIN_URL; ?>/js/newsmanform.js"></script>
    <script src="<?php echo NEWSMAN_PLUGIN_URL; ?>/js/jquery.placeholder.js"></script>
	<script>
		jQuery(function($){
			var iv = setInterval(function() {
				if ( window.location.hash ) {
					$('.newsman-form-url').val(window.location.hash.substr(1));
					clearInterval(iv);
				}
			}, 100);
		});
	</script>    
  </body>
</html>