<div class="wrap wp_bootstrap" id="newsman-page-upgrade-to-pro">
	<?php include("_header.php"); ?>
	<h2><?php _e('Top 5 Reasons to Upgrade to WPNewsman Pro', NEWSMAN); ?></h2>	
	<hr>
	<div class="row-fluid">
		<div class="span8">
			<h3 style="margin-bottom: 20px;"><?php _e('WPNewsman Pro is a significant upgrade over WPNewsman Lite that will allow you to:', NEWSMAN); ?></h3>
			<ol>
				<li style="margin-bottom: 10px;"><?php _e('<strong>Send to unlimited number of subscribers</strong> (vs. 2000 subscribers in the Lite version) using Amazon SES SMTP settings and enjoy high deliverability rate like big email service providers.', NEWSMAN); ?></li>
                <li style="margin-bottom: 10px;"><?php _e('<strong>Automatically process bounced emails</strong> after each mailing and maintain your list clean and verified -- you protect your IP from being blacklisted for continuous sending to invalid email addresses.', NEWSMAN); ?></li> 
				<li style="margin-bottom: 10px;"><?php _e('<strong>Get FULL email statistics for ultimate campaign tracking</strong>. It is very important to track open rates and CTR (clickthroughs) of your email campaigns. See which messages get a higher response and optimize your email campaigns for each of your subscriber segment. Play with subjects, layouts, call-to-actions and text and you will create the perfect campaign which will inevitably lead to sales.', NEWSMAN); ?></li>
				<li style="margin-bottom: 10px;"><?php _e('<strong>Merge Google Analytics or Piwik remote tracking into links</strong>. Find out which of your visitors came to your site via your newsletters. Campaign tracking in Piwik or Google Analytics lets you track how efficient various marketing campaigns are in bringing visitors to your website (visits, page views, etc.), how well these visitors convert and how much revenue they generate.', NEWSMAN); ?></li>
              	<li style="margin-bottom: 10px;"><?php _e('<strong>Get the top priority customer support</strong>. We provide great documentation and support for everybody but the users of WPNewsman Pro jump the queue and get priority help. Visit our support site <a href="http://support.glocksoft.net" target="_blank">http://support.glocksoft.net</a>', NEWSMAN); ?></li>              	
			</ol>
			<br>
			<h3><?php _e('License & Terms', NEWSMAN); ?></h3>
			<p><?php _e('Our Pro license is available per domain or single sub domain. Read Our <a href="http://wpnewsman.com/terms-conditions/">Terms and Conditions</a>.', NEWSMAN); ?></p>
			<br>
			<?php if ( has_action('newsman_exta_code_form') ) : ?>
			<?php do_action('newsman_exta_code_form'); ?>
			<?php else : ?>
			<div>
				<div style="float: left;"><a target="_blank" href="https://secure.avangate.com/order/checkout.php?PRODS=4630229&amp;QTY=1&amp;CART=1&amp;CLEAN_CART=1&amp;ADDITIONAL_site_address[4630229]=<?php echo $domain; ?>" class="btn btn-warning btn-large"><?php echo sprintf( __('Upgrade to Pro for $%d/year', NEWSMAN), 49); ?></a></div>
			</div><br>
			<div style="margin-top: 25px;"><?php echo sprintf( __('or get special <a href="https://secure.avangate.com/order/checkout.php?PRODS=4630229&QTY=5&CART=1&CLEAN_CART=1&ADDITIONAL_site_address[4630229]=%s">5-sites discounted license for $%s</a> <br><br> To activate the PRO version, you\'ll need to download an extra plugin WPNewsman Pro Extension.', NEWSMAN), $domain, 149 );?></div>
			<?php endif; ?>
		</div>
	</div>

	<?php include("_footer.php"); ?>
	
</div>



