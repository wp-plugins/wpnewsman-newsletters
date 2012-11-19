<script>
	window.NEWSMAN_LIST_ID = '<?php echo $_REQUEST['id']; ?>';
</script>
<div class="wrap wp_bootstrap" id="newsman-page-list">

	<div class="row-fluid" style="border-bottom: 1px solid #DADADA;">
		<div class="span12">
			<h2><?php _e('Edit subscribers list', NEWSMAN); ?></h2>
		</div>		
	</div>

	<br>

	<form name="newsman_form_g" id="newsman_form_g" method="POST" action="">
		
		<label for="newsman_form_name">List name:</label>
		<input type="text" class="span8" id="newsman_form_name" name="newsman-form-name" placeholder="List Name">

		<!--												Submission form	 							-->

		<div class="row-fluid ">
			<div class="span8">
				<h2><?php _e('Submission Form', NEWSMAN); ?></h2>

				<label for="newsman_form_title"><?php _e('Form Title (Headline)', NEWSMAN); ?></label>
				<textarea id="newsman_form_title" class="span8" type="text" rows="5" cols="60" name="newsman-form-title"></textarea>

				<label for="newsman_form_header"><?php _e('Form Header', NEWSMAN); ?></label>
				<textarea id="newsman_form_header" class="span8" type="text" rows="5" cols="60" name="newsman-form-header"></textarea>

				<label><?php _e('Form Footer', NEWSMAN); ?></label>
				<textarea id="newsman_form_footer" class="span8" type="text" rows="5" cols="60" name="newsman-form-footer"></textarea><br />
			</div>
			<div class="span4">
				<h4><?php _e('Define Why People Should Subscribe', NEWSMAN); ?></h4><br>
				<p><?php _e('If you don\'t give people a good reason (better yet, several good reasons) to subscribe, well... they won\'t subscribe. Even if they love your blog.', NEWSMAN); ?></p>
				<p><?php _e('To get more subscribers faster, write a good headline for your signup form that sells visitors on subscribing. Give people a good reason to subscribe. In most cases it is a simple convenience of being notified about new articles on your blog. It may be also a promise of giving something of value that non-subscribers won\'t get. Whatever your incentive is, clearly define it either in the form header or footer.'); ?></p>
			</div>
		</div>

		<div class="row-fluid newsman-form-builder">
			<div class="span8">
				<div class="h-toolbar">
					<h2><?php _e('Form Builder', NEWSMAN); ?></h2>
					<div id="btn-add-field" class="btn-group">
						<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown"><i class="icon-plus-sign icon-white"></i> Add Field <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a type="text"><?php _e('Text', NEWSMAN); ?></a></li>
							<!-- <li><a type="email"><?php _e('Email', NEWSMAN); ?></a></li> -->
							<li><a type="checkbox"><?php _e('Checkbox', NEWSMAN); ?></a></li>
							<li><a type="radio"><?php _e('Radio buttons', NEWSMAN); ?></a></li>
						</ul>
					</div>
					<button class="btn"><i class="icon-warning-sign"></i> <?php _e('Load Default Form', NEWSMAN); ?></button>
				</div>

				<div class="row">
					<div class="span5">
						<?php
							$frm = new newsmanForm($_REQUEST['id'], true);
							$frm->renderForm();
						?>
					</div>
					<div class="span3">
						<div id="newsman-formbuilder-formoptions" class="alert">
							<strong><?php _e('Form options', NEWSMAN); ?></strong><br>
							<label class="checkbox" for="use-inline-labels"><input id="use-inline-labels" type="checkbox"> Use inline labels </label>
						</div>
						<div id="newsman-formbuilder-options">
							
						</div>
					</div>
				</div>	

				<input type="hidden" id="newsman_form_json" name="newsman-form-json" value="" />					
			</div>
			<div class="span4 ext-form-block">
				<?php do_action_ref_array('newsman_get_ext_form_options', array( $list )); ?>
				<h3>One more thing,</h3>
				<p>you can put this form inside any post content with this short-code:</p>
				<pre><code>[newsman-form id='<?php echo $list->id; ?>']</code></pre>
			</div>

		</div>

		<hr>
		<button id="newsman-save-list" type="button" class="btn btn-primary btn-large newsman-update-options"><?php _e('Save', NEWSMAN); ?></button>

	</form>

	<?php include("_footer.php"); ?>
	
</div>