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
	window.NEWSMAN_ENTITY_ID = '<?php echo isset($email) ? $email->id : ""; ?>' || 0;
	window.NEWSMAN_ENT_STATUS = '<?php echo isset($email) ? $email->status : ""; ?>';	
	window.NEWSMAN_ENT_TYPE = 'email';
	window.NEWSMAN_LISTS = <?php $g = newsman::getInstance(); echo $g->listNamesAsJSArr(); ?>;
</script>

<div id="newsman-page-compose" class="wrap wp_bootstrap">

	<div class="row">
		<div class="span12">
			<div style="border-bottom: 1px solid #DADADA; overflow: hidden;">
				<h2><?php _e('Compose email', NEWSMAN); ?></h2>
			</div>			
		</div>
	</div>

	<div action="" class="form-horizontal form-compose-email">

		<div class="row">
			<div class="span8">
				<!--	TO	 -->

				<div class="control-group">
					<label class="control-label" for="newsman-email-to"><?php _e('To:', NEWSMAN); ?></label>
					<div class="controls">
						<div id="eml-to" type="text" class="multis span7" name="newsman_email_to">
							<?php $g = newsman::getInstance(); echo $g->getEmailToAsOptions(); ?>
						</div>
					</div>
				</div>

				<!--	Subject	 -->
				<div class="control-group">
					<label class="control-label" for="newsman-email-subj"><?php _e('Subject:', NEWSMAN); ?></label>
					<div class="controls">
						<input type="text" class="span7" id="newsman-email-subj" value="<?php echo isset($email) ? $email->subject : ''; ?>">
					</div>
				</div>				

				<!--	Content	 -->
				<div id="poststuff">

					<textarea name="content" class="nsmn-type-simple" id="content" cols="30" rows="10"><?php echo isset($email) ? $email->html : ''; ?></textarea>
				</div>				
			</div>

			<div class="span4">
				<h3><?php _e('Sending', NEWSMAN); ?></h3>
				<label for="newsman-send-now" class="radio"><input type="radio" name="newsman-send" value="now" checked="checked" id="newsman-send-now"> <?php echo ( isset($email) && $email->status == 'stopped' ) ? __('Resume', NEWSMAN) : __('Send immediately', NEWSMAN); ?></label>
				<label for="newsman-schedule" class="radio"><input <?php if ( isset($email) && $email->status === 'scheduled' ) { echo 'checked="checked"'; } ?> type="radio" name="newsman-send" value="schedule" id="newsman-schedule"> <?php _e('Schedule sending on', NEWSMAN); ?></label>
				<div style="margin: 1em 0;">
					<input ype="text" id="newsman-send-datepicker" class="span3" value="<?php echo $email->schedule*1000; ?>">
				</div>				
				<button type="button" id="newsman-send" class="btn btn-primary"><?php echo ( isset($email) && ( $email->status === 'stopped' || $email->status === 'error' ) ) ? __('Resume', NEWSMAN) : __('Send', NEWSMAN); ?></button>
				<a type="button" href="<?php echo NEWSMAN_BLOG_ADMIN_URL; ?>admin.php?page=newsman-mailbox" id="newsman-close" class="btn"><?php _e('Close', NEWSMAN); ?></a>
				<br><br>
				<button class="btn btn-info" id="btn-send-test-email"><i class="icon-envelope icon-white"></i> <?php _e('Send test email', NEWSMAN);?></button>

				<h4 style="margin: 1.5em 0 1em;">wpNewsman Shortcodes <a href="http://codex.wordpress.org/Shortcode_API"><i class="icon-question-sign"></i></a></h4>
				<?php $g = newsman::getInstance(); $g->putApShortcodesMetabox(); ?>
			</div>
		</div>
		
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
				<input type="text" name="email" id="test-email-addr" value="<?php echo $o->get('sender.email');?>">
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