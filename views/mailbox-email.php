<!-- proto-->

<style type="text/css">
	
	.form-compose-email {
		margin-top: 1em;
	}

	.form-compose-email .control-group .control-label {
		width: 50px;
	}

	.form-compose-email .control-group .controls {
		margin-left: 60px;
	}

	.form-compose-email textarea.wp-editor-area {
		border: none;
		-ms-box-sizing: border-box;
		-moz-box-sizing: border-box;
		-webkit-box-sizing: border-box;
		-o-box-sizing: border-box;
		box-sizing: border-box	
	}

	.input-append .add-on:last-child,
	.input-append .btn:last-child {
		-webkit-border-radius: 0 3px 3px 0;
		-moz-border-radius: 0 3px 3px 0;
		border-radius: 0 3px 3px 0;
	}	

</style>

<script>
	window.NEWSMAN_ENTITY_ID = '<?php echo isset($ent) ? $ent->id : ""; ?>' || 0;
	window.NEWSMAN_ENT_STATUS = '<?php echo ( isset($ent) && isset($ent->status) ) ? $ent->status : ""; ?>';
	window.NEWSMAN_ENT_TYPE = '<?php echo NEWSMAN_EDIT_ENTITY; ?>';
	window.NEWSMAN_LISTS = <?php $g = newsman::getInstance(); echo $g->listNamesAsJSArr(); ?>;
</script>

