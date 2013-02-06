/*
Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

(function()
{
	CKEDITOR.plugins.add( 'newsmanshortcodes',
	{   
	   requires : ['richcombo' ],
	   init : function( editor )
	   {
		  var config = editor.config,
			 lang = editor.lang.format;

		  // Gets the list of tags from the settings.
		  var sCodes = {
		  	'unsubscribe': {
		  		title: 'Unsubscribe',
		  		code: '[newsman link="unsubscribe"]'
		  	},
		  	'update-subscription': {
		  		title: 'Update Subscription',
		  		code: '[newsman link="update-subscription"]'
		  	}
		  };

			function htmlEntities(str) {
				return (str+'').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			}
		  
		  // Create style objects for all defined styles.

		  editor.ui.addRichCombo( 'newsman_shortcodes',
			 {
				label : "Newsman",
				title :"Newsman",
				voiceLabel : "Newsman",
				className : 'cke_styles',
				toolbar: 'newsmanBar',
				multiSelect : false,

				panel :
				{
				   css : [ config.contentsCss, CKEDITOR.skin.getPath('editor') ],
				   voiceLabel : lang.panelVoiceLabel
				},

				init : function()
				{
				   this.startGroup( "Insert Shortcode" );

				   for ( var val in sCodes ) {
				   		this.add(val, sCodes[val].title);
				   }
				},

				onClick : function( value )
				{         
					var code = sCodes[value] && sCodes[value].code || '';

				   editor.focus();
				   editor.fire( 'saveSnapshot' );
				   editor.insertHtml(code);
				   editor.fire( 'saveSnapshot' );
				}
			 });
	   }
	});
})();