<div class="alert newsman-admin-notification">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<h3><?php _e('Warning!', NEWSMAN); ?></h3>
	<p><?php echo sprintf(__('Your blog configuration uses "%s" WP_Filesystem API which is not supported by the WPNewsman plugin. Some features such as the import of subscribers and import/installation of email templates may not work. Please, consider switching the wordpress configuration to use direct file system access.', NEWSMAN), FS_METHOD); ?></p>
</div>