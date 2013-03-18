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
		  		code: '<a href="[newsman link=\'unsubscribe\']">Unsubscribe</a>',
		  		codeBefore: '<a href="[newsman link=\'unsubscribe\']">',
		  		codeAfter: '</a>'
		  	},
		  	'update-subscription': {
		  		title: 'Update Subscription',
		  		code: '<a href="[newsman link=\'update-subscription\']">Update Subscription</a>',
		  		codeBefore: '<a href="[newsman link=\'update-subscription\']">',
		  		codeAfter: '</a>'
		  	},
		  	'view-email-online': {
		  		title: 'View Email Online',
		  		code: "<a href=\"[newsman link='email']\">View email online</a>",
		  		codeBefore: "<a href=\"[newsman link='email']\">",
		  		codeAfter: "</a>"
		  	}
		  };

			function htmlEntities(str) {
				return (str+'').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
			}

			function getSelection() {
				var sel = editor.document.getSelection();

				switch ( sel.getType() ) {
					case CKEDITOR.SELECTION_TEXT:
						return sel.getSelectedText();
						break;
					case CKEDITOR.SELECTION_ELEMENT:
						return sel.getSelectedElement().$.outerHTML;
						break;					
					case CKEDITOR.SELECTION_NONE:
					default:
						return null;
						break;						
				}
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
				   css : [ config.contentsCss, CKEDITOR.skin.getPath('editor') ]
				   //voiceLabel : lang.panelVoiceLabel
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
					var output = '', SC = sCodes[value] && sCodes[value];

					var sel = getSelection();

					if ( SC ) {
						if ( sel ) {
							if ( typeof SC.codeBefore !== 'undefined' && SC.codeAfter !== 'undefined' ) {
								output = SC.codeBefore + sel + SC.codeAfter;
							}
						} else {
							output = SC.code || '';
						}						
					}

					editor.focus();
					editor.fire( 'saveSnapshot' );

    				//var content = this.prepareContent(output);
    				editor.insertElement(CKEDITOR.dom.element.createFromHtml(output));

					//editor.insertHtml(output);
					editor.fire( 'saveSnapshot' );
					editor.fire('newsmanSave.ckeditor');
				}
			 });
	   }
	});
})();