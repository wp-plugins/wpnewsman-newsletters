<!-- proto-->
<script type="text/javascript">
	var NEWSMAN_PLUGIN_URL = '<?php echo NEWSMAN_PLUGIN_URL; ?>';
	var NEWSMAN_BLOG_ADMIN_URL = '<?php echo get_bloginfo("wpurl")."/wp-admin"; ?>';
</script>
<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="row-fluid" style="border-bottom: 1px solid #DADADA;">
		<div class="span12">
			<h2><?php _e('Mailbox', NEWSMAN); ?>   <form id="newsman-email-search-form" class="form-search" style="display: inline-block; float: right;">
				<input id="newsman-email-search" type="text" class="input-medium search-query">
				<button id="newsman-email-search-clear" type="button" style="display:none;" class="btn"><?php _e('Clear', NEWSMAN); ?></button>
				<button id="newsman-email-search-btn" type="submit" class="btn"><?php _e('Search', NEWSMAN); ?></button>
				</form>			
			</h2>
		</div>		
	</div>

	<div class="row-fluid">
		<div class="span12">
			<ul class="subsubsub" style="float:left; margin: 5px 0 0 0;">
				<li><a href="#/all" id="newsman-mailbox-all" class="newsman-flink current"><?php _e('All emails', NEWSMAN); ?></a> |</li>
				<li><a href="#/drafts" id="newsman-mailbox-drafts" class="newsman-flink"><?php _e('Drafts', NEWSMAN); ?></a> |</li>
				<li><a href="#/inprogress" id="newsman-mailbox-inprogress" class="newsman-flink"><?php _e('In progress', NEWSMAN); ?></a> |</li>
				<li><a href="#/pending" id="newsman-mailbox-pending" class="newsman-flink"><?php _e('Pending', NEWSMAN); ?></a> |</li>
				<li><a href="#/sent" id="newsman-mailbox-sent" class="newsman-flink"><?php _e('Sent', NEWSMAN); ?></a></li>
			</ul>
			<button style="float:right; display:none;" class="button subsubsub" id="newsman-search-cancel-btn"><?php _e('Remove Search Filter', NEWSMAN); ?></button>
		</div>
	</div>

	<div class="newsman-tbl-controls row-fluid">
		<div class="span6">
			<div class="btn-group">
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="icon-pencil"></i> <?php _e('Compose', NEWSMAN); ?>
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<li><a id="btn-compose" href="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/admin.php?page=newsman-mailbox&amp;action=compose"><i class="icon-pencil"></i> <?php _e('From Template', NEWSMAN); ?></a></li>
					<li><a href="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/admin.php?page=newsman-mailbox&amp;action=compose&amp;type=wp"><i class="icon-font"></i> <?php _e('Quick Message', NEWSMAN); ?></a></li>
				</ul>
			</div>			
<!-- 			<div class="btn-group" style="">
				<a class="btn" id="btn-compose" href="#compose"><i class="icon-pencil"></i> <?php _e('Compose', NEWSMAN); ?></a>
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#"><span class="caret"></span></a>
				<ul class="dropdown-menu">
					<li><a href="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/admin.php?page=newsman-mailbox&amp;action=compose"><i class="icon-font"></i> <?php _e('Compose simple message', NEWSMAN); ?></a></li>
				</ul>
			</div>		 -->
			<button id="newsman-btn-stop" type="button" class="btn"><?php _e('Stop', NEWSMAN); ?></button>
			<button id="newsman-btn-resume" type="button" class="btn"><?php _e('Resume', NEWSMAN); ?></button>
			<button id="newsman-btn-delete" style="margin: 0 3px;" type="button" class="btn btn-danger"><?php _e('Delete', NEWSMAN); ?></button>
			<button id="newsman-btn-reconfirm" style="margin: 0 3px 0 2em; display: none;" type="button" class="btn"><?php _e('Resend Confirmation Request', NEWSMAN); ?></button>			
		</div>
		<div class="span6" style="text-align: right;">
			<div class="pagination" style="display: none;">
				<ul>
				</ul>
			</div>
		</div>
	</div>


	<table id="newsman-mailbox" class="table table-striped table-bordered">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input id="newsman-checkall" type="checkbox"></th>
				<th style="width: 300px;" scope="col"><?php /* translators: email property */ _e('Subject', NEWSMAN); ?></th>
				<th style="width: 200px;" scope="col"><?php /* translators: email property */ _e('To', NEWSMAN); ?></th>
				<th style="width: 130px;" scope="col"><?php /* translators: email property */ _e('Created', NEWSMAN); ?></th>
				<th style="width: 100px;" scope="col"><?php /* translators: email property */ _e('Status', NEWSMAN); ?></th>
				<th scope="col"><?php _e('Status message', NEWSMAN); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td colspan="6" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
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

	<div class="modal dlg" id="newsman-modal-delete" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Please, confirm...', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			<p><?php _e('Are you sure you want to delete selected emails?', NEWSMAN); ?></p>
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
			<p><?php sprintf( _e('Are you sure you want to change status of selected subscribers to %s?', NEWSMAN), '<strong class="newsman-status"></strong>');?></p>
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
			<a class="btn btn-warning" mr="ok"><?php _e('Change', NEWSMAN); ?></a>
		</div>
	</div>

	<div class="modal dlg" id="newsman-modal-compose" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Select template:', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body scrollable" style="height: 300px;">

			<ul class="nav nav-tabs" id="myTab">
				<li class="active"><a href="#my-tpls" data-toggle="tab"><?php _e('My Templates', NEWSMAN); ?></a></li>
				<li><a href="#basic-tpls" data-toggle="tab"><?php _e('Basic Templates', NEWSMAN); ?></a></li>
			</ul>

			<div class="tab-content">
				<div class="tab-pane active" id="my-tpls">
					<table id="dlg-templates-tbl" class="table table-striped table-bordered">
					</table>					
				</div>
				<div class="tab-pane" id="basic-tpls">
					<div class="tpl-buttons">
						<table>
							<tr>
								<td>
									<div mr="ok" class="tpl-btn" tplname="basic">
										<i class="simple"></i>
										<div>
											<h3><?php _e('Simple', NEWSMAN); ?></h3>
										</div>
									</div>							
								</td>
								<td>
									<div mr="ok" class="tpl-btn" tplname="2cols">
										<i class="col2"></i>
										<div>
											<h3><?php _e('2 columns', NEWSMAN); ?></h3>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div mr="ok" class="tpl-btn" tplname="3cols">
										<i class="col3"></i>
										<div>
											<h3><?php _e('3 columns', NEWSMAN); ?></h3>
										</div>
									</div>
								</td>
								<td>
									<div mr="ok" class="tpl-btn" tplname="gallery">
										<i class="gallery"></i>
										<div>
											<h3><?php _e('Gallery', NEWSMAN); ?></h3>
										</div>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<div mr="ok" class="tpl-btn" tplname="mobile">
										<i class="simple"></i>
										<div>
											<h3><?php _e('Mobile', NEWSMAN); ?></h3>
										</div>
									</div>
								</td>
								<td>
									<div mr="ok" class="tpl-btn" tplname="digest">
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
			</div>			


		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>		

	<div class="modal dlg" id="newsman-modal-errorlog" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal">×</button>
			<h3><?php _e('Sending log', NEWSMAN); ?></h3>
		</div>
		<div class="modal-body">
			
		</div>
		<div class="modal-footer">
			<a class="btn" mr="cancel"><?php _e('Close', NEWSMAN); ?></a>
		</div>
	</div>	

	<?php include("_footer.php"); ?>

</div>