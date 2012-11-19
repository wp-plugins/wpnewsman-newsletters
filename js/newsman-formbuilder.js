jQuery(function($){


	$.widget('newsman.newsmanFormElement', {
		options: {
			useInlineLabels: false,
			optionsContainer: null,
			formItem: null
		},
		_create: function() {
			this.formItem = $(this.options.formItem);

			// 'use-inline-labels'

			this.options.useInlineLabels = !!this.formItem.find('input').attr('placeholder');

			this.optionsContainer = $(this.options.optionsContainer);
			this.optionsEl = $('<div class="alert newsman-field-options">');

			$([
				'<strong>Field options</strong>',
				'<br>',
				'<label for="newsman_fb_field_name">Name:</label>',
				'<input class="newsman_fb_field_name span2" type="text">'
			].join('')).appendTo(this.optionsEl);			
		},
		_init: function() {
			var that = this;

			this.formItem.click(function(e){
				e.preventDefault();
				that.selectedItem();
				that.showOptions();
			});

			// field name
			$('.newsman_fb_field_name', this.optionsEl).keyup(function(e){
				var newName = $(this).val();
				that.setFormElName(newName);
			});

			$('.close', this.formItem).click(function(e){
				that.formItem.fadeOut(function(){
					that.formItem.remove();
				});
			});			
		},
		selectedItem: function() {
			var form = this.formItem.closest('.newsman-form');
			console.log(form.get(0));
			$('.newsman-form-item', form).removeClass('selected');
			this.formItem.addClass('selected');
		},
		showOptions: function() {
			this.optionsEl.find('.newsman_fb_field_name').val(this.getFormElName());

			this.optionsContainer.empty();
			this.optionsEl.appendTo(this.optionsContainer);
		},
		setRequired: function(req) {
			this.formItem[req ? 'addClass' : 'removeClass']('newsman-required');
		},
		getRequired: function() {
			return this.formItem.hasClass('newsman-required');			
		},
		getFormElName: function() {
			var lbl = this.formItem.find('label').get(0)
			return $(lbl).text();
		},
		setFormElName: function(newName) {			
			var lbl = $('label', this.formItem).get(0),
				textNode = lbl.childNodes[1] || lbl.childNodes[0];
			textNode.textContent = newName;
		},
		setFormElValue: function(value){

		},
		setFormElReq: function(required) {

		},
		updateOptions: function(newOpts) {
			for ( var p in newOpts ) {
				this.options[p] = newOpts[p];
			}
			if ( this.optionsUpdated ) {
				this.optionsUpdated();
			}
		}
	});

	$.widget('newsman.newsmanFormElementText', $.newsman.newsmanFormElement, {
		_create: function() {
			var that = this;

			$.newsman.newsmanFormElement.prototype._create.apply(this, arguments);

			// creating options controls
			
			$([
				'<br>',
				'<label class="checkbox"><input class="required" type="checkbox" name="required"> Required</label>'
			].join('')).appendTo(this.optionsEl);
		},
		_init: function() {
			var that = this;
			$.newsman.newsmanFormElement.prototype._init.apply(this, arguments);

			$('input.required', this.optionsEl).change(function(e){
				that.setRequired(this.checked);
			});
		},
		showOptions: function() {
			$.newsman.newsmanFormElement.prototype.showOptions.apply(this, arguments);

			$('input[name="required"]', this.optionsEl).get(0).checked = this.getRequired();
		},
		getFormElName: function() {
			if ( this.options.useInlineLabels ) {
				return this.formItem.find('input').attr('placeholder');
			} else {
				return $.newsman.newsmanFormElement.prototype.getFormElName.apply(this, arguments);
			}
		},
		setFormElName: function(newName) {
			if ( this.options.useInlineLabels ) {
				this.formItem.find('input').attr('placeholder', newName);
			} else {
				$.newsman.newsmanFormElement.prototype.setFormElName.apply(this, arguments);
			}
		},
		optionsUpdated: function() {
			var op = this.options.useInlineLabels ? 'hide' : 'show';
			var lbl = $('label', this.formItem)[op]().text();
			if ( this.options.useInlineLabels ) {
				$('input[type="text"], input[type="email"]', this.formItem).attr('placeholder', lbl)
			} else {
				$('input[type="text"], input[type="email"]', this.formItem).removeAttr('placeholder');
			}
		}		
	});	

	$.widget('newsman.newsmanFormElementCheckbox', $.newsman.newsmanFormElement, {
		_create: function() {
			var that = this;

			$.newsman.newsmanFormElement.prototype._create.apply(this, arguments);

			// creating options controls
			
			$([
				'<br>',
				'<label for="newsman_fb_field_value" class="checkbox"><input type="checkbox" class="newsman_fb_field_value"> Default state</label>',
				'<br>',
				'<label class="checkbox"><input class="required" type="checkbox" name="required"> Required</label>'
			].join('')).appendTo(this.optionsEl);

		},
		_init: function() {
			var that = this;
			$.newsman.newsmanFormElement.prototype._init.apply(this, arguments);

			$('input.required', this.optionsEl).change(function(e){
				that.setRequired(this.checked);
			});

			$('input.newsman_fb_field_value', this.optionsEl).change(function(e){
				$('input[type="checkbox"]', that.formItem)[0].checked = this.checked;
			});
		}		
	});		

	$.widget('newsman.newsmanFormElementRadio', $.newsman.newsmanFormElement, {
		_create: function() {
			var that = this;

			$.newsman.newsmanFormElement.prototype._create.apply(this, arguments);

			// creating options controls

			this.optionsEl.addClass('newsman-field-options-radio');
			
			$([
				'<div class="options">',					
				'</div>',
				'<button class="btn" type="button"><i class="icon-plus-sign"></i> Add Option</button>',
				'<br>',
				'<div class="option-params" style="display: none;">',
					'<hr>',
					'<strong>Selected option name</strong>',
					'<br>',
					'<input class="newsman_fb_option_name span2" type="text">',
				'</div>'
				// '<label class="checkbox"><input class="required" type="checkbox" name="required"> Required</label>'
			].join('')).appendTo(this.optionsEl);

			$('button', this.optionsEl).click(function(e){
				that.addlinkedRadio();
			});

			this.optionsList = $('.options', this.optionsEl);

			this.formItem.find('input[type="radio"]').each(function(i, formRadioEl){
				that.addlinkedRadio($(formRadioEl).closest('label'));
			});
		},
		_init: function() {
			$.newsman.newsmanFormElement.prototype._init.apply(this, arguments);
			var that = this;
			$('.newsman_fb_field_name', this.optionsEl).change(function(){
				that.setCommonName();
			});
			$('input.required', this.optionsEl).change(function(e){
				that.setRequired(this.checked);
			});			

			$('.newsman_fb_option_name', that.optionsEl).keyup(function(e){
				var newName = $(this).val();
				that.optionsEl.find('label.radio.selected span').text(newName);
				that.selectedFormRadioEl.find('span').text(newName);
			});	
		},
		setCommonName: function() {
			var commonName = safeName( $('.newsman_fb_field_name', this.optionsEl).val() );
			this.formItem.find('input[type="radio"]').attr('name', commonName);
			this.optionsList.find('input[type="radio"]').attr('name', 'ed-'+commonName);			
		},
		showOptions: function() {
			$.newsman.newsmanFormElement.prototype.showOptions.apply(this, arguments);
			this.setCommonName();
		},
		addlinkedRadio: function(formRadioEl) {
			var that = this;
			var num = this.formItem.find('input[type="radio"]').length+1;

			var commonName = safeName( $('.newsman_fb_field_name', this.optionsEl).val() );

			if ( !formRadioEl ) {
				formRadioEl = $('<label class="radio"><input type="radio" name="'+commonName+'" value="new-option-'+num+'"><span>new option '+num+'</span></label>').appendTo(this.formItem);
			} else {
				formRadioEl = $(formRadioEl);
			}

			var name = formRadioEl.closest('label').text();

			var edRadio = $('<label class="radio radio-option"><input type="radio" name="ed-'+commonName+'"><span>'+name+'</span><i class="icon-minus-sign newsman-remove-option"></i></label>').appendTo(this.optionsList);

			edRadio.click(function(e){
				that.optionsEl.find('label.radio').removeClass('selected');
				edRadio.addClass('selected');
				that.selectedFormRadioEl = formRadioEl;
				$('.option-params', that.optionsEl).show();
				$('.newsman_fb_option_name', that.optionsEl).val(edRadio.find('span').text());
			});

			var rbOpt  = $('input', edRadio)[0],
				rbForm = $('input', formRadioEl)[0];

			rbOpt.checked = rbForm.checked;

			$(rbOpt).change(function(e){
				rbForm.checked = rbOpt.checked;
			});

			edRadio.find('i').click(function(e){
				formRadioEl.remove();
				edRadio.remove();
			});
		}
	});		

	$.widget('newsman.newsmanFormElementSubmit', $.newsman.newsmanFormElement, {
		_create: function() {
			var that = this;

			$.newsman.newsmanFormElement.prototype._create.apply(this, arguments);

			// creating options controls
			
			$([
				'<br>',
				'<label class="checkbox"><input class="required" type="checkbox" name="required"> Required</label>'
			].join('')).appendTo(this.optionsEl);
		},
		getFormElName: function() {
			return this.formItem.find('input').val();
		},
		setFormElName: function(newName) {
			$('input[type="submit"]', this.formItem).val(newName);
		}
	});	

	var selectedItem;

	function getTextNodeValue(el) {
		if (!el) { return ''; }
		for (var i = 0; i < el.childNodes.length; i++) {
			if (el.childNodes[i].nodeType == 3) {
				return el.childNodes[i].textContent;
			}
		}
	}


	function safeName(str) {
		return str.replace(/\W+$/ig, '').replace(/\W+/ig, '-').toLowerCase();
	}

	function showOptions() {
		var type = selectedItem.attr('gstype'),
			label = getTextNodeValue($('label', selectedItem).get(0)),
			required = selectedItem.hasClass('required'),
			value = $('input', selectedItem).val(),
			foBlock = null;

		$('.newsman-field-options').hide();

		switch ( type ) {
			case 'radio':
				foBlock = $('#newsman-field-options-radio').show();
				$('#newsman-field-options-otitle').show();
				$('#newsman-field-options-radio .newsman_fb_field_name').val(label);

				var optionsUl =$('#newsman-field-options-radio .options').empty();
				var name = safeName(label);

				$('label.radio', selectedItem).each(function(i, el){
					var lbl = getTextNodeValue(el);
					var chkd = $('input', el).is(':checked') ? 'checked="checked"' : '';
					$('<label id="ed-'+el.id+'" class="radio radio-option"><input name="ed-'+name+'" '+chkd+' type="radio">'+lbl+' <i class="icon-minus-sign newsman-remove-option"></i></label>').appendTo(optionsUl);
				});
				
				break;
			case 'text':
			case 'email':
				foBlock = $('#newsman-field-options-text').show();
				$('#newsman-field-options-text .newsman_fb_field_name').val(label);
				$('#newsman-field-options-text .newsman_fb_field_value').val(value);									
				break;
			case 'checkbox':
				foBlock = $('#newsman-field-options-checkbox').show();
				$('#newsman-field-options-checkbox .newsman_fb_field_name').val(label);
				$('#newsman-field-options-checkbox input[type="checkbox"]').get(0).checked = 
					$('input[type="checkbox"]', selectedItem).is(':checked');
				break;

			case 'submit':
				$('#newsman-field-options-submit').show();
				label = $('input[type="submit"]', selectedItem).val();
				$('#newsman-field-options-submit .newsman_fb_field_name').val(label);
				break;
		}

		if ( foBlock ) {
			$('input.required', foBlock).attr('checked', required);
		}
	}

	$.widget('newsman.newsmanFormBuilder', {
		options: {
			useInlineLabels: false
		},
		_create: function() {
			var that = this;
			this.formEl = this.element.find('.newsman-form');

			this.options.useInlineLabels = this.formEl.hasClass('inline-labels');

			$('#use-inline-labels').get(0).checked = this.options.useInlineLabels;

			function setFormOptions (argument) {
				$('.newsman-form')[that.options.useInlineLabels ? 'addClass' : 'removeClass']('inline-labels');
			}
			setFormOptions();
			
			$('#use-inline-labels').change(function() {
				var u = that.options.useInlineLabels = $(this).get(0).checked;
				that.eachElement('updateOptions', {
					useInlineLabels: u
				});
				setFormOptions();
			});

			$('.newsman-form-item').each(function(i, el) {
				var opts = {
					useInlineLabels: that.options.useInlineLabels,
					optionsContainer: '#newsman-formbuilder-options',
					formItem: $(el)
				};

				switch ( $(el).attr('gstype') ) {
					case 'text':
					case 'email':
						$(el).newsmanFormElementText(opts);
						break;
					case 'checkbox':
						$(el).newsmanFormElementCheckbox(opts);
						break;
					case 'radio':
						$(el).newsmanFormElementRadio(opts);
						break;
					case 'submit':
						$(el).newsmanFormElementSubmit(opts);
						break;
				}
			});

			$('.newsman-form').sortable();

			$('#btn-add-field li').click(function(e){
				that.addField($(e.target).attr('type'));
			});
	
		},
		eachElement: function(method, params) {
			var args = Array.prototype.slice.call(arguments);

			$('.newsman-form-item').each(function(i, el) {
				switch ( $(el).attr('gstype') ) {
					case 'text':
					case 'email':
						$(el).newsmanFormElementText(method, params);
						break;
					case 'checkbox':
						$(el).newsmanFormElementCheckbox(method, params);
						break;
					case 'radio':
						$(el).newsmanFormElementRadio(method, params);
						break;
					case 'submit':
						$(el).newsmanFormElementSubmit(method, params);
						break;
				}
			});
		},
		addField: function(type) {
			var html = '',
				inl = this.options.useInlineLabels;
			switch ( type ) {
				case 'checkbox':
					html = ['<li gstype="checkbox" class="newsman-form-item">',
								'<label class="checkbox">',
									'<input type="checkbox" name="check-me" value="1">',
									newsmanL10n.checkMe,
								'</label>',
								'<span style="display:none" class="newsman-required-msg cbox">'+newsmanL10n.required+'</span>',
								'<button class="close">&times;</button>',
							'</li>'].join('');
					break;
				case 'radio':
					html = [
						'<li gstype="radio" class="newsman-form-item">',
							'<label>'+newsmanL10n.chooseAnOption+'</label>',
							'<span style="display:none;" class="newsman-required-msg radio">'+newsmanL10n.required+'</span>',
							'<label class="radio"><input type="radio" name="choose_an_option" value="new-option-1"><span>'+newsmanL10n.optionOne+'</span></label>',
							'<button class="close">×</button>',
						'</li>'].join('');
					break;
				case 'text':
					html = ['<li gstype="text" class="newsman-form-item">',
								'<label'+(inl ? ' style="display: none;"': '')+'>'+newsmanL10n.untitled+'</label>',
								'<input type="text" name="untitled" value="" '+(inl ? 'placeholder="'+newsmanL10n.untitled+'"' : '')+'>',
								'<span class="newsman-required-msg" style="display:none;">'+newsmanL10n.required+'</span>',
								'<button class="close">&times;</button>',
							'</li>'].join('');
					break;
			}
			var el = $(html).appendTo($('ul.newsman-form'));

			var opts = {
				useInlineLabels: this.options.useInlineLabels,
				optionsContainer: '#newsman-formbuilder-options',
				formItem: $(el)
			};			

			switch ( type ) {
				case 'text':
				case 'email':
					$(el).newsmanFormElementText(opts);
					break;
				case 'checkbox':
					$(el).newsmanFormElementCheckbox(opts);
					break;
				case 'radio':
					$(el).newsmanFormElementRadio(opts);
					break;
				case 'submit':
					$(el).newsmanFormElementSubmit(opts);
					break;
			}			
		},
		serializeForm: function(dontStore) {
			var form = { elements: [] };
			form.useInlineLabels = $('.newsman-form').hasClass('inline-labels');

			$('.newsman-form-builder ul.newsman-form li').each(function(i, el){
				var lbl = $('label', el).get(0);
				var elObj = {
					type: $(el).attr('gstype')
				};

				if ( $(el).hasClass('newsman-required') ) {
					elObj.required = true;
				}

				if ( lbl ) {
					elObj.label = getTextNodeValue(lbl);
					elObj.name = safeName(elObj.label);
				}

				switch ( elObj.type ) {
					case 'text':
					case 'submit':
					case 'email':
						elObj.value = $('input', el).val();
						break;
					case 'checkbox':
						elObj.checked = $('input', el).is(':checked');
						elObj.value = '1';
						break;
					case 'radio':
						elObj.children = [];
						$('label.radio input', el).each(function(i, el){
							var chld = {}, el = $(this);
							chld.checked = el.is(':checked');
							chld.value = el.val();
							chld.label = $(el).closest('label').find('span').text();

							elObj.children.push(chld);											
						});
						var v = $('label.radio input').val();
						break;
				}


				form.elements.push(elObj);
			});	

			var jsonForm = JSON.stringify(form)
			
			return jsonForm;
		}
	});	

});