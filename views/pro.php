<div class="wrap wp_bootstrap" id="newsman-page-options">
	<h2><?php _e('Top 4 Reasons to Upgrade to Newsman Pro', NEWSMAN); ?></h2>	
	<hr>
	<div class="row-fluid">
		<div class="span8">
			<h3>Newsman Pro is a significant upgrade over Newsman Lite that will allow you to:</h3>
			<ol>
				<li>Get the top priority customer support. We provide great documentation and support for everybody but the users of Newsman Pro jump the queue and get priority help. Visit our support site <a href="http://support.glocksoft.net" target="_blank">http://support.glocksoft.net</a></li>
				<li>Send to unlimited number of subscribers (vs. 2000 subscribers in the Lite version).</li>
				<li>Create multiple subscribers' lists with their own forms (vs. only one list in the Lite version).</li>
				<li>Embed forms on external sites.</li>
			</ol>
			<br>
			<h3>License &amp; Terms</h3>
			<p>Our Pro license is available per domain or single sub domain. Read Our <a href="http://wpnewsman.com/terms-conditions/">Terms and Conditions</a>.</p>
			<br>
			<?php if ( has_action('newsman_exta_code_form') ) : ?>
			<?php do_action('newsman_exta_code_form'); ?>
			<?php else : ?>
			<p><a target="_blank" href="https://www.iportis.com/buynow.php?pid=wpnewsmanpro&amp;noshop=1&amp;cust_site_address=<?php echo $domain; ?>" style="margin-right: 2em;" class="btn btn-primary btn-large">Upgrade to Pro for $49/year</a>You'll need to download an extra plugin.</p>
			<?php endif; ?>
						
		</div>
	</div>

	<?php include("_footer.php"); ?>
	
</div>



