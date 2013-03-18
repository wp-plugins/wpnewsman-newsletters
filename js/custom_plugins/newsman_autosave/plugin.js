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

			editor.on('instanceReady', function(){
				label = editor.ui.get('newsmanSavingState');				

				// keyup handler
				var t = null;
				this.document.on("keyup", function(e){
					if ( t ) {
						clearTimeout(t);
					}
					if ( !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey ) {
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
					label.set('Saved at '+(new Date()).toLocaleTimeString());
				}				
			});
		}
	});

})();
