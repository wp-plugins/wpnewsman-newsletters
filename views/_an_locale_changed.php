<div class="alert newsman-admin-notification">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<form class="newsman-ajax-from" action="ajSwitchLocale">		
		<h3><?php _e('Warning!', NEWSMAN); ?></h3>
		<p><?php /* translators: Replace the name of the language with the one you are translating to */ echo sprintf(__('You switched your blog to a new locale %s. Would you also like to replace the action pages and system email templates with English default versions?', NEWSMAN), $this->wplang); ?></p>
		<div class="notification-action-form">
			<div><label class="radio"><input name="switch-locale" value="replace-all" type="radio"> <?php _e('Yes, please replace my action pages and system email templates with translated default versions.', NEWSMAN); ?></label></div>
			<div><label class="radio"><input name="switch-locale" value="just-update-locale" type="radio"> <?php /* translators: Replace the name of the language with the one you are translating to */ _e('No, I already translated them myself. Just take a note they are in English.', NEWSMAN); ?></label></div>
			<div>
				<label class="radio"><input name="switch-locale" value="custom" type="radio"> <?php _e('Let me choose.', NEWSMAN); ?></label>
				<div style="padding-left: 10px;" id="newsman-loc-mig-custom-opts">
					<label class="checkbox"><input name="swtich-locale-pages" type="checkbox"> <?php _e("Replace action pages", NEWSMAN); ?></label>
					<label class="checkbox"><input name="swtich-locale-templates" type="checkbox"> <?php _e("Replace email templates", NEWSMAN); ?></label>
				</div>
			</div>
			<div><label class="radio"><input name="switch-locale" value="nothing" type="radio"> <?php _e('Do nothing, I\'m just checking', NEWSMAN); ?></label></div>
		</div>
		<button type="submit" name="proceed" class="btn btn-primary"><?php _e('Proceed', NEWSMAN); ?></button>
	</form>
</div>