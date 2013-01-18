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
			<a href="http://wpnewsman.com/documentation/"><?php echo __("Support & documentation", NEWSMAN); ?></a> | <a href="http://support.glocksoft.net/feedback"><?php _e("Request feature", NEWSMAN); ?></a> | <a href="http://wpnewsman.com/terms-conditions/">
<?php _e("Terms and Conditions", NEWSMAN); ?></a> | <a href="http://wpnewsman.com/follow-us-and-spread-the-word-about-wpnewsman/"><?php _e("Spread the Word", NEWSMAN); ?></a> | <span><?php _e("WPNewsman Version: ", NEWSMAN); echo nwsmn_get_prop('version'); ?></span>
		</div>
		<div class="buttons">
			<div>
				<div class="fb-like" data-href="http://wpnewsman.com/" data-send="false" data-layout="button_count" data-width="80" data-show-faces="true"></div>			
			</div>
			<div>
				<div class="g-plusone" data-size="medium" data-width="300" data-href="http://wpnewsman.com"></div>
			</div>
			<div>
				<a href="https://twitter.com/glocksoft" class="twitter-follow-button" data-show-count="true">Follow @glocksoft</a>
			</div>
		</div>
	</div>
</div>
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