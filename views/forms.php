<!-- proto-->
<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="page-header">
		<h2><?php _e('Lists and Forms', NEWSMAN); ?></h2>
	</div>

	<div class="newsman-tbl-controls">
		<div class="pagination" style="display: none;">
			<ul>
			</ul>
		</div>	

		<a class="btn" href="#" id="btn-new-form"><?php _e('New List', NEWSMAN); ?></a>
 		<button id="btn-delete-forms" style="margin: 0 3px;" type="button" class="btn btn-danger"><?php _e('Delete', NEWSMAN); ?></button>
	</div>


	<table id="newsman-forms" class="table table-striped table-bordered">
		<thead>
			<tr>
				<th class="check-column"><input id="newsman-checkall" type="checkbox"></th>				
				<th><?php /* translators: lists and forms table header */ _e('Name', NEWSMAN); ?></th>
				<th><?php /* translators: lists and forms table header */ _e('Confirmed', NEWSMAN); ?></th>
				<th><?php /* translators: lists and forms table header */ _e('Unconfirmed', NEWSMAN); ?></th>
				<th><?php /* translators: lists and forms table header */ _e('Unsubscribed', NEWSMAN); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="5" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
			</tr>
		</tbody>
	</table>

	<!--		 MODALS 		-->

	<div class="modal dlg" id="newsman-modal-new-form" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('New List', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<div class="form-vertical">
				<div class="control-group">
					<label class="control-label" for="ed-tpl-name"><?php _e('Name:', NEWSMAN); ?></label>	
					<div class="controls">
						<input type="text" id="ed-form-name" />
						<span style="display:none;" class="help-inline"><?php _e('Please enter the name of a form', NEWSMAN); ?></span>
					</div>
				</div>
			</div>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Create', NEWSMAN); ?></a>
		</div>
	</div>	
	
	<div class="modal dlg" id="newsman-modal-delete" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to delete selected forms and subscribers lists? This operation cannot be undone!', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-danger" mr="ok"><?php _e('Delete', NEWSMAN); ?></a>
		</div>
	</div>	

	<?php include("_footer.php"); ?>
</div>