<div class="wrap wp_bootstrap" id="newsman-page-options">
	<?php include("_header.php"); ?>
	
	<div class="row-fluid" style="border-bottom: 1px solid #DADADA; height: 63px;">
		<div class="span12">
			<h2><?php _e('Settings', NEWSMAN); ?></h2>
		</div>		
	</div>

	<br>

	<form name="newsman_form_g" id="newsman_form_g" method="POST" action="">
		
		<ul class="nav nav-tabs" id="myTab">
			<?php newsmanOutputTabs(array(
				/* translators: Options page tab title */
				array( 'title' => __('General', NEWSMAN), 'id' => 'general' ),
				/* translators: Options page tab title */
				array( 'title' => __('Email Settings', NEWSMAN), 'id' => 'emailsettings' ),
				/* translators: Options page tab title */
				array( 'title' => __('Delivery Settings', NEWSMAN), 'id' => 'delivery' ),
				/* translators: Options page tab title */
				array( 'title' => __('API', NEWSMAN), 'id' => 'api' ),				
				/* translators: Options page tab title */
				array( 'title' => __('Uninstallation', NEWSMAN), 'id' => 'uninstall' )
			)); ?> 		
	 	</ul>
		 
		<div class="tab-content">
			<div class="tab-pane active" id="general">
				<!--												General	 	 							-->
				<div class="row-fluid">
					<div class="span8">
						<label class="checkbox" for="newsman_cron_clean_unsubscribed_every_week"><input type="checkbox" name="newsman-cleanUnconfirmed" value="1" /> <?php _e("Delete subscribers who didn't confirm their subscription within 7 days", NEWSMAN); ?></label>
						<label class="checkbox" for="newsman_use_double_optout"><input type="checkbox" name="newsman-useDoubleOptout" value="1" /> <?php _e("Use double opt-out procedure", NEWSMAN); ?></label>
						<div style="margin-top: 10px;">
							<h3 style="margin-bottom: 5px;"><?php _e('Plugin statistics on the dashboard', NEWSMAN); ?></h3>
							<label class="radio"><input type="radio" name="newsman-dashboardStats" value="off" /> <?php _e('Do not show', NEWSMAN); ?></label>					
							<label class="radio"><input type="radio" name="newsman-dashboardStats" value="abox" /> <?php _e('Show in the activity box (Right Now)', NEWSMAN); ?></label>					
							<label class="radio"><input type="radio" name="newsman-dashboardStats" value="widget" /> <?php _e('Show in the separate widget', NEWSMAN); ?></label>
						</div>
						<?php do_action('newsman_general_options'); ?>
						<div style="margin-top: 10px;">
							<h3 style="margin-bottom: 5px;"><?php _e('PokeBack mode', NEWSMAN); ?></h3>
							<label class="checkbox"><input type="checkbox" name="newsman-pokebackMode"> <?php _e('Use PokeBack mode ( will make secure calls to our web server and back. No sensitive data is shared with our server).', NEWSMAN); ?></label>
						</div>						
						<div style="margin-top: 10px;">
							<h3 style="margin-bottom: 5px;"><?php _e('Social profiles links', NEWSMAN); ?></h3>
							<label>Twitter</label>
							<input type="text" name="newsman-social-twitter">
							<label>Facebook</label>
							<input type="text" name="newsman-social-facebook">
							<label>Google+</label>
							<input type="text" name="newsman-social-googleplus">
							<label>LinkedIn</label>
							<input type="text" name="newsman-social-linkedin">
						</div>
						<div style="margin-top: 10px;">
							<h3 style="margin-bottom: 5px;"><?php _e('Advanced', NEWSMAN); ?></h3>
							<label class="checkbox"><input type="checkbox" name="newsman-debug"> <?php _e('Enable debug mode. ( Warning! This may slowdown your website. )', NEWSMAN); ?></label>
						</div>
						<hr>
						<div>
							<?php _e('WPNewsman Enhancement Plugin version: '.( defined('WPNEWSMAN_MU_VERSION') ? WPNEWSMAN_MU_VERSION : 'NOT INSTALLED' ), NEWSMAN) ?>
						</div>
					</div>
					<div class="span4">
						<!--   			 Info column  			 -->
 
						<h3><?php _e('Important!', NEWSMAN); ?></h3>
						<p><?php _e('If your site doesn\'t get visitors, the WordPress task scheduler will not run. This typically delays sending. If you suffer from delayed or inconsistent sending, setup a cron job on your server or use a free cron service as described in <a href="http://support.glocksoft.net/kb/articles/69-how-to-make-wordpress-cron-work">this tutorial</a>.', NEWSMAN); ?></p>
						<div class="well" style="overflow: hidden;">
							<strong><?php _e('Your blog\'s wp-cron URL:', NEWSMAN); ?></strong><br>
							<a href="<?php echo get_bloginfo('wpurl').'/wp-cron.php'; ?>"><?php echo get_bloginfo('wpurl').'/wp-cron.php'; ?></a>
						</div>
					</div>
				</div>
				<!--												/General	 	 							-->		
			</div>
			<div class="tab-pane" id="emailsettings">
				<!--												Email settings	 	 							-->
				<div class="row-fluid">
					<div class="span8">
						<h2><?php _e('Email Settings', NEWSMAN); ?></h2>
						<label class="newsman-edit-label" for="newsman-sender-name"><?php _e('From Name:', NEWSMAN); ?></label>
						<input  type="text" size="40" name="newsman-sender-name" value="" /><br>

						<label class="newsman-edit-label" for="newsman-sender-email"><?php _e('From Email:', NEWSMAN); ?></label>
						<input  type="text" size="40" name="newsman-sender-email" value="" /><br>

						<label class="newsman-edit-label" for="newsman-sender-returnEmail"><?php _e('Return Email Address:', NEWSMAN); ?></label>
						<input  type="text" size="40" name="newsman-sender-returnEmail" value="" />

						<label class="newsman-edit-label" for="newsman-notificationEmail"><?php _e('Email Address for Admin Notifications:', NEWSMAN); ?></label>
						<input  type="text" size="40" name="newsman-notificationEmail" value="" />

						<div style="margin-top: 10px;">
							<label class="checkbox" for="newsman-sendWelcome"><input type="checkbox" name="newsman-sendWelcome" value="1" /> <?php _e('Send Welcome Message', NEWSMAN); ?></label>
							<label class="checkbox" for="newsman-sendUnsubscribed"><input type="checkbox" name="newsman-sendUnsubscribed" value="1" /> <?php _e('Send Unsubscribe Notification', NEWSMAN); ?></label>
							<label class="checkbox" for="newsman-notifyAdmin"><input type="checkbox" name="newsman-notifyAdmin" value="1" /> <?php _e('Send Subscribe/Unsubscribe Event Notifications to Admin', NEWSMAN); ?></label>				
						</div>

					</div>
					<div class="span4"></div>
				</div>
				<!--												/Email settings	 	 							-->
			</div>
			<div class="tab-pane" id="delivery">
				<!--												Delivery Settings	 	 							-->
				<h2><?php _e('Email Delivery Settings', NEWSMAN); ?></h2>

				<div class="row-fluid">
					<div class="span8">
						<h3 style="margin-bottom: 10px;"><input style="vertical-align: middle;" name="newsman-mailer-throttling-on" type="checkbox"> <?php _e("Throttling", NEWSMAN); ?></h3>
						<span class="form-line"><?php _e("Limit sending to ", NEWSMAN); ?><input class="input-small" name="newsman-mailer-throttling-limit" type="text"> <?php _e("emails per", NEWSMAN); ?> <select style="width: 100px;" name="newsman-mailer-throttling-period"><option value="min"><?php _e("Minute", NEWSMAN); ?></option><option value="hour"><?php _e("Hour", NEWSMAN); ?></option><option value="day"><?php _e("Day", NEWSMAN); ?></option></select> </span>
					</div>
				</div>

				<div class="row-fluid">		
					<div class="span8">
						<fieldset id="newsman-mail-delivery" class="well" >
							<div id="newsman-advice-use-smtp" class="alert alert-info"> <strong><?php _e('Advice!', NEWSMAN); ?></strong><?php _e(' We strongly recommend that you use custom SMTP server option.', NEWSMAN); ?></div>
							<label class="radio" for="newsman-mailer-mdo"><input class="newsman-mdo" id="newsman-mdo-phpmail" type="radio" name="newsman-mailer-mdo" value="phpmail" /> <?php _e('Use PHP Mail', NEWSMAN); ?></label>
							<label class="radio" for="newsman-mailer-mdo"><input class="newsman-mdo" id="newsman-mdo-sendmail" type="radio" name="newsman-mailer-mdo" value="sendmail" /> <?php _e('Use Sendmail Directly (*nix only)', NEWSMAN); ?></label>
							<label class="radio" for="newsman-mailer-mdo"><input class="newsman-mdo" id="newsman-mdo-smtp" type="radio" name="newsman-mailer-mdo" value="smtp" /> <?php _e('Use Custom SMTP Server', NEWSMAN); ?></label><br>
							
							<div style="display: none;" id="newsman-smtp-settings" >

								<div class="btn-group">
									<div class="btn btn-mini btn-load-mail-settings" preset="gmail"><?php _e('Load GMail Settings', NEWSMAN); ?></div>
									<div class="btn btn-mini btn-load-mail-settings" preset="ses"><?php _e('Load Amazon SES SMTP Settings', NEWSMAN); ?></div>
								</div>
								<br>

								<label class="newsman-edit-label-small" for="newsman-mailer-smtp-host"><?php _e('Hostname:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="text" id="newsman_smtp_hostname" name="newsman-mailer-smtp-host" value="" /><br>
								
								<label class="newsman-edit-label-small" for="newsman_smtp_username"><?php _e('Username:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="text" id="newsman_smtp_username" name="newsman-mailer-smtp-user" value="" /><br>
								
								<label class="newsman-edit-label-small" for="newsman-mailer-smtp-user"><?php _e('Password:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="password" id="newsman_smtp_password" name="newsman-mailer-smtp-pass" value="" /><br>
								
								<label class="newsman-edit-label-small" for="newsman-mailer-smtp-port"><?php _e('Port:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="text" id="newsman_smtp_port" name="newsman-mailer-smtp-port" value="" /><br>
								
								<!-- <fieldset id="newsman_smtp_secure_conn" > -->
								<h3 style="margin-bottom: 5px;"><?php _e('Secure Connection', NEWSMAN); ?></h3>
								<div id="newsman_smtp_secure_conn" style="padding-left: 11px;">
									<label class="radio"><input  type="radio" id="newsman_smtp_secure_conn_off" name="newsman-mailer-smtp-secure" value="off" /> <?php _e("Don't Use", NEWSMAN); ?></label>
									<label class="radio"><input  type="radio" id="newsman_smtp_secure_conn_tls" name="newsman-mailer-smtp-secure" value="tls" /> <?php _e("Use Start TLS", NEWSMAN); ?></label>				
									<label class="radio"><input  type="radio" id="newsman_smtp_secure_conn_ssl" name="newsman-mailer-smtp-secure" value="ssl" /> <?php _e("Use SSL", NEWSMAN); ?></label>
								</div>
								<!-- </fieldset> -->
							</div>

							<div class="alert alert-info" style="margin-top: 10px;">
								<strong><?php _e('Test your settings:', NEWSMAN); ?></strong>
								<div class="control-group">
									<input value="<?php echo get_option("admin_email"); ?>" class="newsman-edit-small" type="text" id="newsman_smtp_test_email" />
									<button type="button" class="btn btn-info" id="smtp-btn-test-ph"><?php _e('Send Test Email', NEWSMAN); ?></button>
								</div>
								<div id="newsman-test-email-status"></div>
							</div>
						
						</fieldset>								
					</div>
					<div class="span4">
						<h3><?php _e('Have an Amazon SES account?', NEWSMAN); ?></h3>
						<p><?php _e('Take a look at our article on <a href="http://www.glocksoft.com/email-marketing-software/how-to-use-amazon-ses-smtp-interface-to-send-emails/">how to use Amazon SES SMTP interface</a>.', NEWSMAN); ?></p>
					</div>
					
					<div class="span4" style="margin-top: 20px;">
						<h3><?php _e('Need Professional SMTP Server?', NEWSMAN); ?></h3>
						<p><?php _e('While you can use any SMTP service with our Plugin, we have partnered with SMTP.com, one of the best SMTP providers on the Internet to offer you a Free smtp account. You can get a Free 28-day trial account on <a href="http://www.smtp.com/glocksoft">http://www.smtp.com/glocksoft</a>.', NEWSMAN); ?></p>
					</div>
				</div>	
				<!--												/Delivery Settings	 	 							-->
			</div>
			<div class="tab-pane" id="api">
				<!--												General	 	 							-->
				<div class="row-fluid">
					<div class="span8">
						<h3 style="margin-bottom: 5px;"><?php _e('API key', NEWSMAN); ?></h3>
						<input type="text" name="newsman-apiKey" style="width: 320px" readonly="readonly">
						<button type="button" class="btn btn-small" newsman-bind-option="apiKey" newsman-attr="data-clipboard-text" data-clipboard-text="Copy Me!" title="Click to copy API Key to clipboard.">Copy</button>
    
							
						<h3 style="margin-bottom: 5px;"><?php _e('API endpoint', NEWSMAN); ?></h3>
						<p><code><?php echo NEWSMAN_PLUGIN_URL;?>/api.php</code>
							<button type="button" class="btn btn-small" data-clipboard-text="<?php echo NEWSMAN_PLUGIN_URL;?>/api.php" title="Click to copy API endpoint to clipboard.">Copy</button>    
						</p>
					</div>
					<div class="span4">
						<!--   			 Info column  			 -->
						<h3><?php _e('API description', NEWSMAN); ?></h3>
						<a href="http://wpnewsman.com/documentation/use-wpnewsman-api/">WPNewsman API</a>
						<h3><?php _e('API integration', NEWSMAN); ?></h3>
						<?php printf(__('<a href="%s" target="_blank">Check out our guide</a> on how to send emails to WPNewsman subscribers lists from G-Lock EasyMail7.'), 'http://easymail7.com/tutorials/send-emails-to-wpnewsman-subscribers-lists/?pk_campaign=wpnewsman'); ?>						
					</div>
				</div>
				<!--												/General	 	 							-->		
			</div>			
			<div class="tab-pane" id="uninstall">
				<!--												Uninstall	 	 							-->
				<h2 style="color: #B94A48;"><?php _e('Uninstallation', NEWSMAN); ?></h2>
				<div class="row-fluid">
					<div class="span8">
						<div class="alert alert-danger" style="padding: 12px 14px 14px 14px;">
							<label for="newsman-uninstall-deleteSubscribers" class="checkbox" style="color: #B94A48;"><input id="cb-deleteSubscribers" name="newsman-uninstall-deleteSubscribers" type="checkbox" value="1"> <?php _e('Delete subscribers\' lists during uninstallation', NEWSMAN); ?></label>
							<p><?php _e('Checking this option will remove all the subscribers\' data during the plugin uninstallation. Be careful, there is no undo.', NEWSMAN); ?></p>
							<div style="text-align: right;">
								<a id="btn-uninstall-now" class="btn btn-danger"><?php _e("Uninstall now", NEWSMAN); ?></a>
							</div>
						</div>
					</div>
				</div>
				<!--												/Uninstall	 	 							-->
			</div>
			<?php do_action('newsman_options_tabpanes'); ?>
		</div>


		<p class="submit">
			<button type="button" class="btn btn-primary newsman-update-options"><?php _e('Update Options', NEWSMAN); ?></button>
		</p>    
	</form>

	<div class="modal dlg" id="newsman-modal-uninstall" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to uninstall WPNewsman Plugin and all of its settings?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-danger" mr="ok"><?php _e('Uninstall', NEWSMAN); ?></a>
		</div>
	</div>

	<?php include("_footer.php"); ?>

</div>