<div id="newsman-editor" class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	
	<div class="row">
		<div class="span12">
			<div style="border-bottom: 1px solid #DADADA; overflow: hidden;">
				<?php if ( NEWSMAN_EDIT_ENTITY == 'email' ): ?>
					<h2><?php _e('Edit email', NEWSMAN); ?></h2>
				<?php else: ?>
					<h2><?php _e('Edit template', NEWSMAN); ?></h2>
				<?php endif; ?>
			</div>			
		</div>
	</div>

	<div action="" class="form-horizontal form-compose-email">

		<div class="row">
			<div class="span9">
				<!--	TO	 -->
				
				<?php if ( NEWSMAN_EDIT_ENTITY == 'email' ): ?>

				<div class="control-group">
					<label class="control-label" for="newsman-email-to"><?php _e('To:', NEWSMAN); ?></label>
					<div class="controls">
						<div id="eml-to" type="text" class="multis" name="newsman_email_to">
							<?php $g = newsman::getInstance(); echo $g->getEmailToAsTags('li'); ?>
						</div>
					</div>
				</div>

				<?php endif; ?>
				
				<?php if ( NEWSMAN_EDIT_ENTITY == 'template' ): ?>
				
				<!--	Template name	 -->				
				<div class="control-group">
					<label class="control-label" for="newsman-template-name"><?php _e('Name:', NEWSMAN); ?></label>
					<div class="controls">
						<input type="text" class="span7" id="newsman-template-name" value="<?php echo isset($ent) ? $ent->name : ''; ?>">
					</div>
				</div>
				

				<?php endif; ?>				

				<!--	Subject	 -->
				<div class="control-group">
					<label class="control-label" for="newsman-email-subj"><?php _e('Subject:', NEWSMAN); ?></label>
					<div class="controls">
						<input type="text" class="span7" id="newsman-email-subj" value="<?php newsmanEEnt( isset($ent) ? $ent->subject : $email->subject ); ?>">
					</div>
				</div>

				<!--	Content	 -->
				<div id="poststuff">

					<textarea name="content" class="nsmn-type-simple" id="content" cols="30" rows="10"><?php echo isset($ent) ? $ent->html : ''; ?></textarea>
				</div>				
			</div>

			<div class="span3">
				<?php if ( defined('NEWSMAN_EDIT_ENTITY') && NEWSMAN_EDIT_ENTITY == 'email' ): ?>
				<h3><?php _e('Email Analytics', NEWSMAN); ?></h3>
				<!-- <span class="newsman-help-label">Track opens &amp; clicks</span> -->
				<div>
					<label class="checkbox"><input <?php echo $ent->emailAnalytics ? 'checked="checked"' : ''; ?> id="newsman-email-analytics" type="checkbox"> Track opens &amp; clicks</label>
				</div>				
				<hr>					
				<?php do_action('newsman_put_tracking_settings', $ent); ?>
				<h3><?php _e('Sending', NEWSMAN); ?></h3>
				<label for="newsman-send-now" class="radio"><input type="radio" name="newsman-send" value="now" checked="checked" id="newsman-send-now"> <?php echo ( isset($email) && $email->status == 'stopped' ) ? __('Resume', NEWSMAN) : __('Send immediately', NEWSMAN); ?></label>
				<label for="newsman-schedule" class="radio"><input <?php if ( isset($email) && $email->status === 'scheduled' ) { echo 'checked="checked"'; } ?> type="radio" name="newsman-send" value="schedule" id="newsman-schedule"> <?php _e('Schedule sending on', NEWSMAN); ?></label>
				<div style="margin: 1em 0;">
					<input type="text" id="newsman-send-datepicker" class="span3" value="<?php echo isset($email) ? $email->schedule*1000 : ''; ?>">
				</div>				
				<button type="button" id="newsman-send" class="btn btn-large btn-success"><?php echo ( isset($email) && ( $email->status === 'stopped' || $email->status === 'error' ) ) ? __('Resume', NEWSMAN) : __('Send', NEWSMAN); ?></button>
				<br><br>				
				<?php endif; ?>				
				<button class="btn btn-info" id="btn-send-test-email"><i class="icon-envelope icon-white"></i> <?php _e('Send test email', NEWSMAN);?></button>
				
				<?php $parentPage = ( NEWSMAN_EDIT_ENTITY == 'email' ) ? 'newsman-mailbox' : 'newsman-templates'; ?>
				<a type="button" href="<?php echo NEWSMAN_BLOG_ADMIN_URL; ?>admin.php?page=<?php echo $parentPage; ?>" id="newsman-close" class="btn"><?php _e('Close', NEWSMAN); ?></a>
				
				<div style="margin-top: 1em;">
					<h4 style="margin: 1.5em 0 1em;"><?php echo (NEWSMAN_EDIT_ENTITY == 'email') ? __('Email particles', NEWSMAN) : __('Template particles', NEWSMAN); ?></h4>
					<p><button id="btn-edit-post-tpl" class="btn"><?php _e('Edit Post Template', NEWSMAN); ?></button></p>
					<p><button id="btn-edit-divider-tpl" class="btn"><?php _e('Edit Post Divider Template', NEWSMAN); ?></button></p>
				</div>

				<?php if ( defined('NEWSMAN_EDIT_ENTITY') && NEWSMAN_EDIT_ENTITY == 'email' ): ?>
				<h4 style="margin: 1.5em 0 1em;"><?php _e('Publish this email', NEWSMAN); ?></h4>				
				<p><?php printf(__('You can make the email to be accessible by other people on the web with <a href="%s">this link</a>. Shortcodes for the subscriber\'s data do not work in the published email. It\'s a good idea to hide unsubscribe links with <a target="_blank" href="http://wpnewsman.com/documentation/short-codes-for-email-messages/#conditional-pair-shortcodes">conditional shortcodes</a>', NEWSMAN), (NEWSMAN_EDIT_ENTITY === 'email') ? $ent->getPublishURL() : '#error' ); ?>.</p>
				<?php endif; ?>

				<h4 style="margin: 1.5em 0 1em;"><?php _e('WPNewsman Shortcodes', NEWSMAN); ?> <a href="http://codex.wordpress.org/Shortcode_API"><i class="icon-question-sign"></i></a></h4>
				<?php $g = newsman::getInstance(); $g->putApShortcodesMetabox(); ?>
			</div>
		</div>
		
 	</div>
 	
	<div id="dialog" style="display: none;">
		<div class="editor-dialog-title">Content editor <span class="newsman-editor-dlg-close">&times;</span></div>
		<form>
			<textarea class="source-editor" name="editor1"></textarea>
		</form>
	</div>	

	<div class="modal dlg" id="newsman-modal-send-test" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Send test email', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<form>
				<?php $o = newsmanOptions::getInstance();?>
				<label for="test-email-addr"><?php _e('Email address:', NEWSMAN); ?></label>
				<input type="text" name="email" id="test-email-addr" value="">
			</form>
		</div>
		<div class="modal-footer">
			<div class="modal-loading-block" style="display: none;">
				<img src="<?php echo NEWSMAN_PLUGIN_URL;?>/img/ajax-loader.gif" alt="<?php esc_attr_e('Loading...', NEWSMAN); ?>"> <?php _e('Sending email...', NEWSMAN); ?>
			</div>
			<a class="btn" mr="cancel"><?php _e('Cancel', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Send', NEWSMAN); ?></a>
		</div>
	</div>

	<?php include("_footer.php"); ?>

</div>