<div class="wrap wp_bootstrap" id="newsman-page-options">
	<h2><?php _e('Top 5 Reasons to Upgrade to WPNewsman Pro', NEWSMAN); ?></h2>	
	<hr>
	<div class="row-fluid">
		<div class="span6">
			<h3 style="margin-bottom: 20px;">WPNewsman Pro is a significant upgrade over WPNewsman Lite that will allow you to:</h3>
			<ol>
				<li style="margin-bottom: 10px;"><strong>Send to unlimited number of subscribers</strong> (vs. 2000 subscribers in the Lite version) using Amazon SES SMTP settings and enjoy high deliverability rate like big email service providers.</li>
                <li style="margin-bottom: 10px;"><strong>Automatically process bounced emails</strong> after each mailing and maintain your list clean and verified -- you protect your IP from being blacklisted for continuous sending to invalid email addresses.</li> 
				<li style="margin-bottom: 10px;"><strong>Create multiple subscribers' lists</strong> with their own forms (vs. only one list in the Lite version) and send targeted email newsletters to each list following the subscriber's preferences.</li>
				<li style="margin-bottom: 10px;"><strong>Embed subscription forms on external sites</strong> (not necessarily WordPress) and collect subscribers into one main database in WordPress. More subscribers, more potentional buyers!</li>
              <li style="margin-bottom: 10px;"><strong>Get the top priority customer support</strong>. We provide great documentation and support for everybody but the users of WPNewsman Pro jump the queue and get priority help. Visit our support site <a href="http://support.glocksoft.net" target="_blank">http://support.glocksoft.net</a></li>
			</ol>
			<br>
			<h3>License &amp; Terms</h3>
			<p>Our Pro license is available per domain or single sub domain. Read Our <a href="http://wpnewsman.com/terms-conditions/">Terms and Conditions</a>.</p>
			<br>
			<?php if ( has_action('newsman_exta_code_form') ) : ?>
			<?php do_action('newsman_exta_code_form'); ?>
			<?php else : ?>
			<div>
				<div style="float: left;"><a target="_blank" href="https://www.iportis.com/buynow.php?pid=wpnewsmanpro&amp;cc=INTRO&amp;noshop=1&amp;cust_site_address=<?php echo $domain; ?>" class="btn btn-primary btn-large">Upgrade to Pro for $79/year</a></div>
				
			</div><br />
			<div style="margin-top: 25px;">Special introductory price: $79/year (retail price: $97/year) <br> To activate the PRO version, you'll need to download an extra plugin WPNewsman Pro Extension.</div>
			<?php endif; ?>
		</div>
	</div>

	<?php include("_footer.php"); ?>
	
</div>



