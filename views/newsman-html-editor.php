<!-- proto-->

<script>
	window.NEWSMAN_LISTS = <?php $g = newsman::getInstance(); echo $g->listNamesAsJSArr(); ?>;

	window.onload = function(){
		if ( parent.newsmanHtmlEditorContentLoaded ) {
			parent.newsmanHtmlEditorContentLoaded();
		}
	};
</script>

<div id="newsman-html-editor" class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="row">
		<div class="span12">
			<div style="border-bottom: 1px solid #DADADA; overflow: hidden;">
				<?php
					$e_name = ( NEWSMAN_EDIT_ENTITY == 'email' ) ? __('Email', NEWSMAN) : __('Template', NEWSMAN);

					$s_ed =  sprintf( __('Edit %s', NEWSMAN), $e_name );
					$s_new = sprintf( __('New %s', NEWSMAN), $e_name );

					$title = $id ? $s_ed : $s_new ;
				?>
				<h2><?php echo $title ?></h2>				
			</div>			
		</div>
	</div>

	<?php if ( NEWSMAN_EDIT_ENTITY == 'email' ) : ?>
	<div class="row" style="margin-top: 10px;">
		<div class="span12">
			<div class="form-vertical">
				<label for="eml-to"><h3><?php _e('To:', NEWSMAN); ?></h3></label>
				<div id="eml-to" type="text" class="multis span9">
					<?php $g = newsman::getInstance(); echo $g->getEmailToAsOptions(); ?>
				</div>
			</div>
		</div>
	</div>	
	<?php endif; ?>


	<div class="row" style="margin-top: 10px;">
		<div class="span12">
			<div class="form-vertical">
				<label for="tpl-subject"><h3><?php _e('Subject:', NEWSMAN); ?></h3></label>
				<input id="tpl-subject" type="text" class="span9">
			</div>
		</div>
	</div>	

	<div action="" class="form-horizontal form-compose-email">

		<div class="row">
			<div class="span12" id="tpl-styling-controls">
				<ul class="nav nav-tabs" id="style-tabs">
<!-- 					<li class="active"><a href="#page" data-toggle="tab"><?php _e('Page', NEWSMAN); ?></a></li>
					<li><a href="#header" data-toggle="tab"><?php _e('Header', NEWSMAN); ?></a></li>
					<li><a href="#body" data-toggle="tab"><?php _e('Body', NEWSMAN); ?></a></li>
					<li><a href="#footer" data-toggle="tab"><?php _e('Footer', NEWSMAN); ?></a></li> -->
				</ul>

				<div class="tab-content" id="sub-tabs">
					<div class="tab-pane active" id="home">						
						<div class="modal-loading-block">
							<img src="<?php echo NEWSMAN_PLUGIN_URL;?>/img/ajax-loader.gif" alt="<?php esc_attr_e('Loading...', NEWSMAN); ?>"> <?php _e('Loading...', NEWSMAN); ?>
						</div>
						<ul class="nav nav-tabs nav-tabs-sl" id="page-tabs">							
<!-- 							<li class="active"><a href="#page" data-toggle="tab"><?php _e('background color', NEWSMAN); ?></a></li>
							<li><a href="#header" data-toggle="tab"><?php _e('Header', NEWSMAN); ?></a></li>
							<li><a href="#body" data-toggle="tab"><?php _e('Body', NEWSMAN); ?></a></li>
							<li><a href="#footer" data-toggle="tab"><?php _e('Footer', NEWSMAN); ?></a></li> -->
						</ul>
					</div>
				</div>				
			</div>
		</div>

		<div class="row">			
			<div class="span9">
				<script type="text/javascript">
					var NEWSMAN_PLUGIN_URL = '<?php echo NEWSMAN_PLUGIN_URL; ?>';
					var NEWSMAN_ENTITY_ID = '<?php echo $id; ?>';
					var NEWSMAN_ENT_TYPE = '<?php echo ( $page == "newsman-templates" && $action == "edit" ) ? "template" : "email";  ?>';
					var NEWSMAN_ENT_ASSETS = '';
					var NEWSMAN_ENT_STATUS = '<?php echo isset($email) ? $email->status : ""; ?>';
					</script>
				<?php
					if ( defined('NEWSMAN_EDIT_ENTITY') ) {
						if ( NEWSMAN_EDIT_ENTITY == 'template' ) {
							$url = get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-templates&action=source&id='.$id;
						} elseif ( NEWSMAN_EDIT_ENTITY == 'email' ) {
							$url = get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-mailbox&action=source&id='.$id;
						}
					}
				?>
				<iframe id="tpl-frame" width="700" height="1000" src="<?php echo $url; ?>" frameborder="0"></iframe>
			</div>
			<div class="span4 tpl-actions">

			<?php if ( NEWSMAN_EDIT_ENTITY == 'email' ): ?>
				<div class="form-vertical" id="newsman-send-form">
					<?php do_action('newsman_put_tracking_settings', $email); ?>
					<h3><?php _e('Sending', NEWSMAN); ?></h3>
					<input type="hidden" name="page" value="newsman-mailbox">
					<input type="hidden" name="action" value="send">

					<label for="newsman-send-now" class="radio"><input type="radio" name="newsman-send" value="now" id="newsman-send-now" checked="checked"> <?php echo ( isset($email) && $email->status == 'stopped' ) ? __('Resume', NEWSMAN) : __('Send immediately', NEWSMAN); ?></label>
					<label for="newsman-send-scheduled" class="radio"><input type="radio" name="newsman-send" value="schedule" id="newsman-send-scheduled"> Schedule sending on</label>
					<div style="margin: 1em 0;">
						<input ype="text" id="newsman-send-datepicker" class="span3">
					</div>
					
					<button id="newsman-btn-send" type="button" class="btn btn-primary"><?php echo ( isset($email) && ( $email->status === 'stopped' || $email->status === 'error' ) ) ? __('Resume', NEWSMAN) : __('Send', NEWSMAN); ?></button>
					<a type="button" href="<?php echo NEWSMAN_BLOG_ADMIN_URL; ?>admin.php?page=newsman-mailbox" id="newsman-close" class="btn"><?php _e('Close', NEWSMAN); ?></a>
				</div>
			<?php else : ?>
				<a href="<?php echo get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-templates';?>" type="button" class="btn">&times; <?php _e('Close editor', NEWSMAN);?></a>
				<a href="<?php echo get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-templates&action=download&id='.$id;?>" type="button" class="btn"><i class="icon-download"></i> <?php _e('Export template', NEWSMAN); ?></a>
			<?php endif; ?>
				<hr>
				<a class="btn btn-info" id="btn-send-test-email"><i class="icon-envelope icon-white"></i> <?php _e('Send test email', NEWSMAN); ?></a>
				<div id="digest-controls" style="display: none;">
					<h4 style="margin: 1.5em 0 1em;"><?php _e('Digest controls', NEWSMAN); ?></h4>
					<button id="btn-add-posts" class="btn btn-info"><i class="icon-plus icon-white"></i><?php _e(' Add posts', NEWSMAN); ?></button><br>
					<button id="btn-edit-postblock" class="btn"><i class="icon-edit"></i><?php _e(' Edit post template', NEWSMAN); ?></button><br>
					<button id="btn-edit-post-divider" class="btn"><i class="icon-edit"></i><?php _e(' Edit post divider', NEWSMAN); ?></button>								
				</div>
				<h4 style="margin: 1.5em 0 1em;">wpNewsman Shortcodes <a href="http://codex.wordpress.org/Shortcode_API"><i class="icon-question-sign"></i></a></h4>
				<?php $g = newsman::getInstance(); $g->putApShortcodesMetabox(); ?>
			</div>
		</div>
		
 	</div>

