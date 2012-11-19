<!-- proto-->
<div class="wrap wp_bootstrap">
	<div style="border-bottom: 1px solid #DADADA; overflow: hidden;">
		<h2><?php _e('Email Templates', NEWSMAN); ?></h2>
	</div>

	<div style="overflow:hidden;">
<!-- 		<ul class="subsubsub" style="float:left; margin: 5px 0 0 0;">
			<li><a href="#/all" id="newsman-mailbox-all" class="newsman-flink current"><?php _e('All emails', NEWSMAN); ?></a> |</li>
			<li><a href="#/inprogress" id="newsman-mailbox-inprogress" class="newsman-flink"><?php _e('In progress', NEWSMAN); ?></a> |</li>			
			<li><a href="#/pending" id="newsman-mailbox-pending" class="newsman-flink"><?php _e('Pending', NEWSMAN); ?></a> |</li>
			<li><a href="#/sent" id="newsman-mailbox-sent" class="newsman-flink"><?php _e('Sent', NEWSMAN); ?></a></li>
		</ul> -->
		<button style="float:right; display:none;" class="button subsubsub" id="newsman-search-cancel-btn"><?php _e('Remove Search Filter', NEWSMAN); ?></button>
	</div>

	<div class="newsman-tbl-controls">
		<div class="pagination" style="display: none;">
			<ul>
			</ul>
		</div>	

		<a class="btn" href="#" id="btn-new-tpl"><?php _e('New Template', NEWSMAN); ?></a>
		<button id="btn-delete-tpls" style="margin: 0 3px;" type="button" class="btn btn-danger"><?php _e('Delete', NEWSMAN); ?></button>
		<button id="newsman-btn-reconfirm" style="margin: 0 3px 0 2em; display: none;" type="button" class="btn"><?php _e('Resend Confirmation Request', NEWSMAN); ?></button>
	</div>


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
			<p><?php _e('Are you sure you want to delete selected templates?', NEWSMAN); ?></p>
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

	<div class="modal dlg" id="newsman-modal-new-template" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('New Template', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<div class="form-vertical">
				<div class="control-group">
					<label class="control-label" for="ed-tpl-name"><?php _e('Name:', NEWSMAN); ?></label>	
					<div class="controls">
						<input type="text" id="ed-tpl-name" />
						<span style="display:none;" class="help-inline"><?php _e('Please enter the name of a template', NEWSMAN); ?></span>
					</div>
				</div>
			</div>
			<div class="tpl-buttons">
				<table>
					<tr class="tpl-type-error">
						<td colspan="2" class="error standalone"><?php _e('Please choose a template type', NEWSMAN); ?></td>
					</tr>					
					<tr>
						<td>
							<div class="tpl-btn" tplname="basic">
								<i class="simple"></i>
								<div>
									<h3><?php _e('Simple', NEWSMAN); ?></h3>
								</div>
							</div>							
						</td>
						<td>
							<div class="tpl-btn" tplname="2cols">
								<i class="col2"></i>
								<div>
									<h3><?php _e('2 columns', NEWSMAN); ?></h3>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<div class="tpl-btn" tplname="3cols">
								<i class="col3"></i>
								<div>
									<h3><?php _e('3 columns', NEWSMAN); ?></h3>
								</div>
							</div>
						</td>
						<td>
							<div class="tpl-btn" tplname="gallery">
								<i class="gallery"></i>
								<div>
									<h3><?php _e('Gallery', NEWSMAN); ?></h3>
								</div>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<div class="tpl-btn" tplname="mobile">
								<i class="simple"></i>
								<div>
									<h3>Mobile</h3>
								</div>
							</div>
						</td>
						<td>
							<div class="tpl-btn" tplname="digest">
								<i class="simple"></i>
								<div>
									<h3><?php _e('Digest', NEWSMAN); ?></h3>
								</div>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Create', NEWSMAN); ?></a>
		</div>
	</div>	

	<?php include("_footer.php"); ?>
</div>