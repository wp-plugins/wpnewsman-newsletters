/*
Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

//console.log('CKEDITOR.basePath: '+CKEDITOR.basePath);

var ckeditorBasePath = CKEDITOR.basePath.substr(0, CKEDITOR.basePath.indexOf("ckeditor/"));
var customPluginsRoot = ckeditorBasePath+ 'custom_plugins/';

//console.log('> '+customPluginsRoot+'newsman_insert_posts/');

CKEDITOR.plugins.addExternal('newsman_insert_posts', customPluginsRoot+'newsman_insert_posts/plugin.js', '');	
CKEDITOR.plugins.addExternal('newsman_save', customPluginsRoot+'newsman_save/plugin.js', '');	

CKEDITOR.editorConfig = function( config )
{
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	// config.uiColor = '#AADC6E';

	config.width = 850;
	config.height = 400;	

	config.extraPlugins = 'newsman_insert_posts,newsman_save';	

	config.toolbar = 'NEWSMAN';

	config.toolbar_NEWSMAN = [
		//{ name: 'document', items : [ 'Source','-','Save','NewPage','DocProps','Preview','Print','-','Templates' ] },
		{ name: 'document',    items : [ 'Source', 'newsmanSave', '-',  'Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo', '-', 'SpellChecker', 'Scayt' ] },
		{ name: 'insert',      items : [ 'Image','Table','SpecialChar','PageBreak' ] },
		{ name: 'links',       items : [ 'Link','Unlink','Anchor' ] },
		'/',
		{ name: 'basicstyles', items : [ 'Bold','Italic','Underline','-','RemoveFormat' ] },
		{ name: 'paragraph',   items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','-','BidiLtr','BidiRtl' ] },
				
		'/',
		{ name: 'styles',      items : [ 'Styles','Format','Font','FontSize' ] },
		{ name: 'colors',      items : [ 'TextColor','BGColor' ] },
		{ name: 'tools',       items : [ 'ShowBlocks', 'Maximize' ] }
	];	

	// 'newsman_btn_insert_posts'


	
	config.htmlEncodeOutput = false;
	config.entities = false;
	config.enterMode = CKEDITOR.ENTER_BR;
};
