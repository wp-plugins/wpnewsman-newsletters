<div class="alert newsman-admin-notification">
	<button type="button" class="close" data-dismiss="newsman-admin-notification">&times;</button>
	<form class="newsman-ajax-from" action="ajHideCronAlternativeModeMessage">
		<h3><?php _e('Warning!', NEWSMAN); ?></h3>
		<p><?php _e('The wp-cron system on your blog works in alternative mode. Scheduled email sending will work only with WPNewsman admin page opened in the browser.' , NEWSMAN); ?></p>
		<div class="button-group">
			<button type="submit" name="dismiss" data-dismiss="newsman-admin-notification" class="btn"><?php _e('Dismiss', NEWSMAN); ?></button>
		</div>		
	</form>
</div>