/**
 * @license Copyright (c) 2013, Alex Ladyga, G-Lock Software. All rights reserved.
 * Released under MIT and GPL licenses
 */

(function() {
	var template = '<span id="{id}"' +
		' class="cke_label cke_label__{name} {cls}"' +
		' tabindex="-1"' +
		' style="{style}"' +
		' hidefocus="true"' +
		' role="label">{label}</span>';

	var lblTpl = CKEDITOR.addTemplate( 'ui_newsman_label', template );

	CKEDITOR.plugins.add( 'ui_newsman_label', {
		beforeInit: function( editor ) {
			editor.ui.addHandler( CKEDITOR.UI_NEWSMAN_LABEL, CKEDITOR.ui.newsman_label.handler );
		}
	});

	/**
	 * Button UI element.
	 *
	 * @readonly
	 * @property {String} [='label']
	 * @member CKEDITOR
	 */
	CKEDITOR.UI_NEWSMAN_LABEL = 'newsman_label';

	/**
	 * Represents a label UI element. This class should not be called directly. To
	 * create new buttons use {@link CKEDITOR.ui#addLabel} instead.
	 *
	 * @class
	 * @constructor Creates a label class instance.
	 * @param {Object} definition The label definition.
	 */
	CKEDITOR.ui.newsman_label = function( definition ) {
		CKEDITOR.tools.extend( this, definition,
		// Set defaults.
		{
			title: definition.label,
			click: definition.click ||
			function( editor ) {
				editor.execCommand( definition.command );
			}
		});

		this._ = {};
	};

	/**
	 * Represents label handler object.
	 *
	 * @class
	 * @singleton
	 * @extends CKEDITOR.ui.handlerDefinition
	 */
	CKEDITOR.ui.newsman_label.handler = {
		/**
		 * Transforms a label definition in a {@link CKEDITOR.ui.label} instance.
		 *
		 * @member CKEDITOR.ui.label.handler
		 * @param {Object} definition
		 * @returns {CKEDITOR.ui.label}
		 */
		create: function( definition ) {
			return new CKEDITOR.ui.newsman_label( definition );
		}
	};

	/** @class CKEDITOR.ui.newsman_label */
	CKEDITOR.ui.newsman_label.prototype = {
		/**
		 * Renders the newsman_label.
		 *
		 * @param {CKEDITOR.editor} editor The editor instance which this label is
		 * to be used by.
		 * @param {Array} output The output array to which append the HTML relative
		 * to this label.
		 */
		render: function( editor, output ) {
			var env = CKEDITOR.env,
				id = this._.id = CKEDITOR.tools.getNextId(),
				stateName = '',
				command = this.command,
				// Get the command name.
				clickFn;

			var toolbarId = id.replace(/\d+/, function(match){
				var newId = parseInt(match, 10);
				return (newId-1)+'';
			});

			editor.on('uiReady', function(){
				var toolbar = document.getElementById(toolbarId);
				toolbar.style.float = 'right';

				var c = toolbar.children;

				for (var i = 0; i < c.length; i++) {
					if ( c[i].className === 'cke_toolgroup' ) {
						c[i].style.border = 'none';
						c[i].style.background = 'none';
						c[i].style.boxShadow = 'none';
						break;
					}
				}				
			});

			this._.editor = editor;

			this.set = function(newString) {
				var lbl = document.getElementById(id);
				if ( !lbl ) { return; }

				if ( typeof lbl.textContent !== 'undefined' ) {
					lbl.textContent = newString;
				} else if ( typeof lbl.innerText !== 'undefined' ) {
					lbl.innerText = newString;
				}
			};

			var instance = {
				id: id,
				label: this,
				editor: editor
			};

			var name = this.name || this.command;

			this.icon = null;

			var style = 'line-height: 26px; padding: 0 10px;';

			var params = {
				id: id,
				name: name,
				style: style,
				label: this.label,
				cls: this.className || ''
			};

			lblTpl.output( params, output );

			if ( this.onRender )
				this.onRender();

			return instance;
		}
	};

	/**
	 * Adds a button definition to the UI elements list.
	 *
	 *		editorInstance.ui.addButton( 'MyBold', {
	 *			label: 'My Bold',
	 *			command: 'bold',
	 *			toolbar: 'basicstyles,1'
	 *		} );
	 *
	 * @member CKEDITOR.ui
	 * @param {String} name The button name.
	 * @param {Object} definition The button definition.
	 */
	CKEDITOR.ui.prototype.addNewsmanLabel = function( name, definition ) {
		return this.add( name, CKEDITOR.UI_NEWSMAN_LABEL, definition );
	};

})();
