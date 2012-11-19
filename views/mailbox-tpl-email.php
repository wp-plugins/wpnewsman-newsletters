<div id="newsman-tpl-edit" class="wrap wp_bootstrap">

	<div class="row">
		<div class="span12">
			<div style="border-bottom: 1px solid #DADADA; overflow: hidden;">
				<h2><?php echo isset($_REQUEST['id']) ? __('Edit Template', NEWSMAN) : __('New Template', NEWSMAN) ?></h2>				
			</div>			
		</div>
	</div>

	<div class="row" style="margin-top: 10px;">
		<div class="span12">
			<div class="form-vertical">
				<label for="eml-to"><h3><?php _e('To:', NEWSMAN); ?></h3></label>
				<input id="eml-to" type="text" class="span9">
			</div>
		</div>
	</div>		

	<div class="row" style="margin-top: 10px;">
		<div class="span12">
			<div class="form-vertical">
				<label for="eml-subject"><h3><?php _e('Subject:', NEWSMAN); ?></h3></label>
				<input id="eml-subject" type="text" class="span9">
			</div>
		</div>
	</div>	

	<div action="" class="form-horizontal form-compose-email">

		<div class="row">
			<div class="span12">
				<ul class="nav nav-tabs" id="style-tabs">
					<li class="active"><a href="#page" data-toggle="tab"><?php _e('Page', NEWSMAN); ?></a></li>
					<li><a href="#header" data-toggle="tab"><?php _e('Header', NEWSMAN); ?></a></li>
					<li><a href="#body" data-toggle="tab"><?php _e('Body', NEWSMAN); ?></a></li>
					<li><a href="#footer" data-toggle="tab"><?php _e('Footer', NEWSMAN); ?></a></li>
				</ul>

				<div class="tab-content" id="sub-tabs">
					<div class="tab-pane active" id="home">
						<ul class="nav nav-tabs nav-tabs-sl" id="page-tabs">
							<li class="active"><a href="#page" data-toggle="tab"><?php _e('background color', NEWSMAN); ?></a></li>
							<li><a href="#header" data-toggle="tab"><?php _e('Header', NEWSMAN); ?></a></li>
							<li><a href="#body" data-toggle="tab"><?php _e('Body', NEWSMAN); ?></a></li>
							<li><a href="#footer" data-toggle="tab"><?php _e('Footer', NEWSMAN); ?></a></li>
						</ul>
					</div>
					<div class="tab-pane" id="profile">...</div>
					<div class="tab-pane" id="messages">...</div>
					<div class="tab-pane" id="settings">...</div>
				</div>				
			</div>
		</div>

		<div class="row">			
			<div class="span9">
				<script type="text/javascript">
					var NEWSMAN_PLUGIN_URL = '<?php echo NEWSMAN_PLUGIN_URL; ?>';
					var NEWSMAN_EMAIL_ID = '<?php echo $_REQUEST["id"]; ?>';
					</script>
				<iframe id="tpl-frame" width="700" height="700" src="<?php echo get_bloginfo('wpurl').'/wp-admin/admin.php?page=newsman-templates&action=source&id='.$_REQUEST['id'];?>" frameborder="0"></iframe>
			</div>
			<div class="span3">
				<h3><?php _e('Sending', NEWSMAN); ?></h3>
				<label for="newsman-send-now" class="radio"><input type="radio" name="newsman-send" value="now" selected="selected" id="newsman-send-now"> <?php _e('Send immediately', NEWSMAN); ?></label>
				<label for="newsman-send-now" class="radio"><input type="radio" name="newsman-send" value="schedule" id="newsman-send-now"> <?php _e('Schedule sending on', NEWSMAN); ?></label>
				<div style="margin: 1em 0;">
					<input ype="text" id="newsman-send-datepicker" class="span3">
				</div>
				<button type="button" class="btn"><?php _e('Save', NEWSMAN); ?></button> or <button type="button" id="newsman-send" class="btn btn-primary"><?php _e('Send', NEWSMAN); ?></button>
			</div>
		</div>
		
 	</div>

	<div id="dialog" title="Basic dialog" style="display: none;" class="wp_bootstrap">
		<div class="dialog-bar">
			<button id="btn-save-content" class="btn btn-primary" type="button"><?php _e('Save', NEWSMAN); ?></button>
		</div>
		<textarea class="source-editor" name="editor1">hello</textarea>		
	</div>	

	<?php include("_footer.php"); ?>	 	

</div>