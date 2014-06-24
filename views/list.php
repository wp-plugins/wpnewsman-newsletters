<script>
	window.NEWSMAN_LIST_ID = '<?php echo $id; ?>';
	window.NEWSMAN_DEF_FORM = <?php echo newsmanGetDefaultForm();?>;
</script>

<!--  form builder templates -->

<script type="text/html" id="tpl-newsman-form-options">
	<strong><?php _e('Form options', NEWSMAN); ?></strong><br>
	<label class="checkbox" for="use-inline-labels"><input id="use-inline-labels" type="checkbox"> <?php _e('Use inline labels', NEWSMAN); ?> </label>
</script>


<!-- title element templates -->
<script type="text/html" id="tpl-newsman-form-el-title">
	<li gstype="title" class="newsman-form-item" data-bind="css: { selected: active }, click: $parent.elClick">
		<h3 data-bind="text: value"></h3>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>

<script type="text/html" id="tpl-newsman-options-title">
	<div class="newsman-field-options newsman-field-options-title" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong>
		<label><?php _e('Title', NEWSMAN); ?></label>
		<input type="text" data-bind="value: value, valueUpdate:'afterkeydown'">
	</div>
</script>

<!-- /title element templates -->

<!-- html element templates -->
<script type="text/html" id="tpl-newsman-form-el-html">
	<li gstype="title" class="newsman-form-item" data-bind="css: { selected: active }, click: $parent.elClick">
		<div data-bind="html: value"></div>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>

<script type="text/html" id="tpl-newsman-options-html">
	<div class="newsman-field-options newsman-field-options-html" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong>
		<label><?php _e('HTML content', NEWSMAN); ?></label>
		<textarea data-bind="value: value, valueUpdate:'afterkeydown'"></textarea>
	</div>
</script>

<!-- /html element templates -->

<!-- Text element templates -->
<script type="text/html" id="tpl-newsman-form-el-text">
	<li gstype="text" class="newsman-form-item text" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" style="display: none;" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<input type="text" name="" value="" placeholder="" data-bind="value: value, placeholder: ph(), attr: { name: name() }">
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-form-el-email">
	<li gstype="text" class="newsman-form-item text" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" style="display: none;" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<input type="text" name="" value="" placeholder="" data-bind="value: value, placeholder: ph(), attr: { name: name() }">
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-text">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>
		<label for="newsman_fb_field_name"><?php _e('Name', NEWSMAN); ?></label>
		<input class="newsman_fb_field_name input-large" type="text" data-bind="value:label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> <?php _e('Required', NEWSMAN); ?></label>
	</div>
</script>
<!-- /Text element templates -->

<!-- Textarea element templates -->
<script type="text/html" id="tpl-newsman-form-el-textarea">
	<li gstype="textarea" class="newsman-form-item textarea" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" style="display: none;" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<textarea data-bind="value: value, valueUpdate:'afterkeydown', attr: { name: name(), placeholder: ph() } "></textarea>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-textarea">
	<div class="newsman-field-options newsman-field-options-textarea" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>

		<label for="newsman_fb_field_name"><?php _e('Name', NEWSMAN); ?></label>
		<input class="newsman_fb_field_name input-large" type="text" data-bind="value:label">
		<label><?php _e('Content', NEWSMAN); ?></label>
		<textarea data-bind="value: value, valueUpdate:'afterkeydown'"></textarea>

		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> <?php _e('Required', NEWSMAN); ?></label>
	</div>
</script>
<!-- /Textarea element templates -->

<!-- Checkbox element templates -->
<script type="text/html" id="tpl-newsman-form-el-checkbox">
	<li gstype="checkbox" class="newsman-form-item" style="position: relative;" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label checkbox"><input type="checkbox" name="" value="1" data-bind="attr: {name: name()}"><span data-bind="text: label"></span></label>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-checkbox">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>
		<label for="newsman_fb_field_name"><?php _e('Name', NEWSMAN); ?></label>
		<input class="newsman_fb_field_name input-large" type="text" data-bind="value:label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> <?php _e('Required', NEWSMAN); ?></label>
	</div>
</script>
<!-- /Checkbox element templates -->


