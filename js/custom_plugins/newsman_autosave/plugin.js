/**
 * @license Copyright (c) 2013, Alex Ladyga, G-Lock Software. All rights reserved.
 * Released under MIT and GPL licenses
 */
(function() {

	CKEDITOR.plugins.add( 'newsman_autosave', {
		requires: ['ui_newsman_label'],
		init: function( editor ) {
			var label;

			editor.ui.addNewsmanLabel( 'newsmanSavingState', {
				label : '',
				toolbar: 'newsmanSavingState'
			});

			function getFunkKeys(e) {
				if ( e.originalEvent ) {
					e = e.originalEvent;
				} else if ( e.data && e.data.$ ) {
					e = e.data.$;
				}
				return {
					metaKey: 	e.keyCode === 91 || e.keyCode === 92 || e.metaKey,
					shiftKey: 	e.keyCode === 16 || e.shiftKey,
					ctrlKey: 	e.keyCode === 17 || e.ctrlKey,
					altKey: 	e.keyCode === 18 || e.altKey
				};
			}

			editor.on('instanceReady', function(){
				label = editor.ui.get('newsmanSavingState');				

				// keyup handler
				var t = null;
				this.document.on("keydown", function(e){
					if ( t ) { clearTimeout(t); }
				});
				this.document.on("keyup", function(e){
					if ( t ) {
						clearTimeout(t);
					}
					var ev = getFunkKeys(e);

					if ( !ev.metaKey && !ev.ctrlKey && !ev.shiftKey && !ev.altKey ) {
						t = setTimeout(function() {
							t = null;
							editor.fire('newsmanSave.ckeditor');
						}, 500);
					}
				});
			});			

			editor.on('blur', function(){
				editor.fire('newsmanSave.ckeditor');
			});

			// mode change handler
			editor.on('mode', function(){
				editor.fire('newsmanSave.ckeditor');
			});

			editor.on('changed', function(){
				editor.fire('newsmanSave.ckeditor');
			});					

			editor.on('newsmanSave.ckeditor', function(){
				if ( label ) {
					var lbl = (typeof newsmanL10n !== 'undefined' && newsmanL10n.savedAt) || 'Saved at'
					label.set(lbl+' '+(new Date()).toLocaleTimeString());
				}				
			});
		}
	});

})();