<!-- 	<div id="dialog" title="Edit section" style="display: none;" class="wp_bootstrap">
		<div class="dialog-bar">
			<button id="btn-save-content" class="btn btn-primary" type="button"><?php _e('Save', NEWSMAN); ?></button>
		</div>
		<textarea class="source-editor" name="editor1"><?php _e('hello', NEWSMAN); ?></textarea>		
	</div> -->

	<div id="dialog" style="display: none;">
		<div class="editor-dialog-title">Content editor <span class="newsman-editor-dlg-close">&times;</span></div>
		<form>
		<textarea class="source-editor" name="editor1"><?php _e('hello', NEWSMAN); ?></textarea>
		</form>
	</div>	
	
	<div class="modal dlg" id="newsman-modal-add-posts" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Choose posts to include into digest', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<div id="post-selector">
				<div class="row-fluid maxh">
					<div class="span12 maxh rel">
						<div id="posts-controls">
							<input type="text" id="newsman-search" placeholder="<?php esc_attr_e('Search...', NEWSMAN); ?>">
							<div class="newsman-bcst-topbar">
								<label><span class="text"><?php _e('Post type:', NEWSMAN);?></span>
									<select name="newsman_post_type" id="newsman-post-type">
									<?php
										$u = newsmanUtils::getInstance();
										$types = $u->getPostTypes();
										
										foreach ($types as $t) {
											$sel = $t['selected'] ? ' selected="selected"' : '';
											echo '<option value="'.$t['name'].'"'.$sel.'>'.$t['name'].'</option>';
										}
									?>
									</select>
								</label>
								<label><span class="text"><?php _e('Categories:', NEWSMAN); ?></span>
									<select name="newsman_bcst_sel_cat" id="newsman-bcst-sel-cat" multiple="multiple">
									<?php
										$u = newsmanUtils::getInstance();
										$categories = $u->getCategories();
										
										foreach ($categories as $item) {
											$sel = $item->selected ? ' selected="selected"' : '';
											echo '<option value="'.$item->cat_ID.'"'.$sel.'>'.$item->name.'</option>';
										}
									?>
									</select>
								</label>
								<label><span class="text"><?php _e('Authors:', NEWSMAN); ?></span>
									<select id="newsman-bcst-sel-auth" name="newsman_bcst_sel_auth"  multiple="multiple">
									<?php
										 $authors = $u->getAuthors();
										foreach ($authors as $item) {
											$sel = $item['selected'] ? ' selected="selected"': '';
											echo '<option value="'.$item['ID'].'"'.$sel.'>'.$item['user_nicename'].'</option>';			
										}
									?>
									</select>
								</label>								
								<label><span class="text"><?php _e('Use content:', NEWSMAN); ?></span>
									<select name="" id="newsman-content-type">
										<option value="full"><?php _e('Full post', NEWSMAN); ?></option>
										<option value="excerpt"><?php _e('Excerpt', NEWSMAN); ?></option>
										<option value="fancy" selected="selected"><?php _e('Fancy excerpt', NEWSMAN); ?></option>
									</select>
								</label>
								<label class="checkbox"><input type="checkbox" id="newsman-bcst-include-private"><?php _e(' Show private posts', NEWSMAN); ?></label>
								<h3 id="posts-counter"><?php _e('No posts selected', NEWSMAN); ?></h3>
							</div>
						</div>
						<div id="newsman-bcst-posts">
						</div>
					</div>	
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Cancel', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="insert"><?php _e('Insert', NEWSMAN); ?></a>
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