<!-- Dummy element templates -->
<script type="text/html" id="tpl-newsman-form-el-dummy">
	<li gstype="text" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<p>dummy</p>
	</li>
</script>

<script type="text/html" id="tpl-newsman-options-dummy">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>
		<p>No options in dummy template</p>
	</div>
</script>

<!-- /Dummy element templates -->


<!-- Submit element templates -->
<script type="text/html" id="tpl-newsman-form-el-submit">
	<!-- newsman-button newsman-button-brick yellow -->
	<li gstype="submit" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<button type="submit" class="" data-bind="text: value, css: getSubmitClass()"></button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-submit">
	<div class="newsman-field-options newsman-field-options-text" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>
		<label for="newsman_fb_field_name"><?php _e('Name', NEWSMAN); ?></label>
		<input class="newsman_fb_field_name input-large" type="text" data-bind="value:value">

		<label><?php _e('Size', NEWSMAN); ?></label>
		<select class="input-large" data-bind="value:size">
			<option value="mini"><?php _e('Mini', NEWSMAN); ?></option>
			<option value="small"><?php _e('Small', NEWSMAN); ?></option>
			<option value="medium"><?php _e('Medium', NEWSMAN); ?></option>
			<option value="large"><?php _e('Large', NEWSMAN); ?></option>
		</select>

		<label><?php _e('Color', NEWSMAN); ?></label>
		<select class="input-large" data-bind="value: color">
			<option value="gray"><?php _e('Gray', NEWSMAN); ?></option>
			<option value="pink"><?php _e('Pink', NEWSMAN); ?></option>
			<option value="blue"><?php _e('Blue', NEWSMAN); ?></option>
			<option value="green"><?php _e('Green', NEWSMAN); ?></option>
			<option value="turquoise"><?php _e('Turquoise', NEWSMAN); ?></option>
			<option value="black"><?php _e('Black', NEWSMAN); ?></option>
			<option value="darkgray"><?php _e('Dark Gray', NEWSMAN); ?></option>
			<option value="yellow"><?php _e('Yellow', NEWSMAN); ?></option>
			<option value="purple"><?php _e('Purple', NEWSMAN); ?></option>
			<option value="darkblue"><?php _e('Dark blue', NEWSMAN); ?></option>
		</select>		

		<label><?php _e('Style', NEWSMAN); ?></label>
		<select class="input-large" data-bind="value: style">
			<option value="none"><?php _e('None ( Theme native )', NEWSMAN); ?></option>
			<option value="brick"><?php _e('Brick', NEWSMAN); ?></option>
			<option value="rounded"><?php _e('Rounded', NEWSMAN); ?></option>
			<option value="pill"><?php _e('Pill', NEWSMAN); ?></option>
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
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>
		<label for="newsman_fb_field_name"><?php _e('Name', NEWSMAN); ?></label>
		<input class="newsman_fb_field_name input-large" type="text" data-bind="value: label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> <?php _e('Required', NEWSMAN); ?></label>
		<div class="newsman-opt-sect-header">
			<strong><?php _e('Radio options', NEWSMAN); ?></strong>
			<div class="btn-group">
				<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown"><i class="icon-plus-sign"></i> <?php _e('Add', NEWSMAN); ?> <span class="caret"></span></button>
				<ul class="dropdown-menu">
					<li><a href="#" data-bind="click: addOption"><?php _e('New option', NEWSMAN); ?></a></li>
					<li class="divider"></li>
					<li><a href="#" data-bind="click: loadOptionsList" data-list="genders"><?php _e('Genders', NEWSMAN); ?></a></li>
				</ul>
			</div>			
		</div>
		<ul class="options unstyled" data-bind="foreach: children">
			<li class="radio-option" title="<?php _e('Double click to edit', NEWSMAN); ?>" data-bind="css: { edit: edit }, event: { dblclick: $root.toggleEdit}"><span data-bind="text:label"></span><input type="text" data-bind="value: label, valueUpdate:'afterkeydown', event: { blur: $root.toggleEdit }" class="newsman-field-options-radio-opt-input"><i class="icon-trash newsman-remove-option" data-bind="click: $parent.removeOption"></i></li>
		</ul>		
	</div>
