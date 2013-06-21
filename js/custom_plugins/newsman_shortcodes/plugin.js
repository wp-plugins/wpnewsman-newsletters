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
		  	'view-email-online': {
		  		title: 'View Email Online',
		  		code: "<a href=\"[newsman link='email']\">View email online</a>",
		  		codeBefore: "<a href=\"[newsman link='email']\">",
		  		codeAfter: "</a>"
		  	},
		  	'hr1': true,
		  	'follow-on-twitter': {
		  		title: 'Follow on Twitter',
		  		code: "<a href=\"[newsman profileurl='twitter']\">Follow on Twitter</a>",
		  		codeBefore: "<a href=\"[newsman profileurl='twitter']\">",
		  		codeAfter: "</a>"
		  	},
		  	'friend-on-facebook': {
		  		title: 'Friend on Facebook',
		  		code: "<a href=\"[newsman profileurl='facebook']\">Friend on Facebook</a>",
		  		codeBefore: "<a href=\"[newsman profileurl='facebook']\">",
		  		codeAfter: "</a>"
		  	},
		  	'linkedin-shortcode': {
		  		title: 'Connect via LinkedIn',
		  		code: "<a href=\"[newsman profileurl='linkedin']\">Connect via LinkedIn</a>",
		  		codeBefore: "<a href=\"[newsman profileurl='linkedin']\">",
		  		codeAfter: "</a>"
		  	},
		  	'add-on-googleplus': {
		  		title: 'Add on Google+',
		  		code: "<a href=\"[newsman profileurl='googleplus']\">Add on Google+</a>",
		  		codeBefore: "<a href=\"[newsman profileurl='googleplus']\">",
		  		codeAfter: "</a>"
		  	},
		  	'hr2': true,
		  	'current-date': {
		  		title: 'Current Date',
		  		code: '<span>[newsman date="F j, Y"]</span>',
		  		codeBefore: '',
		  		codeAfter: ''
		  	},
		  	'subscribed-date': {
		  		title: 'Subscription Date',
		  		code: '<span>[newsman sub=\'subscribed\' format=\'F j, Y\']</span>',
		  		codeBefore: '',
		  		codeAfter: ''
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

				onOpen : function() {
					this._.panel.element.$.className += ' newsman-shortcode-menu';	
				},

				init : function()
				{
				   this.startGroup( "Insert Shortcode" );

				   for ( var val in sCodes ) {
				   		if ( sCodes[val] === true ) {
							this.add('-', '<hr>');
				   		} else {
				   			this.add(val, sCodes[val].title);	
				   		}				   		
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