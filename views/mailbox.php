<!-- proto-->
<script type="text/javascript">
	var NEWSMAN_PLUGIN_URL = '<?php echo NEWSMAN_PLUGIN_URL; ?>';
	var NEWSMAN_BLOG_ADMIN_URL = '<?php echo get_bloginfo("wpurl")."/wp-admin"; ?>';
</script>
<div class="wrap wp_bootstrap">
	<?php include("_header.php"); ?>
	<div class="page-header">
		<h2><?php _e('Mailbox', NEWSMAN); ?>   <form id="newsman-email-search-form" class="form-search">
			<input id="newsman-email-search" type="text" class="input-medium search-query">
			<button id="newsman-email-search-clear" type="button" style="display:none;" class="btn"><?php _e('Clear', NEWSMAN); ?></button>
			<button id="newsman-email-search-btn" type="submit" class="btn"><?php _e('Search', NEWSMAN); ?></button>
			</form>			
		</h2>
	</div>

	<div class="row">
		<div class="span12">
			<ul class="radio-links">
				<li><a href="#/all" id="newsman-mailbox-all" class="newsman-flink current"><?php _e('All emails', NEWSMAN); ?></a> |</li>
				<li><a href="#/draft" id="newsman-mailbox-draft" class="newsman-flink"><?php _e('Drafts', NEWSMAN); ?></a> |</li>
				<li><a href="#/inprogress" id="newsman-mailbox-inprogress" class="newsman-flink"><?php _e('In progress', NEWSMAN); ?></a> |</li>
				<li><a href="#/pending" id="newsman-mailbox-pending" class="newsman-flink"><?php _e('Pending', NEWSMAN); ?></a> |</li>
				<li><a href="#/sent" id="newsman-mailbox-sent" class="newsman-flink"><?php _e('Sent', NEWSMAN); ?></a></li>
			</ul>			
		</div>
	</div>

	<div class="newsman-tbl-controls row">
	<?php
		$toolbar = '
		<div class="span7">
			<div class="btn-group">
				<a class="btn dropdown-toggle" data-toggle="dropdown" href="#">
					<i class="icon-pencil"></i> '.__('Compose', NEWSMAN).'
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<li><a id="btn-compose" href="'.get_bloginfo("wpurl").'/wp-admin/admin.php?page=newsman-mailbox&amp;action=compose-from-tpl"><i class="icon-pencil"></i> '.__('From Template', NEWSMAN).'</a></li>
					<li><a href="'.get_bloginfo("wpurl").'/wp-admin/admin.php?page=newsman-mailbox&amp;action=compose"><i class="icon-font"></i> '.__('Quick Message', NEWSMAN).'</a></li>
				</ul>
			</div>			

			<button id="newsman-btn-compose-from-msg" type="button" class="btn btn-primary" style="display: none;">'.__('Compose from Message', NEWSMAN).'</button>

			<button id="newsman-btn-stop" type="button" class="btn"><i class="icon icon-stop"></i> '.__('Stop', NEWSMAN).'</button>
			<button id="newsman-btn-start" type="button" class="btn"><i class="icon icon-play"></i> '.__('Start', NEWSMAN).'</button>
			<button id="newsman-btn-delete" style="margin: 0 3px;" type="button" class="btn btn-danger"><i class="icon icon-white icon-trash"></i> '.__('Delete', NEWSMAN).'</button>
			<button id="newsman-btn-reconfirm" style="margin: 0 3px 0 2em; display: none;" type="button" class="btn">'.__('Resend Confirmation Request', NEWSMAN).'</button>			
		</div>
		';

		$pagination = '
			<div class="span5 newsman-pagination">
				<div class="pagination" style="display: none;">
					<ul>
					</ul>
				</div>
			</div>
		';

		if ( is_rtl() ) {
			echo $pagination;
			echo $toolbar;
		} else {
			echo $toolbar;
			echo $pagination;
		}
	?>
	</div>

	<div class="row-fluid">
		<div class="span12">
			<div id="newsman-mailbox" class="newsman-mailbox">

<!-- 				<div class="newsman-email">
					<div class="newsman-email-checkmark">
						<i class="icon-ok"></i>
					</div>
					<div class="newsman-email-general">
						<div class="newsman-email-subject">
							<a href="http://blog.dev/wp-admin/admin.php?page=newsman-mailbox&amp;action=edit&amp;type=null&amp;id=37">test</a>
						</div>					
						<div class="newsman-email-to">
							<span class="label label-info">island test</span>
						</div>					
						<div class="newsman-email-status-message">Sent 1785 of 1785 emails</div>
						<ul class="newsman-email-meta">
							<li class="newsman-email-created">
								<span class="newsman-tbl-emails-created" title="April 3 2014 3:00 AM" style="border-bottom: 1px dashed #cacaca; cursor: default;">a month ago</span>
							</li>
							<li class="newsman-email-public-url">
								<a href="http://blog.dev/?newsman=email&amp;email=1iyPHEodHCLLJW3cAVmvHUmvKNM">View in browser</a>
							</li>
							<li class="newsman-email-status">
								<span class="newsman-tbl-emails-status"><i class="icon-ok"></i> Sent</span>
							</li>
							<li class="newsman-email-delete">
								<a href="#">Delete</a>
							</li>
						</ul>
					</div>
					<div class="newsman-email-stats">
						<div class="newsman-email-opens">
							<div class="nfo">
								<span class="num">150</span>
								<span class="lbl">opens</span>	
							</div>							
						</div>
						<div class="newsman-email-clicks"><span class="num">74</span><span class="lbl">clicks</span></div>
						<div class="newsman-email-unsubscribes"><span class="num">26</span><span class="lbl">unsubscribes</span></div>						
					</div>
				</div> -->

			</div>

<!-- 			<table id="newsman-mailbox" class="table table-striped table-bordered">
				<thead>
					<tr>
						<th scope="col" class="check-column"><input id="newsman-checkall" type="checkbox"></th>
						<th style="width: 300px;" scope="col"><?php /* translators: email property */ _e('Subject', NEWSMAN); ?></th>
						<th style="width: 150px;" scope="col"><?php /* translators: email property */ _e('To', NEWSMAN); ?></th>
						<th style="width: 130px;" scope="col"><?php /* translators: email property */ _e('Created', NEWSMAN); ?></th>
						<th style="width: 100px;" scope="col"><?php /* translators: email property */ _e('Status', NEWSMAN); ?></th>
						<th style="width: 100px;"><?php /* translators: email property */ _e('Public URL', NEWSMAN); ?></th>
						<th scope="col"><?php _e('Status message', NEWSMAN); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td colspan="6" class="blank-row"><img src="<?php echo NEWSMAN_PLUGIN_URL; ?>/img/ajax-loader.gif"> <?php _e('Loading...', NEWSMAN); ?></td>
					</tr>
				</tbody>
			</table> -->			
		</div>
	</div>

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
			<table id="dlg-templates-tbl" class="table table-striped table-bordered">
			</table>					
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