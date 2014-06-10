<!-- proto-->
<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="page-header">
		<h2><?php _e('Email Templates', NEWSMAN); ?></h2>
		<?php do_action('newsman_admin_notices'); ?>
	</div>

	<div style="overflow:hidden;">
		<button style="float:right; display:none;" class="button radio-links" id="newsman-search-cancel-btn"><?php _e('Remove Search Filter', NEWSMAN); ?></button>
	</div>

	<div class="newsman-tbl-controls row">
		
		<div class="span6">
			<a class="btn" href="<?php echo NEWSMAN_BLOG_ADMIN_URL.'admin.php?page=newsman-templates&action=create-template'; ?>" id="btn-new-tpl"><?php _e('New Template', NEWSMAN); ?></a>
			
			<div class="btn-group">
				<button class="btn dropdown-toggle" data-toggle="dropdown">
					<?php _e('Install New Templates', NEWSMAN); ?>
					<span class="caret"></span>
				</button>
				<ul class="dropdown-menu">
					<li><a id="btn-open-store" href="#"><?php _e('From Templates Store...', NEWSMAN); ?></a></li>
					<li><a id="btn-import-from-file" href="#"><?php _e('From File...', NEWSMAN); ?></a></li>
				</ul>
			</div>
			
			<button id="btn-delete-tpls" style="margin: 0 3px;" type="button" class="btn btn-danger"><?php _e('Delete', NEWSMAN); ?></button>
		</div>
		<div class="span6" style="text-align: right;">
			<div class="pagination" style="display: none;">
				<ul>
				</ul>
			</div>
		</div>

	</div>


	<div class="bs-docs-example">
		<ul id="tabs-header" class="nav nav-tabs">
			<li class="active" id="newsman-tab-my-templates"><a href="#my-templates" data-toggle="tab"><?php _e('My Templates', NEWSMAN); ?></a></li>
			<!-- <li><a href="#system-templates" data-toggle="tab">System Templates</a></li> -->
			<li class="dropdown" id="newsman-tab-system-templates">
				<a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php _e('System Templates', NEWSMAN); ?> <b class="caret"></b></a>
				<ul class="dropdown-menu">
<!-- 					<li><a href="#default-system-templates" data-toggle="tab">Default System Templates</a></li>
					<li><a href="#wp-users-system-templates" data-toggle="tab">wp-users System Templates</a></li> -->
				</ul>
			</li>			
		</ul>
		<div id="tabs-container" class="tab-content">
			<div class="tab-pane fade in active" id="my-templates">
				<table id="newsman-templates" class="table table-striped table-bordered">
					<thead>
						<tr>
							<th scope="col" class="check-column"><input id="newsman-checkall" class="newsman-cb-selectall" type="checkbox"></th>
							<th scope="col"><?php _e('Name', NEWSMAN); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="6" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="tab-pane fade" id="default-system-templates">
				<h4>Default System Templates</h4>
				<table id="newsman-templates" class="table table-striped table-bordered">
					<thead>
						<tr>
							<th scope="col" class="check-column"><input id="newsman-checkall" type="checkbox"></th>
							<th scope="col"><?php _e('Name', NEWSMAN); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="6" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class="tab-pane fade" id="wp-users-system-templates">
				<h4><span class="label label-info">WP-USERS</span> system templates</h4>
				<table id="newsman-templates" class="table table-striped table-bordered">
					<thead>
						<tr>
							<th scope="col" class="check-column"><input id="newsman-checkall" type="checkbox"></th>
							<th scope="col"><?php _e('Name', NEWSMAN); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td colspan="6" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>





	<!--		 MODALS 		-->

	<div class="modal dlg" id="newsman-modal-unsubscribe" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to unsubscribe selected people?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-warning" mr="ok"><?php _e('Unsubscribe', NEWSMAN); ?></a>
		</div>
	</div>

	<div class="modal dlg" id="newsman-modal-delete" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p id="info-have-shared-res"><?php _e('Some of selected templates have resources shared with other email templates or emails.', NEWSMAN); ?></p>
			<p><?php _e('Are you sure you want to delete selected templates?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-danger" mr="rm"><?php _e('Delete', NEWSMAN); ?></a>
			<a id="btn-del-with-res" class="btn btn-danger" mr="rm_res"><?php _e('Delete with resources', NEWSMAN); ?></a>
		</div>
	</div>
	
	<div class="modal dlg" id="newsman-modal-delete-single" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to delete this template?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-danger" mr="ok"><?php _e('Delete', NEWSMAN); ?></a>
		</div>
	</div>	
	
				

	<div class="modal dlg" id="newsman-modal-chstatus" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php sprintf( _e('Are you sure you want to change status of selected subscribers to %s?', NEWSMAN), '<strong class="newsman-status"></strong>'); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-warning" mr="ok"><?php _e('Change', NEWSMAN); ?></a>
		</div>
	</div>

	<div class="modal dlg" id="newsman-modal-import-from-file" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Import Template', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<table id="uploaded-files" class="table table-striped table-bordered">
				<thead>
					<tr>
						<td colspan="2"><?php _e('Template file name', NEWSMAN); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="2" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="modal-footer">
			<a class="btn pull-left" id="btn-upload-file"><?php _e('Upload a file', NEWSMAN); ?></a>
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Import', NEWSMAN); ?></a>
		</div>
	</div>	


	<div class="modal dlg" id="newsman-modal-template-store" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Get more templates', NEWSMAN); ?>
				<select name="store" id="store-selector" style="margin-left: 10px;">
				</select>
			</h3>
		</div>
		<div class="modal-body">
			<table id="templates-previews" class="">
				<tr>
					<td class="blank-row">Loading...</td>
				</tr>			
			</table>
		</div>
		<div class="modal-footer">
			<div class="pull-left">
				<div class="pagination">
					<ul>

					</ul>
				</div>
			</div>
			<a class="btn" mr="ok"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>	



	<?php include("_footer.php"); ?>
</div>