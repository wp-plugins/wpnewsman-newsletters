jQuery(function($){

	// common functions

	var o = {};

	var postsSelector;

	window.NEWSMAN = {};

	var showMessage = NEWSMAN.showMessage = function(msg, type, cb) {

		type = type || 'info';
		var cls = 'alert-'+type;

		if ( type === 'error' ) {
			msg = '<strong>'+newsmanL10n.error+'</strong>'+msg;
		}

		var wrap = $('.wrap.wp_bootstrap');

		var wnd = $('<div class="alert gs-fixed '+cls+'">'+msg+'<a class="close" data-dismiss="alert" href="#">&times;</a></div>').appendTo(wrap);
		function close() {
			if ( wnd ) {
				wnd.remove();
			}
			if ( typeof cb === 'function' ) {
				cb();
			}
		}
		if ( type !== 'error' ) {
			setTimeout(close, 2000);
		}		
	};

	var showLoading = NEWSMAN.showLoading = function(str) {
		if ( $('#gs-loading-msg').get(0) ) { return; }
		str = (!str) ? ' Please wait...' : ' '+str;
		msg = $([
			'<div id="gs-loading-msg" class="gs-loading">',
				'<img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader-blk.gif">',
				str,
			'</div>'
		].join('')).appendTo(document.body);
		msg.show();
	};

	var hideLoading = NEWSMAN.hideLoading = function() {
		$('#gs-loading-msg').remove();
	};

	function refreshActiveVariables() {
		var lis = [], vars = [];

		$('.newsman-form input, .newsman-form textarea').each(function(i, el){			
			var n = $(el).attr('name');
			if ( n && $.inArray(n, vars) === -1 ) {
				vars.push(n);
			}			
		});	

		
		$(vars).each(function(i, name){
			lis.push('<li>$'+name+'</li>');
		});

		lis = lis.join('');

		//$('.newsman-varlist').empty();

		$('.newsman-varlist').empty().each(function(i, el){
			$(lis).appendTo(el);
		});
	}

	function tryRefreshVars(href) {
		if ( $.inArray(href, ['#tab-templates', '#tab-pages', '#tab-broadcast-template']) >= 0 ) {
			refreshActiveVariables();
		}
	}

	function loadMailSettings(presetName) {

		function load(vals) {
			$('#newsman_smtp_hostname').val(vals.host);
			$('#newsman_smtp_username').val(vals.username);
			$('#newsman_smtp_password').val(vals.password);
			$('#newsman_smtp_port').val(vals.port);
			$('#newsman_smtp_secure_conn .radio input[value="'+vals.ssl+'"]').attr('checked', 'checked');
		}

		var presets = {
			gmail: {
				host: 'smtp.gmail.com',
				username:'your_address@gmail.com',
				password: '',
				port: 465,
				ssl: 'ssl', // 'tls', 'ssl'
			},
			ses: {
				host: 'email-smtp.us-east-1.amazonaws.com',
				username:'your Amazon SES smtp username',
				password: '',
				port: 465,
				ssl: 'ssl', // 'tls', 'ssl'
			}
		};

		presets[presetName] && load(presets[presetName]);
	}


	function loadOptions(callback) {

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'newsmanAjGetOptions'
			}
		}).done(function(data){
			var opts = JSON.parse(data.options);	

			function isObject(o) {
				return (typeof o === 'object') && ( o !== null ) && (typeof o.slice === 'undefined');
			}

			function optToArr(obj, p) {
				for ( var name in obj ) {
					if ( isObject(obj[name]) ) {
						optToArr(obj[name], p.concat([name]));
					} else {
						o[ [].concat(p, [name]).join('-') ] = obj[name];
					}
				}
			}

			optToArr(opts, ['newsman']);

			var el, cb, f = 'val';
			debugger;

			for ( name in o ) {
				f = 'val';
				if ( typeof o[name] === 'boolean' ) {
					cb = $('input[name="'+name+'"]'); 
					if ( o[name] ) {
						cb.attr('checked', 'checked')
					} else {
						cb.removeAttr('checked');
					}
					
				} else {
					var radios = $('input[name="'+name+'"]').filter('[type="radio"]').removeAttr('checked').length;
					if ( radios ) {
						$('input[name="'+name+'"]').filter('input[value="'+o[name]+'"]').attr('checked', 'checked');
					} else {
						$('input[name="'+name+'"], textarea[name="'+name+'"], select[name="'+name+'"]').not('[type="radio"]').val(o[name]);
						$('input[name="'+name+'"]').filter('[type="hidden"]').change();
					}
					
				}
				
			}

			refreshMDO();

			if ( typeof callback === 'function' ) {
				callback();
			}

		}).fail(function(t, status, message) {
			//console.log(arguments);
		});			
	}


	// initiating tabs if present on the page	
	$('#tabs').tabs({
		 select: function(event, ui) {
		 	tryRefreshVars($(ui.tab).attr('href'));
		 }
	});

	var href =$('#tabs > ul li.ui-tabs-selected a').attr('href');
	tryRefreshVars(href);


	$('.btn-load-mail-settings').click(function(e){
		loadMailSettings( $(this).attr('preset') );
	});

	$('#btn-uninstall-now').click(function(e){
		e.preventDefault();
		saveOptions();

		showModal('#newsman-modal-uninstall', function(mr){
			if ( mr === 'ok' ) {
				$.ajax({
					url: ajaxurl,
					type: 'post',
					data: {
						action: 'newsmanAjRunUninstall'
					}
				}).done(function(data){
					showMessage(data.msg, 'success');
					setTimeout(function() {
						if ( data.redirect ) {
							window.location = data.redirect;
						}						
					}, 500);
				}).fail(function(xhr){
					var data = JSON.parse(xhr.responseText);
					showMessage(data.msg, 'error');
				});
			}
		});
	});


	/*******	  Modal Dialogs 	**********/

	var mrOk, mrCancel, mrCallback;

	function showModal(id, opts) {

		opts = opts || {};

		if ( typeof opts == 'function' ) {
			var resultCallback = opts;
			opts = { result: resultCallback };
		}

		mrCallback = function(modalResult) {			
			var res = opts.result.call($(id), modalResult);
			if ( res ) {
				mrCallback = null;
			}
			return res;
		};

		if ( opts.show ) {
			opts.show.call($(id));
		}

		$(id).modal({ show: true, keyboard: true });
	}

	$('.modal.dlg .btn, .modal.dlg .tpl-btn').click(function(e){		

		var mr = $(this).attr('mr');

		if ( mr && mrCallback ) {
			if ( mrCallback(mr) !== false ) {
				$('.modal.dlg').modal('hide');
			}
		} else if ( mr ) {
			$('.modal.dlg').modal('hide');
		}
	});

	function statusToText(st, bounceStatus) {

		if ( bounceStatus ) {
			bounceStatus = '<div><span class="label label-important">'+bounceStatus+'</span></div>';
		} else {
			bounceStatus = '';
		}

		st += '';
		switch ( st ) {
			case '0': return '<span class="newsmanColorUNC">'+newsmanL10n.unconfirmed+'</span>';
			case '1': return '<span class="newsmanColorCON">'+newsmanL10n.confirmed+'</span>';
			case '2': return '<span class="newsmanColorUNS">'+newsmanL10n.unsubscribed+'</span>'+bounceStatus;
			default: return 'UNKNOWN';
		}
	}

	/*******	  Post selector iframe 	**********/

	if ( $('#post-selector').get(0) ) {
		(function(){
			var ul = $('#newsman-posts').empty();

			postsSelector = {};

			var paging = {
				page: 1,
				ipp: 15
			};

			function countSelectedPosts() {
				var cnt = $('.newsman-bcst-post.active').length,
					p = ( cnt == 1 ) ? 'post' : 'posts',
					lbl = 'No posts selected';

				if ( cnt ) { 
					lbl = cnt+' '+p+' selected';
				}
				$('#posts-counter').text(lbl);
			}

			postsSelector.getIDS = function(){
				var ids = [];
				$('.newsman-bcst-post.active input').each(function(i, el){
					ids.push(parseInt($(el).val(), 10));
				});
				return ids;
			};

			postsSelector.clearSelection = function(){
				$('.newsman-bcst-post.active').removeClass('active');
			};			



			function search() {
				paging.page = 1;
				refreshPosts();
			}


			var iv;

			$('#newsman-search').keyup(function(){
				if (iv) {
					clearTimeout(iv);
				}
				iv = setTimeout(function() {
					search();
				}, 800);
			});


			$('.newsman-bcst-post').live('click', function(){
				$(this).toggleClass('active');
				countSelectedPosts();
			});


			$('#newsman-bcst-sel-cat').multiselect({
				selectedText: "# of # categories selected",
				noneSelectedText: 'Select categories',
				selectedList: 4
			});
			$('#newsman-bcst-sel-auth').multiselect({
				selectedText: "# of # authors selected",
				noneSelectedText: 'Select author(s)',
				selectedList: 4
			});	

			function getSelectedCats() {
				var cats = $('#newsman-bcst-sel-cat').val();
				return (cats || []).join(',');
			}

			function getSelectedAuthors() {
				var auths = $('#newsman-bcst-sel-auth').val();
				return (auths || []).join(',');
			}

			function loadMore() {
				$('h3', this).text('Loading...');
				paging.page += 1;
				refreshPosts('append');
			}

			$('#newsman-bcst-posts .newsman-bcst-load-button').live('click', loadMore);

			function refreshPosts(append) {
				append = append == 'append';
				var ccats = getSelectedCats(),
					cauths = getSelectedAuthors(),
					limit, q, d, s;

					s = $('#newsman-search').val();


				d = {
					action: "newsmanAjGetPosts",
					includePrivate: $('#newsman-bcst-include-private').is(':checked') ? 1 : 0					
				};

				$.extend(d, paging);

				if ( s ) {
					d.search = s;
				} else {
					$.extend(d, {
						cats: ccats,
						auths: cauths,
					});
				}


				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: d
				}).done(function(data){
					$('#newsman-bcst-posts .stub-wrap').remove();

					if ( data && data.posts ) {
						var container = $('#newsman-bcst-posts');

						if ( !append ) {
							container.empty();
						}

						$('.newsman-bcst-load-button', container).remove();

						if ( data.posts.length ) {
							$(data.posts).each(function(i, p){
								$(['<div class="newsman-bcst-post">',
									'<div class="newsman-bcst-post-hdr">',
										'<input type="hidden" value="'+p.id+'">',
										'<span class="newsman-date">'+p.date+'</span>',
										'<span class="newsman-title">'+p.title+'</span>',
									'</div>',
									'<span>'+p.description+'</span>',
								'</div>'].join('')).appendTo(container);
							});

							if ( data.posts.length >= paging.ipp ) {
								// adding "load more" button
								$([
								'<div class="newsman-bcst-load-button">',
									'<h3>Load more...</h3>',
								'</div>'
								].join('')).appendTo(container);								
							}

						} else {
							$('<div class="stub-wrap"><div class="stub">Sorry, no posts matched your criteria.</div></div>').appendTo(container);
						}

						countSelectedPosts();

					}
				});
			}

			$('#newsman-bcst-sel-auth, #newsman-bcst-sel-cat').change(refreshPosts);
			$('#newsman-bcst-include-private').click(refreshPosts);

			loadOptions(function(){
				refreshPosts();
			});			

			$('#newsman_btn_bcst_opts_submit').click(function(e){
				//updateBroadcastOptions();
				saveOptions(function(opts){

					opts.broadcast.query = {
						cats: getSelectedCats(),
						authors: getSelectedAuthors(),
						date: $('#query_date').val()
					};

					//return false;
				});
				e.preventDefault();
			});			

		})();
		
	}

	// move to separate file

	$.widget('newsman.importForm', {
		options: {
			list: null, 
			formPanel: null,
			delimiter: ',',
			skipFirstRow: false,			
			messages: {
				selectFile: 'Please select a file to import.',
				loading: 'Loading...'
			}
		},
		_create: function(){
			var that = this;
			if ( !this.options.list || !this.options.formPanel ) {
				throw new Error('[newsman.importForm] list and formPanel element must be defined');
			}

			var cbSkip = $('#skip-first-row');
			cbSkip.get(0).checked = this.options.skipFirstRow;
			cbSkip.change(function(e){
				that.options.skipFirstRow = $(this).is(':checked');

				var tbody = $('tbody', this.mappingTable);
				tbody[ that.options.skipFirstRow ? 'addClass' : 'removeClass' ]('skip-first-row');
			});

			$('#import-delimiter')
				.val(this.options.delimiter)
				.change(function(){
					that.options.delimiter = $(this).val();
					that._buildTable();
				});

			this.formFields = {};

			this.list = $(this.options.list);
			this.form = $(this.options.formPanel+' form');
			this.mappingTable = $('table', this.form);
			this.info = $(this.options.formPanel+' .import-form-info');

			this.showInfo('selectFile');
		},
		showInfo: function(type) {
			this.info.html(this.options.messages[type] || 'Error');
		},
		_fileSelected: function(fileName) {
			this.selectedFile = fileName;
			this.showInfo('loading');
			this.loadFile(fileName);
		},
		_removeFile: function(filename, done) {
			var that = this;
			done = done || function(){};

			$.ajax({
				url: ajaxurl,
				data: {
					action: 'newsmanAjRemoveImportedFile',
					fileName: filename
				}
			}).done(function(data){				
				showMessage(data.msg, 'success');
				done();
				that.reset();
			}).fail(function(xhr){
				var r = xhr.responseText;
				showMessage(r, 'error');
			});
		},
		reset: function() {
			this.showInfo('selectFile');
			this.info.show();
			this.form.hide();			
		},
		getImportOptions: function() {
			var fields = {};

			$('.map-select', this.mappingTable).each(function(i, sel){
				sel = $(sel);
				var v = sel.val();
				if ( v !== 'null' ) {
					var n = /map-col-(\d+)/.exec(sel.attr('name'))[1];				
					fields[n] = sel.val();					
				}
			});

			return {
				fileName: this.selectedFile,
				delimiter: this.options.delimiter,
				skipFirstRow: this.options.skipFirstRow,
				fields: fields
			};
		},
		setFormFields: function(formFields) {
			this.formFields = formFields;
		},
		_buildMapFieldSelect: function(colNum, val) {
			var sel =[
				'<select class="map-select" name="map-col-'+colNum+'" id="map-col-'+colNum+'">',
					'<option value="null"></option>'];

			for ( var n in this.formFields ) {
				if ( n ) {
					var rxStr = this.formFields[n].replace(/\W+/, '.*'),
						rx = new RegExp(rxStr, 'i'),
						selected = rx.exec(val) ? ' selected="selected"' : '';

					if ( selected ) {
						// with a greate probability we found a header
						$('#skip-first-row').get(0).checked = true;
						$('#skip-first-row').change();
					}

					sel.push('<option value="'+n+'"'+selected+'>'+this.formFields[n]+'</option>');	
				}				
			}

			sel.push('</select>');
						
			return sel.join('');
		},

		_regexQuote: function(str) {
			return str.replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}=!<>\|\:])/g, function(s, p1){
				return '\\'+p1;
			});
		},
		_buildTable: function() {
			var that = this;
			var h = this.currentHeader,
				thead = $('thead', this.mappingTable).empty(),
				tbody = $('tbody', this.mappingTable).empty();

			tbody[ this.options.skipFirstRow ? 'addClass' : 'removeClass' ]('skip-first-row');

			var rows = [];

			var quoteChar = '"',
				delimiter = that.options.delimiter;

			$(h).each(function(r, line){
				line = $.trim(line);
				var c = '', inQuote = false, row = [];

				for (var i = 0; i < line.length; i++) {
					if ( line[i] == delimiter && !inQuote ) {
						row.push(c);
						c = '';
					} else if ( line[i] === quoteChar ) {
						if ( inQuote ) {
							// if properly escaped quoteChar
							if ( line[i-1] === quoteChar ) {
								c += line[i];
							} else if ( line[i+1] === delimiter ) {
								inQuote = false;
							}
							// else - skip the char
						} else {
							inQuote = true;
						}
					} else {
						c += line[i];
					}
				}
				row.push(c);

				rows.push(row);
			});

			var headRow = $('<tr></tr>').appendTo(thead);

			$(rows[0]).each(function(i, col){
				$('<th>'+that._buildMapFieldSelect(i, col)+'</th>').appendTo(headRow);
			});

			$(rows).each(function(i, row){
				var tr = $('<tr></tr>').appendTo(tbody);
				$(row).each(function(j, col){
					$('<td>'+col+'</td>').appendTo(tr);
				});
			});

		},
		loadFile: function(fileName) {
			var that = this;

			$.ajax({
				url: ajaxurl,
				type: 'post',
				data: {
					action: 'newsmanAjGetCSVFields',
					filename: fileName
				}
			}).done(function(data){
				that.info.hide();
				that.form.show();

				that.currentHeader = data.header;
				that._buildTable();

			}).fail(function(t){
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});

		},
		addFile: function(obj) {
			var that = this;
			var li = $([
				'<li class="active" fileid="'+obj.id+'">',
					'<a href="#">',
						'<span class="filename">'+obj.fileName+'</span>',
						'<div class="progress"><div class="bar" style="width: 0%;"></div></div>',
						'<i class="icon-remove-sign"></i>',
					'</a>',
				'<li>'
			].join('')).appendTo( this.list );

			li.find('a').click(function(e){
				e.preventDefault();
				that._fileSelected(obj.fileName);
			});

			li.find('i').click(function(e){
				e.preventDefault();
				e.stopPropagation();
				if ( li.find('.upload-error').get(0) ) {
					li.remove();
					that.reset();
				} else {
					that._removeFile(obj.fileName, function(){
						// successfully deleted
						li.remove();
					});
				}
			});			
		},
		addUploadedFile: function(obj) {
			var that = this;
			var li = $([
				'<li>',
					'<a href="#">',
						'<span class="filename">'+obj.fileName+'</span>',
						'<i class="icon-remove-sign"></i>',
					'</a>',
				'<li>'
			].join('')).appendTo( this.list );

			li.find('a').click(function(e){
				e.preventDefault();
				that._fileSelected(obj.fileName);
			});

			li.find('i').click(function(e){
				e.preventDefault();
				e.stopPropagation();
				if ( li.find('.upload-error').get(0) ) {
					li.remove();
					that.reset();
				} else {
					that._removeFile(obj.fileName, function(){
						// successfully deleted
						li.remove();
					});
				}
			});
		},
		uploadDone: function(obj) {
			var bar = $('.neo-upload-list [fileid="'+obj.id+'"] .bar');
			bar.css({
				width: '100%'
			});	 			
			var li = bar.closest('li');
			li.removeClass('active');
			setTimeout(function() {
				li.find('.progress').remove();
				li.find('span').text(obj.actualFileName);
			}, 200);
		},
		uploadError: function(obj) {
			var li = $('.neo-upload-list [fileid="'+obj.id+'"]');
			li.find('.progress').remove();
			var err = $('<span class="label label-important" title="'+obj.reason+'"><i class="icon-warning-sign icon-white"></i> Error</span>');
			var wrap = $('<div class="upload-error" style="margin-top: 4px;"></div>');
			err.appendTo(wrap);
			wrap.appendTo(li.find('a'));
			$(err).tooltip({ placement: 'right' });
			
		},
		uploadProgress: function(obj) {
			var bar = $('.neo-upload-list [fileid="'+obj.id+'"] .bar').get(0);
			bar.style.width = obj.percents+'%';
		}
	});


	/*******	 Manage Subscribers 	**********/

	if ( $('#newsman-mgr-subscribers').get(0) ) {

		var pageState = {
			pg: 1,
			ipp: 25,
			show: 'all'
		};

		function search() {
			var q = $('#newsman-subs-search').val();
			
			if ( q ) {
				pageState.q = q;
				pageState.pg = 1;
				$('#newsman-subs-search-clear').show();
			} else {
				delete pageState.q;
				pageState.pg = 1;
				$('#newsman-subs-search-clear').hide();
			}
			getSubscribers();
		}

		$('#subs-search-form').bind('submit', function(e){
			e.preventDefault();
			return false;
		});

		$('#newsman-subs-search').keypress(function(e){
			if ( e.keyCode === 13 ) {
				search();
				e.preventDefault();	
			}			
		})

		$('#newsman-subs-search-clear').click(function(e){				
			e.preventDefault();
			$('#newsman-subs-search').val('');
			$('#newsman-subs-search-clear').hide();
			delete pageState.q;
			getSubscribers();
		});

		$('#newsman-subs-search-btn').click(function(e){
			e.preventDefault();
			search();
		});	


		var ST_UNCONFIRMED = 0,
			ST_CONFIRMED = 1,
			ST_UNSUBSCRIBED = 2;

		var statusMap = {
			'0': newsmanL10n.unconfirmed,
			'1': newsmanL10n.confirmed,
			'2': newsmanL10n.unsubscribed
		}

		var iform = $('<div></div>').importForm({
			list: '#import-files-list',
			formPanel: '#file-import-settings'
		});

		// 

		var uploader = $('<div></div>').neoFileUploader({
			debug: true,
			action: NEWSMAN_PLUGIN_URL+'/upload.php',
			button: $('#newsman-modal-import .qq-upload-button').get(0),
			onAdd: function(e, obj) {	 			
				iform.importForm('addFile', obj)
			},
			onDone: function(e, obj) {
				iform.importForm('uploadDone', obj);
			},
			onError: function(e, obj) {
				iform.importForm('uploadError', obj);
			},
			onProgress: function(e, obj) {
				iform.importForm('uploadProgress', obj);
			}
		});

		$('#newsman-btn-export').click(function(){
			window.location = NEWSMAN_PLUGIN_URL+'/export_list.php?listId=' + ($('#newsman-lists').val() || '1')+'&type='+pageState.show;
		});

		$('#newsman-btn-import').click(function(){

			function getFields() {
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjGetFormFields',
						id: $('#newsman-lists').val() || '1'
					}
				}).done(function(data){
					iform.importForm('setFormFields', data.fields);

				}).fail(function(xhr){
					showMessage(newsmanL10n.error+xhr.responseText);
				});
			}


			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'newsmanAjGetUploadedFiles'
				}
			}).done(function(data){

				getFields();

				var list = $('.neo-upload-list').empty().get(0);

				$(data.files).each(function(i, file){
					iform.importForm('addUploadedFile', { fileName: file });
				});
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});

			var sel = $('#newsman-lists').get(0);

			if ( sel ) {
				$('#import-list-name').text( sel.options[sel.selectedIndex].innerText );	
			} else {
				$('#import-list-name').text('');
			}
			
			iform.importForm('reset');
			showModal('#newsman-modal-import', function(mr){
				if ( mr == 'ok' ) {

					showLoading('Importing...');

					var params = iform.importForm('getImportOptions');

					params.listId = $('#newsman-lists').val() || '1';
					
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'newsmanAjImportFiles',
							params: JSON.stringify(params)
						}
					}).done(function(data){

						showMessage(data.msg, 'success');

						getSubscribers();

					}).fail(function(t, status, message) {
						var data = JSON.parse(t.responseText);
						showMessage(data.msg, 'error');
					}).always(function(){
						hideLoading();
					});
				}
				return true;
			});
		});

		$('#newsman-btn-bulk-unsubscribe').click(function(){
			$('#cb-uns-from-all').get(0).checked = false;
			showModal('#newsman-modal-bulk-unsubscribe', function(mr){
				if ( mr == 'ok' ) {

					var emails = $('#newsman-unsubscribe-list').val();

					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {					
							action: 'newsmanAjBulkUnsubscribe',
							emails: emails,
							allLists: $('#cb-uns-from-all').is(':checked') ? '1' : '0',
							listId: $('#newsman-lists').val() || '1'
						}
					}).done(function(data){
						showMessage(data.msg, 'success');

						$('#newsman-unsubscribe-list').val('');

						getSubscribers();

					}).fail(function(t, status, message) {
						var data = JSON.parse(t.responseText);
						showMessage(data.msg, 'error');
					});
				}
				return true;
			});
		});

		// Unsubscribe & Delete modal windows

		if ( typeof NEWSMANEXT !== 'undefined' ) {
			NEWSMANEXT.initListsSelect(getSubscribers, showMessage, showModal);
		}

		// -----------	

		// check all checkbox
		$('#newsman-checkall').change(function(e){
			if ( $(this).is(':checked') ) {
				$('#newsman-mgr-subscribers tbody input').attr('checked', 'checked');
			} else {
				$('#newsman-mgr-subscribers tbody input').removeAttr('checked');
			}
		});

		// unsubscribe button
		$('#newsman-btn-unsubscribe').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubsWhichYouWantToUnsub);
			} else {
				showModal('#newsman-modal-unsubscribe', function(mr){
					if ( mr === 'ok' || mr === 'all' ) {

						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								all: (mr==='all') ? 1 : 0,
								action: 'newsmanAjUnsubscribe',
								listId: $('#newsman-lists').val() || '1'
							}
						}).done(function(data){

							showMessage(newsmanL10n.youHaveSuccessfullyUnsubscribedSelectedSubs, 'success');
							getSubscribers();

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						});
						
					}
					return true;
				});				
			}
		});

		$('#newsman-btn-delete').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubsWhichYouWantToDelete);
			} else {
				showModal('#newsman-modal-delete', function(mr){
					if ( mr === 'ok' || mr === 'all' ) {

						var type = pageState.show;

						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								all: ( mr === 'all' ) ? 1 : 0,
								listId: $('#newsman-lists').val() || '1', 
								type: type,
								action: 'newsmanAjDeleteSubscribers'
							}
						}).done(function(data){

							showMessage(newsmanL10n.youHaveSucessfullyDeletedSelSubs, 'success');

							getSubscribers();

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						});						
					}
					return true;
				});				
			}
		});

		$('#newsman-btn-chToSubscribed').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubscribersToChange);
			} else {
				$('#newsman-modal-chstatus .newsman-status').text(statusMap[ST_UNCONFIRMED+'']);

				showModal('#newsman-modal-chstatus', function(mr){
					if ( mr === 'ok' || mr === 'all' ) {
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								listId: $('#newsman-lists').val() || '1',
								all: (mr === 'all') ? 1 : 0,
								action: 'newsmanAjSetStatus',
								status: ST_UNCONFIRMED
							}
						}).done(function(data){

							showMessage(newsmanL10n.youHaveSuccessfullyChangedSelSubs, 'success');

							getSubscribers();

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						});
					}
					return true;
				});
			}
		});

		$('#newsman-btn-chToConfirmed').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubscribersToChange);
			} else {
				$('#newsman-modal-chstatus .newsman-status').text(statusMap[ST_CONFIRMED+'']);

				showModal('#newsman-modal-chstatus', function(mr){
					if ( mr === 'ok' || mr === 'all' ) {
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								action: 'newsmanAjSetStatus',
								all: ( mr === 'all' ) ? 1 : 0,
								status: ST_CONFIRMED,
								listId: $('#newsman-lists').val() || '1'
							}
						}).done(function(data){

							showMessage(newsmanL10n.youHaveSuccessfullyChangedSelSubs, 'success');

							getSubscribers();

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						});
					}
					return true;
				});
			}
		});		

		$('#newsman-btn-reconfirm').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubsToSendConfirmationTo);
			} else {
				showModal('#newsman-modal-reconfirm', function(mr){
					if ( mr == 'ok' || mr == 'to_all' ) {

						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								all: (mr === 'to_all') ? '1' : '0',
								action: 'newsmanAjResendConfirmation',
								listId: $('#newsman-lists').val() || '1'
							}
						}).done(function(data){

							showMessage(newsmanL10n.youHaveSuccessfullySentConfirmation, 'success');

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						});
						return true;
					} else {
						return true;
					}
				});

			}
		});

	 	var email = $('#newsman-email-search').val();

	 	function getSubscribers() {
	 		var q = $.extend({}, pageState);
	 		q.action = 'newsmanAjGetSubscribers';
	 		q.listId = $('#newsman-lists').val() || '1';

	 		$('#newsman-checkall').removeAttr('checked');

	 		var x32 = '1ug';

	 		showLoading();

	 		function get95pcnt() {
	 			var x = parseInt(x32, 32);
	 			return Math.ceil(x*0.95);
	 		}

	 		function getx() {
	 			return parseInt(x32, 32);
	 		}

	 		function warn(msg) {
	 			$('#newsman-limit-alert').remove();
	 			$('<div id="newsman-limit-alert" class="alert">'+msg+'</div>').insertBefore($('#newsman-mgr-subscribers'));
	 		}

	 		function fillCounters(cnt) {

	 			if ( typeof NEWSMAN_LITE_MODE !== 'undefined' && NEWSMAN_LITE_MODE ) {
		 			if ( cnt.all >= get95pcnt() && cnt.all < getx() ) {
		 				warn('Warning! You are close to the limit of '+getx()+' subscribers you can send emails to in the Lite version of the plugin. Please, <a href="">upgrade to the Pro version</a> to send emails without limitations.');
		 			} else if ( cnt.all >= getx() ) {
		 				warn('Warning! You exceeded the limit of '+getx()+' subscribers you can send emails to in the Lite version of the plugin. Please, <a href="">upgrade to the Pro version</a> to send emails without limitations.');
		 			}
	 			}


	 			$('#newsman-subs-all').text(newsmanL10n.allSubs.replace('#', cnt.all));
	 			$('#newsman-subs-confirmed').text(newsmanL10n.confirmedSubs.replace('#', cnt.confirmed));
	 			$('#newsman-subs-unconfirmed').text(newsmanL10n.unconfirmedSubs.replace('#', cnt.unconfirmed));
	 			$('#newsman-subs-unsubscribed').text(newsmanL10n.unsubscribedSubs.replace('#', cnt.unsubscribed));
	 		}

	 		function noData() {
	 			var tbody = $('#newsman-mgr-subscribers tbody').empty();
	 			$('<tr><td colspan="6" class="blank-row">'+newsmanL10n.noSubsYet+'</td></tr>').appendTo(tbody);
	 		}

	 		function showLoading() {
	 			var tbody = $('#newsman-mgr-subscribers tbody').empty();
	 			$('<tr><td colspan="6" class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> Loading...</td></tr>').appendTo(tbody);	 			
	 		}

	 		function renderButtons(start, num, current, count) {
	 			var cl ,el = $('.pagination ul').empty();	

	 			if ( count < 2 ) {
					$('.pagination').hide();
	 			} else {
	 				$('.pagination').show();
	 			}

	 			var end = start+num-1;

	 			end = end > count ? count : end;

	 			// prev button
	 			if ( current > 1 ) {
	 				$('<li><a href="#/'+pageState.show+'/'+(current-1)+'">«</a></li>').appendTo(el);
	 			}

	 			for (var i = start; i <= end; i++) {
	 				cl = ( i === current ) ? 'class="active"' : '';
	 				$('<li '+cl+'><a href="#/'+pageState.show+'/'+i+'">'+i+'</a></li>').appendTo(el);
	 			}

	 			if ( current < count ) {
	 				$('<li><a href="#/'+pageState.show+'/'+(current+1)+'">»</a></li>').appendTo(el);
	 			}
	 		}

	 		function renderPagination(cntData) {
	 			var cnt =  cntData[pageState.show],
	 				pages = Math.ceil( cnt / pageState.ipp ),
	 				buttonsNum = 4,
	 				btnPages = Math.ceil( pageState.pg / buttonsNum );

	 			if ( pages < 5 ) {
	 				renderButtons(1, buttonsNum, pageState.pg, pages);
	 			} else {
	 				var start = ( (btnPages-1) * buttonsNum ) + 1;
	 				renderButtons(start, buttonsNum, pageState.pg, pages);
	 			}			
	 		}

	 		function fieldsToHTML(obj) {
	 			var html = ['<ul class="unstyled">'];
	 			for ( var name in obj ) {
	 				html.push('<li>'+name+': '+obj[name]+'</li>');
	 			}
	 			html.push('</ul>');
	 			return html.join('');
	 		}

			function fillRows(rows) {
				var tbody = $('#newsman-mgr-subscribers tbody').empty();
				if (rows.length) {
					$(rows).each(function(i, r){
						$(['<tr>',
								'<td><input value="'+r.id+'" type="checkbox"></td>',
								'<td>'+r.email+'</td>',
								'<td>'+r.ts+'</td>',
								'<td>'+r.ip+'</td>',
								'<td>'+statusToText(r.status, r.status == 2 ? r.bounceStatus : '')+'</td>',
								'<td>'+fieldsToHTML(r.fields)+'</td>',
							'</tr>'].join('')).appendTo(tbody);
					});	 				
				} else {
					noData();
				}
			}

			 // ---------------------------------

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: q
			}).done(function(data){

				fillCounters(data.count);
				fillRows(data.rows);
				renderPagination(data.count);

			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});	 		
	 	}


	 	function r(type, p) {
			type = type || 'all';
			p = p || 1;
			pageState.pg = parseInt(p, 10);
			pageState.show = type.toLowerCase();
			getSubscribers();

			if ( type == 'unconfirmed' ) {
				$('#newsman-btn-reconfirm').show();
			} else {
				$('#newsman-btn-reconfirm').hide();
			}

			$('.subsubsub a.current').removeClass('current');
			$('#newsman-subs-'+type).addClass('current');	

	 	}

		var router = new Router({
			'/:type/:p': r,
			'/:type': r
		});

		router.init('/all/1');	 	
	}


	/*******	 Manage Options 	**********/

	if ( $('#newsman-page-options').get(0) ) {

		// $('#btn-update-general-options').click(function(e){
		// 	var sform = serializeForm();
		// 	$('#newsman-form-json').val(sform);
		// });

		function setTextContent(el, val) {
			for (var i = 0; i < el.childNodes.length; i++) {
				if (el.childNodes[i].nodeType == 3) {
					el.childNodes[i].textContent = val;
					break;
				}
			}
		}

		function refreshMDO() {
			var mdo = $('.newsman-mdo:checked').val();					
			$('#newsman-smtp-settings')[ mdo === 'smtp' ? 'show' : 'hide' ]();
		}
		$('.newsman-mdo').click(refreshMDO);
		refreshMDO();



		function saveOptions(cb) {
			var opts = {};
			var o = {};
			$('.wrap input[type="checkbox"]').each(function(i, el){
				var $el = $(el),
					n = $el.attr('name');

				if ( n && n.match(/^newsman\-/) ) {
					o[n] = $el.is(':checked');
				}
			});
			$([
				'.wrap select',
				'.wrap input[type="radio"]:checked',
				'.wrap input[type="hidden"]',
				'.wrap input[type="text"]',
				'.wrap input[type="password"]',
				'.wrap input[type="email"]',
				'.wrap textarea'
			  ].join(',')).each(function(i, el){
				var $el = $(el),
					n = $el.attr('name'),
					v = $el.val();
				if ( n && n.match(/^newsman\-/) ) {
					v = ( $.isNumeric(v) ) ? parseInt(v, 10) : v
					o[n] = v;	
				}				
			});
			delete o['newsman-email'];

			function walkAndSet(origin, path, value) {
				var o = origin, v, pa = path.split('-');

				pa.shift(); // removing "newsman" part;

				while ( pa.length ) {
					v = pa.shift();
					if ( typeof o[v] == 'undefined' ) {
						o[v] = {};						
					}

					if ( pa.length === 0 ) {
						o[v] = value;
					} else {
						o = o[v];	
					}					
				}
			}

			for ( var p in o ) {
				walkAndSet(opts, p, o[p]);	
			}

			if ( typeof cb == 'function' ) {
				if ( cb(opts) === false ) {
					return;
				}
			}			

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					options: JSON.stringify(opts),
					action: 'newsmanAjSetOptions'
				}
			}).done(function(data){
				var type = data.state ? 'success' : 'error';
				showMessage(data.msg, type);
			}).fail(function(t, status, message) {
				showMessage(message, 'error');
			});
		}


		loadOptions();

		// mail delivery settings
		$('#smtp-btn-test-ph').click(function(){
			var btn = $(this),
				txt = btn.text(),
				email = $(this).closest('.control-group').find('input').val();

			var q = {
				'host': $('#newsman_smtp_hostname').val(),
				'user': $('#newsman_smtp_username').val(),
				'pass': $('#newsman_smtp_password').val(),
				'port': $('#newsman_smtp_port').val(),
				'email': email,
				'secure': $('#newsman_smtp_secure_conn .radio input:checked').val(),
				'mdo': $('.newsman-mdo:checked').val()
			};

			q.action = 'newsmanAjTestMailer';

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: q,
				beforeSend: function() {
					btn.text(newsmanL10n.sending).attr("disabled", "disabled");
				}
			}).done(function(data){
				var type = data.state ? 'success' : 'error';
				showMessage(data.msg, 'success');
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			}).always(function(){
				btn.text(txt).removeAttr('disabled');
			});
		});


		$('button.newsman-update-options').click(function(){
			saveOptions();
		});
	}

	/*******	 Edit List & Form Options 	**********/

	if ( $('#newsman-page-list').get(0) ) {

		$('.newsman-form-builder').newsmanFormBuilder();

		function safeName(str) {
			return str.replace(/\W+$/ig, '').replace(/\W+/ig, '-').toLowerCase();
		}

		function loadData() {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'newsmanAjGetListSettings',
					id: NEWSMAN_LIST_ID
				}
			}).done(function(data){

				for ( var p in data.list ) {
					$('#newsman_form_'+p).val(data.list[p]);
				}

			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});	 

		}

		loadData();

		function saveList() {

			var data = {},
				form = $('#newsman_form_g');

			$('#newsman_form_json').val( $('.newsman-form-builder').newsmanFormBuilder('serializeForm') );

			$('input, textarea, select', form).each(function(i, el){
				var name = $(el).attr('name'),
					res, rx = /^newsman-form-(.*)/;
				if ( res = rx.exec(name) ) {
					data[ res[1] ] = $(el).val();
				}
			});

			data.action = 'newsmanAjSetListSettings';
			data.id = NEWSMAN_LIST_ID;

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data
			}).done(function(data){

				showMessage(data.msg, 'success');

			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});	 


		}

		$('#newsman-save-list').click(saveList);

		function insertAtCursor(myField, myValue) {
			//IE support
			if (document.selection) {
				var temp;
				myField.focus();
				sel = document.selection.createRange();
				temp = sel.text.length;
				sel.text = myValue;
				if (myValue.length == 0) {
					sel.moveStart('character', myValue.length);
					sel.moveEnd('character', myValue.length);
				} else {
					sel.moveStart('character', -myValue.length + temp);
				}
				sel.select();
			}
			//MOZILLA/NETSCAPE support
			else if (myField.selectionStart || myField.selectionStart == '0') {
				var startPos = myField.selectionStart;
				var endPos = myField.selectionEnd;
				myField.value = myField.value.substring(0, startPos) + myValue + myField.value.substring(endPos, myField.value.length);
				myField.selectionStart = startPos + myValue.length;
				myField.selectionEnd = startPos + myValue.length;
			} else {
				myField.value += myValue;
			}
		}		

		$('#newsman_form_extcss').keydown(function(e){
			if ( e.keyCode === 9 ) {				
				insertAtCursor(this, '	');
				e.preventDefault();
			}
		});

	}

	/*******	 Compose Email Page 	**********/

	if ( $('#newsman-page-compose').get(0) ) {

		var editor = CKEDITOR.replace( 'content', {
			width: 'auto',
			height: 300
		});

		function changed(){
			saveEmail();
		}

		if ( typeof NEWSMAN_ENT_STATUS !== 'undefined' && ( NEWSMAN_ENT_STATUS === 'stopped' || NEWSMAN_ENT_STATUS === 'error' ) ) {
			$('#newsman-page-compose input[name="newsman-send"]').change(function(e){
				$('#newsman-send').text( ( $(this).val() === 'now' ) ? 'Resume' : 'Send');
			});			
		}


		editor.on("instanceReady", function(){
			var t = null;
			this.document.on("keyup", function(){
				if ( t ) {
					clearTimeout(t);
				}
				t = setTimeout(function() {
					t = null;
					changed();
				}, 500);
			});
		});

		editor.on('mode', function(){
			changed();
		});

		$('#newsman-email-subj').change(function(){
			changed();
		});

		function sendEmail() {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: "newsmanAjScheduleEmail",
					id: NEWSMAN_ENTITY_ID,
					send: $('input[name="newsman-send"]:checked').val(),
					ts: Math.round( $('#newsman-send-datepicker').datetimepicker('getDate') / 1000 )
				}
			}).done(function(data){
				showMessage(data.msg, 'success', function(){
					if ( data.redirect ) {
						window.location = data.redirect;
					}					
				});
			}).fail(function(t) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});			

		}

		function saveEmail(done) {
			done = done || function(){};
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					id: NEWSMAN_ENTITY_ID,
					action: 'newsmanAjSavePlainEmail',
					to: $('#eml-to').multis('getItems')+'',
					subj: $('#newsman-email-subj').val(),
					plain: editor.document ? editor.document.getBody().$.innerText : '',
					html: editor.getData(),
					send: $('input[name="newsman-send"]:checked').val(),
					ts: Math.round( $('#newsman-send-datepicker').datetimepicker('getDate') / 1000 )					
				}
			}).done(function(data){
				if ( data.id ) {
					NEWSMAN_ENTITY_ID = data.id;
				}
				done();
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});
		}

		$('#newsman-send-datepicker').datetimepicker({
			dateFormat: 'DD, d MM, yy'
			// altFormat: "yy-mm-dd",
			// altField: '#newsman-bcst-start-date-sql'
		});

		var dtVal = $('#newsman-send-datepicker').val()-0,
			startDate = dtVal ? new Date(dtVal) : new Date();

		$('#newsman-send-datepicker').datetimepicker('setDate', startDate);		

		$('#newsman-send').click(function(){
			editor.setMode('wysiwyg');
			sendEmail();
		});
	}

	/*******	 Manage Mailbox 	**********/

	if ( $('#newsman-mailbox').get(0) ) {
		(function(){

			//var email = $('#newsman-email-search').val();
			var pageState = {
				show: 'all',
				pg: 1,
				ipp: 15
			};

			function search() {
				var q = $('#newsman-email-search').val();
				
				if ( q ) {
					pageState.q = q;
					pageState.pg = 1;
					$('#newsman-email-search-clear').show();
				} else {
					delete pageState.q;
					pageState.pg = 1;
					$('#newsman-email-search-clear').hide();
				}
				
				getEmails();
			}

			$('#newsman-email-search-form').submit(function(e){
				e.preventDefault();
				return false;
			});

			$('#newsman-email-search').keypress(function(e){
				if ( e.keyCode === 13 ) {
					search();
					e.preventDefault();	
				}
			});

			$('#newsman-email-search-clear').click(function(e){				
				e.preventDefault();
				$('#newsman-email-search').val('');
				$('#newsman-email-search-clear').hide();
				delete pageState.q;
				getEmails();
			});

			$('#newsman-email-search-btn').click(function(e){
				e.preventDefault();
				search();
			});			


			$('#newsman-checkall').change(function(e){
				if ( $(this).is(':checked') ) {
					$('#newsman-mailbox tbody input').attr('checked', 'checked');
				} else {
					$('#newsman-mailbox tbody input').removeAttr('checked');
				}
			});

			function showSendingLog(emailId) {
				showLoading();
				var b = $('#newsman-modal-errorlog .modal-body').empty();

				$('<div><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> Loading...</div>').appendTo(b);

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjGetErrorLog',
						emailId: emailId
					}
				}).done(function(data){

					$('#newsman-modal-errorlog .modal-body').empty();

					$('<h3 style="margin-bottom: 1em;">Sent '+data.sent+' of '+data.recipients+' emails</h3>').appendTo(b);

					if ( data.msg ) {
						$('<h3 style="margin-bottom: 1em;">Status: '+data.msg+'</h3>').appendTo(b);	
					}					

					if ( data.errors.length ) {
						$('<h3 style="margin-bottom: .5em;">Email Errors</h3>').appendTo(b);

						function getList(err) {
							var id = 'newsman-errlog-'+err.listId,
								tb = $('#'+id+' tbody');

							if ( tb.get(0) ) {
								return tb;
							} else {
								$('<h4 style="margin-bottom: .5em;">List: '+err.listName+'</h4>').appendTo(b);
								var tbl = $([
									'<table id="'+id+'" class="table table-striped table-bordered">',
										'<thead>',
											'<tr>',
												'<th>Email Address</th>',
												'<th>Error Message</th>',
											'</tr>',
										'</thead>',
										'<tbody>',
										'</tbody>',
									'</table>',
								].join('')).appendTo(b);
								return $('tbody',tbl);
							}
						}

						$(data.errors).each(function(i, err){
							var tbl = getList(err);
							$('<tr><td>'+err.email+'</td><td>'+err.statusMsg+'</td></tr>').appendTo(tbl);

						});						
					}


					hideLoading();
					showModal('#newsman-modal-errorlog', function(mr){
						return true;
					});

				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});					
			}

			var refreshTimer = null;

			function runPolling() {
				if ( !refreshTimer ) {
					setTimeout(function() {
						refreshTimer = null;
						getUpdates();
					}, 5000);
				}
			}

			function getUpdates() {
				// newsman-eml-'+r.id+'-msg
				var q = $.extend({}, pageState);
				q.action = 'newsmanAjGetEmails';				
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: q
				}).done(function(data){

					fillCounters(data.count);
					renderPagination(data.count);

					$(data.rows).each(function(i, r){
						var d = new Date( parseInt(r.schedule,10)*1000 ),
							fd = d.toLocaleString().replace(/\s+GMT.*/, '');

						$('#newsman-eml-'+r.id+'-status').empty().append(formatStatus(r.status, fd));
						$('#newsman-eml-'+r.id+'-msg').html(formatMsg(r));
					});

					runPolling();

				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});	 				
			}

			function formatStatus(st, date) {
				date = date || '';
				var el;

				switch ( st ) {
					case 'sent': el = '<span class="newsman-tbl-emails-status"><i class="icon-ok"></i> '+newsmanL10n.stSent+'</span>'; break;
					case 'pending': el = '<span class="newsman-tbl-emails-status"><i class="icon-time"></i> '+newsmanL10n.stPending+'</span>'; break;
					case 'draft': el = '<span><i class="icon-edit"></i> '+newsmanL10n.stDraft+'</span>'; break;
					case 'inprogress': el = '<span class="newsman-tbl-emails-status"><i class="icon-play"></i> '+newsmanL10n.stInprogress+'</span>'; break;
					case 'stopped': el = '<span class="newsman-tbl-emails-status"><i class="icon-stop"></i> '+newsmanL10n.stStopped+'</span>'; break;
					case 'error': el = '<span class="newsman-tbl-emails-status"><i class="icon-warning-sign"></i> '+newsmanL10n.stError+'</span>'; break;
					case 'scheduled': el = '<span class="newsman-tbl-emails-status"><i class="icon-time"></i> '+newsmanL10n.stScheduledOn+' '+date+'</span>'; break;
				}

				el = $(el);

				if ( st !== 'draft' ) {
					$(el).click(function(e){
						e.preventDefault();
						var emailId = $(this).closest('tr').find('input').val();					
						showSendingLog(emailId);
					});	
				}

				return el;
			}

			function formatMsg(data) {
				if ( data.msg ) {
					return data.msg;
				} else {
					if ( data.recipients > 0 )	{
						return 'Sent '+data.sent+' of '+data.recipients+' emails';
					} else {
						return '';
					}
				}				
			}


			function fillCounters(cnt) {
				$('#newsman-mailbox-all').text('All emails ('+cnt.all+')');
				$('#newsman-mailbox-inprogress').text('In progress ('+cnt.inprogress+')');
				$('#newsman-mailbox-drafts').text('Drafts ('+cnt.drafts+')');					
				$('#newsman-mailbox-pending').text('Pending ('+cnt.pending+')');
				$('#newsman-mailbox-sent').text('Sent ('+cnt.sent+')');
			}		

			function renderButtons(start, num, current, count) {
				var cl ,el = $('.pagination ul').empty();	

				if ( count < 2 ) {
				$('.pagination').hide();
				} else {
					$('.pagination').show();
				}

				var end = start+num-1;

				end = end > count ? count : end;

				// prev button
				if ( current > 1 ) {
					$('<li><a href="#/'+pageState.show+'/'+(current-1)+'">«</a></li>').appendTo(el);
				}

				for (var i = start; i <= end; i++) {
					cl = ( i === current ) ? 'class="active"' : '';
					$('<li '+cl+'><a href="#/'+pageState.show+'/'+i+'">'+i+'</a></li>').appendTo(el);
				}

				if ( current < count ) {
					$('<li><a href="#/'+pageState.show+'/'+(current+1)+'">»</a></li>').appendTo(el);
				}
			}

			function renderPagination(cntData) {
				var cnt =  cntData[pageState.show],
					pages = Math.ceil( cnt / pageState.ipp ),
					buttonsNum = 4,
					btnPages = Math.ceil( pageState.pg / buttonsNum );

				if ( pages < 5 ) {
					renderButtons(1, buttonsNum, pageState.pg, pages);
				} else {
					var start = ( (btnPages-1) * buttonsNum ) + 1;
					renderButtons(start, buttonsNum, pageState.pg, pages);
				}
			}	

			function getEmails() {
				var q = $.extend({}, pageState);
				q.action = 'newsmanAjGetEmails';

				$('#newsman-checkall').removeAttr('checked');

				showLoading();



				function noData() {
					var tbody = $('#newsman-mailbox tbody').empty();
					$('<tr><td colspan="6" class="blank-row">'+newsmanL10n.youHaveNoEmailsYet+'</td></tr>').appendTo(tbody);
				}

		 		function showLoading() {
		 			var tbody = $('#newsman-mgr-subscribers tbody').empty();
		 			$('<tr><td colspan="6" class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> Loading...</td></tr>').appendTo(tbody);	 			
		 		}				

				function fieldsToHTML(obj) {
					var html = ['<ul class="unstyled">'];
					for ( var name in obj ) {
						html.push('<li>'+name+': '+obj[name]+'</li>');
					}
					html.push('</ul>');
					return html.join('');
				}

				function formatTo(to) {
					var arr = [];
					try {
						arr = JSON.parse(to);
					} catch(e) {
					}

					for (var i = 0; i < arr.length; i++) {
						arr[i] = '<span class="label label-info">'+arr[i]+'</span>';
					}

					return arr.join('');	 			
				}


				function fillRows(rows) {
					var tbody = $('#newsman-mailbox tbody').empty();
					if (rows.length) {
						$(rows).each(function(i, r){

							var st = r.status,
								d = new Date( parseInt(r.schedule,10)*1000 );	

							//var fdate = $.datepicker.formatDate('D, d M y', d) +' '+$.datepicker.formatTime('hh:mm:ss', d);
							fdate = d.toLocaleString().replace(/\s+GMT.*/, '');

							function formatTS(uts) {
								return (new Date(uts*1000)).toLocaleString().replace(/\s+GMT.*/, '');
							}

							$(['<tr>',
									'<td><input value="'+r.id+'" type="checkbox"></td>',
									'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=edit&type='+r.editor+'&id='+r.id+'">'+(r.subject || 'No subject')+'</a></td>',
									'<td>'+formatTo(r.to)+'</td>',
									'<td>'+formatTS(r.created)+'</td>',
									'<td id="newsman-eml-'+r.id+'-status"></td>',
									'<td id="newsman-eml-'+r.id+'-msg">'+formatMsg(r)+'</td>',
								'</tr>'].join('')).appendTo(tbody);

							$('#newsman-eml-'+r.id+'-status').append(formatStatus(st, fdate));
						});	 				
					} else {
						noData();
					}
				}

			 	// ---------------------------------

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: q
				}).done(function(data){

					fillCounters(data.count);
					fillRows(data.rows);
					renderPagination(data.count);

					runPolling();

				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});	 		
			}


			function r(type, p) {
				type = type || 'all';
				p = p || 1;
				pageState.pg = parseInt(p, 10);
				pageState.show = type.toLowerCase();
				getEmails();

				$('.subsubsub a.current').removeClass('current');
				$('#newsman-mailbox-'+type).addClass('current');	
			}

			var router = new Router({
				'/:type/:p': r,
				'/:type': r
			});

			router.init('/all/1');	

			// --- ui elements

			$('#newsman-btn-stop').click(function(e){
				var ids = [];
				$('#newsman-mailbox tbody input:checked').each(function(i, el){
					ids.push( parseInt($(el).val(), 10) );
				});
				if ( ids.length === 0 ) {
					showMessage(newsmanL10n.pleaseSelectEmailsToStop);
				}
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjStopSending',
						ids: ids+'' 
					}
				}).done(function(data){
					showMessage(newsmanL10n.youHaveSuccessfullyStoppedSending, 'success');
					getEmails();
				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});
			});

			$('#newsman-btn-resume').click(function(e){
				var ids = [];
				$('#newsman-mailbox tbody input:checked').each(function(i, el){
					ids.push( parseInt($(el).val(), 10) );
				});
				if ( ids.length === 0 ) {
					showMessage(newsmanL10n.pleaseSelectEmailsFist);
				}
				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjResumeSending',
						ids: ids+'' 
					}
				}).done(function(data){
					showMessage(data.msg, 'success');
					getEmails();
				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});
			});

			$('#btn-compose').click(function(e){
				e.preventDefault();

				var tbody = $('#dlg-templates-tbl').empty();

				$('<tr><td class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> Loading...</td></tr>').appendTo(tbody);

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjGetTemplates',
						type: 'user'
					}
				}).done(function(data){

					tbody.empty();
					if ( data.rows.length ) {
						$(data.rows).each(function(i, row){
							var url = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=compose-from-tpl&id='+row.id;
							$('<tr><td><a href="'+url+'">'+row.name+'</a></td></tr>').appendTo(tbody);
						});						
					} else {
						var url = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-templates#/newtemplate'
						$('<tr><td style="text-align: center;">'+newsmanL10n.youDontHaveAnyTemplates+' <a href="'+url+'">'+newsmanL10n.createOne+'</a></td></tr>').appendTo(tbody)
					}

					//fillCounters(data.count);
					//fillRows(data.rows);
					// renderPagination(data.count);

				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});	 				

				showModal('#newsman-modal-compose', function(mr){
					return true;
				});
			});

			$('#newsman-btn-delete').click(function(e){
				var ids = [];
				$('#newsman-mailbox tbody input:checked').each(function(i, el){
					ids.push( parseInt($(el).val(), 10) );
				});

				if ( !ids.length ) {
					showMessage(newsmanL10n.pleaseMarkEmailsForDeletion);
				} else {
					showModal('#newsman-modal-delete', function(mr){
						if ( mr === 'ok' || mr === 'all' ) {							
							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									ids: ids+'',
									all: ( mr === 'all' ) ? 1 : 0,
									action: 'newsmanAjDeleteEmails'
								}
							}).done(function(data){

								showMessage(newsmanL10n.youHaveSuccessfullyDeletedSelectedEmails, 'success');
								getEmails();

							}).fail(function(t, status, message) {
								var data = JSON.parse(t.responseText);
								showMessage(data.msg, 'error');
							});
						}

					});
				}
			});			

			$('#basic-tpls .tpl-btn').click(function(e){				
				var type = $(this).attr('tplname');

				showLoading('Creating email...');

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {						
						action: "newsmanAjCreateEmailFromBasicTemplate",
						type: type
					}
				}).done(function(data){
					if ( data.id ) {
						window.location = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=edit&type=html&id='+data.id;
					} else {
						getEmails();
					}
				}).always(function(){
					hideLoading();
				});


			});

		})();
	}

	/*******	 Manage Templates 	**********/

	if ( $('#newsman-templates').get(0) ) {
		(function(){

			//var email = $('#newsman-email-search').val();
			var pageState = {
				show: 'all',
				pg: 1,
				ipp: 15
			};

			function newTemplate(name, type) {
				var q = {
					action: 'newsmanAjCreateEmailTemplate',
					name: name,
					type: type
				};

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: q
				}).done(function(data){

					if ( data.id ) {
						window.location = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates&action=edit&id='+data.id;
					} else {
						showMessage('Some error occured. temaplte id is '+data.id, 'error');
					}

				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});	 					
			}

			function showNewTplDialog() {
				r();
				showModal('#newsman-modal-new-template', {

					show: function() {
						$('input', this).val('');
						$('.control-group', this).removeClass('gerror');
						$('.help-inline', this).hide();
						$('.tpl-type-error', this).hide();
					},

					result: function(mr){

						var abtn = $('.tpl-btn.active', this),
							type = abtn.attr('tplname'),
							name = $('input', this).val();

						abtn.removeClass('active');

						if ( mr == 'ok' ) {

							name = name.replace(/^\s/, '').replace(/\s$/, '');

							var err = false;

							if ( !name.length ) {
								err = true;
								$('.control-group', this).addClass('gerror');
								$('.help-inline', this).show();
							} else {
								$('.control-group', this).removeClass('gerror');
								$('.help-inline', this).hide();								
							}

							if ( !type ) {
								err = true;
								$('.tpl-type-error', this).show();
							} else {
								$('.tpl-type-error', this).hide();
							}

							if ( !err ) {
								newTemplate(name, type);
							} else {
								return false;
							}
							
						} else {
							window.location.href= '#/all/1';
						}
						return true;
					}
				});				
			}


			$('#newsman-modal-new-template .tpl-btn').click(function(e){
				e.preventDefault();
				$('#newsman-modal-new-template .tpl-btn').removeClass('active');
				$(this).addClass('active');
			});


			$('#btn-new-tpl').click(function(e){
				e.preventDefault();
				showNewTplDialog();
			});

			$('#btn-delete-tpls').click(function(e){
				e.preventDefault();
				// ajDeleteEmailTemplates				
				var ids = [];
				$('#newsman-templates tbody input:checked').each(function(i, el){
					ids.push( parseInt($(el).val(), 10) );
				});

				if ( !ids.length ) {
					showMessage(newsmanL10n.pleaseMarkTemplatesForDeletion);
				} else {
					showModal('#newsman-modal-delete', function(mr){
						if ( mr === 'ok' || mr === 'all' ) {

							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									ids: ids+'',
									all: ( mr === 'all' ) ? 1 : 0,
									action: 'newsmanAjDeleteEmailTemplates'
								}
							}).done(function(data){

								showMessage(data.msg, 'success');

								getTemplates();

							}).fail(function(t, status, message) {
								var data = JSON.parse(t.responseText);
								showMessage(data.msg, 'error');
							});							
						}
						return true;
					});				
				}				
			});


			function getTemplates() {
				var q = $.extend({}, pageState);
				q.action = 'newsmanAjGetTemplates';

				function fillCounters(cnt) {
					$('#newsman-mailbox-all').text(newsmanL10n.emlsAll.replace('#', cnt.all));
					$('#newsman-mailbox-inprogress').text(newsmanL10n.emlsInProgress.replace('#', cnt.inprogress ));
					$('#newsman-mailbox-pending').text(newsmanL10n.emlsPending.replace('#', cnt.pending ));
					$('#newsman-mailbox-sent').text(newsmanL10n.emlsSent.replace('#', cnt.sent ));
				}

				function noData() {
					var tbody = $('#newsman-mailbox tbody').empty();
					$('<tr><td colspan="5" class="blank-row">'+newsmanL10n.youHaveNoTemplatesYet+'</td></tr>').appendTo(tbody);
				}

				function renderButtons(start, num, current, count) {
					var cl ,el = $('.pagination ul').empty();	

					if ( count < 2 ) {
					$('.pagination').hide();
					} else {
						$('.pagination').show();
					}

					var end = start+num-1;

					end = end > count ? count : end;

					// prev button
					if ( current > 1 ) {
						$('<li><a href="#/'+pageState.show+'/'+(current-1)+'">«</a></li>').appendTo(el);
					}

					for (var i = start; i <= end; i++) {
						cl = ( i === current ) ? 'class="active"' : '';
						$('<li '+cl+'><a href="#/'+pageState.show+'/'+i+'">'+i+'</a></li>').appendTo(el);
					}

					if ( current < count ) {
						$('<li><a href="#/'+pageState.show+'/'+(current+1)+'">»</a></li>').appendTo(el);
					}
				}

				function renderPagination(cntData) {
					var cnt =  cntData[pageState.show],
						pages = Math.ceil( cnt / pageState.ipp ),
						buttonsNum = 4,
						btnPages = Math.ceil( pageState.pg / buttonsNum );

					if ( pages < 5 ) {
						renderButtons(1, buttonsNum, pageState.pg, pages);
					} else {
						var start = ( (btnPages-1) * buttonsNum ) + 1;
						renderButtons(start, buttonsNum, pageState.pg, pages);
					}			
				}

				function fieldsToHTML(obj) {
					var html = ['<ul class="unstyled">'];
					for ( var name in obj ) {
						html.push('<li>'+name+': '+obj[name]+'</li>');
					}
					html.push('</ul>');
					return html.join('');
				}



				function fillRows(rows) {
					var tbody = $('#newsman-templates tbody').empty();
					if (rows.length) {
						$(rows).each(function(i, r){
							var icon = r.system ? '<i class="icon-cog" rel="tooltip" title="System Template. Cannot be deleted."></i>' : '';
							$(['<tr>',
									'<td><input value="'+r.id+'" type="checkbox"></td>',
									'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates&action=edit&id='+r.id+'">'+icon+' '+r.name+'</a></td>',
								'</tr>'].join('')).appendTo(tbody);
						});	 				
					} else {
						noData();
					}
				}

			 	// ---------------------------------

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: q
				}).done(function(data){

					//fillCounters(data.count);
					fillRows(data.rows);
					// renderPagination(data.count);

				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				});	 		
			}


			function r(type, p) {
				type = type || 'all';
				p = p || 1;

				pageState.pg = parseInt(p, 10);
				pageState.show = type.toLowerCase();
				getTemplates();

				$('.subsubsub a.current').removeClass('current');
				$('#newsman-mailbox-'+type).addClass('current');	



			}

			var router = new Router({
				'/newtemplate': showNewTplDialog,
				'/:type/:p': r,
				'/:type': r				
			});

			router.init('/all/1');	


		})();
	}

	/*******	 HTML editor 	**********/

	if ( $('#newsman-html-editor').get(0) ) {

		if ( typeof NEWSMAN_ENT_STATUS !== 'undefined' && ( NEWSMAN_ENT_STATUS === 'stopped' || NEWSMAN_ENT_STATUS === 'error' ) ) {
			$('#newsman-html-editor input[name="newsman-send"]').change(function(e){
				$('#newsman-btn-send').text( ( $(this).val() === 'now' ) ? 'Resume' : 'Send');
			});			
		}		

		/***
		 * Pacth for dialog-fix ckeditor problem [ by ticket #4727 ]
		 * 	http://dev.jqueryui.com/ticket/4727
		 */

		$.extend($.ui.dialog.overlay, { create: function(dialog){
			if (this.instances.length === 0) {
				// prevent use of anchors and inputs
				// we use a setTimeout in case the overlay is created from an
				// event that we're going to be cancelling (see #2804)
				setTimeout(function() {
					// handle $(el).dialog().dialog('close') (see #4065)
					if ($.ui.dialog.overlay.instances.length) {
						$(document).bind($.ui.dialog.overlay.events, function(event) {
							var parentDialog = $(event.target).parents('.ui-dialog');
							if (parentDialog.length > 0) {
								var parentDialogZIndex = parentDialog.css('zIndex') || 0;
								return parentDialogZIndex > $.ui.dialog.overlay.maxZ;
							}
							
							var aboveOverlay = false;
							$(event.target).parents().each(function() {
								var currentZ = $(this).css('zIndex') || 0;
								if (currentZ > $.ui.dialog.overlay.maxZ) {
									aboveOverlay = true;
									return;
								}
							});
							
							return aboveOverlay;
						});
					}
				}, 1);
				
				// allow closing by pressing the escape key
				$(document).bind('keydown.dialog-overlay', function(event) {
					(dialog.options.closeOnEscape && event.keyCode
							&& event.keyCode == $.ui.keyCode.ESCAPE && dialog.close(event));
				});
					
				// handle window resize
				$(window).bind('resize.dialog-overlay', $.ui.dialog.overlay.resize);
			}
			
			var $el = $('<div></div>').appendTo(document.body)
				.addClass('ui-widget-overlay').css({
				width: this.width(),
				height: this.height()
			});
			
			(dialog.options.stackfix && $.fn.stackfix && $el.stackfix());
			
			this.instances.push($el);
			return $el;
		}});			

		$('#newsman-btn-send').click(function(e){

			var toList = $('#eml-to').multis('getItems');
			if ( !toList.length ) {
				showMessage(newsmanL10n.pleaseChooseSubscribersList);
				return;
			}

			var opt = $('#newsman-send-form input[name="newsman-send"]:checked').val();
			var datetime = $('#newsman-send-datepicker').datetimepicker('getDate');

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: "newsmanAjScheduleEmail",
					id: NEWSMAN_ENTITY_ID,
					send: opt,
					ts: Math.round(datetime.getTime() / 1000)
				}
			}).done(function(data){
				showMessage(data.msg, 'success', function(){
					if ( data.redirect ) {
						window.location = data.redirect;
					}					
				});
			}).fail(function(t) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});
		});

		function setPostTemaplateType(callback) {

			var newType, et = $('#newsman-content-type').val(),
				etMap = {
					'content': 'post_content',
					'excerpt': 'post_excerpt',
					'fancy': 'fancy_excerpt'
				};
				newType = etMap[et] || 'post_content';

			$.ajax({
				url: ajaxurl,
				data: {
					action: 'newsmanAjSetPostTemplateType',
					entType: NEWSMAN_ENT_TYPE,
					entity: NEWSMAN_ENTITY_ID,
					postTemplateType: newType
				}
			}).done(function(e){
				callback(null);
			}).fail(function(xhr){
				callback( new Error(xhr.responseText) );
			});
		}


		$('#btn-add-posts').click(function(e){
			//newsman-modal-add-posts

			showModal('#newsman-modal-add-posts', function(mr){
				
				if ( mr == 'cancel' ) {
					// 
				} else if ( mr == 'insert' ) {

					showLoading();

					// first we update the post temaplte with the type of 
					// content which we want to insert
					setPostTemaplateType(function(err){
						if ( err ) { console.error(err); }

						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								pids: postsSelector.getIDS()+'',
								entType: NEWSMAN_ENT_TYPE,
								entity: NEWSMAN_ENTITY_ID,
								action: 'newsmanAjCompilePostsBlock'
							}						
						}).done(function(data){

							var idoc = $('#tpl-frame').get(0).contentDocument;
							$('[gsspecial="posts"]', idoc).html(data.content);

							$('#tpl-frame').get(0).newsmanInit();

							postsSelector.clearSelection();

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						}).always(function(){
							hideLoading();
						});
					});					

				}
				return true;
			});
		});

		/**
		 *		Editing temaplte particles
		 */

		var currentParticleName = '';

		function getParticleSource() {
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					name: currentParticleName,
					entType: NEWSMAN_ENT_TYPE,
					entity: NEWSMAN_ENTITY_ID,
					action: 'newsmanAjGetEntityParticle'
				}
			}).done(function(data){
				var html = data.particle;

				$('#dialog').editorDialog({
					edSelector: '.source-editor',
					save: function(ev, data) {
						saveParticleSource(data.html);
						$('#dialog').editorDialog('close');
					}
				}).editorDialog('setData', html)
				  .editorDialog('open');				
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});			
		}

		function saveParticleSource(newContent) {

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					name: currentParticleName,
					entType: NEWSMAN_ENT_TYPE,
					entity: NEWSMAN_ENTITY_ID,
					content: newContent,
					action: 'newsmanAjSetEntityParticle'
				}
			}).done(function(data){
				showMessage(data.msg, 'success');
			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});
		}

		$('#btn-edit-postblock').click(function(e){

			currentParticleName = 'post_block';
			getParticleSource();

		});

		$('#btn-edit-post-divider').click(function(e){
			currentParticleName = 'post_divider';
			getParticleSource();
		});

		$('#btn-save-content').click(function(){
			saveParticleSource();
		});		


		// Code to fix iframe redraw bug. not fully fixed, but at least something
		$(window).load(function(){
			var t;
			$($('#tpl-frame').contents()).scroll(function(e){
				if ( t ) {
					clearTimeout(t);
					t = null;
				}				
				t = setTimeout(function() {
					$('#tpl-frame').css({ height: '701px' });
					setTimeout(function(){
						$('#tpl-frame').css({ height: '700px' });
					}, 1);
				}, 100);
			});
		});
	}

	/* Common functionality */

	$('#eml-to').multis({
		preserveClasses: true,
		width: null,
		getData: function(enteredText, done) {

			if ( typeof NEWSMAN_LISTS !== 'undefined' ) {
				done(NEWSMAN_LISTS);
				return;
			}

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'newsmanAjGetLists'
				}
			}).done(function(data){

				var lists = [];

				$(data.lists).each(function(i, list){
					lists.push(list.name);
				});

				done(lists);

			}).fail(function(t, status, message) {
				var data = JSON.parse(t.responseText);
				showMessage(data.msg, 'error');
			});	 						
		}
	});

	$('#eml-to').change(function(){

		if ( !NEWSMAN_ENTITY_ID ) { return; }

		var items = $('#eml-to').multis('getItems');

		if ( !items.length ) {
			items = '';
		}

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'newsmanAjSetEmailData',
				id: NEWSMAN_ENTITY_ID,
				key: 'to',
				value: items
			}
		}).fail(function(t, status, message) {
			var data = JSON.parse(t.responseText);
			showMessage(data.msg, 'error');
		});
	});	


	$('#btn-send-test-email').click(function(){
		if ( editor ) {
			editor.setMode('wysiwyg');
		}
		showModal('#newsman-modal-send-test', function(mr){
			var that = this;
			if ( mr == 'ok' ) {
				$('.modal-loading-block', this).show();
				$('.btn[mr="ok"]', this).attr('disabled', 'disabled');

				if (window.NEWSMAN_ENT_TYPE === 'undefined') {
					window.NEWSMAN_ENT_TYPE = 'email';
				}

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						entType: NEWSMAN_ENT_TYPE,
						entity: NEWSMAN_ENTITY_ID,
						toEmail: $('#test-email-addr').val(),
						action: 'newsmanAjSendTestEmail'
					}
				}).done(function(data){
					showMessage(data.msg, 'success');
				}).fail(function(t, status, message) {
					var data = JSON.parse(t.responseText);
					showMessage(data.msg, 'error');
				}).always(function(){
					$('.modal-loading-block', that).hide();
					$('.btn[mr="ok"]', that).removeAttr('disabled');
				});		

			} else {
				return true;	
			}				
		});
	});	

});