</script>
<!-- /Radio element templates -->

<!-- Select element templates -->
<script type="text/html" id="tpl-newsman-form-el-select">
	<li gstype="radio" class="newsman-form-item" data-bind="css: { selected: active, 'newsman-required': required }, click: $parent.elClick">
		<label class="newsman-form-item-label" data-bind="text: label, visible: !$parent.useInlineLabels()"></label>
		<select data-bind="foreach: children">
			<!-- ko if: $index() === 0 && $root.useInlineLabels() -->
			<option value="" data-bind="text: $parent.label()" selected="selected"></option>
			<!-- /ko -->
			<option data-bind="html: label, value: value"></option>
		</select>
		<button class="close" data-bind="click: removeFormItem">×</button>
	</li>
</script>
<script type="text/html" id="tpl-newsman-options-select">
	<div class="newsman-field-options newsman-field-options-select" data-bind="visible: active()">
		<strong><?php _e('Field options', NEWSMAN); ?></strong><br>
		<label for="newsman_fb_field_name"><?php _e('Name', NEWSMAN); ?></label>
		<input class="newsman_fb_field_name input-large" type="text" data-bind="value: label">
		<label class="checkbox"><input type="checkbox" data-bind="checked: required"> <?php _e('Required', NEWSMAN); ?></label>
		<div class="newsman-opt-sect-header">
			<strong><?php _e('Select options', NEWSMAN); ?></strong>
			<div class="btn-group">
				<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown"><i class="icon-plus-sign"></i> <?php _e('Add', NEWSMAN); ?> <span class="caret"></span></button>
				<ul class="dropdown-menu">
					<li><a href="#" data-bind="click: addOption"><?php _e('New option', NEWSMAN); ?></a></li>
					<li class="divider"></li>
					<li><a href="#" data-bind="click: loadOptionsList" data-list="countries"><?php _e('Countries', NEWSMAN); ?></a></li>
					<li><a href="#" data-bind="click: loadOptionsList" data-list="states"><?php _e('States', NEWSMAN); ?></a></li>
				</ul>
			</div>			
		</div>
		<ul class="options unstyled" data-bind="sortable: children">
			<li class="radio-option" title="<?php _e('Double click to edit', NEWSMAN); ?>" data-bind="css: { edit: edit }, event: { dblclick: $root.toggleEdit}"><span data-bind="html:label"></span><input type="text" data-bind="value: label, valueUpdate:'afterkeydown', event: { blur: $root.toggleEdit }" class="newsman-field-options-radio-opt-input"><i class="icon-trash newsman-remove-option" data-bind="click: $parent.removeOption"></i></li>
		</ul>		
	</div>
</script>
<!-- /Select element templates -->

<!--  /form builder templates -->

