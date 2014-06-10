<script>	
	var NEWSMAN_PHP_VERSION = '<?php echo phpversion(); ?>';
	var NEWSMAN_VERSION = '<?php echo NEWSMAN_VERSION; ?>';
</script>
	<div class="modal dlg" id="newsman-modal-debugmsg" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Debug Messages', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<h4><?php _e('Raw response', NEWSMAN); ?></h4>
			<textarea id="debug-response"></textarea>
			<h4><?php _e('Additional info', NEWSMAN); ?></h4>
			<textarea id="debug-extra-info"></textarea>
			<label class="alert alert-warn"><?php _e('Note: you can remove all sensitive data before sending bug report.', NEWSMAN); ?></label>
		</div>
		<div class="modal-footer">
			<a class="btn pull-left" mr="send"><?php _e('Send Bug Report', NEWSMAN); ?></a>
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>	

<div class="row-fluid common-footer">
	<div class="span12">
		<div class="newsman-links">
			<?php
				$nwsmn_rateURL = '<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters">★★★★★</a>';
				$nwsmn_pluginURL = '<a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/wpnewsman-newsletters">wordpress.org</a>';

				$nwsmn_rateStr = __('Rate (%s) WPNewsman on %s to help their creators make it better', NEWSMAN);
			?>			
			<span><?php printf($nwsmn_rateStr, $nwsmn_rateURL, $nwsmn_pluginURL); ?></span> | <a href="http://wpnewsman.com/documentation/"><?php echo __("Documentation", NEWSMAN); ?></a> | <a href="http://support.glocksoft.net/feedback"><?php _e("Request feature", NEWSMAN); ?></a> | <a href="<?php echo NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-mailbox&thanks=1'; ?>"><?php _e("Thanks!", NEWSMAN); ?></a> | <span><?php _e("WPNewsman: ", NEWSMAN); echo nwsmn_get_prop('version'); ?></span>

</div>
		<?php
			if ( !defined('NEWSMAN_DEV_HOST') ) :
		?>
		<div class="buttons">
			<div>
				<div class="fb-like" data-href="https://www.facebook.com/WPNewsman" data-send="false" data-layout="button_count" data-width="80" data-show-faces="true"></div>			
			</div>
			<div>
				<div class="g-plusone" data-size="medium" data-width="300" data-href="http://wpnewsman.com"></div>
			</div>
			<div>
				<a href="https://twitter.com/glocksoft" class="twitter-follow-button" data-show-count="true">Follow @glocksoft</a>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
	if ( !defined('NEWSMAN_DEV_HOST') ) :
?>

<!-- social media scripts -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId=175966249095051";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));
</script>

<!-- google +1 -->
<!-- Place this tag after the last +1 button tag. -->
<script type="text/javascript">
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</script>	
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="//platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

<?php endif; ?>