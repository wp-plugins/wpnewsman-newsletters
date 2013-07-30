<div class="alert newsman-admin-notification">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<form class="newsman-ajax-from" action="ajHideCronFilaWarning">
	<h3><?php _e('Warning!', NEWSMAN); ?></h3>
	<p><?php printf( __( 'There was a problem spawning a call to the WP-Cron system on your site. This means WP-Cron jobs and email sending on your site may not work. The problem was:<br /><strong>%s</strong>. Try to add this line %s to your <strong>wp-config.php</strong> file.', NEWSMAN ), $error, $code ); ?></p>
	<div class="button-group">
		<button type="submit" name="dismiss" data-dismiss="newsman-admin-notification" class="btn"><?php _e('Dismiss', NEWSMAN); ?></button>
	</div>
	</form>
</div>