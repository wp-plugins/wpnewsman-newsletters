/**
 * Ajax Multiupload component with fallback
 *
 * Copyright: © 2012, Alex Ladyga, neocoder@gmail.com
 *
 * Licensed under MIT license, GNU GPL 2 or later, GNU LGPL 2 or later.
 */

jQuery(function($){

	if (typeof Object.create !== 'function') {
		Object.create = function (o) {
			function F() {}
			F.prototype = o;
			return new F();
		};
	}

	var env = {
		ie      : navigator.userAgent.indexOf('MSIE') != -1,
		safari  : navigator.vendor != undefined && navigator.vendor.indexOf("Apple") != -1,
		opera 	: navigator.appName !== undefined && navigator.appName.indexOf("Opera") != -1,
		chrome  : navigator.vendor != undefined && navigator.vendor.indexOf('Google') != -1,
		firefox : (navigator.userAgent.indexOf('Mozilla') != -1 && navigator.vendor != undefined && navigator.vendor == ''),
		windows : navigator.platform.match(/^Win(32|64)$/i),
	};

	/**
	 * addQuerystring() serialises object to querystring and
	 * appends it to the url
	 * 
	 * Usage: 
	 *
	 *  addQuerystring('http://test.com/upload.php?preserved=value', {one:'1',two:'2'});
	 *  > http://test.com/upload.php?preserved=value&one=1&two=2
	 * 
	 * @param  String url
	 * @param  Object JSON-Object
	 * @return String url with appended encoded querystring params
	 */
	function addQuerystring(url, obj){
		var qs = $.param(obj), 
			del = ( url.match(/\?/) ) ? '&' : '?';

		return url+del+qs;
	}

	var neo = {
		transports: { }
	};

	window.neo = neo;

	/**
	 * base "interface" class for upload transport
	 */
	neo.transportsBase = function(o) {
		var that = {
			options: {
				debug: false,
				action: '/wpnewsman-upload',
				// maximum number of concurrent uploads
				maxConnections: 999,
				onProgress: function(id, fileName, loaded, total){},
				onComplete: function(id, fileName, response){},
				onCancel: function(id, fileName){},
				onUpload: function(id, fileName, xhr){}				
			},
			queue: [],
			// params for files in queue
			params: []			
		};

		$.extend(that.options, o);

		$.extend(that, {
			log: function(str){
				if (this.options.debug && window.console) console.log('[uploader] ' + str);
			},
			/**
			 * Adds file or file input to the queue
			 * @returns id
			 **/
			add: function(file){},
			/**
			 * Sends the file identified by id and additional query params to the server
			 */
			upload: function(id, params){
				var len = this.queue.push(id);

				var copy = {};
				$.extend(copy, params);
				this.params[id] = copy;

				// if too many active uploads, wait...
				if (len <= this.options.maxConnections){
					this.upload(id, this.params[id]);
				}
			},
			/**
			 * Cancels file upload by id
			 */
			cancel: function(id){
				this.cancel(id);
				//this.dequeue(id);
			},
			/**
			 * Cancells all uploads
			 */
			cancelAll: function(){
				for (var i=0; i<this.queue.length; i++){
					this.cancel(this.queue[i]);
				}
				this.queue = [];
			},
			/**
			 * Returns name of the file identified by id
			 */
			getName: function(id){},
			/**
			 * Returns size of the file identified by id
			 */
			getSize: function(id){},
			/**
			 * Returns id of files being uploaded or
			 * waiting for their turn
			 */
			getQueue: function(){
				return this.queue;
			},
			/**
			 * Removes element from queue, starts upload of next
			 */
			dequeue: function(id){
				var i = this.queue.indexOf(id);
				if ( i > -1 ) {
					this.queue.splice(i, 1);

					var max = this.options.maxConnections;

					if ( this.queue.length >= max && i < max ){
						var nextId = this.queue[max-1];
						this.upload(nextId, this.params[nextId]);
					}					
				} else {
					throw new Error('[neo.transportsBase] no element with id '+id+' in the queue');
				}
			}
		});

		return that;
	};

	neo.transports.xhr = function(o) {

		var that = Object.create(neo.transportsBase(o));

		var files = [],
			loaded = [], // current loaded size in bytes for each file
			xhrs = [];

		$.extend(that, {
			type: 'xhr',

			/**
			 * Adds file to the queue
			 * Returns id to use with upload, cancel
			 **/
			add: function(file){
				if ( !(file instanceof File) ){
					throw new Error('[neo.transports.xhr] Passed obj in not a File');
				}

				return files.push(file) - 1;
			},
			getName: function(id){
				var file = files[id];
				// fix missing name in Safari 4
				//NOTE: fixed missing name firefox 11.0a2 file.fileName is actually undefined
				return (file.fileName !== null && file.fileName !== undefined) ? file.fileName : file.name;
			},
			getSize: function(id){
				var file = files[id];
				return typeof file.fileSize !== 'undefined' ? file.fileSize : file.size;
			},
			/**
			 * Returns uploaded bytes for file identified by id
			 */
			getLoaded: function(id){
				return loaded[id] || 0;
			},

			/**
			 * Sends the file identified by id and additional query params to the server
			 * @param {Object} params name-value string pairs
			 */
			upload: function(id, params){
				this.options.onUpload(id, this.getName(id), true);

				var file = files[id],
					name = this.getName(id),
					size = this.getSize(id);

				loaded[id] = 0;

				var xhr = xhrs[id] = new XMLHttpRequest();

				var that = this;

				xhr.upload.onprogress = function(e){
					if (e.lengthComputable){
						loaded[id] = e.loaded;
						that.options.onProgress(id, name, e.loaded, e.total);
					}
				};

				xhr.onreadystatechange = function(){
					if (xhr.readyState == 4){
						that.onComplete(id, xhr);
					}
				};

				// build query string
				params = params || {};
				params[this.options.inputName] = name;
				var queryString = addQuerystring(this.options.action, params);

				var protocol = this.options.demoMode ? "GET" : "POST";
				xhr.open(protocol, queryString, true);
				xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
				xhr.setRequestHeader("X-File-Name", encodeURIComponent(name));
				xhr.setRequestHeader("Cache-Control", "no-cache");

				if ( this.options.forceMultipart ) {
					var formData = new FormData();
					formData.append(this.options.inputName, file);
					file = formData;
				} else {
					xhr.setRequestHeader("Content-Type", "application/octet-stream");
					//NOTE: return mime type in xhr works on chrome 16.0.9 firefox 11.0a2
					xhr.setRequestHeader("X-Mime-Type",file.type );
				}
				for (var key in this.options.customHeaders){
					xhr.setRequestHeader(key, this.options.customHeaders[key]);
				}
				xhr.send(file);
			},

			onComplete: function(id, xhr){
				"use strict";
				// the request was aborted/cancelled
				if (!files[id]) { return; }

				var name = this.getName(id);
				var size = this.getSize(id);
				var response; //the parsed JSON response from the server, or the empty object if parsing failed.

				this.options.onProgress(id, name, size, size);

				this.log("xhr - server response received");
				this.log("responseText = " + xhr.responseText);

				try {
					if ( typeof JSON.parse === "function" ) {
						response = JSON.parse(xhr.responseText);
					} else {
						response = eval("(" + xhr.responseText + ")");
					}
				} catch (err) {
					response = {};
				}

				if ( xhr.status !== 200 ) {
					this.options.onError(id, name, "XHR returned response code " + xhr.status);
				}
				this.options.onComplete(id, name, response);

				xhrs[id] = null;
				//this.dequeue(id);
			},

			cancel: function(id){
				this.options.onCancel(id, this.getName(id));

				files[id] = null;

				if ( xhrs[id] ) {
					xhrs[id].abort();
					xhrs[id] = null;
				}
			}
		});
		return that;
	};

	neo.transports.xhr.isSupported = function(){

		var input = document.createElement('input');
		input.type = 'file';

		return (
			'multiple' in input &&
			typeof File != "undefined" &&
			typeof FormData != "undefined" &&
			typeof (new XMLHttpRequest()).upload != "undefined" );
	};

	/**
	 * Transport that uses form in iframe
	 */

	neo.transports.form = function(o) {
		var that = Object.create(neo.transportsBase(o));

		function uid() {
			return (new Date())*1000 + Math.floor(Math.random()*1000).toString(16).toUpperCase();
		}

		$.extend(that, {
			type: 'form',
			inputs: {},
			detach_load_events: {},

			add: function(fileInput){
				fileInput.setAttribute('name', this.options.inputName);
				var id = 'upload-transport-iframe-' + uid();

				this.inputs[id] = fileInput;

				// remove file input from DOM
				if (fileInput.parentNode){
					$(fileInput).remove()
				}

				return id;
			},
			getName: function(id){
				// get input value and remove path to normalize
				return this.inputs[id].value.replace(/.*(\/|\\)/, "");
			},
			cancel: function(id){
				this.options.onCancel(id, this.getName(id));

				delete this.inputs[id];
				delete this.detach_load_events[id];

				var iframe = document.getElementById(id);
				if (iframe){
					// to cancel request set src to something else
					// we use src="javascript:false;" because it doesn't
					// trigger ie6 prompt on https
					iframe.attr('src', 'javascript:false;');
					iframe.remove();
				}
			},
			upload: function(id, params){
				this.options.onUpload(id, this.getName(id), false);
				var input = this.inputs[id];

				if (!input){
					throw new Error('file with passed id was not added, or already uploaded or cancelled');
				}

				var fileName = this.getName(id);
				params[this.options.inputName] = fileName;

				var iframe = this.createIframe(id);
				var form = this.createForm(iframe, params);

				form.get(0).appendChild(input);

				var that = this;
				this.attachLoadEvent(iframe, function(){

					var response = that.getIframeContentJSON(iframe);

					that.options.onComplete(id, fileName, response);
					//that.dequeue(id);

					delete that.inputs[id];
					// timeout added to fix busy state in FF3.6
					setTimeout(function(){
						that.detach_load_events[id]();
						delete that.detach_load_events[id];
						$(iframe).remove();
					}, 1);
				});

				form.submit();
				form.remove();

				return id;
			},
			attachLoadEvent: function(iframe, callback){
				this.detach_load_events[iframe.get(0).id] = function(){
					$(iframe).unbind('load');
				};

				$(iframe).bind('load', function(){
					// when we remove iframe from dom
					// the request stops, but in IE load
					// event fires
					if (!iframe.get(0).parentNode){
						return;
					}

					try {
						// fixing Opera 10.53
						if (iframe.get(0).contentDocument &&
							iframe.get(0).contentDocument.body &&
							iframe.get(0).contentDocument.body.innerHTML == "false"){
							// In Opera event is fired second time
							// when body.innerHTML changed from false
							// to server response approx. after 1 sec
							// when we upload file with iframe
							return;
						}
					}
					catch (error) {
						//IE may throw an "access is denied" error when attempting to access contentDocument on the iframe in some cases
					}

					callback();
				});
			},
			/**
			 * Returns json object received by iframe from server.
			 */
			getIframeContentJSON: function(iframe){
				var response;
				//IE may throw an "access is denied" error when attempting to access contentDocument on the iframe in some cases
				try {
					// iframe.contentWindow.document - for IE<7
					var doc = iframe.get(0).contentDocument ? iframe.get(0).contentDocument: iframe.get(0).contentWindow.document;

					var innerHTML = doc.body.innerHTML;
					this.log("converting iframe's innerHTML to JSON");
					this.log("innerHTML = " + innerHTML);
					//plain text response may be wrapped in <pre> tag
					if (innerHTML.slice(0, 5).toLowerCase() == '<pre>' && innerHTML.slice(-6).toLowerCase() == '</pre>') {
						innerHTML = doc.body.firstChild.firstChild.nodeValue;
					}
					response = eval("(" + innerHTML + ")");
				} catch(err){
					response = {success: false};
				}

				return response;
			},
			/**
			 * Creates iframe with unique name
			 */
			createIframe: function(id){
				// We can't use following code as the name attribute
				// won't be properly registered in IE6, and new window
				// on form submit will open
				// var iframe = document.createElement('iframe');
				// iframe.setAttribute('name', id);

				var iframe = $('<iframe src="javascript:false;" name="' + id + '" />');
				// src="javascript:false;" removes ie6 prompt on https

				iframe
					.attr('id', id)
					.css({ display: 'none' })
					.appendTo(document.body);

				return iframe;
			},
			/**
			 * Creates form, that will be submitted to iframe
			 */
			createForm: function(iframe, params){
				// We can't use the following code in IE6
				// var form = document.createElement('form');
				// form.setAttribute('method', 'post');
				// form.setAttribute('enctype', 'multipart/form-data');
				// Because in this case file won't be attached to request
				var protocol = this.options.demoMode ? "GET" : "POST",
					form = $('<form method="' + protocol + '" enctype="multipart/form-data"></form>');

				for ( var p in params ) {
					$('<input type="hidden" name="'+p+'" value="'+params[p]+'">').appendTo(form);
				}

				form.attr({
					//'action': addQuerystring(this.options.action, params),
					'action': this.options.action,
					'target': iframe.get(0).name
				}).css({
					display: 'none'
				}).appendTo(document.body);

				return form;
			}
		});
		return that;
	}; 

	neo.transports.form.isSupported = function(){
		return true;
	};

	neo.createUploadTransport = function(o) {
		var supportedTransport;

		for ( var name in neo.transports ) {
			if ( neo.transports[name].isSupported() ) {
				supportedTransport = name;
				break;
			}
		}

		if ( !supportedTransport ) {
			throw new Error('[uploaded] No supported transports for this environment');
		}

		return neo.transports[supportedTransport](o);		
	};

	neo.createUploadButton = function(o) {
		var that = {
			options: {
				element: null,
				// if set to true adds multiple attribute to file input
				multiple: false,
				acceptFiles: null,
				// name attribute of file input
				name: 'file',
				onChange: function(input){},
				hoverClass: 'nu-upload-button-hover',
				focusClass: 'nu-upload-button-focus'
			}
		};

		$.extend(that.options, o);

		if ( !that.options.element ) {
			throw new Error('[neo.createUploadButton] element is not defined');
		}

		that.element = $(that.options.element);

		// make button suitable container for input
		that.element.css({
			position: 'relative',
			overflow: 'hidden',
			// Make sure browse button is in the right side
			// in Internet Explorer
			direction: 'ltr'
		});

		$.extend(that, {
			getInput: function(){
				return this.input;
			},
			reset: function() {
				if ( this.input.parentNode ){
					this.input.remove();
				}
				this.element.removeClass(this.options.focusClass);
				this.input = this.createInput();
			},
			createInput: function() {
				var input = $('<input type="file" name="'+this.options.name+'">');

				if ( this.options.multiple ){
					input.attr("multiple", "multiple");
				}

				// if ( env.firefox ) {
				// 	this.element
				// 		.unbind('click')
				// 		.click(function(e){
				// 			input.focus();
				// 			//e.preventDefault();
				// 		});					
				// }

				if (this.options.acceptFiles) input.attr("accept", this.options.acceptFiles);

				input.css({
					position: 'absolute',
					// in Opera only 'browse' button
					// is clickable and it is located at
					// the right side of the input
					top: 0,
					right: 0,
					bottom: 0,
					
					fontFamily: 'Arial',
					// 4 persons reported this, the max values that worked for them were 243, 236, 236, 118
					fontSize: '118px',
					margin: 0,
					padding: 0,
					cursor: 'pointer',
					opacity: 0
				});

				input.appendTo(this.element);

				var that = this;
				input.change(function(){
					that.options.onChange(input);
				});

				input.mouseover(function(){
					that.element.addClass(that.options.hoverClass);
				});

				input.mouseout(function(){
					that.element.removeClass(that.options.hoverClass);
				});

				input.focus(function(){
					that.element.addClass(that.options.focusClass);
				});

				input.blur(function(){
					that.element.removeClass(that.options.focusClass);
				});

				// IE and Opera, unfortunately have 2 tab stops on file input
				// which is unacceptable in our case, disable keyboard access
				if ( env.opera || env.ie ) {
					input.attr('tabIndex', "-1");
				}

				return input;
			}
		});

		that.input = that.createInput();
		return that;
	};

	neo.createFileUploaderBasic = function(o){
		var that = {};
		that.options = {
			// set to true to see the server response
			debug: false,
			action: '/server/upload',
			params: {},
			customHeaders: {},
			button: null,
			multiple: true,
			maxConnections: 3,
			disableCancelForFormUploads: false,
			autoUpload: true,
			forceMultipart: false,
			// validation
			allowedExtensions: [],
			acceptFiles: null,		// comma separated string of mime-types for browser to display in browse dialog
			sizeLimit: 0,
			minSizeLimit: 0,
			stopOnFirstInvalidFile: true,
			// events
			// return false to cancel submit
			onSubmit: function(id, fileName){
				// console.log('('+fileName+') submit id: '+id+' ');
			},
			onComplete: function(id, fileName, responseJSON){
				// console.log('('+fileName+') complete id: '+id+' '+responseJSON);
			},
			onCancel: function(id, fileName){
				// console.log('('+fileName+') cancel id: '+id+' ');
			},
			onUpload: function(id, fileName, xhr){
				// console.log('('+fileName+') upload id: '+id+' ');
			},
			onProgress: function(id, fileName, loaded, total){
				// console.log('('+fileName+') progress id: '+id+' '+Math.round(loaded/total*100));
			},
			onError: function(id, fileName, reason) {
				// console.log('('+fileName+') error id: '+id+' '+reason);				
			},
			// messages
			messages: {
				typeError: "{file} has an invalid extension. Valid extension(s): {extensions}.",
				sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
				minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
				emptyError: "{file} is empty, please select files again without it.",
				noFilesError: "No files to upload.",
				onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
			},
			showMessage: function(message){
				alert(message);
			},
			inputName: 'nufile'
		};

		$.extend(that.options, o);
			//$.extend(that, nu.DisposeSupport);

		$.extend(that, {
			log: function(str){
				if ( this.options.debug && window.console ) console.log('[uploader] ' + str);
			},
			setParams: function(params){
				this.options.params = params;
			},
			getInProgress: function(){
				return this.filesInProgress;
			},
			uploadStoredFiles: function(){
				while ( this.storedFileIds.length ) {
					this.filesInProgress++;
					this.transport.upload(this.storedFileIds.shift(), this.options.params);
				}
			},
			clearStoredFiles: function(){
				this._storedFileIds = [];
			},
			createButton: function(element){
				var that = this;

				var button = neo.createUploadButton({
					element: element,
					multiple: this.options.multiple && neo.transports.xhr.isSupported(),
					acceptFiles: this.options.acceptFiles,
					onChange: function(input){
						that.onInputChange(input);
					}
				});

				//that.addDisposer(function() { button.dispose(); });
				return button;
			},
			preventLeaveInProgress: function(){
				var that = this;

				$(window).bind('beforeunload', function(e){
					if (!that.filesInProgress){return;}

					// for ie, ff
					e.returnValue = that.options.messages.onLeave;
					// for webkit
					return that.options.messages.onLeave;
				});
			},
			onSubmit: function(id, fileName){
				if (this.options.autoUpload) {
					this.filesInProgress++;
				}
			},
			onProgress: function(id, fileName, loaded, total){
			},
			onComplete: function(id, fileName, result){
				this.filesInProgress--;

				if (!result.success){
					var errorReason = result.error ? result.error : "Upload failure reason unknown";
					this.options.onError(id, fileName, errorReason);
				}
			},
			onCancel: function(id, fileName){
				var storedFileIndex = this._storedFileIds.indexOf(id);
				if ( this.options.autoUpload || storedFileIndex < 0 ) {
					this.filesInProgress--;
				} else if ( !this._options.autoUpload ) {
					this.storedFileIds.splice(storedFileIndex, 1);
				}
			},
			onUpload: function(id, fileName, xhr){
			},
			onInputChange: function(input){
				if ( this.transport.type === 'xhr' ){
					this.uploadFileList(input[0].files);
				} else {
					if (this.validateFile(input[0])){
						this.uploadFile(input[0]);
					}
				}
				this.button.reset();
			},
			uploadFileList: function(files){
				if ( files.length > 0 ) {
					for (var i=0; i<files.length; i++){
						if ( this.validateFile(files[i]) ){
							this.uploadFile(files[i]);
						} else {
							if ( this.options.stopOnFirstInvalidFile ){
								return;
							}
						}
					}
				} else {
					this.error('noFilesError', "");
				}
			},
			uploadFile: function(fileContainer){
				var id = this.transport.add(fileContainer);
				var fileName = this.transport.getName(id);

				if ( this.options.onSubmit(id, fileName) !== false ){
					this.onSubmit(id, fileName);
					if ( this.options.autoUpload ) {
						this.transport.upload(id, this.options.params);
					} else {
						this.storeFileForLater(id);
					}
				}
			},
			storeFileForLater: function(id) {
				this.storedFileIds.push(id);
			},
			validateFile: function(file){
				var name, size;

				if (file.value){
					// it is a file input
					// get input value and remove path to normalize
					name = file.value.replace(/.*(\/|\\)/, "");
				} else {
					// fix missing properties in Safari 4 and firefox 11.0a2
					name = (file.fileName !== null && file.fileName !== undefined) ? file.fileName : file.name;
					size = (file.fileSize !== null && file.fileSize !== undefined) ? file.fileSize : file.size;
				}

				if (! this.isAllowedExtension(name)){
					this.error('typeError', name);
					return false;

				} else if (size === 0){
					this.error('emptyError', name);
					return false;

				} else if (size && this.options.sizeLimit && size > this.options.sizeLimit){
					this.error('sizeError', name);
					return false;

				} else if (size && size < this.options.minSizeLimit){
					this.error('minSizeError', name);
					return false;
				}

				return true;
			},
			error: function(code, fileName){
				var message = this.options.messages[code];
				function r(name, replacement){ message = message.replace(name, replacement); }

				var extensions = this.options.allowedExtensions.join(', ');

				r('{file}', this.formatFileName(fileName));
				r('{extensions}', extensions);
				r('{sizeLimit}', this.formatSize(this.options.sizeLimit));
				r('{minSizeLimit}', this.formatSize(this.options.minSizeLimit));

				this.options.onError(null, fileName, message);
				this.options.showMessage(message);
			},
			formatFileName: function(name){
				if (name.length > 33){
					name = name.slice(0, 19) + '...' + name.slice(-13);
				}
				return name;
			},
			isAllowedExtension: function(fileName){
				var ext = (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '').toLowerCase() : '',
					allowed = this.options.allowedExtensions;

				if ( !allowed.length ) { return true; }

				for (var i=0; i<allowed.length; i++){
					if (allowed[i].toLowerCase() == ext){ return true;}
				}

				return false;
			},
			formatSize: function(bytes){
				var i = -1;
				do {
					bytes = bytes / 1024;
					i++;
				} while (bytes > 99);

				return Math.max(bytes, 0.1).toFixed(1) + ['kB', 'MB', 'GB', 'TB', 'PB', 'EB'][i];
			},
			wrapCallbacks: function() {
				var self, safeCallback;

				self = this;

				safeCallback = function(callback, args) {
					try {
						return callback.apply(this, args);
					} catch (exception) {
						self.log("Caught " + exception + " in callback: " + callback);
					}
				};

				for (var prop in this._options) {
					if ( /^on[A-Z]/.test(prop) ) {
						(function() {
							var oldCallback = self._options[prop];
							self._options[prop] = function() {
								return safeCallback(oldCallback, arguments);
							};
						}());
					}
				}
			}
		});

		$.extend(that, {
			filesInProgress: 0,
			storedFileIds: [],
			transport: neo.createUploadTransport({
				debug: that.options.debug,
				action: that.options.action,
				forceMultipart: that.options.forceMultipart,
				maxConnections: that.options.maxConnections,
				customHeaders: that.options.customHeaders,
				inputName: that.options.inputName,
				demoMode: that.options.demoMode,
				onProgress: function(id, fileName, loaded, total){
					that.onProgress(id, fileName, loaded, total);
					that.options.onProgress(id, fileName, loaded, total);
				},
				onComplete: function(id, fileName, result){
					that.onComplete(id, fileName, result);
					that.options.onComplete(id, fileName, result);
				},
				onCancel: function(id, fileName){
					that.onCancel(id, fileName);
					that.options.onCancel(id, fileName);
				},
				onError: that.options.onError,
				onUpload: function(id, fileName, xhr){
					that.onUpload(id, fileName, xhr);
					that.options.onUpload(id, fileName, xhr);
				}
			})
		});

		// number of files being uploaded
		if ( that.options.button ) {
			that.button = that.createButton(that.options.button);
		}

		that.preventLeaveInProgress();
	};

	$.widget('neo.neoFileUploader', {
		options: {
			debug: false,
			canCancelUpload: true,
			autoUpload: true,
			maxConnections: 3,
			multiple: true,
			customHeaders: {},
			params: {},
			acceptFiles: null,
			extensions: [],
			mimes: null,
			sizeLimit: 0,
			minSizeLimit: 0,
			forceMultipart: false,
			action: '/url/to/upload/script',
			stopOnFirstInvalidFile: true,
		},
		_create: function() {
			var that = this;
			var o = this.options;
			this.uploader = neo.createFileUploaderBasic({
				// set to true to see the server response
				debug: o.debug,
				action: o.action,
				params: o.params,
				button: o.button,
				customHeaders: o.customHeaders,
				multiple: o.multiple,
				maxConnections: o.maxConnections,
				disableCancelForFormUploads: !o.canCancelUpload,
				autoUpload: o.autoUpload,
				forceMultipart: o.forceMultipart,
				// validation
				allowedExtensions: o.extensions,
				acceptFiles: o.mimes || o.acceptFiles,		// comma separated string of mime-types for browser to display in browse dialog
				sizeLimit: o.sizeLimit,
				minSizeLimit: o.minSizeLimit,
				stopOnFirstInvalidFile: o.stopOnFirstInvalidFile,
				// events
				// return false to cancel submit
				onSubmit: function(id, fileName){
					that._trigger('onAdd', 0, { id: id, fileName: fileName });
				},
				onComplete: function(id, fileName, responseJSON){					
					that._trigger('onDone', 0, {
						id: id,
						fileName: fileName,
						responseJSON: responseJSON,
						actualFileName: responseJSON.actualFileName						
					});
				},
				onCancel: function(id, fileName){
					that._trigger('onCancel', 0, { id: id, fileName: fileName });
				},
				onUpload: function(id, fileName, xhr){
					that._trigger('onUpload', 0, { id: id, fileName: fileName });
				},
				onProgress: function(id, fileName, loaded, total){
					that._trigger('onProgress', 0, {
						id: id,
						fileName: fileName,
						loaded: loaded,
						total: total,
						percents: Math.round(loaded/total*100)
					});
				},
				onError: function(id, fileName, reason) {
					that._trigger('onError', 0, { id: id, fileName: fileName, reason: reason });
				},
				// messages
				messages: {
					typeError: "{file} has an invalid extension. Valid extension(s): {extensions}.",
					sizeError: "{file} is too large, maximum file size is {sizeLimit}.",
					minSizeError: "{file} is too small, minimum file size is {minSizeLimit}.",
					emptyError: "{file} is empty, please select files again without it.",
					noFilesError: "No files to upload.",
					onLeave: "The files are being uploaded, if you leave now the upload will be cancelled."
				},
				showMessage: function(message){
					alert(message);
				},
				inputName: 'nufile'
			});
		}
	});
});