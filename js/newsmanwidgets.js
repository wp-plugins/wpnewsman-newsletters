jQuery(function($){

	$.widget('glock.editorDialog', {
		options: {			
			edSelector: null, // editor selector
			particleName: ''
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

			// $(this.options.edSelector).bind('newsmanSave.ckeditor', function(){
			// 	alert('newsmanSave.ckeditor fired!!');
			// });

			var closeBtn = $('.newsman-editor-dlg-close', this.element);

			closeBtn.click(function(){
				that.close();
			});

			this.element.css({ position: 'absolute' });

			var editor = CKEDITOR.replace( this.options.edSelector, {
				width: 850,
				height: 400,
				fullPage: false,
				extraPlugins: 'ui_newsman_label,newsman_autosave,iframedialog,newsman_add_wp_media,newsman_save,newsmanshortcodes',
				customConfig: NEWSMAN_PLUGIN_URL+'/js/custom_config.js'
			});			

			window.ed = editor;

			editor.on('newsmanSave.ckeditor', function(){
				that._trigger('save', 0, { html: editor.getData(), particleName: that.options.particleName } );	
				editor.fire('afterNewsmanSave.ckeditor');
			});		

			editor.on('instanceReady', function(){
				that.edReady();
			});

			this.ed = editor;

			editor.on('key', function(e){
				if ( e.data.keyCode === 27) {
					that.close();
					e.cancel();
				}				
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
			this.ed.setData(data);
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

});