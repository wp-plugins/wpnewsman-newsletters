<div class="wrap wp_bootstrap" id="newsman-page-options">
	<div class="row-fluid" style="border-bottom: 1px solid #DADADA; height: 63px;">
		<div class="span12">
			<h2><?php _e('Settings', NEWSMAN); ?></h2>
		</div>		
	</div>

	<br>

	<form name="newsman_form_g" id="newsman_form_g" method="POST" action="">
		
		<ul class="nav nav-tabs" id="myTab">
			<?php newsmanOutputTabs(array(
				array( 'title' => 'General', 'id' => 'general' ),
				array( 'title' => 'Email Settings', 'id' => 'emailsettings' ),
				array( 'title' => 'Delivery Settings', 'id' => 'delivery' ),
				array( 'title' => 'Uninstallation', 'id' => 'uninstall' )
			)); ?> 		
	 	</ul>
		 
		<div class="tab-content">
			<div class="tab-pane active" id="general">
				<!--												General	 	 							-->
				<div class="row-fluid">
					<div class="span8">
						<label class="checkbox" for="newsman_cron_clean_unsubscribed_every_week"><input type="checkbox" name="newsman-cleanUnconfirmed" value="1" /> Delete subscribers who didn't confirm their subscription within 7 days</label>
						<div style="margin-top: 10px;">
							<h3 style="margin-bottom: 5px;"><?php _e('Plugin statistics on the dashboard', NEWSMAN); ?></h3>
							<label class="radio"><input type="radio" name="newsman-dashboardStats" value="off" /> <?php _e('Do not show', NEWSMAN); ?></label>					
							<label class="radio"><input type="radio" name="newsman-dashboardStats" value="abox" /> <?php _e('Show in the activity box (Right Now)', NEWSMAN); ?></label>					
							<label class="radio"><input type="radio" name="newsman-dashboardStats" value="widget" /> <?php _e('Show in the separate widget', NEWSMAN); ?></label>
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
<!-- 						<h3><?php _e('Other', NEWSMAN); ?></h3>
						<label class="checkbox" for="newsman-hideWelcomePage"><input type="checkbox" name="newsman-hideWelcomePage" value="1" /> Hide plugin welcome page.</label>
 -->						
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
						<input  type="text" size="40" name="newsman-sender-name" value="" /><br />

						<label class="newsman-edit-label" for="newsman-sender-email"><?php _e('From Email:', NEWSMAN); ?></label>
						<input  type="text" size="40" name="newsman-sender-email" value="" /><br />

						<label class="newsman-edit-label" for="newsman-sender-returnEmail"><?php _e('Return Email Address:', NEWSMAN); ?></label>
						<input  type="text" size="40" name="newsman-sender-returnEmail" value="" />

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
						<h3 style="margin-bottom: 10px;"><input style="vertical-align: middle;" name="newsman-mailer-throttling-on" type="checkbox"> Throttling</h3>
						<span class="form-line">Limit sending to <input class="span1" name="newsman-mailer-throttling-limit" type="text"> emails per <select style="width: 100px;" name="newsman-mailer-throttling-period"><option value="min">Minute</option><option value="hour">Hour</option><option value="day">Day</option></select> </span>
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
								<input class="newsman-edit-small" type="text" id="newsman_smtp_hostname" name="newsman-mailer-smtp-host" value="" /><br />
								
								<label class="newsman-edit-label-small" for="newsman_smtp_username"><?php _e('Username:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="text" id="newsman_smtp_username" name="newsman-mailer-smtp-user" value="" /><br />
								
								<label class="newsman-edit-label-small" for="newsman-mailer-smtp-user"><?php _e('Password:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="password" id="newsman_smtp_password" name="newsman-mailer-smtp-pass" value="" /><br />
								
								<label class="newsman-edit-label-small" for="newsman-mailer-smtp-port"><?php _e('Port:', NEWSMAN); ?></label>
								<input class="newsman-edit-small" type="text" id="newsman_smtp_port" name="newsman-mailer-smtp-port" value="" /><br />
								
								<!-- <fieldset id="newsman_smtp_secure_conn" > -->
								<h3 style="margin-bottom: 5px;"><?php _e('Secure Connection', NEWSMAN); ?></h3>
								<div id="newsman_smtp_secure_conn" style="padding-left: 11px;">
									<label class="radio"><input  type="radio" id="newsman_smtp_secure_conn_off" name="newsman-mailer-smtp-secure" value="off" /> <?php _e('Don\'t Use'); ?></label>
									<label class="radio"><input  type="radio" id="newsman_smtp_secure_conn_tls" name="newsman-mailer-smtp-secure" value="tls" /> <?php _e('Use Start TLS'); ?></label>				
									<label class="radio"><input  type="radio" id="newsman_smtp_secure_conn_ssl" name="newsman-mailer-smtp-secure" value="ssl" /> <?php _e('Use SSL'); ?></label>
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
						<p><?php _e('Take a look at our article on <a href="http://www.glocksoft.com/how-to-use-amazon-ses-smtp-interface-to-send-emails/">how to use Amazon SES SMTP interface</a>.', NEWSMAN); ?></p>
					</div>
					
					<div class="span4" style="margin-top: 20px;">
						<h3><?php _e('Need Professional SMTP Server?', NEWSMAN); ?></h3>
						<p><?php _e('While you can use any SMTP service with our Plugin, we have partnered with SMTP.com, one of the best SMTP providers on the Internet to offer you a Free smtp account. You can get a Free 28-day trial account on <a href="http://www.smtp.com/glocksoft">http://www.smtp.com/glocksoft</a>.', NEWSMAN); ?></p>
					</div>
				</div>	
				<!--												/Delivery Settings	 	 							-->
			</div>
			<div class="tab-pane" id="uninstall">
				<!--												Uninstall	 	 							-->
				<h2 style="color: #B94A48;"><?php _e('Uninstallation', NEWSMAN); ?></h2>
				<div class="row-fluid">
					<div class="span8">
						<div class="alert alert-danger" style="padding: 12px 14px 14px 14px;">
							<label for="newsman-uninstall-deleteSubscribers" class="checkbox" style="color: #B94A48;"><input name="newsman-uninstall-deleteSubscribers" type="checkbox" value="1"> <?php _e('Delete subscribers\' lists during uninstallation', NEWSMAN); ?></label>
							<p><?php _e('Checking this option will remove all the subscribers\' data during the plugin uninstallation. Be carefull, there is no undo.', NEWSMAN); ?></p>
							<div style="text-align: right;">
								<a id="btn-uninstall-now" class="btn btn-danger">Uninstall now</a>
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
			<p><?php _e('Are you sure you want to uninstall Glock Newsletter Plugin and all of its settings?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-danger" mr="ok"><?php _e('Uninstall', NEWSMAN); ?></a>
		</div>
	</div>

	<?php include("_footer.php"); ?>

</div>