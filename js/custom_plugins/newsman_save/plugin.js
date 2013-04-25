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
		icons: 'newsmanSave',
		init : function( editor ) {
			var command = editor.addCommand( pluginName, saveCmd );
			command.modes = { wysiwyg : !editor.element.$.className.match('nsmn-type-simple') };

			if ( !editor.element.$.className.match('nsmn-type-simple') ) {

				editor.ui.addButton( 'newsmanSave', {
					label : (typeof newsmanL10n !== 'undefined' && newsmanL10n.save) || 'Save',
					command : pluginName,
					toolbar: 'newsmanSave'
					//className: 'cke_button_save'
				});

				CKEDITOR.skin.icons.newsmansave = {
					path: CKEDITOR.getUrl( 'plugins/icons.png' ),
					offset: -1344
				};

			}
		}
	});		


})();
