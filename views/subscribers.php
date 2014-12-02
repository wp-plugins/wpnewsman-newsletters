<script type="text/javascript">
	var NEWSMAN_PLUGIN_URL = '<?php echo NEWSMAN_PLUGIN_URL;?>';
</script>
<style>

	.status-radio {
		line-height: 42px;
	}
	.status-radio label.radio {
		display: inline-block;
		margin-bottom: 0;
		margin-left: 10px;
	}	
</style>
<!-- proto-->
<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="page-header">

		<h2><?php _e('Manage Subscribers', NEWSMAN); ?><?php do_action('newsman_put_list_select', true); ?> <a href="<?php echo NEWSMAN_BLOG_ADMIN_URL; ?>admin.php?page=newsman-forms&amp;sub=subscribers&amp;action=editlist" id="btn-edit-form" class="btn"><?php newsmanEEnt(__('Form & List options', NEWSMAN)); ?></a> <form id="subs-search-form" class="form-search" style="display: inline-block; float: right;">
			<input  id="newsman-subs-search" type="text" class="input-medium search-query">
			<button id="newsman-subs-search-clear" style="display:none;" class="btn"><?php _e('Clear', NEWSMAN); ?></button>
			<button id="newsman-subs-search-btn" type="submit" class="btn"><?php _e('Search', NEWSMAN); ?></button>
		</form>
		</h2>
	</div>

	<div style="overflow:hidden;">
		<ul class="radio-links">
			<li><a href="#/all" id="newsman-subs-all" class="newsman-flink current"><?php _e('All Subscribers', NEWSMAN); ?></a> |</li>
			<li><a href="#/confirmed" id="newsman-subs-confirmed" class="newsman-flink"><?php _e('Confirmed', NEWSMAN); ?></a> |</li>
			<li><a href="#/unconfirmed" id="newsman-subs-unconfirmed" class="newsman-flink"><?php _e('Unconfirmed', NEWSMAN); ?></a> |</li>
			<li><a href="#/unsubscribed" id="newsman-subs-unsubscribed" class="newsman-flink"><?php _e('Unsubscribed', NEWSMAN); ?></a></li>
		</ul>
		<button style="float:right; display:none;" class="button radio-links" id="newsman-search-cancel-btn"><?php _e('Remove Search Filter', NEWSMAN); ?></button>
	</div>

	<div class="newsman-tbl-controls">
		<div class="pagination" style="display: none;">
			<ul>
			</ul>
		</div>	

		<div class="btn-group action-group">
			<button id="newsman-btn-unsubscribe" type="button" class="btn"><?php _e('Unsubscribe', NEWSMAN); ?></button>
			<button class="btn dropdown-toggle" data-toggle="dropdown">
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu">
				<li><a id="newsman-btn-bulk-unsubscribe"><?php _e('Bulk unsubscribe', NEWSMAN); ?></a></li>
				 <li class="divider"></li>
				<li><a id="newsman-btn-chToSubscribed"><?php _e('Change to Unconfirmed', NEWSMAN); ?></a></li>
				<li><a id="newsman-btn-chToConfirmed"><?php _e('Change to Confirmed', NEWSMAN); ?></a></li>
			</ul>
		</div>
		<button id="newsman-btn-add-subscriber" style="margin: 0 3px;" type="button" class="btn btn-success"><i class="newsman-icon newsman-icon-plus-sign"></i> <?php _e('Add Subscriber', NEWSMAN); ?></button>
		<button id="newsman-btn-delete" style="margin: 0 3px;" type="button" class="btn btn-danger"><?php _e('Delete', NEWSMAN); ?></button>
	
		<button id="newsman-btn-export" type="button" class="btn pull-right"><i class="icon-download"></i> <?php _e('Export to CSV', NEWSMAN); ?></button>
		<button id="newsman-btn-import" style="margin-right: 5px;" type="button" class="btn pull-right"><i class="icon-upload"></i> <?php _e('Import from CSV', NEWSMAN); ?></button>
		<button id="newsman-btn-validate" style="display:none; margin-right: 5px;" type="button" class="btn pull-right"><i class="icon-ok-circle"></i> <?php _e('Validate email addresses', NEWSMAN); ?></button>
		
		<div class="newsman-btn-reconfirm-group pull-right">
			<span class="pull-right button-row-divider">|</span>
			<div class="btn-group pull-right" style="margin: 0 3px 0 2em;">
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="newsman-icon newsman-icon-envelope"></i> <?php _e('Send request', NEWSMAN); ?>
				<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<!-- dropdown menu links -->
					<li><a id="newsman-btn-resubscribe" href="#"><?php _e('Send Re-Subscribe Request', NEWSMAN); ?></a></li>
					<li><a id="btn-resend-confirmation-req" href="#"><?php _e('Resend Confirmation request', NEWSMAN); ?></a></li>
				</ul>
			</div>
		</div>
	</div>

	<table id="newsman-mgr-subscribers" class="table table-striped table-bordered">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input id="newsman-checkall" type="checkbox"></th>
				<th scope="col"><?php _e('Email', NEWSMAN); ?></th>
				<th scope="col"><?php _e('Date', NEWSMAN); ?></th>
				<th scope="col"><?php _e('IP Address', NEWSMAN); ?></th>
				<th scope="col"><?php _e('Status', NEWSMAN); ?></th>
				<th scope="col"><?php _e('Form Data', NEWSMAN); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="6" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
			</tr>
		</tbody>
	</table>

	<!--		 MODALS 		-->

	<div class="modal dlg" id="newsman-modal-add-sub" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Add Subscriber', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			
		</div>
		<div class="modal-footer">
			<div class="pull-left">
				<select name="type" class="form-control" id="newsman-sub-type">
					<option value="confirmed">Confirmed</option>
					<option value="unconfirmed">Unconfirmed</option>
					<option value="unsubscribed">Unsubscribed</option>
				</select>
				<input type="text" class="form-control" id="newsman-sub-datepicker">
			</div>
			<a class="btn" mr="cancel"><?php _e('Cancel', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Save', NEWSMAN); ?></a>
		</div>
	</div>	

	<div class="modal dlg" id="newsman-modal-unsubscribe" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to unsubscribe selected people?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<label class="checkbox pull-left" title="Apply to all subscribers in the list"><input type="checkbox" xmr="all"> <?php _e('Unsubscribe all', NEWSMAN); ?></label>
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
			<p><?php _e('Are you sure you want to delete %s selected subscribers?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<label class="checkbox pull-left" title="Apply to all subscribers in the list"><input type="checkbox" xmr="all"> <?php _e('Delete all', NEWSMAN); ?></label>
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
			<p><?php printf( __('Are you sure you want to change status of selected subscribers to %s?', NEWSMAN), '<strong class="newsman-status"></strong>'); ?></p>
		</div>
		<div class="modal-footer">
			<label class="checkbox pull-left" title="Apply to all subscribers in the list"><input type="checkbox" xmr="all"> <?php _e('Change all', NEWSMAN); ?></label>
		<!-- 	<a class="btn pull-left" mr="all" title="Apply to all subscribers in the list"><?php _e('Change all', NEWSMAN); ?></a> -->
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-warning" mr="ok"><?php _e('Change', NEWSMAN); ?></a>
		</div>
	</div>

	<div class="modal dlg" id="newsman-modal-resubscribe" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Warning!', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('This action will send re-subscribe request <strong>to all unconfirmed subscribers</strong> in the list.', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Cancel', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Send re-subscribe request', NEWSMAN); ?></a>
		</div>
	</div>	

	<div class="modal dlg" id="newsman-modal-resend-confirmation" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to re-send confirmation emails to selected subscribers?', NEWSMAN); ?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Send', NEWSMAN); ?></a>
		</div>
	</div>

	<div class="modal dlg" id="newsman-modal-import" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3>Import subscribers into <span id="import-list-name"></span><?php _e(' list:', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body" style="height: 337px;">	
			<div class="row-fluid" id="import-form-titles">
				<div class="span3">
					<h4><?php _e('Uploaded files', NEWSMAN); ?></h4>					
				</div>
				<div class="span9">
					<h4><?php _e('Import options', NEWSMAN); ?></h4>
				</div>
			</div>
			<div class="row-fluid">
				<div class="span3" id="upload-list-wrap">
					<ul id="import-files-list" class="neo-upload-list nav nav-tabs nav-stacked"></ul>
				</div>
				<div class="span9" style="overflow-x: auto;" id="file-import-settings">
					<center><p class="import-form-info"><?php _e('Please select a file to import.', NEWSMAN); ?></p></center>
					<center><p class="import-form-notice"><?php _e('Remember - a fully confirmed opted-in list is important.<br> It is a general prerequisite for sustainable e-mail deliverability and conversion rates.', NEWSMAN); ?></p></center>
					<form style="display: none;">
						<div class="import-controls" style="margin: 10px 0;">
							<div class="row-fluid">
								<div class="span3">
									<label>Delimiter  <input id="import-delimiter" style="display: inline-block; vertical-align: baseline;" class="input-mini" type="text"></label>
								</div>
								<div class="span3">
									<label class="checkbox"><input id="skip-first-row" type="checkbox"><?php _e(' Skip first row', NEWSMAN); ?></label>
								</div>
								<div class="span6 status-radio">
									<span>Status: <label for="st-confirmed" class="radio"><input id="st-confirmed" type="radio" name="apply-status" value="confirmed" checked="checked"> Confirmed</label>  <label for="st-unconfirmed" class="radio"><input id="st-unconfirmed" type="radio" name="apply-status" value="unconfirmed"> Unconfirmed</label></span>
								</div>
							</div>
						</div>			
						<table id="tbl-field-map" class="table table-bordered table-condensed">
							<thead>
								<tr>
									<th>
										<select class="map-select" name="map-col-1" id="map-col-1">
											<option value="null"></option>
											<option value="email"><?php _e('email', NEWSMAN); ?></option>
											<option value="first-name"><?php _e('First Name', NEWSMAN); ?></option>
											<option value="last-name"><?php _e('Last Name', NEWSMAN); ?></option>
										</select>
									</th>
									<th>
										<select class="map-select" name="map-col-1" id="map-col-1">
											<option value="null"></option>
											<option value="email"><?php _e('email', NEWSMAN); ?></option>
											<option value="first-name"><?php _e('First Name', NEWSMAN); ?></option>
											<option value="last-name"><?php _e('Last Name', NEWSMAN); ?></option>
										</select>										
									</th>
									<th>
										<select class="map-select" name="map-col-1" id="map-col-1">
											<option value="null"></option>
											<option value="email"><?php _e('email', NEWSMAN); ?></option>
											<option value="first-name"><?php _e('First Name', NEWSMAN); ?></option>
											<option value="last-name"><?php _e('Last Name', NEWSMAN); ?></option>
										</select>										
									</th>
									<th>
										<select class="map-select" name="map-col-1" id="map-col-1">
											<option value="null"></option>
											<option value="email"><?php _e('email', NEWSMAN); ?></option>
											<option value="first-name"><?php _e('First Name', NEWSMAN); ?></option>
											<option value="last-name"><?php _e('Last Name', NEWSMAN); ?></option>
										</select>										
									</th>
									<th>
										<select class="map-select" name="map-col-1" id="map-col-1">
											<option value="null"></option>
											<option value="email"><?php _e('email', NEWSMAN); ?></option>
											<option value="first-name"><?php _e('First Name', NEWSMAN); ?></option>
											<option value="last-name"><?php _e('Last Name', NEWSMAN); ?></option>
										</select>										
									</th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td>email</td>
									<td>firstName</td>
									<td>lastName</td>
									<td>lastName</td>
									<td>lastName</td>
								</tr>
								<tr>
									<td>white@glocksoft.com</td>
									<td>Whiliam</td>
									<td>White</td>
									<td>White</td>
									<td>White</td>
								</tr>
								<tr>
									<td>brown@glocksoft.com</td>
									<td>Benjamin</td>
									<td>Brown</td>
									<td>Brown</td>
									<td>Brown</td>
								</tr>																
							</tbody>
						</table>
						<div><i class="icon-info-sign"></i> Duplicate email addresses will be skipped</div>
					</form>					
				</div>
			</div>
			<div id="file-uploader">
			    <noscript>
			        <p><?php _e('Please enable JavaScript to use file uploader.', NEWSMAN); ?></p>
			        <!-- or put a simple form for upload here -->
			    </noscript>
			</div>
		</div>
		<div class="modal-footer">
			<a id="btn-upload" type="button" class="btn qq-upload-button pull-left"><?php _e('Upload a file', NEWSMAN); ?></a>
			<a class="btn btn-primary" mr="ok"><?php _e('Import', NEWSMAN); ?></a>
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>	

	<div class="modal dlg" id="newsman-modal-bulk-unsubscribe" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Bulk unsubscribe:', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body" style="height: 250px; position: relative;">	
			<div>
				<p><?php _e('Enter an email addresses which you want to unsubscribe. Place each email on a separate row.', NEWSMAN); ?></p>
			</div>
			<div style="height: 200px;">
				<textarea id="newsman-unsubscribe-list"></textarea>	
			</div>
		</div>
		<div class="modal-footer">
			<label for="cb-uns-from-all" id="lbl-uns-from-all" class="checkbox"><input type="checkbox" id="cb-uns-from-all"><?php _e(' Unsubscribe from all lists', NEWSMAN); ?></label>
			<a class="btn btn-warning" mr="ok"><?php _e('Unsubscribe', NEWSMAN); ?></a>
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>

<!-- :proversion -->
	<div class="modal dlg" id="newsman-modal-create-list" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Add new list:', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body" style="height: 50px; position: relative;">	
			<input type="text" name="list-name" id="new-list-name" placeholder="List name...">
		</div>
		<div class="modal-footer">
			<a class="btn btn-primary" mr="ok"><?php _e('Create', NEWSMAN); ?></a>
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>
<!-- /proversion -->


	<?php include("_footer.php"); ?>
	
</div>