( function() {


	// CKEDITOR PLUGIN * * * * * * * * * * * * * * * * * * * * * * * * 

    CKEDITOR.plugins.add( 'newsman_add_wp_media',
    {
        requires: [ 'button' ],
        init: function( editor )
        {

			function initMediaDialog(doc) {
				var that = {},
					hook = false,
					target_el,
					placeHolder,
					wrapper;

				that.inject = function(){
					var old_send_to_editor = send_to_editor;

					send_to_editor = function(html) {
						// returning back the send_to_editor callback
						send_to_editor = old_send_to_editor;

						// var src = html.match(/src=(['"])(.*?)\1/i);
						// src = (src && src[2]) ? src[2] : '';

						// var href = html.match(/href=(['"])(.*?)\1/i);
						// href = (href && href[1]) ? href[1] : '';

						//var url = src ? src : href;

						if ( hook ) {
							//editor.insertHtml('<img src="'+url+'" />');
							editor.focus();
							editor.insertHtml(html);
							editor.fire('newsmanSave.ckeditor');
							hook = false;
							tb_remove();
						} else {
							return old_send_to_editor.apply(this, arguments);	
						}			
					};

					var old_tb_remove = tb_remove;

					tb_remove = function() {
						hook = false;

						tb_remove = old_tb_remove;
						old_tb_remove.apply(this, arguments);
					};
				};

				that.show = function() {
					if ( hook ) {
						return;
					}
					if ( typeof send_to_editor === 'undefined' ) {
						throw new Error('send_to_editor callback not found.');
					}
					this.inject();
					hook = true;
					tb_show('', 'media-upload.php?type=image&amp;TB_iframe=true');
				};

				return that;
			}

			var dlg;

        	editor.on('instanceReady', function(){
        		var doc = CKEDITOR.instances[editor.name].document;
        		if ( doc && doc.$ ) {
        			dlg = initMediaDialog(doc.$);
        		}        		
        	});

			editor.addCommand( 'newsmanAddWPMedia', {
				exec: function(editor) {
					dlg.show();
				}
			});

            editor.ui.addButton('newsman_add_wp_media', {
            	label: 'Add Media',
            	command: 'newsmanAddWPMedia',
            	toolbar: 'insert,2',
            	icon: NEWSMAN_BLOG_ADMIN_URL+'images/media-button.png'
            });
        }
    } );

} )();