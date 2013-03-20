var NEWSMAN_HTML_TO_TEXT = (function(){
	/*
	Copyright (C) 2006 Google Inc.

	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at

	     http://www.apache.org/licenses/LICENSE-2.0

	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.

	HTML decoding functionality provided by: http://code.google.com/p/google-trekker/
	*/


	function htmlToText(html, extensions) {
	    var text = html;

	    if (extensions && extensions['preprocessing'])
	        text = extensions['preprocessing'](text);

	    text = text
	        // Remove line breaks
	        .replace(/(?:\n|\r\n|\r)/ig, " ")
	        // Remove content in script tags.
	        .replace(/<\s*script[^>]*>[\s\S]*?<\/script>/mig, "")
	        // Remove content in style tags.
	        .replace(/<\s*style[^>]*>[\s\S]*?<\/style>/mig, "")
	        // Remove content in comments.
	        .replace(/<!--.*?-->/mig, "")
	        // Remove !DOCTYPE
	        .replace(/<!DOCTYPE.*?>/ig, "");

	    /* I scanned http://en.wikipedia.org/wiki/HTML_element for all html tags.
	    I put those tags that should affect plain text formatting in two categories:
	    those that should be replaced with two newlines and those that should be
	    replaced with one newline. */

	    if (extensions && extensions['tagreplacement'])
	        text = extensions['tagreplacement'](text);

	    var doubleNewlineTags = ['p', 'h[1-6]', 'dl', 'dt', 'dd', 'ol', 'ul',
	        'dir', 'address', 'blockquote', 'center', 'div', 'hr', 'pre', 'form',
	        'textarea', 'table'];

	    var singleNewlineTags = ['li', 'del', 'ins', 'fieldset', 'legend',
	        'tr', 'th', 'caption', 'thead', 'tbody', 'tfoot'];

	    for (i = 0; i < doubleNewlineTags.length; i++) {
	        var r = RegExp('</?\\s*' + doubleNewlineTags[i] + '[^>]*>', 'ig');
	        text = text.replace(r, '\n\n');
	    }

	    for (i = 0; i < singleNewlineTags.length; i++) {
	        var r = RegExp('<\\s*' + singleNewlineTags[i] + '[^>]*>', 'ig');
	        text = text.replace(r, '\n');
	    }

	    // Replace <br> and <br/> with a single newline
	    text = text.replace(/<\s*br[^>]*\/?\s*>/ig, '\n');

		text = text
	        // Remove all remaining tags.
	        .replace(/(<([^>]+)>)/ig,"")
	        // Trim rightmost whitespaces for all lines
	        .replace(/([^\n\S]+)\n/g,"\n")
	        .replace(/([^\n\S]+)$/,"")
	        // Make sure there are never more than two
	        // consecutive linebreaks.
	        .replace(/\n{2,}/g,"\n\n")
	        // Remove newlines at the beginning of the text.
	        .replace(/^\n+/,"")
	        // Remove newlines at the end of the text.
	        .replace(/\n+$/,"")
	        // Decode HTML entities.
	        .replace(/&([^;]+);/g, decodeHtmlEntity);

	    if (extensions && extensions['postprocessing'])
	        text = extensions['postprocessing'](text);

	    return text;
	}

	function decodeHtmlEntity(m, n) {
		// Determine the character code of the entity. Range is 0 to 65535
		// (characters in JavaScript are Unicode, and entities can represent
		// Unicode characters).
		var code;

		// Try to parse as numeric entity. This is done before named entities for
		// speed because associative array lookup in many JavaScript implementations
		// is a linear search.
		if (n.substr(0, 1) == '#') {
			// Try to parse as numeric entity
			if (n.substr(1, 1) == 'x') {
				// Try to parse as hexadecimal
				code = parseInt(n.substr(2), 16);
			} else {
				// Try to parse as decimal
				code = parseInt(n.substr(1), 10);
			}
		} else {
			// Try to parse as named entity
			code = ENTITIES_MAP[n];
		}

		// If still nothing, pass entity through
		return (code === undefined || code === NaN) ?
			'&' + n + ';' : String.fromCharCode(code);
	}

	var ENTITIES_MAP = {
	  'nbsp' : 160,
	  'iexcl' : 161,
	  'cent' : 162,
	  'pound' : 163,
	  'curren' : 164,
	  'yen' : 165,
	  'brvbar' : 166,
	  'sect' : 167,
	  'uml' : 168,
	  'copy' : 169,
	  'ordf' : 170,
	  'laquo' : 171,
	  'not' : 172,
	  'shy' : 173,
	  'reg' : 174,
	  'macr' : 175,
	  'deg' : 176,
	  'plusmn' : 177,
	  'sup2' : 178,
	  'sup3' : 179,
	  'acute' : 180,
	  'micro' : 181,
	  'para' : 182,
	  'middot' : 183,
	  'cedil' : 184,
	  'sup1' : 185,
	  'ordm' : 186,
	  'raquo' : 187,
	  'frac14' : 188,
	  'frac12' : 189,
	  'frac34' : 190,
	  'iquest' : 191,
	  'Agrave' : 192,
	  'Aacute' : 193,
	  'Acirc' : 194,
	  'Atilde' : 195,
	  'Auml' : 196,
	  'Aring' : 197,
	  'AElig' : 198,
	  'Ccedil' : 199,
	  'Egrave' : 200,
	  'Eacute' : 201,
	  'Ecirc' : 202,
	  'Euml' : 203,
	  'Igrave' : 204,
	  'Iacute' : 205,
	  'Icirc' : 206,
	  'Iuml' : 207,
	  'ETH' : 208,
	  'Ntilde' : 209,
	  'Ograve' : 210,
	  'Oacute' : 211,
	  'Ocirc' : 212,
	  'Otilde' : 213,
	  'Ouml' : 214,
	  'times' : 215,
	  'Oslash' : 216,
	  'Ugrave' : 217,
	  'Uacute' : 218,
	  'Ucirc' : 219,
	  'Uuml' : 220,
	  'Yacute' : 221,
	  'THORN' : 222,
	  'szlig' : 223,
	  'agrave' : 224,
	  'aacute' : 225,
	  'acirc' : 226,
	  'atilde' : 227,
	  'auml' : 228,
	  'aring' : 229,
	  'aelig' : 230,
	  'ccedil' : 231,
	  'egrave' : 232,
	  'eacute' : 233,
	  'ecirc' : 234,
	  'euml' : 235,
	  'igrave' : 236,
	  'iacute' : 237,
	  'icirc' : 238,
	  'iuml' : 239,
	  'eth' : 240,
	  'ntilde' : 241,
	  'ograve' : 242,
	  'oacute' : 243,
	  'ocirc' : 244,
	  'otilde' : 245,
	  'ouml' : 246,
	  'divide' : 247,
	  'oslash' : 248,
	  'ugrave' : 249,
	  'uacute' : 250,
	  'ucirc' : 251,
	  'uuml' : 252,
	  'yacute' : 253,
	  'thorn' : 254,
	  'yuml' : 255,
	  'quot' : 34,
	  'amp' : 38,
	  'lt' : 60,
	  'gt' : 62,
	  'OElig' : 338,
	  'oelig' : 339,
	  'Scaron' : 352,
	  'scaron' : 353,
	  'Yuml' : 376,
	  'circ' : 710,
	  'tilde' : 732,
	  'ensp' : 8194,
	  'emsp' : 8195,
	  'thinsp' : 8201,
	  'zwnj' : 8204,
	  'zwj' : 8205,
	  'lrm' : 8206,
	  'rlm' : 8207,
	  'ndash' : 8211,
	  'mdash' : 8212,
	  'lsquo' : 8216,
	  'rsquo' : 8217,
	  'sbquo' : 8218,
	  'ldquo' : 8220,
	  'rdquo' : 8221,
	  'bdquo' : 8222,
	  'dagger' : 8224,
	  'Dagger' : 8225,
	  'permil' : 8240,
	  'lsaquo' : 8249,
	  'rsaquo' : 8250,
	  'euro' : 8364
	};

	return {
		convert: htmlToText		
	};

}());

