/*
 * @example An iframe-based dialog with frame window fit dialog size.
 */

( function() {


    CKEDITOR.plugins.add( 'newsman_insert_posts',
    {
        requires: [ 'iframedialog' ],
        init: function( editor )
        {

        	var iconPath = this.path.substr(0, this.path.indexOf("plugin.js"));

	        var height = 480, width = 750;
			CKEDITOR.dialog.addIframe(
					'newsmanInsertPostsDlg',
				   'Insert Posts',
				   NEWSMAN_PLUGIN_URL+'/frmGetPosts.php', width, height,
				   function()
				   {
					   // Iframe loaded callback.					   
				   },

				   {
						onOk : function()
						{
							var that = this;

							jQuery(function($){
								var dialog = that.parts.dialog.$,
									iframe = $('iframe', dialog).get(0),
									frameDoc, ids = [];

								if ( iframe ) {
									frameDoc = iframe.contentDocument;
									// $('.newsman-bcst-post.active input', frameDoc).each(function(i, el){
									// 	ids.push(parseInt($(el).val(), 10));
									// });

									console.log(' selected posts:  '+frameDoc.newsmanGetIDS());
								}
							});
						}
				   }
				);

			editor.addCommand( 'newsmanOpenInsertPostDlg', new CKEDITOR.dialogCommand( 'newsmanInsertPostsDlg' ) );

            editor.ui.addButton( 'newsman_btn_insert_posts',
            {
                label: 'Insert posts',
                command: 'newsmanOpenInsertPostDlg',
                icon: iconPath + 'wpmini-blue.png'
            } );
        }
    } );

} )();