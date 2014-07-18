<div class="alert newsman-admin-notification">
	<button type="button" class="close" data-dismiss="newsman-admin-notification">&times;</button>
	<form class="newsman-ajax-from" action="ajHideCronAlternativeModeMessage">
		<h3><?php _e('Information!', NEWSMAN); ?></h3>
		<p><?php _e('The pokeback mode is enabled on your site. WPNewsman make http request to our server and back. No sensitive data from your website is shared with our server. <a href="http://support.glocksoft.net/kb/articles/90-messages-are-always-pending-and-not-sent" target="_blank">Learn more...</a>' , NEWSMAN); ?></p>
		<div class="button-group">
			<button type="submit" name="dismiss" data-dismiss="newsman-admin-notification" class="btn"><?php _e('Dismiss', NEWSMAN); ?></button>
		</div>		
	</form>
</div>