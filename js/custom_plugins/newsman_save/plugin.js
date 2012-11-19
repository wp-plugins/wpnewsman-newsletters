/**
 * @fileSave plugin.
 */

(function() {
	var saveCmd = {
		modes : { wysiwyg:1, source:1 },
		readOnly : 1,

		exec : function( editor ) {
			editor.fire('newsmanSave.ckeditor');
		}
	};

	var pluginName = 'newsman_save';

	// Register a plugin named "save".
	CKEDITOR.plugins.add( pluginName, {
		init : function( editor ) {
			var command = editor.addCommand( pluginName, saveCmd );
			command.modes = { wysiwyg : !!( editor.element.$.form ) };

			editor.ui.addButton( 'newsmanSave', {
				label : editor.lang.save,
				command : pluginName,
				className: 'cke_button_save'
			});
		}
	});
})();
