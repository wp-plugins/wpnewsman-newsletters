jQuery(function($){

	var showMessage = function(){};

	var editorOptions = {
		template: {
			actGetData: 'newsmanAjGetTemplateData',
			actSetData: 'newsmanAjSetTemplateData',
			actEdit: 'newsmanAjEditTemplate',
			actEditStyle: 'newsmanAjEditStyle',
			actChangeSection: 'newsmanAjChangeTemplateSection'
		},
		email: {
			actGetData: 'newsmanAjGetEmailData',
			actSetData: 'newsmanAjSetEmailData',
			actEdit: 'newsmanAjEditEmail',
			actEditStyle: 'newsmanAjEditEmailStyle',
			actChangeSection: 'newsmanAjChangeEmailSection'
		}		
	};	

	if ( typeof NEWSMAN_ENT_TYPE === 'undefined' || !NEWSMAN_ENT_TYPE ) {
		return;
	}

	var O = editorOptions[NEWSMAN_ENT_TYPE];

	$('#newsman-send-datepicker').datetimepicker({
		dateFormat: 'DD, d MM, yy',
		// altFormat: "yy-mm-dd",
		// altField: '#newsman-bcst-start-date-sql'
	});

	$('#newsman-send-datepicker').datetimepicker('setDate', new Date());

	/**
	 *
	 *       HTML EDITOR
	 *
	 */

	//var md;

	function init() {
		var doc = $('#tpl-frame').get(0).contentDocument;

		$('html', doc).addClass('highlight-gsedit');

		//md = initMediaDialog(doc);
		initOutlets(doc);
		initStyle(doc);

		// $( ".container", doc).sortable({ helper: 'clone', handle: '.outlet-handle span' });
		$( ".container", doc).disableSelection();

		$('.color-selector').miniColors();

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: O.actGetData,
				id: NEWSMAN_ENTITY_ID,
				key: 'subject'
			}
		}).done(function(data){
			if ( data && typeof data.subject != 'undefined' ) {
				$('#tpl-subject').val(data.subject);
			}
		}).fail(function(t, status, message) {
			var data = JSON.parse(t.responseText);
			showMessage(data.msg, 'error');
		});		

		var isDigest = !!$('meta[name="glock-newsletter-digest"]', doc).get(0);

		if ( isDigest ) {
			$('#digest-controls').show();			
		}

		$('#tpl-subject').blur(function(){
			var val = $(this).val();

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: O.actSetData,
					id: NEWSMAN_ENTITY_ID,
					key: 'subject',
					value: val
				}
			}).done(function(data){
				var type = data.state ? 'success' : 'error';
				showMessage(data.msg, 'success');
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});					
		});  		
	}

	NEWSMAN.initOutlets = initOutlets;

	function outletTypeSwitched(ev, data) {

	 	var d = {
			action: O.actChangeSection,
			id: NEWSMAN_ENTITY_ID,
			section: data.gsedit,
			new_type: data.type
	 	};

	 	//*
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: d
		}).done(function(data){
			var type = data.state ? 'success' : 'error';
			showMessage(data.msg, 'success');
		}).fail(function(t, status, message) {
			var data = JSON.parse(t.responseText);
			showMessage(data.msg, 'error');
		});	
		//*/
	}

	function savePlainText(callback) {
		var frm = $('#tpl-frame').get(0),
			txt;

		$('.outlet-buttons', frm.contentDocument.body).remove();

		txt = $(frm.contentDocument.body).text();
		txt = $.trim(txt.replace('[newsman badge]', ''));
		txt = txt.replace(/(^\s+$)|(^\s+)/mg, ''); // removing empty string

	 	var data = {
			action: O.actSetData,
			id: NEWSMAN_ENTITY_ID,
			key: 'plain',
			value: txt 
	 	};

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data
		}).done(function(data){
			callback();
		}).fail(NEWSMAN.ajaxFailHandler);
	}
	

	function saveNewContent(content, outlet) {
	 	var data = {
			action: O.actEdit,
			id: NEWSMAN_ENTITY_ID,
			section: outlet.gsedit,
			new_content: content
	 	};

	 	//*
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data
		}).done(function(data){
			var type = data.state ? 'success' : 'error';

			savePlainText(function(err){
				if ( !err ) {
					showMessage(data.msg, 'success');		
				}
			});
			
		}).fail(NEWSMAN.ajaxFailHandler);	
		//*/
	}

	$.widget('glock.editorDialog', {
		options: {
			edSelector: null // editor selector
		},
		_create: function() {
			var that = this;

			this.element.appendTo(document.body);

			$(this.element).draggable({
				handle: '.editor-dialog-title'
			});

			this.element.hasClass('newsman-editor-dlg');

			if ( !this.options.edSelector ) {
				this.options.edSelector = 'textarea';
			}

			$(this.options.edSelector).bind('instanceReady.ckeditor', function(){
				that.edReady();
			});			

			// $(this.options.edSelector).bind('newsmanSave.ckeditor', function(){
			// 	alert('newsmanSave.ckeditor fired!!');
			// });

			var closeBtn = $('.newsman-editor-dlg-close', this.element);

			closeBtn.click(function(){
				that.close();
			});

			this.element.css({ position: 'absolute' });

			$(this.options.edSelector).ckeditor({
				width: 850,
				height: 400,
				resize_enabled: true,
				resize_dir: 'both',
				entities: false,
				entities_latin: false
			},function(){
				// that.edReady();
			});

			var ed = this.ed = $(this.options.edSelector).ckeditorGet();

			ed.on('key', function(e){
				if ( e.data.keyCode === 27) {
					that.close();
					e.cancel();
				}				
			});

			ed.on('newsmanSave.ckeditor', function(){
				that._trigger('save', 0, { html: ed.getData() } );
			});
		},
		attachKeyClose: function() {
			var that = this;
			this.keyClose = function(e) {
				if ( e.keyCode == 27 ) {
					that.escClose();
				}				
			};
			$(document).bind('keydown', this.keyClose);
		},
		detachKeyClose: function() {
			$(document).unbind('keydown', this.keyClose);
		},
		escClose: function() {
			this.close();
		},
		edReady: function() {

			this.element.css({
				position: 'fixed',
				top: '50%',
				left: '50%',
				zIndex: 1002
			}).show();


			var cont = this.ed.container.$,
				w = $(cont).width(),
				h = $(cont).height();

			this.element.css({
				marginLeft: -(Math.round(w/2)),
				marginTop: -(Math.round( (h-28)/2 ))
			});

			this.ed.focus();

		},
		setData: function(data) {
			$(this.options.edSelector).val(data);
		},
		show: function() {
			if ( !this.overlay ) {
				this.overlay = 
					$('<div class="ui-widget-overlay"></div>').css({
						position: 'fixed',
						top: 0,
						right: 0,
						bottom: 0,
						left: 0,
						zIndex: 1001
					}).appendTo(document.body);
			}

			this.element.show();
		},
		open: function(){
			this.show();
			this.attachKeyClose();
			this.ed.focus();
		},
		hide: function() {			
			this.overlay.remove();
			this.overlay = null;
			this.element.hide();
			this._trigger('close', 0, { });
		},
		close: function() {
			this.hide();
			this.detachKeyClose();
		}
	});

	/**
	 * Editable html block widget. Creates the content edit buttons in HTML template.
	 */
	$.widget('glock.outlet', {
		options: {
			// showMeta: true,				
			// buttons: ['']
		},
		_create: function(){
			var that = this,
				el = this.element;

			this.doc = this.options.doc;

			this.gsedit = $(el).attr('gsedit');
			this.buttons = $('<ul class="outlet-buttons"></ul>');

			this.isImage = $(el).is('img');

			this.type = $(el).is('img') ? 'image' : 'html';

			var gsblock = $(el).attr('gsblock');
				this.blocktypes = gsblock ? gsblock.split(',') : [];

			function enter (e) { that._enter(e); };
			function leave(e) { that._leave(e); };

			this.element
				.bind('mouseenter', enter)
				.bind('mouseleave', leave);

			this.unbind = function() {
				that.element
					.unbind('mouseenter', enter)
					.unbind('mouseleave', leave);

				that.buttons.unbind('mouseleave', leave);
			};


			this._getButtons();

			this.md = initMediaDialog(this.doc, this);
		},

		_bind: function() {
			var that = this;

			$('.ob-insert-html', this.buttons).click(function(e){
				e.preventDefault();
				that.switchTo('html');
			});

			$('.ob-insert-image', this.buttons).click(function(e){
				e.preventDefault();
				that.switchTo('image');
			});

			$('.ob-clear', this.buttons).click(function(e){
				e.preventDefault();
				that.clear();
			});						
		},		

		getAttrs: function() {
			var attrs = {}, el_attrs = this.element.get(0).attributes;

			for (var attr, i=0, l=el_attrs.length; i<l; i++){
			    attr = el_attrs.item(i);
			    attrs[attr.nodeName] = attr.nodeValue;
			}
			return attrs;
		},

		_getButtons: function() {

			var that = this,
				switchButtonsMap = {
					'html': 'insert HTML',
					'image':'insert image'
				};

			$('<li class="ob-clear">clear</li>').appendTo(this.buttons);

			$(this.blocktypes).each(function(i, type){
				if ( type !== that.type ) {
					$('<li class="ob-insert-'+type+'">'+( switchButtonsMap[type] || 'insert '+type )+'</li>').appendTo(that.buttons);	
				}				
			});
		},

		switchTo: function( newType ) {
			var that = this;
			this.destroy();

			var el = this.element.get(0);

			var newOutletType = (newType == 'image') ? 'outletImg' : 'outletHTML';

			$(el)[newOutletType]({ doc: this.doc, typeSwitch: outletTypeSwitched });
			that._trigger('typeSwitch', {}, { type: newType, gsedit: $(el).attr('gsedit') });
			$(el)[newOutletType]('edit');

		},

		_bindMoves: function() {
			var that = this;
			if ( this._moveHandler ) { return; }

			this._moveHandler = function(e) {
				var parentOffset = that.element.offset();
				//or $(this).offset(); if you really just want the current element's offset
				var relX = e.pageX - parentOffset.left,
					relY = e.pageY - parentOffset.top;

				var w = that.element[0].offsetWidth,
					h = that.element[0].offsetHeight,
					w2 = Math.round(w/2),
					h2 = Math.round(h/2),
					posCode;

				/*
					the element rectangle is divided into quarters numbered from 1 from left top corner
					posCode is the number of active quarter
				*/

				if ( relX <= w2 && relY <= h2 ) {
					posCode = 'Q1';
				} else if ( relX > w2 && relY <= h2 ) {
					posCode = 'Q2';
				} else if ( relX > w2 && relY > h2 ) {
					posCode = 'Q3';
				} else if ( relX <= w2 && relY > h2 ) {
					posCode = 'Q4';
				}

				if ( posCode != that._buttonsPosCode ) {
					that._buttonsPosCode = posCode;
					that._setButtonsPosition();
				}
			};

			this.element.bind('mousemove', this._moveHandler);
		},
		_unbindMoves: function() {
			if ( this._moveHandler ) {
				this.element.unbind('mousemove', this._moveHandler);	
			}			
			this._moveHandler = null;
		},

		_enter: function() {
			this.show();
			this._bindMoves();

		},
		_leave: function(e) {
			var ino = this.inOutlet(e.relatedTarget);

			if ( !ino ) {
				this.hide();
				this._unbindMoves();
			}
		},
		inOutlet: function(el) {
			var current = el;
			while ( current ) {
				if ( current === this.buttons[0]) {
					return true;
				}
				current = current.parentNode;				
			}
			return false;
		},
		inTree: function(el){
			var current = el;
			while ( current ) {
				if ( current === this.doc.body ) {
					return true;
				}
				current = current.parentNode;
			}
			return false;
		},
		_setButtonsPosition: function() {
			var pos = $.extend({}, this.element.offset(), {
				width: this.element[0].offsetWidth,
				height: this.element[0].offsetHeight
			});

			var buttonsWidth = this.buttons[0].offsetWidth,
				buttonsHeight = this.buttons[0].offsetHeight;

			var q = this._buttonsPosCode || 'Q2',
				oPos;

			switch ( q ) {
				case 'Q1':
					oPos = { top: pos.top, left: pos.left };
					break;
				case 'Q2':
					oPos = { top: pos.top, left: pos.left + pos.width - buttonsWidth };
					break;
				case 'Q3':
					oPos = { top: pos.top + pos.height - buttonsHeight, left: pos.left + pos.width - buttonsWidth };
					break;
				case 'Q4':
					oPos = { top: pos.top + pos.height - buttonsHeight, left: pos.left };
					break;
			}
			if ( oPos.left < 0 ) { oPos.left = 0; }

			this.buttons.css(oPos);
		},
		show: function() {
			var that = this;
			if ( !this.inTree(this.buttons) ) {
				this.buttons.appendTo(this.doc.body);

				this.buttons.bind('mouseleave', function(e){ that._leave(e); });

				this._setButtonsPosition();

				this.buttons.show();
				this._bind();
			}
		},
		hide: function() {
			this.buttons.remove();
		},
		destroy: function() {

			this.unbind();

			$('li', this.buttons).unbind('click');

			this.buttons.remove();

			$.Widget.prototype.destroy.call(this);
		}
	});

	/**
	 * Editable html block widget. Creates the content edit buttons in HTML template.
	 */
	$.widget('glock.outletImg', $.glock.outlet, {
		_create: function() {
			if ( this.element.get(0).nodeName !== 'IMG' ) {
				var attrs = this.getAttrs(),
					newEl = $('<img />');

				for ( var p in attrs ) {
					newEl.attr(p, attrs[p]);
				}

				this.element.replaceWith(newEl);
				this.element = newEl;
			}

			var src = this.element.attr('src');
			if ( !src || !src.match(/^\w+:\/\/[^\'\"]+/) ) { // simple url match
				var ph = this.element.attr('placehold') || '100x100.gif';
				this.element.attr('src', ph);
			}
			$.glock.outlet.prototype._create.apply(this, arguments);
		},
		selectImage: function() {
			this.md.show(this);
		},
		edit: function() {
			this.selectImage();
		},
		_getButtons: function(){
			$.glock.outlet.prototype._getButtons.apply(this, arguments);

			$('<li class="ob-image">select image</li>').appendTo(this.buttons);

			if ( this.element.attr('gsdefault') ) {
				$('<li class="ob-default-image">default image</li>').appendTo(this.buttons);	
			}

			if ( this.element.attr('gssource') ) {
				$('<li class="ob-download-source">download source</li>').appendTo(this.buttons);	
			}		
			
		},
		_bind: function() {
			$.glock.outlet.prototype._bind.apply(this, arguments);
			var that = this;
			$('.ob-image', this.buttons).click(function(e){
				e.preventDefault();
				that.md.show(that);
			});
			$('.ob-default-image', this.buttons).click(function(e){
				e.preventDefault();
				that.setDefaultImage();
			});
			$('.ob-download-source', this.buttons).click(function(e){
				e.preventDefault();
				that.downloadSourceImage();
			});
		},
		clear: function() {
			var newURL = this.element.attr('placehold');
			this.element.attr('src', newURL);
			saveNewContent(newURL, this);
		},
		setDefaultImage: function() {
			var newURL = this.element.attr('gsdefault');
			this.element.attr('src', newURL);
			saveNewContent(newURL, this);
		},
		downloadSourceImage: function() {
			var url = this.element.attr('gssource');
			window.location = url;
		}
	});

	/**
	 * Editable html block widget. Creates the content edit buttons in HTML template.
	 */
	$.widget('glock.outletHTML', $.glock.outlet, {
		options: {
			// showMeta: true,				
			// buttons: ['']
		},
		_create: function(){
			if ( this.element.get(0).nodeName === 'IMG' ) {
				var attrs = this.getAttrs(),
					newEl = $('<div></div>');

				for ( var p in attrs ) {
					if ( p !== 'src' ) {
						newEl.attr(p, attrs[p]);
					}
				}

				this.element.replaceWith(newEl);
				this.element = newEl;
			}			
			$.glock.outlet.prototype._create.apply(this, arguments);
		},
		getContent: function() {			
			return (this.element && this.element.innerHTML) || null;
		},
		setContent: function(newContent) {
			$(this.element).html(newContent);
		},

		edit: function(){
			var that = this,
				html = this.element.html();

			$('#dialog').editorDialog({
				edSelector: '.source-editor',
				save: function(ev, data) {
					that.setContent(data.html);
					saveNewContent(data.html, that);
					$('#dialog').editorDialog('close');
				}
			}).editorDialog('setData', this.element.html())
			  .editorDialog('open');
		},
		_getButtons: function(){
			$.glock.outlet.prototype._getButtons.apply(this, arguments);
			$('<li class="ob-edit">edit</li>').appendTo(this.buttons);
		},
		_bind: function() {
			$.glock.outlet.prototype._bind.apply(this, arguments);

			var that = this;
			// opening editor dialog and filling with outlet content
			$('.ob-edit', this.buttons).click(function(){
				that.edit();
			});
		},
		clear: function() {
			this.setContent('');
			saveNewContent('', this);
		}
	});

	function initOutlets(document){
		$('[gsedit]', document).each(function(i, el){			
			var params = { doc: document, typeSwitch: outletTypeSwitched },
				oType = ( el.nodeName === 'IMG' ) ? 'outletImg' : 'outletHTML';

			$(el)[oType](params);
		});
	}

	function initMediaDialog(doc, outlet) {
		var that = {},
			hook = false,
			target_el,
			placeHolder,
			wrapper;

		that.inject = function(){
			var old_send_to_editor = send_to_editor;

			send_to_editor = function(html) {
				// returning back the send_to_editor callback
				send_to_editor = old_send_to_editor;

				var src = html.match(/src=(['"])(.*?)\1/i);
				src = (src && src[2]) ? src[2] : '';

				var href = html.match(/href=(['"])(.*?)\1/i);
				href = (href && href[1]) ? href[1] : '';

				var url = src ? src : href;

				if ( hook ) {
					if ( target_el ) {
						$(target_el).attr('src', url).show();
						saveNewContent(url, outlet);
					}
					hook = false;
					tb_remove();
				} else {
					return old_send_to_editor.apply(this, arguments);	
				}			
			};

			var old_tb_remove = tb_remove;

			tb_remove = function() {
				hook = false;

				tb_remove = old_tb_remove;
				old_tb_remove.apply(this, arguments);
			};
		};

		that.show = function() {
			if ( hook ) {
				return;
			}
			if ( typeof send_to_editor === 'undefined' ) {
				throw new Error('send_to_editor callback not found.');
			}
			this.inject();
			target_el = $(outlet.element).get(0);
			hook = true;
			tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
		};

		return that;
	}


	/**
	 *
	 *       CSS EDITOR
	 *
	 */


	function makeStyleControl(o) {
		var that = {};

		var opts = that.opts = $.extend({
			inputClass: 'input-small', //color-selector
			/*
				selector: '#contentBody, body',
				name: 'background-color',
				value: '#71dae4'
			*/
		}, o);

		function getLabel(){
			var n = opts.name.replace(/\W+/, ' ');
			n = n.split(' ');
			$(n).each(function(i, word){
				n[i] = word[0].toUpperCase()+(word.substr(1)).toLowerCase();
			});
			return n.join(' ');
		}

		that.getInputElements = function() {
			return '<input type="text" value="'+opts.value+'" class="'+opts.inputClass+'">';
		};


		that.render = function(containerEl){					
			var html = [
				'<div class="style-control">',
					'<label>'+getLabel()+'</label>',
					that.getInputElements(),
				'</div>'
			].join('');

			that.el = $(html);

			if ( containerEl ) {
				containerEl = $(containerEl);
				that.el.appendTo(containerEl);
			}
			return that;
		};

		that.elDefined = function(){
			if ( !that.el ) {
				console.error('Style controller is not yet rendered.');
			}
			return !!that.el;
		};

		that.init = function(){
			if ( that.elDefined() ) {
				// init here
				if ( o.onChange ) {
					$('input', that.el).change(function(){
						var newValue = $(this).val();
						newValue = parseInt(newValue, 10);
						if ( typeof newValue === 'number' ) {
							if ( newValue != 0 ) {
								newValue += 'px';
							}
						} else {
							newValue = '0';
						}
						o.onChange(o.selector, o.name, newValue);
					});
				}						
			}
			return that;
		};

		that.val = function() {
			return that.elDefined() ? $(that.el).val() : null;
		}

		return that;
	}

	function makeColorStyleControl(o) {
		o.inputClass = 'color-selector';
		var that = makeStyleControl(o);

		that.init = function() {

			if ( that.elDefined() ) {
				$('.color-selector', that.el).miniColors({
					'change': function(newValue) {
						if ( o.onChange ) {
							o.onChange(o.selector, o.name, newValue);	
						}
					}
				});
			}
			return that;
		}

		return that;
	}

	function makeBorderStyleControl(o) {
		var that = makeStyleControl(o);

		that.getInputElements = function(){
			var values = that.opts.value.split(/\s+/);
			var width, style, color;

			if ( that.opts.value == '0' ) {
				width = 0;
				style = 'none';
				color = '';
			} else {
				$(values).each(function(i, v){
					if ( v.match(/\d+px/i) ) {
						width = parseInt(v, 10) || 0;
					} else if ( v.match(/none|dotted|dashed|solid|double|groove|ridge|inset|outset/i) ) {
						style = v;
					} else {
						color = v;
					}
				});				
			}

			function getOptions() {
				var html = [], options = ['none', 'dotted', 'dashed', 'solid','double', 'groove','ridge','inset','outset'];
				$(options).each(function(i, o){
					var selected = ( o === style ) ? ' selected="selected"' : '';
					html.push('<option'+selected+'>'+o+'</option>');
				});
				return html.join();
			}

			var html = [
				'<input type="text" value="'+width+'" class="input-micro"> px ',
				'<select class="input-small">',
					getOptions(),
				'</select>',
				' <input type="text" value="'+color+'" class="color-selector">'
			];
			return html.join('');
		};

		that.init = function(){
			if ( that.elDefined() ) {

				var onChange = function() {
					if ( o.onChange ) {
						o.onChange(o.selector, o.name, that.val());
					}							
				};

				$('.color-selector', that.el).miniColors({
					change: onChange
				});

				$('.input-micro, select', that.el).change(onChange);

			}
			return that;
		};

		that.val = function() {
			var width, style, color;
			width = $('.input-micro', that.el).val();
			style = $('select', that.el).val();
			color = $('.color-selector', that.el).val();

			return width+'px '+style+' '+color;
		};

		return that;				
	}

	function makeSelectStyleControl(o) {
		var that = makeStyleControl(o);

		that.getInputElements = function(){
			var options = that.opts.options;

			function getOptions() {
				var selected, html = [];

				$(options).each(function(i, o){
					if ( typeof o == 'string' ) {
						selected = ( o === that.opts.value ) ? ' selected="selected"' : '';
						html.push('<option'+selected+'>'+o+'</option>');
					} else {
						selected = ( o.value === that.opts.value ) ? ' selected="selected"' : '';
						html.push('<option'+selected+' value="'+o.value+'">'+o.name+'</option>');
					}

				});
				return html.join();
			}

			var html = [
				'<select class="input-small">',
					getOptions(),
				'</select>'
			];
			return html.join('');
		};

		that.init = function() {
			if ( that.elDefined() && o.onChange ) {
				$('select', that.el).change(function(){
					o.onChange(o.selector, o.name, that.val());
				});
			}
		};

		that.val = function(){
			return $('select', that.el).val();
		};

		return that;
	}

	function styleControlFromType(args) {
		var map = {
			"background-color": makeColorStyleControl,

			"border": makeBorderStyleControl,
			"border-top": makeBorderStyleControl,
			"border-right": makeBorderStyleControl,
			"border-bottom": makeBorderStyleControl,
			"border-left": makeBorderStyleControl,
			
			"color": makeColorStyleControl,
			"font-family": function(args){

				args.options = [
					'Helvetica Neue, Arial, Helvetica, Geneva, sans-serif',
					'Lucida Grande, Lucida, Verdana, sans-serif',
					'Georgia, Times New Roman, Times, serif',
					'Courier New, Courier, mono'

					// { value:"sansserif", name: 'Helvetica Neue, Arial, Helvetica, Geneva, sans-serif' },
					// { value:"sansserif2", name: 'Lucida Grande, Lucida, Verdana, sans-serif' },
					// { value:"serif", name: 'Georgia, Times New Roman, Times, serif' },
					// { value:"mono", name:'Courier New, Courier, mono' }
				];

				return makeSelectStyleControl(args);
			},
			"font-size": function(args){
				//makeSelectStyleControl
				// 9px - 72px
				args.options = [
					'9px', '10px', '11px', '12px', '13px', '14px', 
					'16px',	'18px',
					'20px', '22px', '24px', '26px', '28px', 
					'30px',	'32px', '34px', '36px', '38px',
					'40px',	'42px', '44px', '46px', '48px',
					'50px',	'52px', '54px', '56px', '58px',
					'60px',	'62px', '64px', '66px', '68px',
					'70px',	'72px'
				];
				return makeSelectStyleControl(args);
			},
			"font-weight": function(args){
				args.options = [
					'normal',
					'bold'
				];
				return makeSelectStyleControl(args);
			},
			"line-height": function(args){
				args.options = [ '1', '1.3', '1.5', '2' ];
				return makeSelectStyleControl(args);
			},
			"font-style": function(args) {
				args.options = [ 'normal', 'italic', 'oblique' ];
				return makeSelectStyleControl(args);
			},
			"text-align": function(args){
				// makeSelectStyleControl
				// left, center, right, justify
				args.options = [ 'left', 'center', 'right', 'justify' ];
				return makeSelectStyleControl(args);
			},
			"text-decoration": function(args){
				args.options = [ 'none', 'underline', 'line-through' ];
				return makeSelectStyleControl(args);
			},				
			"padding": makeStyleControl,
			"vertical-align": function (args) {
				args.options = [
					'baseline (default)',
					'sub',
					'super',
					'top',
					'text-top',
					'middle',
					'bottom',
					'text-bottom'
				];
				return makeSelectStyleControl(args);
			}
		};

		if ( map[args.name] ) {
			return map[args.name](args);
		} else {
			return makeStyleControl(args);
		}

	}


	 // base control types
	 // ------------------
	 // color
	 // border
	 // select
	 // input


	function initStyle(doc) {

		var tabs = {
			// 'page': {
			// 	tip: 'sesdfsdfsdfsdf',
			// 	sections: {
			// 		'background color': {
			// 			controls: {

			// 			}
			// 		}
			// 	}
			// }
		};

		var debugControlTypes = {};

		function saveStyleChnage(selector, name, value) {

			var regexQuote = function(str) {
				return (str).replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}=!<>\|\:])/g, function(s, p1){
					return '\\'+p1;
				});
			};

			var rxStr = '('+regexQuote(selector)+'\\s*\\{[\\s\\S]*?\\/\\*\\@editable\\*\\/'+regexQuote(name)+':)\s*([^;]*)(;[\\s\\S]*?\\})';
			var s, style = $('style', doc).get(0);
			s = $(style).text();

			s = s.replace(new RegExp(rxStr), '$1'+value+'$3', 'ign');
			$(style).text(s);

		 	var data = {
				action: O.actEditStyle,
				id: NEWSMAN_ENTITY_ID,
				selector: selector,
				name: name,
				value: value
		 	};

		 	//*
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data
			}).done(function(data){
				var type = data.state ? 'success' : 'error';
				showMessage(data.msg, 'success');
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});	
			//*/			
		}			

		// creates tabs if not exists
		function ensureTabs(d) {
			d.tab = d.tab.replace(/\s+/g, '_');

			if ( !tabs[d.tab] ) {
				tabs[d.tab] = {
					sections: {}
				};
			}

			if ( d.tip ) {
				tabs[d.tab].tip = d.tip;
			}

			if ( !tabs[d.tab].sections[d.section] ) {
				tabs[d.tab].sections[d.section] = {
					controls: {}
				};
			}
		}

		// parses CSS rule meta in comments
		function parseDef(def) {
			var d = {}, res, rx = /@(\w+)\s+(.*)/g;

			while ( res = rx.exec(def) ) {
				d[res[1]] = res[2];
			}
			ensureTabs(d);
			return d;
		}

		function createControl(tabDef, styleName, styleValue) {
			var c = tabs[tabDef.tab].sections[tabDef.section].controls;
			c[styleName] = styleControlFromType({
				name: styleName,
				value: styleValue,
				selector: tabDef.selector,
				onChange: function(selector, name, value) {
					saveStyleChnage(selector, name, value);
				}
			});
		}

		// parses @editable style paramas
		function parseEditables(tabDef, content) {
			var res, rx = /\/\*@editable\*\/(.*?):\s*(.*?)\s*;/ig;
			while ( res = rx.exec(content) ) {
				createControl(tabDef, res[1], res[2]);
				debugControlTypes[res[1]] = '1';
			}				
		}

		function processEditableBlock(def, selector, content) {
			var d = parseDef(def);

			d.selector = selector;

			parseEditables(d, content);
		}

		function sanitizeName(name) {
			return name.replace(/\W+/, '-').replace(/\s+/, '-').toLowerCase();
		}

		function prettyName(name) {
			return name.replace(/\_+/, ' ');
		}

		var gid = 0,
			tabMap = {};
		function getID(name) {
			gid += 1;
			tabMap[name] = 'tab-'+gid;
			return 'tab-'+gid;
		}

		function createSubTabs(tabName, sections) {

			var html = [ '<ul class="nav nav-tabs nav-tabs-sl" id="'+sanitizeName(tabName)+'-subtabs">' ],
				id,	panels = [];

			var active = 'active';
				panels.push('<div class="tab-content tpl-controls">')

			for ( var name in sections ) {
				//id = sanitizeName(tabName+'-'+name);
				id = getID(tabName+'-'+name)
				html.push('<li class="'+active+'"><a href="#'+id+'" data-toggle="tab">'+prettyName(name)+'</a></li>');
				panels.push('<div class="tab-pane '+active+'" id="'+id+'"></div>');
				active = '';
				
			}

			panels.push('</div>');
			html.push('</ul>');
			html.push(panels.join(''));			
			
			//$('<div class="tab-pane '+tabPaneActive+'" id="'+tabName+'"></div>').appendTo(subTabs);
			return html.join('');

		}

		function createTabs() {
			var ul = $('#style-tabs').empty();
			var id,
				active = 'class="active"',
				tabPaneActive = 'active',
				subTabs = $('#sub-tabs').empty();

			for ( var tabName in tabs ) {
				id = getID(tabName);
				$('<li '+active+'><a href="#'+id+'" data-toggle="tab">'+prettyName(tabName)+'</a></li>').appendTo(ul);
				active = '';

				$('<div class="tab-pane '+tabPaneActive+'" id="'+id+'">'+createSubTabs(tabName, tabs[tabName].sections)+'</div>').appendTo(subTabs);
				tabPaneActive = '';
			}
		}

		function initTabs() {
			var tab, tabName,
				section, sectionName,
				panelId, controlName;
			for ( tabName in tabs ) {
				tab = tabs[tabName];
				for ( sectionName in tab.sections ) {
					section = tab.sections[sectionName];

					for ( controlName in section.controls ) {
						panelId = tabMap[tabName+'-'+sectionName];						

						section.controls[controlName].render('#'+panelId).init();

					}
				}
			}
		}

		$('style', doc).each(function(i, element){
			var el = $(element);

			if ( !el.attr('href') ) {
				var res, style = el.text();
				var rx = /\/\*\s*(@tab[\s\S]*?)\*\/\s*([\s\S]*?){([\s\S]*?)}/ig;
				while ( res = rx.exec(style) ) {
					processEditableBlock(res[1], res[2], res[3]);
				}
				createTabs();
				initTabs();
			}
			
		});

		//@tab Page
	}

	var frm = $('#tpl-frame').get(0);
	if ( frm ) {
		frm.newsmanInit = init;
	}
	
	$('#tpl-frame').load(init);
});