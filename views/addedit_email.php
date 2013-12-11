<!-- proto-->
<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div style="border-bottom: 1px solid #DADADA; overflow: hidden;">
		<h2><?php _e('Mailbox', NEWSMAN); ?>   <form class="form-search" style="display: inline-block; float: right;">
			<input id="newsman-email-search" type="text" class="input-medium search-query">
			<button id="newsman-email-search-btn" type="submit" class="btn"><?php _e('Search', NEWSMAN); ?></button>
		</form>
		</h2>
	</div>

	<div style="overflow:hidden;">
		<ul class="radio-links">
			<li><a href="#/all" id="newsman-mailbox-all" class="newsman-flink current"><?php _e('All emails', NEWSMAN)?></a> |</li>
			<li><a href="#/inprogress" id="newsman-mailbox-inprogress" class="newsman-flink"><?php _e('In progress'); ?></a> |</li>			
			<li><a href="#/pending" id="newsman-mailbox-pending" class="newsman-flink"><?php _e('Pending'); ?></a> |</li>
			<li><a href="#/sent" id="newsman-mailbox-sent" class="newsman-flink"><?php _e('Sent'); ?></a></li>
		</ul>
		<button style="float:right; display:none;" class="button radio-links" id="newsman-search-cancel-btn"><?php _e('Remove Search Filter'); ?></button>
	</div>

	<div class="newsman-tbl-controls">
		<div class="pagination" style="display: none;">
			<ul>
			</ul>
		</div>	

		<a class="btn" href="/wp-admin/post-new.php?post_type=newsman_email"><?php _e('Compose', NEWSMAN); ?></a>
<!-- 		<div class="btn-group action-group">
			<button id="newsman-btn-unsubscribe" type="button" class="btn">Unsubscribe</button>
			<button class="btn dropdown-toggle" data-toggle="dropdown">
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<li><a id="newsman-btn-chToSubscribed">Change to Subscribed</a></li>
				<li><a id="newsman-btn-chToConfirmed">Change to Confirmed</a></li>
			</ul>
		</div>				 -->
		<button id="newsman-btn-delete" style="margin: 0 3px;" type="button" class="btn btn-danger"><?php _e('Delete', NEWSMAN); ?></button>
		<button id="newsman-btn-reconfirm" style="margin: 0 3px 0 2em; display: none;" type="button" class="btn"><?php _e('Resend Confirmation Request', NEWSMAN); ?></button>
	</div>

<!-- 	<div class="newsman-tbl-controls">
		<button id="newsman-btn-unsubscribe" style="margin: 0 3px;" type="button" class="btn">Unsubscribe</button>
		<button id="newsman-btn-delete" style="margin: 0 3px;" type="button" class="btn btn-danger">Delete</button>

		<div class="pagination">
			<ul>
				<li><a href="#">«</a></li>
				<li class="active"><a href="#">1</a></li>
				<li><a href="#">2</a></li>
				<li><a href="#">3</a></li>
				<li><a href="#">4</a></li>
				<li><a href="#">»</a></li>
			</ul>
		</div>	
	</div> -->

	<table id="newsman-mailbox" class="table table-striped table-bordered">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input id="newsman-checkall" type="checkbox"></th>
				<th scope="col"><?php _e('Subject'); ?></th>
				<th scope="col"><?php _e('To'); ?></th>
				<th scope="col"><?php _e('Created'); ?></th>
				<th scope="col"><?php _e('Status'); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="6" class="blank-row"><?php _e('You have no emails yet.', NEWSMAN); ?></td>
			</tr>
		</tbody>
	</table>

	<!--		 MODALS 		-->

	<div class="modal" id="newsman-modal-unsubscribe" style="display: none;">
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

	<div class="modal" id="newsman-modal-delete" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to delete selected subscribers?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-danger" mr="ok"><?php _e('Delete', NEWSMAN); ?></a>
		</div>
	</div>

	<div class="modal" id="newsman-modal-chstatus" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php sprintf( _e('Are you sure you want to change status of selected subscribers to %s?', NEWSMAN), '<strong class="newsman-status"></strong>' ); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-warning" mr="ok"><?php _e('Change', NEWSMAN); ?></a>
		</div>
	</div>	

	<?php include("_footer.php"); ?>	

</div>