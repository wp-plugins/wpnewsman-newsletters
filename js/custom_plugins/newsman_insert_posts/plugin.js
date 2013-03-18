/*
 * @example An iframe-based dialog with frame window fit dialog size.
 */

( function() {

	var initOutlets;

	function saveNewContent(editor) {
		editor.fire('newsmanSave.ckeditor');
	}

	var hookStateKey = ( window.navigator.platform.indexOf('Mac') > -1 ) ? 'metaKey' : 'ctrlKey';

	var globalKeyHandler;	

	jQuery(function($){

		window.globalKeyHandler =
		globalKeyHandler = (function(){

			var callbacks = [];
			var bindedDocs = [];

			function keydownHandler(e) {
				if ( e[hookStateKey] ) {
					for (var i = 0; i < callbacks.length; i++) {
						callbacks[i](true, e);
					}
				}
			}

			function keyupHandler(e) {
				if ( !e[hookStateKey] ) {
					for (var i = 0; i < callbacks.length; i++) {
						callbacks[i](false, e);
					}
				}
			}

			return {
				on: function(cb) {
					var idx = $.inArray(cb, callbacks);
					if ( idx === -1 ) {
						callbacks.push(cb);
					}
				},
				off: function(cb) {
					var idx = $.inArray(cb, callbacks);
					if ( idx > -1 ) {
						callbacks.splice(idx, 1);
					}
				},
				registerDoc: function(doc) {
					// var idx = $.inArray(doc, bindedDocs);
					// if ( idx === -1 ) {
						$(doc).keydown(keydownHandler);
						$(doc).keyup(keyupHandler);
						bindedDocs.push(doc);
						return true;
					// }
					// return false;
				},
				isDocRegistered: function(doc) {
					return $.inArray(doc, bindedDocs) > -1;
				}
			};
		}());

		globalKeyHandler.registerDoc(document);

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
				this.ckeditor = this.options.editor;

				this.gsedit = $(el).attr('gsedit');
				this.buttons = $('<ul class="outlet-buttons"></ul>');

				this.isImage = $(el).is('img');

				this.type = $(el).is('img') ? 'image' : 'html';

				var gsblock = $(el).attr('gsblock');
					this.blocktypes = gsblock ? gsblock.split(',') : [];

				this._getButtons();

				//this._bindMoves();

				//this._bindShiftHander();
			},

			_bind: function() {
				var that = this;

				$('.ob-remove', this.buttons).click(function(e){

					that.ckeditor.execCommand('newsmanRemoveBlock', that);
					e.preventDefault();
				});			
			},		

			remove: function() {
				this.destroy();
				$(this.element).remove();
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

				//$('<li class="ob-clear">clear</li>').appendTo(this.buttons);
				$('<li class="ob-remove">&times; remove</li>').appendTo(this.buttons);

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

				$(el)[newOutletType]({ doc: this.doc });
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

				// also binding the doc keydown handler here
				$(this.doc).bind('keydown', this._keydown);
			},
			_unbindMoves: function() {
				if ( this._moveHandler ) {
					this.element.unbind('mousemove', this._moveHandler);	
				}			
				this._moveHandler = null;
				// also binding the doc keydown handler here
				$(this.doc).unbind('keydown', this._keydown);				
			},

			/////// STATE KEY HANDLER FUNCTIONS

			_bindShiftHander: function() {
				var that = this;

				if ( this._shiftHandler ) { return; }

				this._shiftHandler = function(shifted, e) {
					if ( shifted ) {
						that.show(e);	
					} else {
						that.hide(e);
					}					
				};

				//globalKeyHandler.on(this._shiftHandler);
			},
			_unbindShiftHander: function() {
				if ( this._shiftHandler ) {
					//globalKeyHandler.off(this._shiftHandler);
					this._shiftHandler = null;
				}
			},

			///////

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
			show: function(e){
				var that = this;	

				if ( !this.buttonsVisible && !this.inTree(this.buttons) ) {
					this.buttonsVisible = true;
					this.buttons.appendTo(this.doc.body);

					this._setButtonsPosition();

					this.buttons.show();
					this._bind();
					this._bindMoves();
				}
			},
			hide: function() {
				if ( this.buttonsVisible ) {
					this.buttons.remove();
					this.buttonsVisible = false;					
				}
			},
			setDoc: function(doc) {
				this.doc = doc;
			},
			destroy: function() {

				this.hide();
				this._unbindMoves();
				this._unbindShiftHander();

				$('li', this.buttons).unbind('click');

				this.buttons.remove();
				return $.Widget.prototype.destroy.call(this);
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
			}
		});

	});

	// CKEDITOR PLUGIN * * * * * * * * * * * * * * * * * * * * * * * * 

    CKEDITOR.plugins.add( 'newsman_insert_posts',
    {
        requires: [ 'iframedialog' ],
        init: function( editor )
        {

        	var cfg = CKEDITOR.config;

        	var $ = jQuery;

			editor.on('initOutlets.ckeditor', function(){

				var doc = CKEDITOR.instances[editor.name].document;
				if ( globalKeyHandler.registerDoc(doc.$) ) {
				}	

				var editable = editor.editable();

				if ( editable && editable.$ ) {

					var body = editable.$,
						head = $(body).closest('html').find('head');

					if ( !$('#newsman-tpleditor-css', body)[0] ) {
						$('<link id="newsman-tpleditor-css" rel="stylesheet" href="'+NEWSMAN_PLUGIN_URL+'/css/tpleditor.css" />').appendTo(head);
					}
					
					$('[gsedit]', body).each(function(i, el){
						var $el = $(el);
						if ( !$el.data('outletHTML') && el.nodeName !== 'IMG' ) {
							$el.outletHTML({ doc: body, editor: editor });
						}
					});	
					//initRemoveOutlets(doc);
				}
			});   

			editor.on('removeOutlets.ckeditor', function(){


				var doc = CKEDITOR.instances[editor.name].document;
				var body = editor.editable().$;

				if ( doc && doc.$ ) {
					var head = doc.$.getElementsByTagName('head')[0];
					if ( head ) {
						$('#newsman-tpleditor-css', head).remove();
					}
				}

				$(body).removeClass('highlight-newsman-outlet-buttons');

				$('[gsedit]', body).each(function(i, el){
					var $el = $(el);
					if ( !$el.data('outletHTML') ) {
						$el.outletHTML({ doc: body, editor: editor });
					} else {
						if ( el.nodeName !== 'IMG' ) {
							$el.outletHTML('setDoc', doc.$);
							$el.outletHTML('hide');
						}
					}
				});

				// var editable = editor.editable();

				// if ( editable && editable.$ ) {
				// 	console.log(' ::: removing outlets');
				// 	$('[gsedit]', editable.$).each(function(i, el){
				// 		var $el = $(el);
				// 		if ( $el.data('outletHTML') && el.nodeName !== 'IMG' ) {
				// 			$el.outletHTML('destroy');
				// 		}
				// 	});
				// }
			});

			editor.on('contentDom', function(){
				editor.fire('initOutlets.ckeditor');
				// var doc = CKEDITOR.instances[editor.name].document;
				// if ( globalKeyHandler.registerDoc(doc.$) ) {
				// 	console.log('... new doc registered');
				// } else {
				// 	console.log('doc is already registered');
				// }
			});

			globalKeyHandler.on(function(down){
				var doc = CKEDITOR.instances[editor.name].document;

				if ( doc ) {
					var body = doc.$.body

					$(body)[ down ? 'addClass' : 'removeClass']('highlight-newsman-outlet-buttons');

					$('[gsedit]', body).each(function(i, el){
						var $el = $(el);
						if ( el.nodeName !== 'IMG' ) {
							$el.outletHTML('setDoc', doc.$);
							$el.outletHTML( down ? 'show' : 'hide' );
						}
					});
				}
			});

			// to make sure the highlighting class is removed if the editor loses focus
			editor.on('blur', function(){
				editor.fire('removeOutlets.ckeditor');
			});			

        	editor.on('instanceReady', function(){
				var editable = editor.editable();

				// Removes the box HTML from editor data string if getData is called.
				// Thanks to that, an editor never yields data polluted by the box.
				// Listen with very high priority, so line will be removed before other
				// listeners will see it.
				editable.attachListener( editor, 'beforeGetData', function() {
					editor.fire('removeOutlets.ckeditor');
				}, null, null, 1 );

				editable.attachListener( editor, 'afterNewsmanSave.ckeditor', function(){
					//console.warn('>>>>>> afterNewsmanSave.ckeditor');
					//editor.fire('initOutlets.ckeditor');
				}, null, null, 1 )

				// Remove the box before an undo image is created.
				// This is important. If we didn't do that, the *undo thing* would revert the box into an editor.
				// Thanks to that, undo doesn't even know about the existence of the box.
				editable.attachListener( editor, 'beforeUndoImage', function() {
					editor.fire('removeOutlets.ckeditor');
				});

				editor.fire('initOutlets.ckeditor');
        	});

			editor.addCommand('newsmanRemoveBlock', {
				exec: function(editor, outlet) {
					if ( outlet ) {
						outlet.remove();
					}
					editor.fire('initOutlets.ckeditor');
				}
			});


        	var iconPath = this.path.substr(0, this.path.indexOf("plugin.js"));

	        var height = 480, 
	        	width = 750;

			var dialog = CKEDITOR.dialog.addIframe(
				'newsmanInsertPostsDlg',
				'Insert Posts',
				NEWSMAN_PLUGIN_URL+'/frmGetPosts.php', width, height,
				function(){
					// Iframe loaded callback.	
				},
				{
					onOk: function() {
						var that = this;

						jQuery(function($){

							var dialog = that.parts.dialog.$,
								iframe = $('iframe', dialog).get(0),
								frameDoc, ids = [];

							if ( iframe ) {
								frameDoc = iframe.contentDocument;
								$('.newsman-bcst-post.active input', frameDoc).each(function(i, el){
									ids.push(parseInt($(el).val(), 10));
								});

								$.ajax({
									type: 'POST',
									url: ajaxurl,
									data: {
										pids: ids+'',
										entType: NEWSMAN_ENT_TYPE,
										entity: NEWSMAN_ENTITY_ID,
										action: 'newsmanAjCompilePostsBlock',
										showTmbPlaceholder: $('#newsman-show-thmb-placeholder', frameDoc).is(':checked') ? 1 : 0
									}						
								}).done(function(data){
									editor.fire('removeOutlets.ckeditor');

									var body = editor.editable().$;

									var posts_container = $('#posts_container', body);
									if ( posts_container[0] ) {
										posts_container.html(data.content);
									} else {
										editor.insertHtml(data.content);
									}
									
									editor.fire('initOutlets.ckeditor');
									editor.fire('newsmanSave.ckeditor');

								}).fail(NEWSMAN.ajaxFailHandler).always(function(){
									//hideLoading();
								});
								//ajCompilePostsBlock
							}
						});
					}

				}
			);

			// dialog.on('ok', function(){

			// });

			editor.addCommand( 'newsmanOpenInsertPostDlg', new CKEDITOR.dialogCommand( 'newsmanInsertPostsDlg' ) );

            editor.ui.addButton( 'newsman_btn_insert_posts',
            {
                label: 'Insert posts',
                command: 'newsmanOpenInsertPostDlg',
                icon: iconPath + 'wpmini-blue.png',
                toolbar: 'newsmanBar'
            } );

        }
    } );

} )();