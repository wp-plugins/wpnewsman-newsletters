<div class="alert newsman-admin-notification">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<form class="newsman-ajax-from" action="ajHideCronFailWarning">
	<h3><?php _e('Warning!', NEWSMAN); ?></h3>
	<p><?php printf( __( 'There was a problem spawning a call to the WP-Cron system on your site. This means email sending on your site may not work. The problem was:<br /><strong>%s</strong>. In order to fix the problem you can enable pokeback mode. Plugin will make calls to our server and back to yours. No sensitive data will be shared with our server. <a href="http://wpnewsman.com">Learn more</a>'  , NEWSMAN ), $error ); ?></p>
	<div class="button-group">
		<button type="submit" el-name="enablePokeback" el-value="1" data-dismiss="newsman-admin-notification" style="margin-right: .5em;" class="btn newsman-ajax-from-set-value"><?php _e('Enable Pokeback mode', NEWSMAN); ?></button>
		<button type="submit" name="dismiss" data-dismiss="newsman-admin-notification" class="btn"><?php _e('Dismiss', NEWSMAN); ?></button>
	</div>
	</form>
</div>