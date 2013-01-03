<script>
	window.NEWSMAN_LIST_ID = '<?php echo $id; ?>';
</script>

<!--  form builder templates -->

<script type="text/html" id="tpl-newsman-form-options">
	<strong>Form options</strong><br>
	<label class="checkbox" for="use-inline-labels"><input id="use-inline-labels" type="checkbox"> Use inline labels </label>
</script>


<!-- title element temaplates -->
<script type="text/html" id="tpl-newsman-form-el-title">
	<li gstype="title" class="newsman-form-item" data-bind="css: { selected: active }, click: $parent.elClick">
		<h3 data-bind="text: value"></h3>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>

<script type="text/html" id="tpl-newsman-options-title">
	<div class="newsman-field-options newsman-field-options-title" data-bind="visible: active()">
		<strong>Field options</strong>
		<label>Title</label>
		<input type="text" data-bind="value: value, valueUpdate:'afterkeydown'">
	</div>
</script>

<!-- /title element temaplates -->

<!-- html element temaplates -->
<script type="text/html" id="tpl-newsman-form-el-html">
	<li gstype="title" class="newsman-form-item" data-bind="css: { selected: active }, click: $parent.elClick">
		<div data-bind="html: value"></div>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>

<script type="text/html" id="tpl-newsman-options-html">
	<div class="newsman-field-options newsman-field-options-html" data-bind="visible: active()">
		<strong>Field options</strong>
		<label>HTML content</label>
		<textarea data-bind="value: value, valueUpdate:'afterkeydown'"></textarea>
	</div>
</script>

<!-- /html element temaplates -->

<!-- Text element templates -->
<script type="text/html" id="tpl-newsman-form-el-text">
	<li gstype="text" class="newsman-form-item text" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" style="display: none;" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<input type="text" name="" value="" placeholder="" data-bind="value: value, attr: { placeholder: label, name: fieldName() }">
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-form-el-email">
	<li gstype="text" class="newsman-form-item text" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" style="display: none;" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<input type="text" name="" value="" placeholder="" data-bind="value: value, attr: { placeholder: label, name: fieldName() }">
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-text">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong>Field options</strong><br>
		<label for="newsman_fb_field_name">Name</label>
		<input class="newsman_fb_field_name span2" type="text" data-bind="value:label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> Required</label>
	</div>
</script>
<!-- /Text element templates -->

<!-- Checkbox element templates -->
<script type="text/html" id="tpl-newsman-form-el-checkbox">
	<li gstype="checkbox" class="newsman-form-item" style="position: relative;" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label checkbox"><input type="checkbox" name="" value="1" data-bind="attr: {name: fieldName()}"><span data-bind="text: label"></span></label>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-checkbox">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong>Field options</strong><br>
		<label for="newsman_fb_field_name">Name</label>
		<input class="newsman_fb_field_name span2" type="text" data-bind="value:label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> Required</label>
	</div>
</script>
<!-- /Checkbox element templates -->


<!-- Dummy element temaplates -->
<script type="text/html" id="tpl-newsman-form-el-dummy">
	<li gstype="text" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<p>dummy</p>
	</li>
</script>

<script type="text/html" id="tpl-newsman-options-dummy">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong>Field options</strong><br>
		<p>No options in dummy template</p>
	</div>
</script>

<!-- /Dummy element temaplates -->


<!-- Submit element templates -->
<script type="text/html" id="tpl-newsman-form-el-submit">
	<!-- newsman-button newsman-button-brick yellow -->
	<li gstype="submit" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<button type="submit" class="" data-bind="text: value, css: getSubmitClass()"></button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-submit">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong>Field options</strong><br>
		<label for="newsman_fb_field_name">Name</label>
		<input class="newsman_fb_field_name span2" type="text" data-bind="value:value">

		<label>Size</label>
		<select class="input-medium" data-bind="value:size">
			<option value="mini">Mini</option>
			<option value="small">Small</option>
			<option value="medium">Medium</option>
			<option value="large">Large</option>
		</select>

		<label>Color</label>
		<select class="input-medium" data-bind="value: color">
			<option value="gray">Gray</option>
			<option value="pink">Pink</option>
			<option value="blue">Blue</option>
			<option value="green">Green</option>
			<option value="turquoise">Turquoise</option>
			<option value="black">Black</option>
			<option value="darkgray">Dark Gray</option>
			<option value="yellow">Yellow</option>
			<option value="purple">Purple</option>
			<option value="darkblue">Dark blue</option>
		</select>		

		<label>Style</label>
		<select class="input-medium" data-bind="value: style">
			<option value="none">None ( Theme native )</option>
			<option value="brick">Brick</option>
			<option value="rounded">Rounded</option>
			<option value="pill">Pill</option>
		</select>


	</div>
</script>
<!-- /Submit element templates -->


<!-- Radio element templates -->
<script type="text/html" id="tpl-newsman-form-el-radio">
	<li gstype="radio" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" data-bind="text: label"></label>
 		<!-- ko foreach: children -->		
		<label class="radio"><input type="radio" name="" value="" data-bind="value: value, checked: $parent.checked"><span data-bind="text: label"></span></label>
		<!-- /ko -->
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-radio">
	<div class="newsman-field-options newsman-field-options-radio" data-bind="visible: active">
		<strong>Field options</strong><br>
		<label for="newsman_fb_field_name">Name</label>
		<input class="newsman_fb_field_name span2" type="text" data-bind="value: label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> Required</label>
		<div class="newsman-opt-sect-header">
			<strong>Radio options</strong>
			<div class="btn-group">
				<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown"><i class="icon-plus-sign"></i> Add <span class="caret"></span></button>
				<ul class="dropdown-menu">
					<li><a href="#" data-bind="click: addOption">New option</a></li>
					<li class="divider"></li>
					<li><a href="#" data-bind="click: loadOptionsList" data-list="genders">Genders</a></li>
				</ul>
			</div>			
		</div>
		<ul class="options unstyled" data-bind="foreach: children">
			<li class="radio-option" title="Double click to edit" data-bind="css: { edit: edit }, event: { dblclick: $root.toggleEdit}"><span data-bind="text:label"></span><input type="text" data-bind="value: label, valueUpdate:'afterkeydown', event: { blur: $root.toggleEdit }" class="newsman-field-options-radio-opt-input"><i class="icon-trash newsman-remove-option" data-bind="click: $parent.removeOption"></i></li>
		</ul>		
	</div>
