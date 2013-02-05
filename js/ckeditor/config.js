/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

var ckeditorBasePath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.indexOf("ckeditor/"));
var customPluginsRoot = ckeditorBasePath+ 'custom_plugins/';

//CKEDITOR.plugins.addExternal('newsman_insert_posts', customPluginsRoot+'newsman_insert_posts/plugin.js', '');	
CKEDITOR.plugins.addExternal('newsman_save', customPluginsRoot+'newsman_save/plugin.js', '');	
CKEDITOR.plugins.addExternal('newsmanshortcodes', customPluginsRoot+'newsman_shortcodes/plugin.js', '');	

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// config.width = 'auto';

	// config.resize_minWidth = 800;
	// config.resize_minHeight = 600;	

	config.entities = false;
	config.entities_latin = false;

	config.resize_enabled = true;

	// config.extraPlugins = 'newsman_insert_posts,newsman_save';	
	config.extraPlugins = 'newsman_save,newsmanshortcodes';	

	//config.toolbar = 'NEWSMAN';

	config.enterMode = CKEDITOR.ENTER_BR;
	config.shiftEnterMode = CKEDITOR.ENTER_P;	

	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups = [
		{ name: 'newsmanBar' },
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		{ name: 'about' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', /*'indent',*/ 'blocks', 'align' ] },
		{ name: 'styles' },
		{ name: 'colors' }		
	];

	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	config.removeButtons = 'Underline,Subscript,Superscript,Save';
};