jQuery(function($){

	/******* 	Pagination widget 	********/

	$.widget('newsman.newsmanPagination', {
		options: {
			pageCount: 10,
			currentPage: 1,
			pageButtons: 5,
			showPrevNext: true
		},
		_create: function() {
			var o = this.options,
				ul = $('ul', this.element);
				ul.empty();

			this.element[ o.pageCount <= 1 ? 'hide' : 'show' ]();

			if ( !ul[0] ) {
				ul = $('<ul></ul>').appendTo(this.element);
			}

			if ( !this.element.hasClass('pagination') ) {
				this.element.addClass('pagination');
			}

			if ( o.showPrevNext ) {
				$('<li><a class="newsman-pagination-prev" href="#">&laquo;</a></li>').appendTo(ul)
			}

			if ( o.showPrevNext ) {
				$('<li><a class="newsman-pagination-next" href="#">&raquo;</a></li>').appendTo(ul)
			}

			this._buildPageButtons();			

		},
		_buildPageButtons: function(silent) {
			var html = [];
			this._unbind();

			silent = silent || false;

			$('.newsman-pagination-page', this.element).remove();

			var o = this.options,
				btnNum = ( o.pageCount < o.pageButtons ) ? o.pageCount : o.pageButtons;

			for (var i = 1; i <= btnNum; i++) {
				html.push('<li><a class="newsman-pagination-page" href="#">&nbsp;</a></li>');
			}

			html = html.join('');

			var prevBtn = $('.newsman-pagination-prev', this.element).closest('li')[0],
				ul = ('ul', this.element);

			if ( prevBtn ) {
				$(html).insertAfter(prevBtn);
			} else {
				$(html).appendTo(ul);
			}

			this._showPageNumbers();
			this._bind();
			this._setActivePageButton(silent);
		},
		_unbind: function(){
			$('a', this.element).unbind('click');
		},
		_bind: function() {
			var that = this;
			this.btnPrev = $('.newsman-pagination-prev', this.element).click(function(e){
				e.preventDefault();
				if ( !$(this).closest('li').hasClass('disabled') ) {
					that.prev();	
				}				
			});

			this.btnNext = $('.newsman-pagination-next', this.element).click(function(e){
				e.preventDefault();
				if ( !$(this).closest('li').hasClass('disabled') ) {
					that.next();
				}
			});

			$('.newsman-pagination-page', this.element).click(function(e){
				e.preventDefault();
				var p = $(this).attr('page')-0;

				if ( p ) {
					// fire page change event here
					that.options.currentPage = p;
					that._setActivePageButton();					
				}
			});
		},
		_setActivePageButton: function(silent) {
			$('.active', this.element).removeClass('active');
			$('.newsman-pagination-page[page="'+this.options.currentPage+'"]', this.element).closest('li').addClass('active');
			$('.newsman-pagination-prev', this.element).closest('li').toggleClass('disabled', this.options.currentPage <= 1);
			$('.newsman-pagination-next', this.element).closest('li').toggleClass('disabled', this.options.currentPage >= this.options.pageCount);
			if ( !silent ) {
				this._trigger('pageChanged', {}, { page: this.options.currentPage });	
			}			
		},
		/**
		 * This function calculates numbers for page buttons
		 */
		_showPageNumbers: function() {
			var o = this.options;

			// calculate what batch( page of page buttons ) to render
			var batch = Math.ceil(o.currentPage / o.pageButtons);

			var startPage = ((batch-1) * o.pageButtons) + 1; // page number to start with

			$('.newsman-pagination-page', this.element).each(function(i, el){

				var n = startPage+i;
				if (n > o.pageCount) {
					$(el).html('&nbsp;').attr('page', '');
				} else {
					$(el).text(n).attr('page', n);
				}
				
			});
		},
		next: function() {
			this.options.currentPage += 1;
			this._showPageNumbers();
			this._setActivePageButton();

		},
		prev: function() {
			this.options.currentPage -= 1;
			this._showPageNumbers();
			this._setActivePageButton();
		},
		setPage: function(page) {
			this.options.currentPage = page;
			this._showPageNumbers();
			this._setActivePageButton(true);
		},
		setPageCount: function(pageCount) {
			this.options.pageCount = pageCount;

			this.element[ pageCount <= 1 ? 'hide' : 'show' ]();

			this._buildPageButtons(false);
		}
	});	

	// php comaptible sprintf function for l10n capabilities
	// taken from http://phpjs.org/functions/sprintf/
	function sprintf () {
		var regex = /%%|%(\d+\$)?([-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g;
		var a = arguments,
		i = 0,
		format = a[i++];

		// pad()
		var pad = function (str, len, chr, leftJustify) {
		if (!chr) {
		  chr = ' ';
		}
		var padding = (str.length >= len) ? '' : Array(1 + len - str.length >>> 0).join(chr);
		return leftJustify ? str + padding : padding + str;
		};

		// justify()
		var justify = function (value, prefix, leftJustify, minWidth, zeroPad, customPadChar) {
		var diff = minWidth - value.length;
		if (diff > 0) {
		  if (leftJustify || !zeroPad) {
		    value = pad(value, minWidth, customPadChar, leftJustify);
		  } else {
		    value = value.slice(0, prefix.length) + pad('', diff, '0', true) + value.slice(prefix.length);
		  }
		}
		return value;
		};

		// formatBaseX()
		var formatBaseX = function (value, base, prefix, leftJustify, minWidth, precision, zeroPad) {
		// Note: casts negative numbers to positive ones
		var number = value >>> 0;
		prefix = prefix && number && {
		  '2': '0b',
		  '8': '0',
		  '16': '0x'
		}[base] || '';
		value = prefix + pad(number.toString(base), precision || 0, '0', false);
		return justify(value, prefix, leftJustify, minWidth, zeroPad);
		};

		// formatString()
		var formatString = function (value, leftJustify, minWidth, precision, zeroPad, customPadChar) {
		if (precision != null) {
		  value = value.slice(0, precision);
		}
		return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar);
		};

		// doFormat()
		var doFormat = function (substring, valueIndex, flags, minWidth, _, precision, type) {
		var number;
		var prefix;
		var method;
		var textTransform;
		var value;

		if (substring == '%%') {
		  return '%';
		}

		// parse flags
		var leftJustify = false,
		  positivePrefix = '',
		  zeroPad = false,
		  prefixBaseX = false,
		  customPadChar = ' ';
		var flagsl = flags.length;
		for (var j = 0; flags && j < flagsl; j++) {
		  switch (flags.charAt(j)) {
		  case ' ':
		    positivePrefix = ' ';
		    break;
		  case '+':
		    positivePrefix = '+';
		    break;
		  case '-':
		    leftJustify = true;
		    break;
		  case "'":
		    customPadChar = flags.charAt(j + 1);
		    break;
		  case '0':
		    zeroPad = true;
		    break;
		  case '#':
		    prefixBaseX = true;
		    break;
		  }
		}

		// parameters may be null, undefined, empty-string or real valued
		// we want to ignore null, undefined and empty-string values
		if (!minWidth) {
		  minWidth = 0;
		} else if (minWidth == '*') {
		  minWidth = +a[i++];
		} else if (minWidth.charAt(0) == '*') {
		  minWidth = +a[minWidth.slice(1, -1)];
		} else {
		  minWidth = +minWidth;
		}

		// Note: undocumented perl feature:
		if (minWidth < 0) {
		  minWidth = -minWidth;
		  leftJustify = true;
		}

		if (!isFinite(minWidth)) {
		  throw new Error('sprintf: (minimum-)width must be finite');
		}

		if (!precision) {
		  precision = 'fFeE'.indexOf(type) > -1 ? 6 : (type == 'd') ? 0 : undefined;
		} else if (precision == '*') {
		  precision = +a[i++];
		} else if (precision.charAt(0) == '*') {
		  precision = +a[precision.slice(1, -1)];
		} else {
		  precision = +precision;
		}

		// grab value using valueIndex if required?
		value = valueIndex ? a[valueIndex.slice(0, -1)] : a[i++];

		switch (type) {
		case 's':
		  return formatString(String(value), leftJustify, minWidth, precision, zeroPad, customPadChar);
		case 'c':
		  return formatString(String.fromCharCode(+value), leftJustify, minWidth, precision, zeroPad);
		case 'b':
		  return formatBaseX(value, 2, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
		case 'o':
		  return formatBaseX(value, 8, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
		case 'x':
		  return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
		case 'X':
		  return formatBaseX(value, 16, prefixBaseX, leftJustify, minWidth, precision, zeroPad).toUpperCase();
		case 'u':
		  return formatBaseX(value, 10, prefixBaseX, leftJustify, minWidth, precision, zeroPad);
		case 'i':
		case 'd':
		  number = +value || 0;
		  number = Math.round(number - number % 1); // Plain Math.round doesn't just truncate
		  prefix = number < 0 ? '-' : positivePrefix;
		  value = prefix + pad(String(Math.abs(number)), precision, '0', false);
		  return justify(value, prefix, leftJustify, minWidth, zeroPad);
		case 'e':
		case 'E':
		case 'f': // Should handle locales (as per setlocale)
		case 'F':
		case 'g':
		case 'G':
		  number = +value;
		  prefix = number < 0 ? '-' : positivePrefix;
		  method = ['toExponential', 'toFixed', 'toPrecision']['efg'.indexOf(type.toLowerCase())];
		  textTransform = ['toString', 'toUpperCase']['eEfFgG'.indexOf(type) % 2];
		  value = prefix + Math.abs(number)[method](precision);
		  return justify(value, prefix, leftJustify, minWidth, zeroPad)[textTransform]();
		default:
		  return substring;
		}
		};

		return format.replace(regex, doFormat);
	}	

	// common functions

	var o = {};

	window.NEWSMAN = $({}); // adding events capability
	window.NEWSMAN.ajFormReq = {};

	NEWSMAN.postsSelector;

	var showMessage = NEWSMAN.showMessage = function(msg, type, cb, rawError) {

		var wrap = $('<div class="wp_bootstrap"></div>').appendTo(document.body);

		type = type || 'info';
		var cls = 'alert-'+type;

		if ( type === 'error' ) {
			msg = '<strong>'+newsmanL10n.error+'</strong>'+msg;
		}

		var rawRespLink = '';
		if ( rawError ) {
			rawRespLink = ' <a class="view-raw-response" href="#view">'+newsmanL10n.bugReport+'</a>';
		}

		var wnd = $('<div class="alert gs-fixed '+cls+'">'+msg+rawRespLink+'<a class="close" data-dismiss="alert" href="#">&times;</a></div>').appendTo(wrap);

		if ( rawError ) {
			$('.view-raw-response', wnd).click(function(e){
				e.preventDefault();

				$('#debug-response').val([
					'Query: \n'+rawError.query+'\n',
					'Response: \n'+rawError.responseText
				].join('\n'));

				getSystemInfo(function(msg) {
					$('#debug-extra-info').val(msg);

					showModal('#newsman-modal-debugmsg', function(mr){
						if ( mr === 'send' ) {
							$('.gs-fixed.alert-error').remove();
							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									action: 'newsmanAjSendBugReport',
									response: $('#debug-response').val(),
									extra: $('#debug-extra-info').val()
								}
							}).done(function(data){
								showMessage(data.msg, 'success');
							}).fail(NEWSMAN.ajaxFailHandler);
						}
					});		
				});

			});
		}

		function close() {
			if ( wrap ) {
				wrap.remove();
			}
			if ( typeof cb === 'function' ) {
				cb();
			}
		}
		if ( type !== 'error' ) {
			setTimeout(close, 2000);
		}		
	};

	NEWSMAN.ajaxFailHandler = function(t, status, message) {

		if ( t.readyState < 3 ) {
			// if we didn't establish the connection to the server
			err = 'Cannot connect to the server. Data: '+this.data;
			showMessage(err, 'error', null, {
				responseText: t.responseText
			});
			console.error(err);
			return;
		}

		var err = status,
			showedError = false;
		try {
			var data = JSON.parse(t.responseText);
			err = data.msg;			
		} catch(e) {
			err = 'Cannot parse server response.';
			showMessage(err, 'error', null, {
				responseText: t.responseText,
				query: this.data
			});
			showedError = true;		
		}

		if ( !showedError ) {
			showMessage(err, 'error', null);
		}
	};

	// global ajax senders

	$(document).on('click','a[href^="http"]', function(e){
		var handlers = jQuery._data( this, "events" );

		if ( handlers && handlers.click && handlers.click.length ) {
			// has click handler, we shouldn't interfere
			return;
		}

		if ( NEWSMAN.ajaxCnt > 0 ) {
			e.preventDefault();
			NEWSMAN.navigate = this.href;
			showLoading();
		}
	});

	NEWSMAN.ajaxCnt = 0;
	NEWSMAN.navigate = '';

	function requestsDone() {
		hideLoading();
		if ( NEWSMAN.navigate && window.location.href !== NEWSMAN.navigate ) {
			window.location.href = NEWSMAN.navigate;	
		}		
	}

	$(document).ajaxSend(function() {
		NEWSMAN.ajaxCnt += 1;
	});

	$(document).ajaxComplete(function() {
		// this is to prevent ajaxCnt go below zero on ajax requests which where initiated before the ajaxSend handler
		if ( NEWSMAN.ajaxCnt > 0 ) {
			NEWSMAN.ajaxCnt -= 1;	
		}		

		if ( NEWSMAN.ajaxCnt === 0 ) {
			requestsDone();
		}
	});

	// -------------------

	function getSystemInfo(callback) {
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'newsmanAjGetSystemInfo'
			}
		}).done(function(data){
			callback(data.msg);
		}).fail(NEWSMAN.ajaxFailHandler);
	}

	var sendValidation = NEWSMAN.sendValidation = (function(){

		var that = {},
			validators = [];

		that.addValidator = function(callback) {
			if ( $.inArray(callback, validators) === -1 ) {
				validators.push(callback);
			}
		};

		that.removeValidator = function(callback) {
			var idx = $.inArray(callback, validators);
			if ( idx !== -1 ) {
				validators.splice(idx, 1);
			}
		};

		that.validate = function() {
			for (var i = 0; i < validators.length; i++) {
				if ( !validators[i]() ) {
					return false;
				}
			}
			return true;
		};

		that.clear = function() {
			validators = [];
		};

		return that;
	}());

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
			var n = $(el).prop('name');
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
			$('#newsman_smtp_secure_conn .radio input[value="'+vals.ssl+'"]').prop('checked', true);
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

			for ( name in o ) {
				f = 'val';
				if ( typeof o[name] === 'boolean' ) {
					cb = $('input[name="'+name+'"]'); 

					cb.prop('checked', !!o[name]);
					
				} else {
					var radios = $('input[name="'+name+'"]').filter('[type="radio"]').prop('checked', false).length;
					if ( radios ) {
						$('input[name="'+name+'"]').filter('input[value="'+o[name]+'"]').prop('checked', true);
					} else {
						$('input[name="'+name+'"], textarea[name="'+name+'"], select[name="'+name+'"]').not('[type="radio"]').val(o[name]);
						$('input[name="'+name+'"]').filter('[type="hidden"]').change();
					}
					
				}
				
			}

			if ( NEWSMAN.refreshMDO ) {
				NEWSMAN.refreshMDO();	
			}			

			if ( typeof callback === 'function' ) {
				callback();
			}

		}).fail(NEWSMAN.ajaxFailHandler);			
	}

	/************************************************/
	/*	Success callbacks for ajax form responses   */
	/************************************************/

	NEWSMAN.ajFormReq.ajSwitchLocale = function(data) {
		$('form[action="ajSwitchLocale"]').closest('.newsman-admin-notification').fadeOut();
	};

	/************************************************/

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
				}).fail(NEWSMAN.ajaxFailHandler);
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

	function shiftSelector(container, opts) {
		opts = opts || {};
		var def = {
			selectClass: 'active',
			onSelect: null,
		},
		rawContainer = $(container).get(0),
		startEl = null,
		lastAction;

		opts = $.extend(def, opts);

		function clickHandler(e) {
			el = $(this);

			if ( startEl && e.shiftKey ) {

				var startIdx = $(startEl).index(),
					curIdx = $(this).index(),
					s, e;

				if ( curIdx < startIdx ) {
					s = curIdx;
					e = startIdx;
				} else {
					s = startIdx;
					e = curIdx;
				}

				var children = $(container).children(),
					actionFunc = lastAction == 'select' ? 'addClass' : 'removeClass';

				for (var i = s; i <= e; i++) {
					$(children[i])[actionFunc](def.selectClass);
				}

			} else {
				if ( el.hasClass(def.selectClass) ) {
					el.removeClass(def.selectClass);
					lastAction = 'deselect';
				} else {
					el.addClass(def.selectClass);
					lastAction = 'select';
				}
			}

			if ( opts.onSelect ) { opts.onSelect(); }

			startEl = this;			
		}

		$(container).children().unbind('click', clickHandler);
		$(container).children().click(clickHandler);		

		function inWatchBlock(el) {
			do {
				if ( el == rawContainer ) {
					return true;
				}
				el = el.parentNode;
			} while ( el );
			return false;
		}

		$(document).click(function(e){
			if ( !inWatchBlock(e.target) ) {
				startEl = null;
			}
		});
	}

	/*******	  Post selector iframe 	**********/

	if ( $('#post-selector').get(0) ) {
		(function(){
			var ul = $('#newsman-posts').empty();

			var postsSelector = NEWSMAN.postsSelector = {};

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
				$('h3', this).text(newsmanL10n.loading);
				paging.page += 1;
				refreshPosts('append');
			}

			function selectAllPosts() {
				$('.newsman-bcst-post').addClass('active');
				countSelectedPosts();
			}

			$(document).on('click', '#newsman-bcst-posts .newsman-bcst-load-button', loadMore);

			function refreshPosts(append, cb) {
				append = append == 'append';

				var container = $('#newsman-bcst-posts');

				if ( !append ) {
					container.empty();
					$('<div class="stub-wrap"><div class="stub">'+newsmanL10n.loading+'</div></div>').appendTo(container);
				}

				var ccats = getSelectedCats(),
					cauths = getSelectedAuthors(),
					limit, q, d, s;

					s = $('#newsman-search').val();


				d = {
					action: "newsmanAjGetPosts",
					postType: $('#newsman-post-type').val(),
					includePrivate: $('#newsman-bcst-include-private').is(':checked') ? 1 : 0					
				};

				$.extend(d, paging);

				if ( s ) {
					d.search = s;
					clearRangeSelection();
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
									'<h3>'+newsmanL10n.loadMore+'</h3>',
								'</div>'
								].join('')).appendTo(container);								
							}

						} else {
							$('<div class="stub-wrap"><div class="stub">'+newsmanL10n.sorryNoPostsMathedCriteria+'</div></div>').appendTo(container);
						}

						countSelectedPosts();

						shiftSelector(container.get(0), {
							onSelect: function() {
								countSelectedPosts();
							}
						});

						if ( paging.select ) {
							selectAllPosts();
						}

						if ( cb ) { cb(); }
					}
				});
			}

			$('#newsman-bcst-sel-auth, #newsman-bcst-sel-cat, #newsman-post-type').change(refreshPosts);
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

			function clearRangeSelection() {
				$('#newsman-select-buttons button.active').removeClass('active');
				delete paging.select;
			}

			$('#newsman-select-buttons button').click(function(e){
				var sel = $(this).attr('sel');
				if ( sel === 'clear' ) {
					clearRangeSelection();
				} else {
					paging.select = sel;					
				}

				refreshPosts(null);
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
				loading: newsmanL10n.loading
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
					action: 'newsmanAjRemoveUploadedFile',
					type: 'csv',
					fileName: filename
				}
			}).done(function(data){				
				showMessage(data.msg, 'success');
				done();
				that.reset();
			}).fail(NEWSMAN.ajaxFailHandler);
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
				if ( n && this.formFields[n] ) {
					var rxStr = this.formFields[n].replace(/\W+/, '.*'),
						rx = new RegExp(rxStr, 'i'), // field full name like "IP Address"
						rx2 = new RegExp(n, 'i'), // field key like "ip"
						selected = rx.exec(val) ? ' selected="selected"' : '';

						if ( !selected ) {
							selected = rx2.exec(val) ? ' selected="selected"' : '';
						}

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

			}).fail(NEWSMAN.ajaxFailHandler);

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
			var err = $('<span class="label label-important" title="'+obj.reason+'"><i class="icon-warning-sign icon-white"></i> '+newsmanL10n.error2+'</span>');
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
			listId: null,
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

	 	function getSubscribers() {
	 		var q = $.extend({}, pageState);
	 		q.action = 'newsmanAjGetSubscribers';
	 		//q.listId = $('#newsman-lists').val() || '1';2

	 		$('#newsman-checkall').prop('checked', false);

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
	 			
	 			var upgradeLink = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-pro';

	 			if ( typeof NEWSMAN_LITE_MODE !== 'undefined' && NEWSMAN_LITE_MODE ) {
		 			if ( cnt.all >= get95pcnt() && cnt.all < getx() ) {
		 				warn( sprintf( newsmanL10n.warnCloseToLimit, getx(), upgradeLink ) );
		 			} else if ( cnt.all >= getx() ) {
		 				warn( sprintf( newsmanL10n.warnExceededLimit, getx(), upgradeLink ) );
		 			}
	 			}

	 			var listId = $('#newsman-lists').val();

	 			$('#newsman-subs-all').prop('href', '#/'+listId+'/all').text(newsmanL10n.allSubs.replace('#', cnt.all));
	 			$('#newsman-subs-confirmed').prop('href', '#/'+listId+'/confirmed').text(newsmanL10n.confirmedSubs.replace('#', cnt.confirmed));
	 			$('#newsman-subs-unconfirmed').prop('href', '#/'+listId+'/unconfirmed').text(newsmanL10n.unconfirmedSubs.replace('#', cnt.unconfirmed));
	 			$('#newsman-subs-unsubscribed').prop('href', '#/'+listId+'/unsubscribed').text(newsmanL10n.unsubscribedSubs.replace('#', cnt.unsubscribed));
	 		}

	 		function noData(err) {
	 			var msg = err || newsmanL10n.noSubsYet;
	 			var tbody = $('#newsman-mgr-subscribers tbody').empty();
	 			$('<tr><td colspan="6" class="blank-row">'+msg+'</td></tr>').appendTo(tbody);
	 		}

	 		function showLoading() {
	 			var tbody = $('#newsman-mgr-subscribers tbody').empty();
	 			$('<tr><td colspan="6" class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> '+newsmanL10n.loading+'</td></tr>').appendTo(tbody);	 			
	 		}

	 		function renderButtons(start, num, current, count) {
	 			var cl ,el = $('.pagination ul').empty();	

	 			var listId = $('#newsman-lists').val();

	 			if ( count < 2 ) {
					$('.pagination').hide();
	 			} else {
	 				$('.pagination').show();
	 			}

	 			var end = start+num-1;

	 			end = end > count ? count : end;

	 			// prev button
	 			if ( current > 1 ) {
	 				$('<li><a href="#/'+listId+'/'+pageState.show+'/'+(current-1)+'">«</a></li>').appendTo(el);
	 			}

	 			for (var i = start; i <= end; i++) {
	 				cl = ( i === current ) ? 'class="active"' : '';
	 				$('<li '+cl+'><a href="#/'+listId+'/'+pageState.show+'/'+i+'">'+i+'</a></li>').appendTo(el);
	 			}

	 			if ( current < count ) {
	 				$('<li><a href="#/'+listId+'/'+pageState.show+'/'+(current+1)+'">»</a></li>').appendTo(el);
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

			}).fail(function(){
				noData('Some error occurred.');
				NEWSMAN.ajaxFailHandler.apply(this, arguments);
			});
	 	}


		var uploader = $('<div></div>').neoFileUploader({
			debug: true,
			action: NEWSMAN_PLUGIN_URL+'/upload.php',
			params: {
				type: 'csv'
			},
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

				}).fail(NEWSMAN.ajaxFailHandler);
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
			}).fail(NEWSMAN.ajaxFailHandler);

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

					}).fail(NEWSMAN.ajaxFailHandler).always(function(){
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

					}).fail(NEWSMAN.ajaxFailHandler);
				}
				return true;
			});
		});

		// Unsubscribe & Delete modal windows

		function enableFormEditButton() {
			var listId = $('#newsman-lists').val();
			$('#btn-edit-form').attr('href', NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+listId);
		}

		var lastListId = null;

		/**
		 * We have "Add new..." list button as an item in the dropdown,
		 * so we remember the current position in the list tot get back to it
		 * if we click cancel in the dialog 
		 */

		$('#newsman-lists')
		.bind('mousedown', function(e){
			lastListId = $('#newsman-lists').val();
		})
		.change(function(e){
			var v = $('#newsman-lists').val();
			if ( v === 'add' ) {
				$('#newsman-lists').val(lastListId);
				showModal('#newsman-modal-create-list', function(mr){
					if ( mr == 'ok' ) {
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								action: 'newsmanAjCreateList',
								name: $('#new-list-name').val()
							}
						}).done(function(data){

							if ( data.id ) {
								$('<option value="'+data.id+'">'+data.name+'</option>').insertBefore( $('#new-list-group') );
								$('#newsman-lists').val(data.id).change();
								window.location.hash = '#/all';
							}

						}).fail(function(t, status, message) {
							var data = JSON.parse(t.responseText);
							showMessage(data.msg, 'error');
						});

					}
					return true;
				});
			} else {
				window.location.hash = '#/'+v+'/all';
			}			
		});

		enableFormEditButton();		

		// -----------	

		// check all checkbox
		$('#newsman-checkall').change(function(e){
			$('#newsman-mgr-subscribers tbody input').prop('checked', $(this).is(':checked') );
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

						}).fail(NEWSMAN.ajaxFailHandler);
						
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

						}).fail(NEWSMAN.ajaxFailHandler);
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

						}).fail(NEWSMAN.ajaxFailHandler);
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

						}).fail(NEWSMAN.ajaxFailHandler);
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

						}).fail(NEWSMAN.ajaxFailHandler);
					}
					return true;
				});

			}
		});

	 	var email = $('#newsman-email-search').val();

	 	var defaultListId = $('#newsman-lists').val();

	 	function r(listId, type, p) {
			type = type || 'all';
			p = p || 1;
			listId = listId || defaultListId;

			$('#newsman-lists').val(listId);

			pageState.listId = listId;
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
			'/:list/:type/:p': r,
			'/:list/:type': r
		});

		router.init('/'+defaultListId+'/all/1');	 	
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

		if ( !NEWSMAN.refreshMDO ) {
			NEWSMAN.refreshMDO = function() {
				var mdo = $('.newsman-mdo:checked').val();					
				$('#newsman-smtp-settings')[ mdo === 'smtp' ? 'show' : 'hide' ]();
			};
		}


		$('.newsman-mdo').click(NEWSMAN.refreshMDO);
		NEWSMAN.refreshMDO();



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
			}).fail(NEWSMAN.ajaxFailHandler);
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
			}).fail(NEWSMAN.ajaxFailHandler).always(function(){
				btn.text(txt).removeAttr('disabled');
			});
		});


		$('button.newsman-update-options').click(function(){
			saveOptions();
		});
	}

	/*******	 Edit List & Form Options 	**********/

	if ( $('#newsman-page-list').get(0) ) {

		$('#newsman-lists').change(function(e){
			var listId = $(this).val();
			window.location = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+listId;
		});

		// 

		//$('.newsman-form-builder').newsmanFormBuilder();

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

			}).fail(NEWSMAN.ajaxFailHandler);	 

		}

		loadData();

		function saveList() {

			var data = {},
				form = $('#newsman_form_g');

			$('#newsman_form_json').val( newsmanFormBuilder.toJSON() );

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

			}).fail(NEWSMAN.ajaxFailHandler);


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

	/*******	 CKeditor view 	**********/

	if ( $('#newsman-editor').get(0) ) {

		var actionsMap = {
			template: {
				actSave: 'newsmanAjSetTemplate'
			},
			email: {
				actSave: 'newsmanAjSetEmail'
			}		
		};

		var actions = actionsMap[NEWSMAN_ENT_TYPE];

		if ( !actions ) {
			throw new Error('Actions map is not defined for entity type '+NEWSMAN_ENT_TYPE);
		}

		var editor = CKEDITOR.replace( 'content', {
			width: 'auto',
			height: 700,
			customConfig: NEWSMAN_PLUGIN_URL+'/js/custom_config.js'
		});

		function changed(){
			saveEntity();
		}

		if ( typeof NEWSMAN_ENT_STATUS !== 'undefined' && ( NEWSMAN_ENT_STATUS === 'stopped' || NEWSMAN_ENT_STATUS === 'error' ) ) {
			$('#newsman-editor input[name="newsman-send"]').change(function(e){
				$('#newsman-send').text( ( $(this).val() === 'now' ) ? 'Resume' : 'Send');
			});			
		}

		editor.on('newsmanSave.ckeditor', function(){
			changed();
			editor.fire('afterNewsmanSave.ckeditor');
		});

		$('#newsman-email-subj, #newsman-template-name').change(function(){
			changed();
		});

		function sendEmail() {

			if ( !sendValidation.validate() ) {
				return;
			}

			saveEntity(function(){

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
				}).fail(NEWSMAN.ajaxFailHandler);

			});
		}

		function convertToPlainText(el) {

			if ( !el ) { return ''; }

			var text = NEWSMAN_HTML_TO_TEXT.convert($(el).html(), {
				preprocessing: function(html) {
					return html.replace(/<a[^>]+href="(.*?)"[^>]*>(.*?)<\/a>/gi, " $2 ( $1 ) ");
				}
			});			

			return text;
		}

		function saveEntity(done) {
			done = done || function(){};

			var data = {
				id: NEWSMAN_ENTITY_ID,
				action: actions.actSave
			},
			edBody = editor.document ? editor.document.getBody().$ : false,
			plain = convertToPlainText(edBody),
			html = editor.getData();

			if ( !plain || !html ) {
				return;
			}			

			data.html = html;
			data.plain = plain;
			data.subj = $('#newsman-email-subj').val();


			if ( NEWSMAN_ENT_TYPE === 'template' ) {
				data.name = $('#newsman-template-name').val();
			} else { // email
				data.to = $('#eml-to').multis('getItems')+'';
				data.send = $('input[name="newsman-send"]:checked').val();
				data.ts = Math.round( $('#newsman-send-datepicker').datetimepicker('getDate') / 1000 );
			}

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: data
			}).done(function(data){
				if ( data.id ) {
					NEWSMAN_ENTITY_ID = data.id;
				}
				done();
			}).fail(NEWSMAN.ajaxFailHandler);
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
				$('#newsman-mailbox tbody input').prop('checked', $(this).is(':checked'));
			});

			function showSendingLog(emailId) {
				showLoading();
				var b = $('#newsman-modal-errorlog .modal-body').empty();

				$('<div><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> '+newsmanL10n.loading+'</div>').appendTo(b);

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjGetErrorLog',
						emailId: emailId
					}
				}).done(function(data){

					$('#newsman-modal-errorlog .modal-body').empty();

					$('<h3 style="margin-bottom: 1em;">'+sprintf(newsmanL10n.sentXofXemails, data.sent, data.recipients)+'</h3>').appendTo(b);

					if ( data.msg ) {
						$('<h3 style="margin-bottom: 1em;">'+newsmanL10n.status+' '+data.msg+'</h3>').appendTo(b);	
					}					

					if ( data.errors.length ) {
						$('<h3 style="margin-bottom: .5em;">'+newsmanL10n.emailErrors+'</h3>').appendTo(b);

						function getList(err) {
							var id = 'newsman-errlog-'+err.listId,
								tb = $('#'+id+' tbody');

							if ( tb.get(0) ) {
								return tb;
							} else {
								$('<h4 style="margin-bottom: .5em;">'+newsmanL10n.list+' '+err.listName+'</h4>').appendTo(b);
								var tbl = $([
									'<table id="'+id+'" class="table table-striped table-bordered">',
										'<thead>',
											'<tr>',
												'<th>'+newsmanL10n.emailAddress+'</th>',
												'<th>'+newsmanL10n.errorMessage+'</th>',
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

				}).fail(NEWSMAN.ajaxFailHandler);
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

				}).fail(NEWSMAN.ajaxFailHandler);
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
				$('#newsman-mailbox-all').text( newsmanL10n.vAllEmails + ' ('+cnt.all+')');
				$('#newsman-mailbox-inprogress').text(newsmanL10n.vInProgress + ' ('+cnt.inprogress+')');
				$('#newsman-mailbox-draft').text(newsmanL10n.vDrafts + ' ('+cnt.draft+')');					
				$('#newsman-mailbox-pending').text( newsmanL10n.vPending + ' ('+cnt.pending+')');
				$('#newsman-mailbox-sent').text( newsmanL10n.vSent + ' ('+cnt.sent+')');
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

				$('#newsman-checkall').prop('checked', false);

				showLoading();



				function noData() {
					var tbody = $('#newsman-mailbox tbody').empty();
					$('<tr><td colspan="6" class="blank-row">'+newsmanL10n.youHaveNoEmailsYet+'</td></tr>').appendTo(tbody);
				}

		 		function showLoading() {
		 			var tbody = $('#newsman-mgr-subscribers tbody').empty();
		 			$('<tr><td colspan="6" class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> '+newsmanL10n.loading+'</td></tr>').appendTo(tbody);	 			
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

					var arr;

					if ( $.isArray(to) ) {
						arr = to;
					} else {
						arr = [];
						try {
							arr = JSON.parse(to);
						} catch(e) {
						}						
					}

					if ( $.isArray(arr) ) {
						for (var i = 0; i < arr.length; i++) {
							arr[i] = '<span class="label label-info">'+arr[i]+'</span>';
						}										
					} else {
						arr = [];
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

				}).fail(NEWSMAN.ajaxFailHandler);

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

			if ( typeof Router !== 'undefined' ) {
				var router = new Router({
					'/:type/:p': r,
					'/:type': r
				});

				router.init('/all/1');	
			}


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
				}).fail(NEWSMAN.ajaxFailHandler);
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
				}).fail(NEWSMAN.ajaxFailHandler);
			});

			$('#btn-compose').click(function(e){
				e.preventDefault();

				var tbody = $('#dlg-templates-tbl').empty();

				$('<tr><td class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> '+newsmanL10n.loading+'</td></tr>').appendTo(tbody);

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

				}).fail(NEWSMAN.ajaxFailHandler);

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

							}).fail(NEWSMAN.ajaxFailHandler);
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
						window.location = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=edit&type=wp&id='+data.id;
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
				ipp: 15,
				total: null
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
						showMessage('Some error occured. template id is '+data.id, 'error');
					}

				}).fail(NEWSMAN.ajaxFailHandler);
			}

			function setUploadTableLoading() {
				var tbody = $('#uploaded-files tbody').empty();

				$([
					'<td colspan="2" class="blank-row">',
						'<img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif">'+newsmanL10n.loading,
					'</td>'
				].join('')).appendTo(tbody);
			}

			function addFile(fileName, id) {
				var blank = $('#uploaded-files tbody .blank-row').get(0),
					tbody = $('#uploaded-files tbody');

				if ( blank ) {
					tbody.empty();
				}

				var status = '',
					rowId;

				if ( typeof id !== 'undefined' ) {
					rowId = 'newsman-uploaded-'+id;
					status = [
						'<div class="progress progress-striped">',
							'<div class="bar" style="width: 0%;"></div>',
						'</div>'					
					].join('');
				} else {
					rowId = 'newsman-uploaded-'+fileName.replace(/[^a-z0-9]+/ig, '');
					status = '<span class="label label-success"><i class="icon-ok icon-white"></i> '+newsmanL10n.done+'</span>';
				}

				$([
					'<tr id="'+rowId+'" class="newsman-upl">',
						'<td class="newsman-upl-filename" style="width: 70%"><span>'+fileName+'</span><button class="newsman-delete-file  pull-right btn btn-mini btn-danger">Delete</button></td>',
						'<td class="newsman-upl-status" style="width: 30%">',
							status,	
						'</td>',
					'</tr>'
				].join('')).appendTo(tbody);

				var row = $('#'+rowId);

				$('.newsman-delete-file', row).click(function(e){
					$.ajax({
						url: ajaxurl,
						data: {
							action: 'newsmanAjRemoveUploadedFile',
							type: 'template',
							fileName: fileName
						}
					}).done(function(data){
						row.fadeOut(function(){
							row.remove();	
						});						
					}).fail(NEWSMAN.ajaxFailHandler);
				});
			}
 
			$('#btn-import-from-file').click(function(e){
				e.preventDefault();
				showModal('#newsman-modal-import-from-file', { 
					show: function() {
						setUploadTableLoading();
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								action: 'newsmanAjGetUploadedFiles',
								type: 'template'
							}
						}).done(function(data){
							if ( data.files.length ) {
								$(data.files).each(function(i, file){
									addFile(file);
								});
							} else {
								$('#uploaded-files tbody .blank-row').html('No files to import.');
							}
							
						}).fail(NEWSMAN.ajaxFailHandler);
					},
					result: function(mr){
						if ( mr === 'ok' ) {
							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									action: 'newsmanAjInstallTemplates'
								}
							}).done(function(data){
								showMessage(data.msg);
								getTemplates();
							}).fail(NEWSMAN.ajaxFailHandler);
						}
					}
				});				
			});

			var uploader = $('<div></div>').neoFileUploader({
				debug: true,
				acceptFiles: '.zip',
				action: NEWSMAN_PLUGIN_URL+'/upload.php',
				params: {
					type: 'template'
				},
				button: $('#btn-upload-file').get(0),
				onAdd: function(e, obj) {
					var blank = $('#uploaded-files tbody .blank-row').get(0),
						tbody = $('#uploaded-files tbody');

					if ( blank ) {
						tbody.empty();
					}

					addFile(obj.fileName, obj.id);
				},
				onDone: function(e, obj) {
					if ( !obj.actualFileName ) { return; }

					var row = $('#newsman-uploaded-'+obj.id).get(0);
					if ( row ) {
						$('span', row).text(obj.actualFileName);
						var st = $('.newsman-upl-status', row).empty();
						$('<span class="label label-success"><i class="icon-ok icon-white"></i> '+newsmanL10n.done+'</span>').appendTo(st);
					}

				},
				onError: function(e, obj) {
					var row = $('#newsman-uploaded-'+obj.id).get(0);
					if ( row ) {						
						var st = $('.newsman-upl-status', row).empty();
						$('<span class="label label-important" title="'+obj.reason+'"><i class="icon-warning-sign icon-white"></i> '+newsmanL10n.error2+'</span>').appendTo(st);
					}

				},
				onProgress: function(e, obj) {
					var row = $('#newsman-uploaded-'+obj.id).get(0);
					if ( row ) {
						$('.bar', row).css({ width: obj.percents+'%' });
					}
				}
			});			

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

			function getSelectedTemplates() {
				var ids = [];
				$('#newsman-templates tbody input:checked').each(function(i, el){
					ids.push( parseInt($(el).val(), 10) );
				});
				return ids;
			}

			function haveSharedAssets(cb) {
				var ids = getSelectedTemplates();

				var err = new Error('Wrong server response');

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						ids: ids+'',
						action: 'newsmanAjHasSharedAssets'
					}
				}).done(function(data){

					if ( typeof data.shared === 'number' ) {
						cb(null, data.shared > 0);
					} else {
						cb(err);
					}

				}).fail(function(){
					cb(err);
					NEWSMAN.ajaxFailHandler.apply(this, arguments);
				});
			}

			$('#btn-delete-tpls').click(function(e){
				e.preventDefault();
				// ajDeleteEmailTemplates

				var ids = getSelectedTemplates();

				if ( !ids.length ) {
					showMessage(newsmanL10n.pleaseMarkTemplatesForDeletion);
				} else {

					showLoading();

					haveSharedAssets(function(err, haveShared){
						hideLoading();

						if ( !err ) {

							showModal('#newsman-modal-delete', {
								show: function(){
									$('#info-have-shared-res')[ haveShared ? 'show' : 'hide' ]();
									$('#btn-del-with-res')[ haveShared ? 'show' : 'hide' ]();
								},
								result: function(mr){
									if ( mr === 'rm' || mr === 'rm_res' ) {

										$.ajax({
											type: 'POST',
											url: ajaxurl,
											data: {
												ids: ids+'',
												delSharedAssets: mr === 'rm_res' ? 1 : 0,
												action: 'newsmanAjDeleteEmailTemplates'
											}
										}).done(function(data){
											showMessage(data.msg, 'success');
											getTemplates();
										}).fail(NEWSMAN.ajaxFailHandler);
									}
									return true;
								}
							});
						}
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

				function inlineDeleteTemplate(e) {
					e.preventDefault();

					var id = $(this).closest('tr').find('input[type="checkbox"]').val();
					if ( id ) {
						showModal('#newsman-modal-delete-single', function(mr){
							if ( mr === 'ok' ) {
								$.ajax({
									type: 'POST',
									url: ajaxurl,
									data: {
										ids: id,
										action: 'newsmanAjDeleteEmailTemplates'
									}
								}).done(function(data){
									showMessage(data.msg, 'success');
									getTemplates();
								}).fail(NEWSMAN.ajaxFailHandler);
							}
							return true;
						});	
					}
				}

				function inlineDuplicateTemplate(e) {
					e.preventDefault();

					var id = $(this).closest('tr').find('input[type="checkbox"]').val();
					if ( id ) {
						showLoading();	

						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								id: id,
								action: 'newsmanAjDuplicateTemplate'
							}
						}).done(function(data){
							hideLoading();
							showMessage(data.msg, 'success');
							if ( data.id ) {
								addTemplateRow(data, 'prepend');
							}
						}).fail(NEWSMAN.ajaxFailHandler);
					}
				}

				function addTemplateRow(r, prepend) {
					var tbody = $('#newsman-templates tbody');
					var icon = r.system ? '<i class="icon-cog" rel="tooltip" title="System Template. Cannot be deleted."></i>' : '';
					var editURL = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates&action=edit&id='+r.id;
					var row = $(['<tr>',
							'<td><input value="'+r.id+'" type="checkbox"'+(r.system ? ' disabled="disabled"' : '')+'></td>',
							'<td>',
								'<a href="'+editURL+'" class="newsman-template-name">'+icon+' '+r.name+'</a>',
								!r.system ? '<div class="newsman-inline-controls"><a class="newsman-duplicate-tpl" href="#">Copy</a> | <a href="'+editURL+'">Edit</a> | <a class="newsman-delete-tpl" href="#">Delete</a> | <a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates&action=download&id='+r.id+'">Export</a></div>':'',
							'</td>',
						'</tr>'].join(''))[ prepend ? 'prependTo' : 'appendTo' ](tbody);

					$('.newsman-delete-tpl', row).click(inlineDeleteTemplate);
					$('.newsman-duplicate-tpl', row).click(inlineDuplicateTemplate);
				}

				function fillRows(rows) {
					var tbody = $('#newsman-templates tbody').empty();
					if (rows.length) {
						$(rows).each(function(i, r){
							addTemplateRow(r);
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

					var pageCount = Math.ceil(data.count / pageState.ipp);

					if ( pageState.total === null ) {
						pageState.total = data.count;
						buildPagination(pageCount);
					}  else if ( pageState.total !== data.count ) {
						pageState.total = data.count
						$('.newsman-tbl-controls .pagination').newsmanPagination('setPageCount', pageCount);
					}
						
				}).fail(NEWSMAN.ajaxFailHandler);
			}

			function buildPagination(pageCount) {
				$('.newsman-tbl-controls .pagination')
					.newsmanPagination({
						pageCount: pageCount,
						currentPage: pageState.pg,
						pageChanged: function(ev, data) {
							window.location.hash = '#/'+pageState.show+'/'+data.page
							// pageState.pg = data.page;
							// getTemplates();
						}
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

			NEWSMAN.on('reloadTemplates', function(){
				getTemplates();
			});

			var router = new Router({
				'/newtemplate': showNewTplDialog,
				'/:type/:p': r,
				'/:type': r				
			});

			router.init('/all/1');	


		})();
	}


	/******* 	Templates Store 	********/

	if ( $('#newsman-modal-template-store')[0] ) {

		var currentPage = 1,
			pageCount = 10;

		var dialogContent = $('#newsman-modal-template-store .modal-body');

		var pagination;

		var stores;

		var disableScrollWatcher = false;

		function scrollToStorePage(page) {
			var rowEl = $('#newsman-store-p-'+page);
			if ( rowEl[0] ) {
				disableScrollWatcher = true;
				dialogContent.animate({ scrollTop: dialogContent.scrollTop() + rowEl.position().top }, {
					duration: 400,
					easing: 'swing',
					done: function(){
						setTimeout(function() {
							disableScrollWatcher = false;	
						}, 50);						
					}
				});	
			}			
		}

		function buildPagination() {
			pagination = $('#newsman-modal-template-store .pagination')
				.newsmanPagination({
					pageCount: pageCount,
					currentPage: currentPage,
					pageChanged: function(ev, data) {
						scrollToStorePage(data.page);
					}
				});
		}

		function initDownloadButton(btn) {
			var $btn = $(btn);

			$btn.click(function(e){
				e.preventDefault();
				var url = $(this).attr('href'),
					tplName = $(this).attr('title');
 
				if ( $btn.attr('disabled') ) {
					return;
				}

				var idx = parseInt($btn.attr('data-idx'), 10),
					storeIdx = parseInt($btn.attr('data-store'), 10),
					tpl = stores[storeIdx].templates[idx];

				$btn.text('Downloading...').attr('disabled', 'disabled');

				$.ajax({
					type: 'post',
					url: ajaxurl,
					data: {
						action: 'newsmanAjDownloadTemplate',
						json: JSON.stringify(tpl)
					}
				}).done(function(data){
					$btn.text('Installed');
					NEWSMAN.trigger('reloadTemplates');
				}).fail(function(){
					$btn.text('Download').removeAttr('disabled');
					NEWSMAN.ajaxFailHandler.apply(this, arguments);
				});
			});
		}

		function getInstalledTemplates(cb) {
			$.ajax({
				type: 'post',
				url: ajaxurl,
				data: {
					action: 'newsmanAjGetInstalledTemplates'
				}
			}).done(function(data){
				cb(null, data.installed);
			}).fail(function(){
				cb(new Error('Some error occured, cannot get installed templates.'));
			});
		}

		// --- Scroll position tracking to autoswitch to appropriate page
		var rowsTops = [],			
			halfRow;

		function findRowsTops() {
			rowsTops = [];
			$('#templates-previews tbody .newsman-templates-row').each(function(index, row) {
				if ( index === 0 ) {
					halfRow = Math.round($(row).height() / 2);
				}
				var t = $(row).position().top;
				rowsTops.push(t);
			});
		}

		$('#store-selector').change(function(){
			var name = $(this).val();
			//debugger;
			var a = $('#store-'+name);
			var t = a.position().top;

			disableScrollWatcher = true;

			dialogContent.animate({ scrollTop: t }, {
				duration: 400,
				easing: 'swing',
				done: function(){
					setTimeout(function() {
						disableScrollWatcher = false;	
					}, 50);						
				}
			});	

			
		});

		dialogContent.scroll(function(){
			if ( disableScrollWatcher ) { return; }
			var scrollTop = dialogContent.scrollTop(),
				viewedPage;

			for (var i = 0; i < rowsTops.length; i++) {
				if (scrollTop > rowsTops[i]+halfRow) { 
					viewedPage = i+2;
				} else {
					break;
				}
			}

			pagination.newsmanPagination('setPage', viewedPage || 1);
		});
		// ---

		function buildTemplatesTable() {

			dialogContent.scrollTop(0);

			getInstalledTemplates(function(err, installedTpls){
				if ( !err ) {

					function getTplName(url) {					
						return url.match(/\/([^\/]*?)\.zip$/)[1];
					}

					function getBadge(tpl) {
						return tpl.madeForNewsman ? '<img class="made-for-newsman" title="Made for WPNewsman" src="'+NEWSMAN_PLUGIN_URL+'/img/star.png"> ' : '';
					}

					function getButton(tpl, idx, storeIdx) {
						var tplName = getTplName(tpl.downloadURL);

						var lite = typeof NEWSMAN_LITE_MODE !== 'undefined' && NEWSMAN_LITE_MODE;

						if ( $.inArray(tplName, installedTpls) > -1 ) {
							return '<a data-store="'+storeIdx+'" data-idx="'+idx+'" title="'+tpl.name+'" href="'+tpl.downloadURL+'" class="btn btn-info" disabled="disabled">'+getBadge(tpl)+'Installed</a>';
						} else if ( lite && tpl.type == 'pro' ) {
							return '<a data-store="'+storeIdx+'" data-idx="'+idx+'" href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-pro" class="btn">'+getBadge(tpl)+'Upgrade to Pro</a>';
						} else {
							return '<a data-store="'+storeIdx+'" data-idx="'+idx+'" title="'+tpl.name+'" href="'+tpl.downloadURL+'" class="btn btn-info">'+getBadge(tpl)+'Download</a>';
						}
					}

					var carouselCnt = 1;

					function getPreviewBlock(preview) {

						var carouselId = 'carousel-'+carouselCnt;
						carouselCnt += 1;

						if ( $.isArray(preview) ) {
							var output = [
								'<div id="'+carouselId+'" class="carousel slide">',
								'<ol class="carousel-indicators">'
							];

							$(preview).each(function(i, url){
								var cls = i === 0 ? ' class="active"' : '';
								output.push('<li data-target="#'+carouselId+'" data-slide-to="'+i+'"'+cls+'></li>');
							});

							output.push('</ol>');

							// Carousel items

							output.push('<div class="carousel-inner">');

							$(preview).each(function(i, url){
								var act = i === 0 ? 'active ' : '';
								output.push([
									'<div class="'+act+'item">',
										'<img src="'+url+'" alt="">',
									'</div>'
								].join(''));
							});

							output.push('</div>');

							output.push('<a class="carousel-control left" href="#'+carouselId+'" data-slide="prev">&#x25C0;</a>');
							output.push('<a class="carousel-control right" href="#'+carouselId+'" data-slide="next">&#x25b6;</a>');

							// '<a class="carousel-control left" href="#myCarousel" data-slide="prev">&lsaquo;</a>'
							// '<a class="carousel-control right" href="#myCarousel" data-slide="next">&rsaquo;</a>'

							output.push('</div>');

							return output.join('');

						} else {
							return '<img src="'+preview+'" alt="">';
						}

					}

					$.ajax({
						type: 'get',
						dataType: 'jsonp',
						//url: 'http://blog.dev/wp-admin/admin.php?page=store.json'
						url: 'http://wpnewsman.com/store.json.php'
					}).done(function(data){

						if ( typeof data === 'string' ) {
							try {
								data = JSON.parse(data);
							} catch(e) {
								return;
							}
						}

						// console.warn('!!!!!!!! REMOVE ME BEFOR RELEASE');
						// data.templates.push({
						// 	name: 'Color direct',
						// 	downloadURL: 'http://blog.dev/store/colordirect/colordirect.zip',
						// 	preview: [
						// 		'http://blog.dev/store/colordirect/colordirect_full_width.jpg',
						// 		'http://blog.dev/store/colordirect/colordirect_left_sidebar.jpg',
						// 		'http://blog.dev/store/colordirect/colordirect_right_sidebar.jpg'								
						// 	]
						// });					

						stores = data.stores;

						var tbody = $('#templates-previews tbody').empty(),
							html = [],
							r = 0,
							p = 1;


						$(data.stores).each(function(i, store){

							var name = store.title.replace(/[^a-z0-9]+/ig, '').toLowerCase();

							html.push([
								'<tr>',
									'<td colspan="3">',
										'<a name="'+name+'"></a>',
										'<h3 id="store-'+name+'">'+store.title+'</h3>',
										'<p>'+store.description+'</p>',
									'</td>',
								'</tr>'
							].join(''));

							$('<option value="'+name+'">'+store.title+'</option>').appendTo($('#store-selector'));

							var lastTplIdx = store.templates.length-1;

							$(store.templates).each(function(j, tpl){
								if ( r === 0 ) { html.push('<tr class="newsman-templates-row" id="newsman-store-p-'+p+'">') };
								html.push([
									'<td>',
										getPreviewBlock(tpl.preview),									
										'<div class="newsman-tpl-meta">',
											getButton(tpl, j, i),
											'<h4>'+tpl.name+'</h4>',
										'</div>',
									'</td>'
								].join(''));

								r += 1;

								if ( r === 3 || lastTplIdx === j ) { html.push('</tr>'); r = 0; p += 1; }
							});

							pageCount = p - 1;
						});

						buildPagination();
						
						var rowEl = $(html.join('')).appendTo(tbody);

						$('.made-for-newsman').tooltip();

						$('.carousel').carousel({ interval: false });

						findRowsTops();

						$('#templates-previews .btn-info').each(function(i, btn){
							initDownloadButton(btn);
						});
					}).fail(NEWSMAN.ajaxFailHandler);
				}
			});

		}

		$('#btn-open-store').click(function(e){

			showModal('#newsman-modal-template-store', function(mr){ return true; });

			buildTemplatesTable();			

		});			
	}

	/*******	 Manage Forms/Lists 	**********/

	if ( $('#newsman-forms').get(0) ) {
		(function(){

			var pageState = {
				// show: 'all',
				// pg: 1,
				// ipp: 15
			};

			function newForm(name) {
				var q = {
					action: 'newsmanAjCreateList',
					name: name
				};

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: q
				}).done(function(data){

					if ( data.id ) {
						var tbody = $('#newsman-forms tbody');
						$([
							'<tr>',
								'<td><input value="'+data.id+'" type="checkbox"></td>',
								//'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+data.id+'">'+data.name+'</a></td>',
								'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+data.id+'/all"><strong>'+data.name+'</strong></a><div class="newsman-inline-controls"><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+data.id+'">Edit Form</a> | <a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+data.id+'/all">View Subscribers</a></div></td>',
								'<td>0</td>',
								'<td>0</td>',
								'<td>0</td>',
							'</tr>'
						].join('')).appendTo(tbody);						
					} else {
						showMessage('Some error occured. template id is '+data.id, 'error');
					}

				}).fail(NEWSMAN.ajaxFailHandler);
			}

			function showNewFormDialog() {
				showModal('#newsman-modal-new-form', {

					show: function() {
						$('input', this).val('');
					},

					result: function(mr){
						var name = $('input', this).val();

						if ( mr == 'ok' ) {
							newForm(name, function(err){
								if ( !err ) {
									getForms();
								}
							});
						}
						return true;
					}
				});				
			}

			$('#btn-new-form').click(function(e){
				e.preventDefault();
				showNewFormDialog();
			});

			$('#btn-delete-forms').click(function(e){
				e.preventDefault();
				// ajDeleteEmailTemplates				
				var ids = [];
				$('#newsman-forms tbody input:checked').each(function(i, el){
					ids.push( parseInt($(el).val(), 10) );
				});

				if ( !ids.length ) {					
					showMessage(newsmanL10n.pleaseMarkFormsForDeletion);
				} else {
					showModal('#newsman-modal-delete', function(mr){
						if ( mr === 'ok' || mr === 'all' ) {
							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									ids: ids+'',
									action: 'newsmanAjRemoveLists'
								}
							}).done(function(data) {
								showMessage(data.msg, 'success');
								getForms();
							}).fail(NEWSMAN.ajaxFailHandler);
						}
						return true;
					});				
				}				
			});


			function getForms() {
				var q = $.extend({}, pageState);
				q.action = 'newsmanAjGetLists';

				function noData() {
					var tbody = $('#newsman-forms tbody').empty();
					$('<tr><td colspan="5" class="blank-row">'+newsmanL10n.youHaveNoFormsYet+'</td></tr>').appendTo(tbody);
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



				function fillRows(lists) {
					var tbody = $('#newsman-forms tbody').empty();
					if (lists.length) {
						$(lists).each(function(i, l){
							$(['<tr>',
									'<td><input value="'+l.id+'" type="checkbox"></td>',
									'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+l.id+'/all"><strong>'+l.name+'</strong></a><div class="newsman-inline-controls"><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+l.id+'">Edit Form</a> | <a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+l.id+'/all">View Subscribers</a></div></td>',
									'<td>'+(l.stats.confirmed || 0)+'</td>',
									'<td>'+(l.stats.unconfirmed || 0)+'</td>',
									'<td>'+(l.stats.unsubscribed || 0)+'</td>',
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
					fillRows(data.lists);
				}).fail(NEWSMAN.ajaxFailHandler);
			}


			function r(type, p) {
				type = type || 'all';
				p = p || 1;

				pageState.pg = parseInt(p, 10);
				pageState.show = type.toLowerCase();
				getForms();

				$('.subsubsub a.current').removeClass('current');
				$('#newsman-mailbox-'+type).addClass('current');
			}

			getForms();

			// var router = new Router({
			// 	'/newform': showNewFormDialog,
			// 	'/:type/:p': r,
			// 	'/:type': r				
			// });

			// router.init('/all/1');	


		})();
	}

	/*******	 HTML editor 	**********/

	// if ( $('#newsman-html-editor').get(0) ) {

	// 	if ( typeof NEWSMAN_ENT_STATUS !== 'undefined' && ( NEWSMAN_ENT_STATUS === 'stopped' || NEWSMAN_ENT_STATUS === 'error' ) ) {
	// 		$('#newsman-html-editor input[name="newsman-send"]').change(function(e){
	// 			$('#newsman-btn-send').text( ( $(this).val() === 'now' ) ? 'Resume' : 'Send');
	// 		});			
	// 	}		

	// 	/***
	// 	 * Pacth for dialog-fix ckeditor problem [ by ticket #4727 ]
	// 	 * 	http://dev.jqueryui.com/ticket/4727
	// 	 */

	// 	$.extend($.ui.dialog.overlay, { create: function(dialog){
	// 		if (this.instances.length === 0) {
	// 			// prevent use of anchors and inputs
	// 			// we use a setTimeout in case the overlay is created from an
	// 			// event that we're going to be cancelling (see #2804)
	// 			setTimeout(function() {
	// 				// handle $(el).dialog().dialog('close') (see #4065)
	// 				if ($.ui.dialog.overlay.instances.length) {
	// 					$(document).bind($.ui.dialog.overlay.events, function(event) {
	// 						var parentDialog = $(event.target).parents('.ui-dialog');
	// 						if (parentDialog.length > 0) {
	// 							var parentDialogZIndex = parentDialog.css('zIndex') || 0;
	// 							return parentDialogZIndex > $.ui.dialog.overlay.maxZ;
	// 						}
							
	// 						var aboveOverlay = false;
	// 						$(event.target).parents().each(function() {
	// 							var currentZ = $(this).css('zIndex') || 0;
	// 							if (currentZ > $.ui.dialog.overlay.maxZ) {
	// 								aboveOverlay = true;
	// 								return;
	// 							}
	// 						});
							
	// 						return aboveOverlay;
	// 					});
	// 				}
	// 			}, 1);
				
	// 			// allow closing by pressing the escape key
	// 			$(document).bind('keydown.dialog-overlay', function(event) {
	// 				(dialog.options.closeOnEscape && event.keyCode
	// 						&& event.keyCode == $.ui.keyCode.ESCAPE && dialog.close(event));
	// 			});
					
	// 			// handle window resize
	// 			$(window).bind('resize.dialog-overlay', $.ui.dialog.overlay.resize);
	// 		}
			
	// 		var $el = $('<div></div>').appendTo(document.body)
	// 			.addClass('ui-widget-overlay').css({
	// 			width: this.width(),
	// 			height: this.height()
	// 		});
			
	// 		(dialog.options.stackfix && $.fn.stackfix && $el.stackfix());
			
	// 		this.instances.push($el);
	// 		return $el;
	// 	}});			

	// 	$('#newsman-btn-send').click(function(e){

	// 		var toList = $('#eml-to').multis('getItems');
	// 		if ( !toList.length ) {
	// 			showMessage(newsmanL10n.pleaseChooseSubscribersList);
	// 			return;
	// 		}

	// 		if ( !sendValidation.validate() ) {
	// 			return;
	// 		}			

	// 		var opt = $('#newsman-send-form input[name="newsman-send"]:checked').val();
	// 		var datetime = $('#newsman-send-datepicker').datetimepicker('getDate');

	// 		$.ajax({
	// 			type: 'POST',
	// 			url: ajaxurl,
	// 			data: {
	// 				action: "newsmanAjScheduleEmail",
	// 				id: NEWSMAN_ENTITY_ID,
	// 				send: opt,
	// 				ts: Math.round(datetime.getTime() / 1000)
	// 			}
	// 		}).done(function(data){
	// 			showMessage(data.msg, 'success', function(){
	// 				if ( data.redirect ) {
	// 					window.location = data.redirect;
	// 				}					
	// 			});
	// 		}).fail(NEWSMAN.ajaxFailHandler);
	// 	});

	// 	function setPostTemplateType(callback) {

	// 		var newType, et = $('#newsman-content-type').val(),
	// 			etMap = {
	// 				'content': 'post_content',
	// 				'excerpt': 'post_excerpt',
	// 				'fancy': 'fancy_excerpt'
	// 			};
	// 			newType = etMap[et] || 'post_content';

	// 		$.ajax({
	// 			url: ajaxurl,
	// 			data: {
	// 				action: 'newsmanAjSetPostTemplateType',
	// 				entType: NEWSMAN_ENT_TYPE,
	// 				entity: NEWSMAN_ENTITY_ID,
	// 				postType: $('#newsman-post-type').val(),
	// 				postTemplateType: newType
	// 			}
	// 		}).done(function(e){
	// 			callback(null);
	// 		}).fail(function(xhr){
	// 			callback( new Error(xhr.responseText) );
	// 		});
	// 	}

	// 	$('#btn-add-posts').click(function(e){
	// 		//newsman-modal-add-posts

	// 		showModal('#newsman-modal-add-posts', function(mr){
				
	// 			if ( mr == 'cancel' ) {
	// 				// 
	// 			} else if ( mr == 'insert' ) {

	// 				showLoading();

	// 				// first we update the post template with the type of 
	// 				// content which we want to insert
	// 				setPostTemplateType(function(err){
	// 					if ( err ) { console.error(err); }

	// 					$.ajax({
	// 						type: 'POST',
	// 						url: ajaxurl,
	// 						data: {
	// 							pids: postsSelector.getIDS()+'',
	// 							entType: NEWSMAN_ENT_TYPE,
	// 							entity: NEWSMAN_ENTITY_ID,
	// 							action: 'newsmanAjCompilePostsBlock',
	// 							showTmbPlaceholder: $('#newsman-show-thmb-placeholder').is(':checked') ? 1 : 0
	// 						}						
	// 					}).done(function(data){

	// 						var idoc = $('#tpl-frame').get(0).contentDocument;
	// 						$('[gsspecial="posts"]', idoc).html(data.content);

	// 						$('#tpl-frame').get(0).newsmanInit();

	// 						postsSelector.clearSelection();

	// 					}).fail(NEWSMAN.ajaxFailHandler).always(function(){
	// 						hideLoading();
	// 					});
	// 				});					

	// 			}
	// 			return true;
	// 		});
	// 	});

	// 	/**
	// 	 *		Editing template particles
	// 	 */

	// 	var currentParticleName = '';

	// 	function getParticleSource() {
	// 		$.ajax({
	// 			type: 'POST',
	// 			url: ajaxurl,
	// 			data: {
	// 				name: currentParticleName,
	// 				entType: NEWSMAN_ENT_TYPE,
	// 				entity: NEWSMAN_ENTITY_ID,
	// 				action: 'newsmanAjGetEntityParticle'
	// 			}
	// 		}).done(function(data){
	// 			var html = data.particle;

	// 			$('#dialog').editorDialog({
	// 				edSelector: '.source-editor',
	// 				save: function(ev, data) {
	// 					saveParticleSource(data.html);
	// 					$('#dialog').editorDialog('close');
	// 				}
	// 			}).editorDialog('setData', html)
	// 			  .editorDialog('open');				
	// 		}).fail(NEWSMAN.ajaxFailHandler);
	// 	}

	// 	function saveParticleSource(newContent) {

	// 		$.ajax({
	// 			type: 'POST',
	// 			url: ajaxurl,
	// 			data: {
	// 				name: currentParticleName,
	// 				entType: NEWSMAN_ENT_TYPE,
	// 				entity: NEWSMAN_ENTITY_ID,
	// 				content: newContent,
	// 				action: 'newsmanAjSetEntityParticle'
	// 			}
	// 		}).done(function(data){
	// 			showMessage(data.msg, 'success');
	// 		}).fail(NEWSMAN.ajaxFailHandler);
	// 	}

	// 	$('#btn-edit-postblock').click(function(e){

	// 		currentParticleName = 'post_block';
	// 		getParticleSource();

	// 	});

	// 	$('#btn-edit-post-divider').click(function(e){
	// 		currentParticleName = 'post_divider';
	// 		getParticleSource();
	// 	});

	// 	$('#btn-save-content').click(function(){
	// 		saveParticleSource();
	// 	});		

	// 	window.newsmanHtmlEditorContentLoaded = function(){
	// 		var frm = $('#tpl-frame'),
	// 			w = frm.width();
	// 		setTimeout(function() {
	// 			frm.width(w+1);				
	// 			setTimeout(function() { 
	// 				frm.width(w);
	// 			}, 0);
	// 		}, 0);
	// 	};


	// 	// Code to fix iframe redraw bug. not fully fixed, but at least something
	// 	$(window).load(function(){
	// 		var t;

	// 		$($('#tpl-frame').contents()).scroll(function(e){
	// 			if ( t ) {
	// 				clearTimeout(t);
	// 				t = null;
	// 			}				
	// 			t = setTimeout(function() {
	// 				var h = $('#tpl-frame').height();

	// 				$('#tpl-frame').css({ height: (h+1)+'px' });
	// 				setTimeout(function(){
	// 					$('#tpl-frame').css({ height: h+'px' });
	// 				}, 1);
	// 			}, 100);
	// 		});
	// 	});
	// }

	/* Common functionality */

	var currentParticleName;

	function saveParticle(content) {
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				name: currentParticleName,
				entType: NEWSMAN_ENT_TYPE,
				entity: NEWSMAN_ENTITY_ID,
				content: content,
				action: 'newsmanAjSetEntityParticle'
			}
		}).fail(NEWSMAN.ajaxFailHandler);
	}

	function editParticle() {

		var dlg;

		showLoading();

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
			hideLoading();
			
			var html = data.particle;

			 $('#dialog').editorDialog({
				edSelector: 'editor1',
				save: function(ev, data) {
					saveParticle(data.html);
				}
			}).editorDialog('setData', html).editorDialog('open');

		}).fail(NEWSMAN.ajaxFailHandler);		
	}

	$('#btn-edit-post-tpl').click(function(){
		currentParticleName = 'post_block';
		editParticle();
	});

	$('#btn-edit-divider-tpl').click(function(){
		currentParticleName = 'post_divider';
		editParticle();
	});

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

			}).fail(NEWSMAN.ajaxFailHandler);
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
				value: JSON.stringify(items)
			}
		}).fail(NEWSMAN.ajaxFailHandler);
	});	

	$('#btn-send-test-email').click(function(){
		if ( editor ) {
			editor.setMode('wysiwyg');
		}

		for (var ins in CKEDITOR.instances) {
			if ( CKEDITOR.instances.hasOwnProperty(ins) ) {
				CKEDITOR.instances[ins].fire('changed');
			}
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
				}).fail(NEWSMAN.ajaxFailHandler).always(function(){
					$('.modal-loading-block', that).hide();
					$('.btn[mr="ok"]', that).removeAttr('disabled');
				});		

			} else {
				return true;	
			}				
		});
	});	

	/***   ajax forms   ***/ 

	$('.newsman-ajax-from').each(function(i, form){
		$(form).submit(function(e){
			e.preventDefault();
			var action = $(this).attr('action'),
				dataObj = {
					action: 'newsman'+action.replace(/\b[a-z]/g, function(с) {
						return с.toUpperCase();
					})
				};

			$($(this).serializeArray()).each(function(i, el){
				dataObj[el.name] = el.value;
			});

			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: dataObj
			}).done(function(data){
				if ( data.msg && data.msg !== 'success' ) {
					showMessage(data.msg, 'success');	
				}
				if ( NEWSMAN.ajFormReq[action] ) {
					NEWSMAN.ajFormReq[action](data);
				}				
			}).fail(NEWSMAN.ajaxFailHandler);

			return false;
		});
	});

	if ( typeof NEWSMANEXT !== 'undefined' && NEWSMANEXT.initAnalyticsControls) {
		NEWSMANEXT.initAnalyticsControls();
	}

});