</script>
<!-- /Radio element temaplates -->

<!-- Select element temaplates -->
<script type="text/html" id="tpl-newsman-form-el-select">
	<li gstype="radio" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<select data-bind="foreach: children">
			<option data-bind="html: label, value: value"></option>
		</select>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-select">
	<div class="newsman-field-options newsman-field-options-select" data-bind="visible: active()">
		<strong>Field options</strong><br>
		<label for="newsman_fb_field_name">Name</label>
		<input class="newsman_fb_field_name span2" type="text" data-bind="value: label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> Required</label>
		<div class="newsman-opt-sect-header">
			<strong>Select options</strong>
			<div class="btn-group">
				<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown"><i class="icon-plus-sign"></i> Add <span class="caret"></span></button>
				<ul class="dropdown-menu">
					<li><a href="#" data-bind="click: addOption">New option</a></li>
					<li class="divider"></li>
					<li><a href="#" data-bind="click: loadOptionsList" data-list="countries">Countries</a></li>
					<li><a href="#" data-bind="click: loadOptionsList" data-list="states">States</a></li>
				</ul>
			</div>			
		</div>
		<ul class="options unstyled" data-bind="foreach: children">
			<li class="radio-option" title="Double click to edit" data-bind="css: { edit: edit }, event: { dblclick: $root.toggleEdit}"><span data-bind="html:label"></span><input type="text" data-bind="value: label, valueUpdate:'afterkeydown', event: { blur: $root.toggleEdit }" class="newsman-field-options-radio-opt-input"><i class="icon-trash newsman-remove-option" data-bind="click: $parent.removeOption"></i></li>
		</ul>		
	</div>
</script>
<!-- /Select element temaplates -->


<!--  /form builder templates -->

<div class="wrap wp_bootstrap" id="newsman-page-list">

	<div class="row-fluid" style="border-bottom: 1px solid #DADADA;">
		<div class="span12">
			<h2><?php _e('Edit subscribers list', NEWSMAN); ?></h2>
		</div>		
	</div>

	<br>

	<form name="newsman_form_g" id="newsman_form_g" method="POST" action="">
		
		<label for="newsman_form_name"><h2>List name</h2></label>
		<input type="text" class="span9" id="newsman_form_name" name="newsman-form-name" placeholder="List Name">

		<!--												Submission form	 							-->

<!-- 		<div class="row-fluid ">
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
		</div> -->

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
							<li><a type="select"><?php _e('Dropdown menu', NEWSMAN); ?></a></li>
							<li><a type="title"><?php _e('Form title', NEWSMAN); ?></a></li>
							<li><a type="html"><?php _e('HTML', NEWSMAN); ?></a></li>
						</ul>
					</div>
					<button class="btn" id="btn-load-default-form"><i class="icon-warning-sign"></i> <?php _e('Load Default Form', NEWSMAN); ?></button>
				</div>

				<div class="row" id="fb-panel">
					<div class="span5">
						<?php
							$frm = new newsmanForm($id, true);
						?>
						<!-- <ul class="newsman-form inline-labels" xdata-bind='template: { name: formItemTpl, foreach: elements }'> -->
						<ul class="newsman-form inline-labels" data-bind="sortable: { template: formItemTpl, data: elements }">
						</ul>
						<input type="hidden" name="serialized-form" id="serialized-form" value="<?php echo htmlspecialchars($frm->raw); ?>">
					</div>
					<div class="span4">
						<div class="alert newsman-options-panel">
							<div id="newsman-formbuilder-formoptions" class="">
								<strong><?php _e('Form options', NEWSMAN); ?></strong><br>
								<label class="checkbox" for="use-inline-labels"><input id="use-inline-labels" data-bind="checked: useInlineLabels" type="checkbox"> Use inline labels </label>
							</div>
							<div id="newsman-formbuilder-options" data-bind="template: { name: optionsTpl, foreach: elements }">
								
							</div>							
						</div>
					</div>
				</div>	

				<input type="hidden" id="newsman_form_json" name="newsman-form-json" value="" />					
			</div>
			<div class="span4 ext-form-block">
				<?php do_action_ref_array('newsman_get_ext_form_options', array( $list )); ?>
				<h3><?php _e('One more thing,'); ?></h3>
				<p><?php _e('you can put this form inside any post content with this short-code:'); ?></p>
				<pre><code>[newsman-form id='<?php echo $list->id; ?>']</code></pre>
				<p><?php _e('and you can make it horizontal with this shortcode'); ?></p>
				<pre><code>[newsman-form id='<?php echo $list->id; ?>' horizontal]</code></pre>
			</div>

		</div>

		<hr>
		<button id="newsman-save-list" type="button" class="btn btn-primary btn-large newsman-update-options"><?php _e('Save', NEWSMAN); ?></button>

	</form>

	<?php include("_footer.php"); ?>
	
</div>