<div class="wrap wp_bootstrap" id="newsman-page-list">
	<?php include("_header.php"); ?>
	<div class="row-fluid" style="border-bottom: 1px solid #DADADA;">
		<div class="span12">
			<h2><?php _e('Edit subscribers list', NEWSMAN); ?><span style="margin-left: 10px;"><?php do_action('newsman_put_list_select', false); ?></span> <a href="#" id="btn-view-subscribers" class="btn"><?php newsmanEEnt(__('View Subscribers', NEWSMAN)); ?></a></h2>
		</div>		
	</div>

	<br>

	<form name="newsman_form_g" id="newsman_form_g" method="POST" action="">
		
		<label for="newsman_form_name"><h2><?php _e('List name', NEWSMAN); ?></h2></label>
		<input type="text" class="span9" id="newsman_form_name" name="newsman-form-name" placeholder="<?php _e('List Name', NEWSMAN); ?>">

		<!--												Submission form	 							-->

		<div class="row-fluid newsman-form-builder">
			<div class="span8">
				<div class="h-toolbar">
					<h2><?php _e('Form Builder', NEWSMAN); ?></h2>
					<div id="btn-add-field" class="btn-group">
						<a class="btn btn-primary dropdown-toggle" data-toggle="dropdown"><i class="icon-plus-sign icon-white"></i> <?php _e('Add Field', NEWSMAN); ?> <span class="caret"></span></a>
						<ul class="dropdown-menu">
							<li><a type="text"><?php _e('Text', NEWSMAN); ?></a></li>
							<li><a type="textarea"><?php _e('Textarea', NEWSMAN); ?></a></li>
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
					<div class="span6">
						<?php
							$frm = new newsmanForm($id, true);
						?>
						<!-- <ul class="newsman-form inline-labels" xdata-bind='template: { name: formItemTpl, foreach: elements }'> -->
						<ul class="newsman-form inline-labels" data-bind="sortable: { template: formItemTpl, data: elements }">
						</ul>
						<input type="hidden" name="serialized-form" id="serialized-form" value="<?php echo htmlspecialchars($frm->raw); ?>">
					</div>
					<div class="span6">
						<div class="alert newsman-options-panel">
							<div id="newsman-formbuilder-formoptions" class="">
								<strong><?php _e('Form options', NEWSMAN); ?></strong><br>
								<label class="checkbox" for="use-inline-labels"><input id="use-inline-labels" data-bind="checked: useInlineLabels" type="checkbox"> <?php _e('Use inline labels', NEWSMAN); ?> </label>
							</div>
							<div id="newsman-formbuilder-options" data-bind="template: { name: optionsTpl, foreach: elements }">
								
							</div>
							<div data-bind="template: { foreach: elements }">
								<div class="newsman-field-shortcode" data-bind="visible: shortcodeAvailable()">
									<strong><?php _e('Field shortcode', NEWSMAN); ?></strong><br>
									<p>[newsman sub=&apos;<span data-bind="text: name()"></span>&apos;]</p>
									<button class="btn btn-copy-shortcode" data-bind="attr: { 'data-clipboard-text': shortcode() }" title="Click me to copy shortcode."><i class="newsman-icon newsman-icon-paste"></i> Copy</button>
									<span class="copy-shortcode-done-msg" style="display: none;">Copied!</span>
								</div>								
							</div>
						</div>
					</div>
				</div>	

				<input type="hidden" id="newsman_form_json" name="newsman-form-json" value="" />					
			</div>
			<div class="span4 ext-form-block">				
				<h3><?php _e('This form on external sites? Sure!', NEWSMAN); ?></h3>
				<p><?php _e('Copy the code below and paste it into any other site that you have.', NEWSMAN); ?></p>
				<pre><code>&lt;iframe src="<?php echo NEWSMAN_PLUGIN_URL; ?>/form.php?uid=<?php echo $list->uid; ?>" style="border: none; min-height: 400px;"&gt;&lt;/iframe&gt;</code></pre>
				<h4><?php _e('Custom CSS for external form.', NEWSMAN); ?></h4>
				<p><?php _e('This CSS will only affect the external form', NEWSMAN); ?></p>
				<textarea id="newsman_form_extcss" name="newsman-form-extcss"></textarea>
				
				
				<h3><?php _e('Here are the unsubscribe links', NEWSMAN); ?></h3>
				<p><?php _e('if your are using 3rd party software to send emails:', NEWSMAN); ?></p>
				<p><strong><?php _e('Link to instant unsubscribe:', NEWSMAN); ?></strong></p>
				<pre><code><?php echo get_bloginfo('wpurl')."/?newsman=unsubscribe&code=".$list->uid.':[ucode]'; ?></code></pre>
				
				<p><?php echo sprintf(__('You must replace the %s with the value of the ucode field of the exported subscribers list.', NEWSMAN), '<code>[ucode]</code>'); ?></p>
				
				<h3><?php _e('One more thing,', NEWSMAN); ?></h3>
				<p><?php _e('you can put this form inside any post content with this short-code:', NEWSMAN); ?></p>
				<pre><code>[newsman-form id='<?php echo $list->id; ?>']</code></pre>
				<p><?php _e('and you can make it horizontal with this shortcode', NEWSMAN); ?></p>
				<pre><code>[newsman-form id='<?php echo $list->id; ?>' horizontal]</code></pre>
			</div>
		</div>

		<hr>
		<button id="newsman-save-list" type="button" class="btn btn-primary btn-large newsman-update-options"><?php _e('Save', NEWSMAN); ?></button>

	</form>

	<?php include("_footer.php"); ?>
	
</div>