/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */
 
//*
var ckeditorBasePath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.indexOf("ckeditor/"));
var customPluginsRoot = ckeditorBasePath+ 'custom_plugins/';

CKEDITOR.plugins.addExternal('iframedialog', customPluginsRoot+'iframedialog/plugin.js', '');
CKEDITOR.plugins.addExternal('newsman_insert_posts', customPluginsRoot+'newsman_insert_posts/plugin.js', '');
CKEDITOR.plugins.addExternal('newsman_add_wp_media', customPluginsRoot+'newsman_add_wp_media/plugin.js', '');
CKEDITOR.plugins.addExternal('newsman_save', customPluginsRoot+'newsman_save/plugin.js', '');	
CKEDITOR.plugins.addExternal('newsmanshortcodes', customPluginsRoot+'newsman_shortcodes/plugin.js', '');	
CKEDITOR.plugins.addExternal('ui_newsman_label', customPluginsRoot+'newsman_ui_label/plugin.js', '');
CKEDITOR.plugins.addExternal('newsman_autosave', customPluginsRoot+'newsman_autosave/plugin.js', '');
//*/

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// %REMOVE_START%
	//The configuration options below are needed when running CKEditor from source files.
	// config.plugins = 'dialogui,dialog,about,a11yhelp,basicstyles,blockquote,clipboard,panel,floatpanel,menu,contextmenu,resize,button,toolbar,elementspath,list,indent,enterkey,entities,popup,filebrowser,floatingspace,listblock,richcombo,format,htmlwriter,horizontalrule,wysiwygarea,image,fakeobjects,link,magicline,maximize,pastetext,pastefromword,removeformat,sourcearea,specialchar,menubutton,scayt,stylescombo,tab,table,tabletools,undo,wsc,panelbutton,colorbutton,font,justify,liststyle';
	// config.plugins = 'a11yhelp,about,basicstyles,blockquote,button,clipboard,colorbutton,contextmenu,dialog,dialogui,elementspath,enterkey,entities,fakeobjects,filebrowser,floatingspace,floatpanel,font,format,horizontalrule,htmlwriter,image,indent,indentlist,justify,link,list,listblock,liststyle,magicline,maximize,menu,menubutton,panel,panelbutton,pastefromword,pastetext,popup,removeformat,resize,richcombo,scayt,sourcearea,specialchar,stylescombo,tab,table,tabletools,toolbar,undo,wsc,wysiwygarea';
	// config.skin = 'moono';
	// %REMOVE_END%

	config.extraPlugins = 'ui_newsman_label,newsman_autosave,iframedialog,newsman_insert_posts,newsman_add_wp_media,newsman_save,newsmanshortcodes';	

	config.fullPage = true;
	config.allowedContent = true;

	config.entities = true; // true - use encoded entities in HTML output
	config.basicEntities = false; // encode basic entities like ", ', etc.
	config.entities_additional = '';
	// config.entities_latin = false;

	config.resize_enabled = true;

	config.enterMode = CKEDITOR.ENTER_BR;
	config.shiftEnterMode = CKEDITOR.ENTER_P;	
	config.fillEmptyBlocks = false;

	// The toolbar groups arrangement, optimized for two toolbar rows.
	config.toolbarGroups = [
		{ name: 'first', 	   groups: ['newsmanSave', 'mode'] },
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'newsmanBar' },
		{ name: 'newsmanSavingState' },
		'/',
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align' ] },
		{ name: 'links' },
		{ name: 'insert' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },		
		{ name: 'styles' },
		{ name: 'colors' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'document', 'doctools' ] },
		{ name: 'others' },
		{ name: 'about' }
	];

	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	config.removeButtons = 'Subscript,Superscript,Save,Styles';
};
