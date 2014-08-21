/*
 * @example An iframe-based dialog with frame window fit dialog size.
 */

( function() {

	var initOutlets;

	var hookStateKey = ( window.navigator.platform.indexOf('Mac') > -1 ) ? 'metaKey' : 'ctrlKey';

	window.globalKeyHandler =
	globalKeyHandler = (function(){

		var callbacks = [];
		var bindedDocs = [];

		function getFunkKeys(e) {
			if ( e.originalEvent ) {
				e = e.originalEvent;
			} else if ( e.data && e.data.$ ) {
				e = e.data.$;
			}
			return {
				// 93 code is actually a right context menu key on PC keyboards, but it's a right command on a mac
				metaKey: 	e.keyCode === 91 || e.keyCode === 92 || e.keyCode === 93 || e.metaKey,
				shiftKey: 	e.keyCode === 16 || e.shiftKey,
				ctrlKey: 	e.keyCode === 17 || e.ctrlKey,
				altKey: 	e.keyCode === 18 || e.altKey
			};
		}			

		function keydownHandler(e) {
			var keys = getFunkKeys(e);
			if ( keys[hookStateKey] ) {
				for (var i = 0; i < callbacks.length; i++) {
					callbacks[i](true, e);
				}
			}
		}

		function keyupHandler(e) {
			var keys = getFunkKeys(e);
			if ( keys[hookStateKey] ) {
				for (var i = 0; i < callbacks.length; i++) {
					callbacks[i](false, e);
				}
			}
		}

		return {
			on: function(cb) {
				var idx = jQuery.inArray(cb, callbacks);
				if ( idx === -1 ) {
					callbacks.push(cb);
				}
			},
			off: function(cb) {
				var idx = jQuery.inArray(cb, callbacks);
				if ( idx > -1 ) {
					callbacks.splice(idx, 1);
				}
			},
			registerDoc: function(doc) {
				// var idx = $.inArray(doc, bindedDocs);
				// if ( idx === -1 ) {
					jQuery(doc).keydown(keydownHandler);
					jQuery(doc).keyup(keyupHandler);
					bindedDocs.push(doc);
					return true;
				// }
				// return false;
			},
			isDocRegistered: function(doc) {
				return jQuery.inArray(doc, bindedDocs) > -1;
			}
		};
	}());

	jQuery(function($){

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
				this.buttons = $('<ul class="outlet-buttons" contenteditable="false" style="width: 60px; position: absolute; top: 0; left: 0;"></ul>');

				this.isImage = $(el).is('img');

				this.type = $(el).is('img') ? 'image' : 'html';

				var gsblock = $(el).attr('gsblock');
					this.blocktypes = gsblock ? gsblock.split(',') : [];

				this._getButtons();
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
				$('<li contenteditable="false" class="ob-remove">&times; remove</li>').appendTo(this.buttons);

				$(this.blocktypes).each(function(i, type){
					if ( type !== that.type ) {
						$('<li contenteditable="false" class="ob-insert-'+type+'">'+( switchButtonsMap[type] || 'insert '+type )+'</li>').appendTo(that.buttons);	
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

					var relX = e.pageX - parentOffset.left,
						relY = e.pageY - parentOffset.top;

					var w = that.element[0].offsetWidth,
						h = that.element[0].offsetHeight,
						w2 = Math.round(w/2),
						h2 = Math.round(h/2),
						posCode;

					/*
						the element rectangle is divided into quarters numbered from 1 from left top corner.
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

				this.element.on('mousemove', this._moveHandler);

				// also binding the doc keydown handler here
				$(this.doc).on('keydown', this._keydown);
			},
			_unbindMoves: function() {
				if ( this._moveHandler ) {
					this.element.off('mousemove', this._moveHandler);	
				}			
				this._moveHandler = null;
				// also binding the doc keydown handler here
				$(this.doc).off('keydown', this._keydown);				
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

			},
			_unbindShiftHander: function() {
				if ( this._shiftHandler ) {
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

					if ( !this._moveHandler ) {
						this._bindMoves();	
					}					

					this._bind();
					this._setButtonsPosition();

					this.buttons.show();
				}
			},
			hide: function() {
				if ( this.buttonsVisible ) {
					this.buttons.remove();
					//this.buttons.remove();
					//this._unbindMoves();
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

			function insertCSS() {
				var editable = editor.editable();

				if ( editable && editable.$ ) {

					var body = editable.$,
						head = $(body).closest('html').find('head');

					if ( !$('#newsman-tpleditor-css', head)[0] ) {
						$('<link id="newsman-tpleditor-css" rel="stylesheet" href="'+NEWSMAN_PLUGIN_URL+'/css/tpleditor.css?'+NEWSMAN_VERSION+'" />').appendTo(head);
					}
				}				
			}

			editor.on('initOutlets.ckeditor', function(){

				insertCSS();

				var doc = CKEDITOR.instances[editor.name].document;
				if ( globalKeyHandler.registerDoc(doc.$) ) {
				}	

				var editable = editor.editable();

				if ( editable && editable.$ ) {

					var body = editable.$;

					$('[gsedit]', body).each(function(i, el){
						var $el = $(el);
						if ( !$el.data('glockOutletHTML') && el.nodeName !== 'IMG' ) {
							$el.outletHTML({ doc: body, editor: editor });
						}
					});
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
					if ( $el.data('glockOutletHTML') && el.nodeName !== 'IMG' ) {
						$el.outletHTML('setDoc', doc.$);
						$el.outletHTML('hide');
					}
				});

			});

			editor.on('contentDom', function(){				
				editor.fire('initOutlets.ckeditor');
			});

			globalKeyHandler.on(function(down){
				var doc = CKEDITOR.instances[editor.name].document;

				if ( doc ) {
					var body = doc.$.body

					$(body)[ down ? 'addClass' : 'removeClass']('highlight-newsman-outlet-buttons');

					$('[gsedit]', body).each(function(i, el){
						var $el = $(el);

						if ( $el.data('glockOutletHTML') && el.nodeName !== 'IMG' ) {
							$el.outletHTML('setDoc', doc.$);
							$el.outletHTML( down ? 'show' : 'hide' );
						}
					});
				}
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
					editor.fire('initOutlets.ckeditor');
				}, null, null, 1 );

				// Remove the box before an undo image is created.
				// This is important. If we didn't do that, the *undo thing* would revert the box into an editor.
				// Thanks to that, undo doesn't even know about the existence of the box.
				editable.attachListener( editor, 'beforeUndoImage', function() {
					editor.fire('removeOutlets.ckeditor');
				});

				editable.attachListener( editor, 'afterUndoImage', function() {
					editor.fire('initOutlets.ckeditor');
				});				

				editor.fire('initOutlets.ckeditor');
        	});

			editor.commands.undo.on('afterUndo', function(){
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

			var idx = this.path.indexOf("plugin.js");
			var iconPath = ( idx > 0 ) ? this.path.substr(0, this.path.indexOf("plugin.js")) : this.path;

        	

	        var height = 480, 
	        	width = 750;

			var dialog = CKEDITOR.dialog.addIframe(
				'newsmanInsertPostsDlg',
				newsmanL10n.insertPosts,
				NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-settings&action=newsman-frame-get-posts',
				width, height,
				function(){
					// Iframe loaded callback.	

					// console.log('iframe loaded');

					// var dialog = this.getDialog();			
					// dialog.parts.dialog.$.style.top = '32px';

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

								var insertPostType = $('#newsman-post-type', frameDoc).val();
								var contentType = $('#newsman-content-type', frameDoc).val();

								$.ajax({
									type: 'POST',
									url: ajaxurl,
									data: {
										pids: ids+'',
										type: insertPostType,
										ctype: contentType,
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

			editor.addCommand( 'newsmanOpenInsertPostDlg', new CKEDITOR.dialogCommand( 'newsmanInsertPostsDlg' ) );

            editor.ui.addButton( 'newsman_btn_insert_posts',
            {
                label: newsmanL10n.insertPosts,
                command: 'newsmanOpenInsertPostDlg',
                icon: iconPath + 'wpmini-blue.png',
                toolbar: 'newsmanBar'
            } );

        }
    } );

} )();