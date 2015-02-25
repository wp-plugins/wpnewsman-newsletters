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

		if (extensions && extensions.preprocessing)
			text = extensions.preprocessing(text);

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

		if (extensions && extensions.tagreplacement)
			text = extensions.tagreplacement(text);

		var doubleNewlineTags = ['p', 'h[1-6]', 'dl', 'dt', 'dd', 'ol', 'ul',
			'dir', 'address', 'blockquote', 'center', 'div', 'hr', 'pre', 'form',
			'textarea', 'table'];

		var r, singleNewlineTags = ['li', 'del', 'ins', 'fieldset', 'legend',
			'tr', 'th', 'caption', 'thead', 'tbody', 'tfoot'];

		for (i = 0; i < doubleNewlineTags.length; i++) {
			r = RegExp('</?\\s*' + doubleNewlineTags[i] + '[^>]*>', 'ig');
			text = text.replace(r, '\n\n');
		}

		for (i = 0; i < singleNewlineTags.length; i++) {
			r = RegExp('<\\s*' + singleNewlineTags[i] + '[^>]*>', 'ig');
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

		if (extensions && extensions.postprocessing)
			text = extensions.postprocessing(text);

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
		return (code === undefined || isNaN(code)) ?
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

	window.newsmanStopWorker = function() {

		var workerId = prompt('Please enter worker ID', '');

		if ( !workerId ) { return; }

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				action: 'newsmanAjStopWorker',
				id: workerId
			}
		}).done(function(data){
			showMessage(data.msg, 'success');
		}).fail(NEWSMAN.ajaxFailHandler);
	};

	$(document).on('click', '[data-dismiss="newsman-admin-notification"]', function(e){
		$(this).closest('.newsman-admin-notification').animate({ height: 'toggle', opacity: 'toggle' }, 'slow');
	});

	function supplant(str, o) {
		return str.replace(/{([^{}]*)}/g,
			function (a, b) {
				var r = o[b];
				return typeof r === 'string' || typeof r === 'number' ? r : a;
				}
		);
	};	

	if ( typeof NEWSMAN_LOCALE !== 'undefined' ) {
		moment.locale(NEWSMAN_LOCALE.substr(0,2));
	}

	/******* Pagination widget ********/

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
				$('<li><a class="newsman-pagination-prev" href="#">&laquo;</a></li>').appendTo(ul);
			}

			if ( o.showPrevNext ) {
				$('<li><a class="newsman-pagination-next" href="#">&raquo;</a></li>').appendTo(ul);
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
				ul = $('ul', this.element);

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

	/******* Pagination widget ********/

	$.widget('newsman.newsmanSmallPagination', {
		options: {
			pageCount: 10,
			currentPage: 1
		},
		_create: function() {
			var o = this.options,
				$el = $(this.element).empty();

				$el.addClass('newsman-small-table-nav');

				$([
					'<span>Page </span><span class="cur-page">1</span> of <span class="total-pages">10</span>',
					'<button class="btn newsman-small-nav-left"><i class="newsman-icon newsman-icon-chevron-left"></i></button>',
					'<button class="btn newsman-small-nav-right"><i class="newsman-icon newsman-icon-chevron-right"></i></button>'
				].join('')).appendTo($el);

				this.lblCurPage = $('.cur-page', $el);
				this.lblTotalPages = $('.total-pages', $el);

				this.btnLeft = $('.newsman-small-nav-left', $el);
				this.btnRight = $('.newsman-small-nav-right', $el);

				// <div class="newsman-small-table-nav">
				// 	Page <span class="cur-page">1</span> of <span class="total-pages">10</span>
				// 	<button class="btn"><i class="newsman-icon newsman-icon-chevron-left"></i></button>
				// 	<button class="btn"><i class="newsman-icon newsman-icon-chevron-right"></i></button>
				// </div>
				this._bind();
		},
		_unbind: function(){
			this.btnLeft.unbind('click');
			this.btnRight.unbind('click');
		},
		_bind: function() {
			var that = this;
			this.btnLeft.click(function(e){
				e.preventDefault();
				that.prev();
			});
			this.btnRight.click(function(e){
				e.preventDefault();
				that.next();
			});
		},
		_updateInterface: function() {
			this.lblCurPage.html(this.options.currentPage);
			this.lblTotalPages.html(this.options.pageCount);
		},
		next: function() {
			this.options.currentPage += 1;
			this._trigger('pageChanged', {}, { page: this.options.currentPage });	
			this._updateInterface();
		},
		prev: function() {
			this.options.currentPage -= 1;
			this._trigger('pageChanged', {}, { page: this.options.currentPage });	
			this._updateInterface();
		},
		setOptions: function(opts) {
			$.extend(this.options, opts);			
			this._updateInterface();
		}
	});

	// php comaptible sprintf function for l10n capabilities
	// taken from http://phpjs.org/functions/sprintf/
	function sprintf () {
		var regex = /%%|%(\d+\$)?([\-+\'#0 ]*)(\*\d+\$|\*|\d+)?(\.(\*\d+\$|\*|\d+))?([scboxXuideEfFgG])/g;
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
			if (precision !== null) {
				value = value.slice(0, precision);
			}
			return justify(value, '', leftJustify, minWidth, zeroPad, customPadChar);
		};

		// doFormat()
		var doFormat = function (substring, valueIndex, flags, minWidth, _, precision, type) {
			var number,
				prefix,
				method,
				textTransform,
				value;

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

	window.NEWSMAN.sprintf = sprintf;

	NEWSMAN.systemTplTypesMap = {
		'1': 'NEWSMAN_ET_WELCOME',
		'2': 'NEWSMAN_ET_ADDRESS_CHANGED',
		'3': 'NEWSMAN_ET_ADMIN_SUB_NOTIFICATION',
		'4': 'NEWSMAN_ET_ADMIN_UNSUB_NOTIFICATION',
		'5': 'NEWSMAN_ET_CONFIRMATION',
		'6': 'NEWSMAN_ET_UNSUBSCRIBE',
		'7': 'NEWSMAN_ET_UNSUBSCRIBE_CONFIRMATION',
		'8': 'NEWSMAN_ET_RECONFIRM'
	};

	function getSysTplDescription(type) {
		var typeDef = NEWSMAN.systemTplTypesMap[type+''];
		return newsmanL10n[typeDef] || '';
	}


	var showMessage = NEWSMAN.showMessage = function(msg, type, cb, rawError, pinMessage) {

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
		if ( type !== 'error' && !pinMessage ) {
			setTimeout(close, 2000);
		}		
	};

	NEWSMAN.ajaxFailHandler = function(t, status, message) {

		if ( t.readyState < 3 ) {
			// if we didn't establish the connection to the server
			err = 'Cannot connect to the server. Data: '+this.data;
			// showMessage(err, 'error', null, {
			// 	responseText: t.responseText
			// });
			if ( typeof console !== 'undefined' ) {
				console.error(err);	
			}			
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

	var ajax = NEWSMAN.ajax = function(action, data, done, fail, always) {

		always = always || function(){};

		if ( !fail ) {
			fail = NEWSMAN.ajaxFailHandler;
		} else {
			var failBack = fail;
			fail = function() {
				failBack.apply(this, arguments);
				NEWSMAN.ajaxFailHandler(this, arguments);
			};
		}

		data.action = 'newsman'+action.replace(/^./, function(str){ return str.toUpperCase(); });

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: data
		}).success(done).fail(fail).always(always);
	};

	$(document).on('change', '.newsman-cb-selectall', function(e){
		var tbody = $(this).closest('table').find('tbody');
		$('input[type="checkbox"]', tbody).prop('checked', $(this).prop('checked'));
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
		var w = msg.width();		
		msg.css({
			marginLeft: Math.ceil(w/2)*-1
		}).show();
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
				ssl: 'ssl' // 'tls', 'ssl'
			},
			ses: {
				host: 'email-smtp.us-east-1.amazonaws.com',
				username:'your Amazon SES smtp username',
				password: '',
				port: 465,
				ssl: 'ssl' // 'tls', 'ssl'
			}
		};

		if ( presets[presetName] ) { 
			load(presets[presetName]);
		}
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
				p = p || [];
				for ( var name in obj ) {
					if ( isObject(obj[name]) ) {
						optToArr(obj[name], p.concat([name]));
					} else {
						o[ [].concat(p, [name]).join('-') ] = obj[name];
					}
				}
			}

			optToArr(opts);

			var pathName, name, el, xtraEl, xtraElAttr, cb, f = 'val';

			for ( pathName in o ) {
				name = 'newsman-'+pathName;
				f = 'val';
				if ( typeof o[pathName] === 'boolean' ) {
					cb = $('input[name="'+name+'"]'); 

					cb.prop('checked', !!o[pathName]);					
				} else {
					var radios = $('input[name="'+name+'"]').filter('[type="radio"]').prop('checked', false).length;
					if ( radios ) {
						$('input[name="'+name+'"]').filter('input[value="'+o[pathName]+'"]').prop('checked', true);
					} else {
						$('input[name="'+name+'"], textarea[name="'+name+'"], select[name="'+name+'"]').not('[type="radio"]').val(o[pathName]);
						$('input[name="'+name+'"]').filter('[type="hidden"]').change();

						// newsman-bind-option="apiKey" newsman-attr="data-clipboard-text"
						xtraEl = $('[newsman-bind-option="'+pathName+'"]');
						if ( xtraEl[0] ) {
							xtraElAttr = xtraEl.attr('newsman-attr');
							if ( xtraElAttr ) {
								xtraEl.attr( xtraElAttr, o[pathName] );
							}
						}
					}					
				}				
			}

			

			$('button[data-clipboard-text]').each(function(i, el){
				new ZeroClipboard(el);
			});

			if ( NEWSMAN.refreshMDO ) {
				NEWSMAN.refreshMDO();	
			}			

			if ( typeof callback === 'function' ) {
				callback();
			}

		}).fail(NEWSMAN.ajaxFailHandler);			
	}

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
				v = ( $.isNumeric(v) ) ? parseInt(v, 10) : v;
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

		showModal('#newsman-modal-uninstall', function(mr){
			if ( mr === 'ok' ) {
				$.ajax({
					url: ajaxurl,
					type: 'post',
					data: {
						action: 'newsmanAjRunUninstall',
						deleteSubs: $('#cb-deleteSubscribers').prop('checked') ? 1 : 0
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

	/******* Modal Dialogs **********/

	var mrOk, mrCancel, mrCallback;

	function showModal(id, opts) {

		opts = opts || {};

		if ( typeof opts == 'function' ) {
			var resultCallback = opts;
			opts = { result: resultCallback };
		}

		mrCallback = function(modalResult, xmr) {
			var res = opts.result.call($(id), modalResult, xmr);
			if ( res ) {
				mrCallback = null;
			}
			if ( opts.close ) {
				opts.close.call($(id));
			}
			return res;			
		};

		if ( opts.show ) {
			opts.show.call($(id));
		}

		$(id).find('[type="checkbox"]').each(function(i, cbx){
			$(cbx).prop('checked', false);
		});

		$(id).modal({ show: true, keyboard: true });
	}

	function showDeleteDialog(id, opts) {
		opts = opts || {};

		if ( typeof opts == 'function' ) {
			var resultCallback = opts;
			opts = { result: resultCallback };
		}

		var messages = jQuery.extend({
			areYouSureYouWantToDeleteXSelectedItems: 'Are you sure you want to delete {x} selected items?',
			areYouSureYouWantToDeleteXItemsMatchedSearchQSearchQuery: 'Are you sure you want to delete {x} items matched {q} search query?',
			areYouSureYouWantToDeleteXItems: 'Are you sure you want to delete {x} items?'
		}, opts.messages);

		// opts.vars.selected = 123123

		function renderMsg(name, varsOverride) {
			var v = $.extend({}, opts.vars, varsOverride || {})
			$('.modal-body > p', $(id)).html( supplant(messages[name], v) );
		}

		function disableButtons() {
			//$('.modal-body > p', $(id)).html( 'Please wait...' );
			$('.modal-body > p', $(id)).html('<center><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> Loading...</center>');
			$('.modal-footer .btn', $(id)).attr('disabled', 'disabled');
		}

		function enableButtons() {
			$('.modal-footer .btn', $(id)).removeAttr('disabled');
		}

		var origianlOpts = opts,
			cbAllChecked = function(){
				var checked = $(this).prop('checked');
				if ( checked ) {
					if ( origianlOpts.getCount ) {
						disableButtons();
						origianlOpts.getCount(function(err, c){
							enableButtons();
							if ( err ) { return console.error(err); }
							if ( opts.vars.q ) {
								renderMsg('areYouSureYouWantToDeleteXItemsMatchedSearchQSearchQuery', { x: c });
							} else {
								renderMsg('areYouSureYouWantToDeleteXItems', { x: c });
							}							
						});
					}
				} else {
					renderMsg('areYouSureYouWantToDeleteXSelectedItems');
				}
			};

		showModal(id, {
			show: function() {
				renderMsg('areYouSureYouWantToDeleteXSelectedItems');
				$('.modal-footer input[type="checkbox"]', this).on('change', cbAllChecked);
				if ( origianlOpts.show ) {
					origianlOpts.show.call(this);	
				}				
			},
			result: origianlOpts.result,
			close: function() {
				$('.modal-footer input[type="checkbox"]', this).off('change', cbAllChecked);
			}
		});	
	}

	$('.modal.dlg .btn, .modal.dlg .tpl-btn').click(function(e){

		var modal = $(this).closest('.modal.dlg'),
			xmr = {};  // extended modal results

		$('[xmr]', modal).each(function(i, el){
			var name = $(el).attr('xmr');			
			xmr[name] = ( $(el).prop('type') === 'checkbox' ) ? $(el).prop('checked') : $(el).val();
		});

		var mr = $(this).attr('mr');

		if ( mr && mrCallback ) {
			if ( mrCallback(mr, xmr) !== false ) {
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
			onSelect: null
		},
		rawContainer = $(container).get(0),
		startEl = null,
		lastAction;

		opts = $.extend(def, opts);

		function clickHandler(ev) {
			el = $(this);

			if ( startEl && ev.shiftKey ) {

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

	function newsmanPage(baseElementSelector, callback) {
		if ( $(baseElementSelector).get(0) ) {
			callback();
		}
	}

	/*******    Post selector iframe   **********/

	newsmanPage('#post-selector', function(){
			var ul = $('#newsman-posts').empty();

			var postsSelector = NEWSMAN.postsSelector = {};

			var paging = {
				page: 1,
				ipp: 15
			};

			var ct = $.cookie("newsmanDefaultPostContentType") || 'fancy';
			$('#newsman-content-type').val(ct);

			$('#newsman-content-type').change(function(){
				var ct = $('#newsman-content-type').val();
				$.cookie("newsmanDefaultPostContentType", ct);				
			})

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
				selectedText: newsmanL10n.nOfNCategoriesSelected,
				noneSelectedText: newsmanL10n.selectCategories,
				selectedList: 4
			});

			$('#newsman-bcst-sel-auth').multiselect({
				selectedText: newsmanL10n.nOFNAuthorsSelected,
				noneSelectedText: newsmanL10n.selectAuthors,
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
						auths: cauths
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


						shiftSelector(container.get(0), {
							onSelect: function() { }
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
	});

	// move to separate file

	$.widget('newsman.importForm', {
		options: {
			list: null, 
			formPanel: null,
			delimiter: ',',
			skipFirstRow: false,	
			status: 'confirmed',		
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

			var rdStatus = $('input[name="apply-status"]');
			rdStatus.change(function(){
				that.options.status = $(this).val();
			});
			this.options.status = rdStatus.filter(':checked').val();

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
			this.notice = $(this.options.formPanel+' .import-form-notice');

			this.showInfo('selectFile');
		},
		showInfo: function(type) {
			this.info.html(this.options.messages[type] || 'Error');
			this.notice[(type === 'selectFile') ? 'show' : 'hide']();
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
				status: this.options.status,
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

	/*******    Manage Subscribers    **********/

	newsmanPage('#newsman-mgr-subscribers', function() {

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
		});

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
		};

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

			// loading
			var tbody = $('#newsman-mgr-subscribers tbody').empty();
			$('<tr><td colspan="6" class="blank-row"><img src="'+NEWSMAN_PLUGIN_URL+'/img/ajax-loader.gif"> '+newsmanL10n.loading+'</td></tr>').appendTo(tbody);
			// --

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
						var row = $(['<tr>',
								'<td><input value="'+r.id+'" type="checkbox"></td>',
								'<td><span class="email">'+r.email+'</span></td>',
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
			action: NEWSMAN_PLUGIN_URL+'/wpnewsman-upload',
			params: {
				type: 'csv'
			},
			button: $('#newsman-modal-import .qq-upload-button').get(0),
			onAdd: function(e, obj) {
				iform.importForm('addFile', obj);
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
			window.location = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&action=export_newsman_list&listId=' + ($('#newsman-lists').val() || '1')+'&type='+pageState.show;
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

		$('#newsman-btn-validate').click(function(){
			showLoading();
			$.ajax({
				type: 'POST',
				url: ajaxurl,
				data: {
					action: 'newsmanAjCheckEmailAddresses',
					listId: $('#newsman-lists').val() || '1'
				}
			}).done(function(data){

				showMessage(data.msg, 'success');
				getSubscribers();

			}).fail(NEWSMAN.ajaxFailHandler).always(function(){
				hideLoading();
			});
		});

		var validaBtnShown = false;
		$(document).on('keydown', function(e){
			if ( e.altKey ) {
				$('#newsman-btn-validate').show();
				validaBtnShown = true;
			}
		});

		$(document).on('keyup', function(e){
			if ( validaBtnShown ) {
				validaBtnShown = false;
				$('#newsman-btn-validate').hide();
			}
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
		 * so we remember the current position in the list to get back to it
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
				enableFormEditButton();
			}			
		});

		enableFormEditButton();		

		// -----------	

		// check all checkbox
		$('#newsman-checkall').change(function(e){
			$('#newsman-mgr-subscribers tbody input').prop('checked', $(this).is(':checked') );
		});

		function clearSelection() {
			$('#newsman-mgr-subscribers tbody input[type="checkbox"]:checked').prop('checked', false);
		}

		// unsubscribe button
		$('#newsman-btn-unsubscribe').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubsWhichYouWantToUnsub);
			} else {
				showModal('#newsman-modal-unsubscribe', function(mr, xmr){
					if ( mr === 'ok' ) {

						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								all: xmr.all ? '1' : '0',
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
				showDeleteDialog('#newsman-modal-delete', {
					messages: {
						areYouSureYouWantToDeleteXSelectedItems: 'Are you sure you want to delete <b>{x}</b> selected subscribers?',
						areYouSureYouWantToDeleteXItemsMatchedSearchQSearchQuery: 'Are you sure you want to delete <b>{x}</b> subscribers matched <b>"{q}"</b> search query?',
						areYouSureYouWantToDeleteXItems: 'Are you sure you want to delete <b>{x}</b> subscribers?'
					},
					vars: {
						x: ids.length,
						q: $('#newsman-subs-search').val()
					},
					getCount: function(done){						

						var type = pageState.show;
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								listId: $('#newsman-lists').val(),
								q: $('#newsman-subs-search').val(),
								type: type,
								action: 'newsmanAjCountSubscribers'
							}
						}).done(function(data){
							done(null, data.count);
						}).fail(NEWSMAN.ajaxFailHandler);
					},
					result: function(mr, xmr){
						if ( mr === 'ok' ) {

							var type = pageState.show;

							$.ajax({
								type: 'POST',
								url: ajaxurl,
								data: {
									ids: ids+'',
									all: xmr.all ? '1' : '0',
									listId: $('#newsman-lists').val() || '1', 
									type: type,
									q: $('#newsman-subs-search').val(),
									action: 'newsmanAjDeleteSubscribers'
								}
							}).done(function(data){

								showMessage(newsmanL10n.youHaveSucessfullyDeletedSelSubs, 'success');

								getSubscribers();

							}).fail(NEWSMAN.ajaxFailHandler);
						}
						return true;
					}
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

				showModal('#newsman-modal-chstatus', function(mr, xmr){
					if ( mr === 'ok' ) {
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								listId: $('#newsman-lists').val() || '1',
								all: xmr.all ? '1' : '0',
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

				showModal('#newsman-modal-chstatus', function(mr, xmr){
					if ( mr === 'ok' ) {
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								ids: ids+'',
								action: 'newsmanAjSetStatus',
								all: xmr.all ? '1' : '0',
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

		$('#newsman-btn-resubscribe').click(function(e){
			var resubscribeTplURL = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates#system-'+$('#newsman-lists').val();
			$('#lnk-resubscribe-tpl').attr('href', resubscribeTplURL);

			showModal('#newsman-modal-resubscribe', function(mr, xmr){
				if ( mr == 'ok' ) {
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'newsmanAjSendResubscribeRequest',
							listId: $('#newsman-lists').val() || '1'
						}
					}).done(function(data){
						showMessage(data.msg, 'success');
						if ( data.redirect ) {
							window.location = data.redirect;
						}						
					}).fail(NEWSMAN.ajaxFailHandler);
				}
				return true;
			});
		});

		$('#btn-resend-confirmation-req').click(function(e){
			var ids = [];
			$('#newsman-mgr-subscribers tbody input:checked').each(function(i, el){
				ids.push( parseInt($(el).val(), 10) );
			});

			if ( !ids.length ) {
				showMessage(newsmanL10n.pleaseMarkSubsToSendConfirmationTo);
			} else {
				$('#newsman-modal-chstatus .newsman-status').text(statusMap[ST_CONFIRMED+'']);

				showModal('#newsman-modal-resend-confirmation', function(mr, xmr){
					if ( mr == 'ok' ) {
						$.ajax({
							type: 'POST',
							url: ajaxurl,
							data: {
								action: 'newsmanAjResendConfirmation',
								listId: $('#newsman-lists').val() || '1',
								ids: ids+''
							}
						}).done(function(data){
							showMessage(data.msg, 'success');
							clearSelection();
						}).fail(NEWSMAN.ajaxFailHandler);
					}
					return true;
				});
			}			
		});

		// TODO add "add email" button here

		$('#newsman-sub-datepicker').datetimepicker({
			dateFormat: 'd MM, yy',
			controlType: 'select',
			timeFormat: 'hh:mm tt'
			// altFormat: "yy-mm-dd",
			// altField: '#newsman-bcst-start-date-sql'
		});


		// Adding & Editing of subscribers

		function showSubAddEditForm(listId, subId) {
			var modalBody = $('#newsman-modal-add-sub .modal-body');
			$('#newsman-sub-datepicker').datetimepicker('setDate', new Date());

			showLoading();

			ajax('ajGetAdminForm', {
				listId: listId,
				subId: subId
			}, function(data){
				modalBody.html(data.html);

				var d = data.sub;
				if ( d && !$.isArray(d) ) {
					if ( d['ts'] ) {
						$('#newsman-sub-datepicker').datetimepicker('setDate', moment(d['ts']).toDate());
					}
					if ( d.status ) {
						var statusMap = [
							'unconfirmed',
							'confirmed',
							'unsubscribed'
						];
						$('#newsman-sub-type').val(statusMap[parseInt(d.status, 10)]);
					}
					$( "input, textarea, select" ).each(function(i, el){
						var $el = $(el),
							value = d[el.name];

						if ( el.name === 'newsman-email' ) {
							$(el).val(d.email);
						} else if ( $el.attr('type') === 'checkbox' ) {
							$el.prop('checked', value == '1');
						} else if ( $el.attr('type') === 'radio' ) {							
							if ( $el.val() == value ) {
								$el.prop('checked', true);	
							}							
						} else {
							if ( value ) {
								$el.val(value);
							}
						}
					});							
				}

				// data.sub				

				showModal('#newsman-modal-add-sub', function(mr){
					var modal = this;
					if ( mr == 'ok' ) {

						var formArr = $('form', modalBody).serializeArray(),
							formObj = {}

						$(formArr).each(function(i, p){
							formObj[p.name] = p.value;
						});

						formObj['email'] = formObj['newsman-email'];
						delete formObj['newsman-email'];
						delete formObj['uid'];
						delete formObj['newsman-form-url'];

						var submission = {
							form: formObj,
							date: $('#newsman-sub-datepicker').datetimepicker('getDate'),
							type: $('#newsman-sub-type').val()
						};

						ajax('ajSaveSubscriber', {
							json: JSON.stringify(submission),
							listId: listId,
							subId: subId
						}, function(data){
							getSubscribers();
							modal.modal('hide');
						});
					}
				});				
			}, null, function(){
				// always
				hideLoading();
			});
		}

		$('#newsman-btn-add-subscriber').click(function(e){
			var listId = $('#newsman-lists').val();		

			showSubAddEditForm(listId);
		});

		$(document).on('click', '#newsman-mgr-subscribers .email', function(e){
			var listId = $('#newsman-lists').val(),
				subId = $(e.srcElement).closest('tr').find('input').val();

			showSubAddEditForm(listId, subId);			
		});

		// ---

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

			$('.newsman-btn-reconfirm-group')[ type == 'unconfirmed' ? 'show' : 'hide' ]();

			$('.radio-links a.current').removeClass('current');
			$('#newsman-subs-'+type).addClass('current');
		}

		var router = new Router({
			'/:list/:type/:p': r,
			'/:list/:type': r
		});

		router.init('/'+defaultListId+'/all/1');
	});

	/*******    Manage Options        **********/

	newsmanPage('#newsman-page-options', function() {

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

		loadOptions();

		// mail delivery settings
		$('#smtp-btn-test-ph').click(function(){
			var btn = $(this),
				txt = btn.text(),
				email = $(this).closest('.control-group').find('input').val();

			function safeTrim(str){
				return $.trim(str+'').replace(/\u0000/g, '');
			}

			var q = {
				'host': safeTrim($('#newsman_smtp_hostname').val()),
				'user': safeTrim($('#newsman_smtp_username').val()),
				'pass': safeTrim($('#newsman_smtp_password').val()),
				'port': safeTrim($('#newsman_smtp_port').val()),
				'email': safeTrim(email),
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

		if ( $('#newsman_geolocation')[0] ) {
			NEWSMANEXT.initGeoipSettingsPage();
		}

	});

	/*******    Edit List & Form Options   **********/

	newsmanPage('#newsman-page-list', function() {

		$('#newsman-lists').change(function(e){
			var listId = $(this).val();
			window.location = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+listId;
		});


		//btn-view-subscribers

		function updateViewSubscribersBtn() {
			var listId = $('#newsman-lists').val();
			$('#btn-view-subscribers').attr('href', NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+listId+'/all');			
		}

		$('#newsman-lists').change(updateViewSubscribersBtn);

		updateViewSubscribersBtn();


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
					rx = /^newsman-form-(.*)/,
					res = rx.exec(name);
				if ( res ) {
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
				if (myValue.length === 0) {
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
	});

	/*******    CKeditor view  **********/

	newsmanPage('#newsman-editor', function() {

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

		NEWSMAN.editor = CKEDITOR.replace( 'content', {
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

		NEWSMAN.editor.on('newsmanSave.ckeditor', function(){
			changed();
			NEWSMAN.editor.fire('afterNewsmanSave.ckeditor');
		});

		$('#newsman-email-subj, #newsman-template-name').change(function(){
			changed();
		});

		$('#newsman-email-analytics').change(function(){
			saveEntity();
		});

		function sendEmail(done) {
			done = done || function(){};

			if ( !sendValidation.validate() ) {
				done();
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
					done();
				}).fail(function(jqXHR, status, msg){
					done(new Error(status+': '+msg));
					NEWSMAN.ajaxFailHandler.apply(NEWSMAN.ajaxFailHandler, arguments);
				});

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
			edBody = NEWSMAN.editor.document ? NEWSMAN.editor.document.getBody().$ : false,
			plain = convertToPlainText(edBody),
			html = NEWSMAN.editor.getData();

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
				data.emailAnalytics = $('#newsman-email-analytics').prop('checked') ? '1' : '0';
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

		$('#newsman-send-datepicker').change(function(){
			$('#newsman-schedule').prop('checked', true);
		});

		var sendBtn = $('#newsman-send');

		sendBtn.click(function(){
			sendBtn.attr('disabled', 'disabled');
			$('<img src="'+NEWSMAN_PLUGIN_URL+'/img/loading-grn.gif">').prependTo(sendBtn);
			NEWSMAN.editor.setMode('wysiwyg');
			sendEmail(function(err){
				$('img', sendBtn).remove();
				if ( !err ) {
					sendBtn.removeAttr('disabled');
				}
			});
		});
	});

	/*******    Manage Mailbox     **********/

	newsmanPage('#newsman-mailbox', function() {
		(function(){

			//var email = $('#newsman-email-search').val();
			var pageState = {
				show: 'all',
				pg: 1,
				ipp: 10
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

			$(document).on('click', '#newsman-mailbox tbody input', function(e){
				var n = $('#newsman-mailbox tbody input:checked').length;
				$('#newsman-btn-compose-from-msg')[ ( n === 1 ) ? 'show' : 'hide' ]();				
			});

			$('#newsman-btn-compose-from-msg').click(function(e){
				var id = $('#newsman-mailbox tbody input:checked')[0].value;
				window.location = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=compose-from-email&id='+id;
			});

			$(document).on('click',  '.newsman-email-start-sending', function(e){
				e.preventDefault();
				e.stopPropagation();

				var emlId = $(e.target).closest('.newsman-email').attr('emlid');
				if ( emlId ) {
					showLoading();
					$.ajax({
						type: 'POST',
						url: ajaxurl,
						data: {
							action: 'newsmanAjResumeSending',
							ids: emlId
						}
					}).done(function(data){
						showMessage(data.msg, 'success');
						getEmails();
					}).fail(NEWSMAN.ajaxFailHandler)
					  .always(function(){
						hideLoading();
					});
				}
			});

			$(document).on('click',  '.newsman-email-stop-sending', function(e){
				e.preventDefault();
				e.stopPropagation();

				var emlId = $(e.target).closest('.newsman-email').attr('emlid');
				if ( emlId ) {
					showLoading();

					ajax('ajStopSending', {
						ids: emlId
					}, function(data){
						showMessage(data.msg, 'success');
						getEmails();
					}, null, function(){
						hideLoading();
					});
				}
			});


			$(document).on('click', '.newsman-email-delete', function(e){
				e.preventDefault();
				e.stopPropagation();
				$('.newsman-email.checked').removeClass('checked');
				var emlId = $(e.target).closest('.newsman-email').addClass('checked').attr('emlid');
				if ( emlId ) {
					showDeleteDialog('#newsman-modal-delete', {
						messages: {
							areYouSureYouWantToDeleteXSelectedItems: 'Are you sure you want to delete selected email?',
							areYouSureYouWantToDeleteXItemsMatchedSearchQSearchQuery: 'Are you sure you want to delete <b>{x}</b> emails matched <b>"{q}"</b> search query?',
							areYouSureYouWantToDeleteXItems: 'Are you sure you want to delete this email?'
						},
						vars: {
							x: 1,
							q: ''
						},
						getCount: function(done){
							done(null, 1);
						},
						result: function(mr, xmr){
							if ( mr === 'ok' ) {
								$.ajax({
									type: 'POST',
									url: ajaxurl,
									data: {
										ids: emlId,
										action: 'newsmanAjDeleteEmails'
									}
								}).done(function(data){

									showMessage(newsmanL10n.youHaveSuccessfullyDeletedSelectedEmails, 'success');
									getEmails();

								}).fail(NEWSMAN.ajaxFailHandler);
							}
							return true;
						}
					});
				}
			});

			function showBulkActionButtons() {
				console.warn('showBulkActionButtons is not yet implented.');
			}

			function hideBulkActionButtons() {
				console.warn('hideBulkActionButtons is not yet implented.');
			}

			function updateUI() {
				if ( $('.newsman-email.checked').length > 1 ) {
					showBulkActionButtons();
				} else {
					hideBulkActionButtons();
				}
			}

			$(document).on('click', '.newsman-email', function(e) {				
				var eml = $(e.target).closest('.newsman-email');
				if ( eml ) {
					eml.toggleClass('checked');
					updateUI();
				}
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

					b.empty();

					$('<h3 style="margin-bottom: 1em;">'+sprintf(newsmanL10n.sentXofXemails, data.sent, data.recipients)+'</h3>').appendTo(b);

					if ( typeof NEWSMAN_BLOCKED_DOMAINS !== 'undefined' && NEWSMAN_BLOCKED_DOMAINS ) {
						$('<p>'+sprintf(newsmanL10n.someRecipientsMightBeFiltered, NEWSMAN_BLOCKED_DOMAINS)+'</p>').appendTo(b);	
					}				

					if ( data.msg ) {
						$('<h3 style="margin-bottom: 1em;">'+newsmanL10n.status+' '+data.msg+'</h3>').appendTo(b);	
					}					

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
								'</table>'
							].join('')).appendTo(b);
							return $('tbody',tbl);
						}
					}

					if ( data.errors.length ) {
						$('<h3 style="margin-bottom: .5em;">'+newsmanL10n.emailErrors+'</h3>').appendTo(b);

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

			var tmr = null;

			function runPolling() {
				if ( !tmr ) {
					tmr = setTimeout(function() {
						tmr = null;
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

						var clicks = r.clicks || 0,
							clicksPCT = !r.recipients ? 0 : ((clicks / r.recipients) * 100).toFixed(1),
							opens = r.opens || 0,
							opensPCT = !r.recipients ? 0 : ((opens / r.recipients) * 100).toFixed(1),
							unsubscribes = r.unsubscribes || 0,
							unsubscribesPCT = !r.recipients ? 0 : ((unsubscribes / r.recipients) * 100).toFixed(1);


						var emlStats = $('#newsman-eml-'+r.id+' .newsman-email-stats');

						emlStats.find('.newsman-email-sent .num').empty().append(formatSent(r));
						emlStats.find('.newsman-email-sent .extra-lbl').html(r.sent+' / '+r.recipients);

						emlStats.find('.newsman-email-opens .num').html(opens);
						emlStats.find('.newsman-email-opens .extra-lbl').html(opensPCT+'%');

						emlStats.find('.newsman-email-clicks .num').html(clicks);
						emlStats.find('.newsman-email-clicks .extra-lbl').html(clicksPCT+'%');

						emlStats.find('.newsman-email-unsubscribes .num').html(unsubscribes);
						emlStats.find('.newsman-email-unsubscribes .extra-lbl').html(unsubscribesPCT+'%');

						$('#newsman-eml-'+r.id+'-status').empty().append(formatStatus(r.status, fd));
						enableInlineStartStopButtons(r.status, r.id);
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
						var emailId = $(this).closest('.newsman-email').attr('emlid');
						showSendingLog(emailId);
					});	
				}

				return el;
			}

			function enableInlineStartStopButtons(st, emlId) {

				switch ( st ) {
					case 'sent': 
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').hide();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').hide();
						break;
					case 'pending': 
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').hide();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').show();
						break;
					case 'draft':
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').show();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').hide();					
						break;
					case 'inprogress': 
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').hide();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').show();					
						break;
					case 'stopped': 
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').show();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').hide();					
						break;
					case 'error': 
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').show();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').hide();
						break;
					case 'scheduled': 
						$('#newsman-eml-'+emlId+' .newsman-email-start-sending').hide();
						$('#newsman-eml-'+emlId+' .newsman-email-stop-sending').hide();
						break;
				}

			}

			function formatMsg(data) {
				if ( data.msg ) {
					return data.msg;
				} else {
					if ( data.recipients > 0 )	{
							var values = [
								data.sent,
								data.recipients
							];
						return newsmanL10n.sentXofXemails.replace(/\%d/ig, function(match){
								return values.shift();
						});
					} else {
						return '';
					}
				}				
			}

			function formatSent(data) {
				if ( !data.recipients ) { return 0; }
				var x = data.sent / data.recipients * 100;
				return Math.round(x)+'%';
			}

			function fillCounters(cnt) {
				if ( cnt ) {
					$('#newsman-mailbox-all').text( newsmanL10n.vAllEmails + ' ('+cnt.all+')');
					$('#newsman-mailbox-inprogress').text(newsmanL10n.vInProgress + ' ('+cnt.inprogress+')');
					$('#newsman-mailbox-draft').text(newsmanL10n.vDrafts + ' ('+cnt.draft+')');					
					$('#newsman-mailbox-pending').text( newsmanL10n.vPending + ' ('+cnt.pending+')');
					$('#newsman-mailbox-sent').text( newsmanL10n.vSent + ' ('+cnt.sent+')');					
				}
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

				function noData() {
					var tbody = $('#newsman-mailbox').empty();
					$('<div class="blank-row">'+newsmanL10n.youHaveNoEmailsYet+'</div>').appendTo(tbody);
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
							arr[i] = '<span class="label label-default">'+arr[i]+'</span>';
						}										
					} else {
						arr = [];
					}

					return arr.join('');
				}

				function fillRows(rows) {
					var tbody = $('#newsman-mailbox').empty();
					if (rows.length) {
						$(rows).each(function(i, r){

							var st = r.status,
								d = new Date( parseInt(r.schedule,10)*1000 );	

							//var fdate = $.datepicker.formatDate('D, d M y', d) +' '+$.datepicker.formatTime('hh:mm:ss', d);
							fdate = d.toLocaleString().replace(/\s+GMT.*/, '');

							function formatTS(uts) {
								var d = new Date(uts*1000),
									t = moment(d).format('LLL');
								return '<span class="newsman-tbl-emails-created" title="'+t+'" style="border-bottom: 1px dashed #cacaca; cursor: default;">'+moment(d).fromNow()+'</span>';
							}

							var clicks = r.clicks || 0,
								clicksPCT = !r.recipients ? 0 : ((clicks / r.recipients) * 100).toFixed(1),
								opens = r.opens || 0,
								opensPCT = !r.recipients ? 0 : ((opens / r.recipients) * 100).toFixed(1),
								unsubscribes = r.unsubscribes || 0,
								unsubscribesPCT = !r.recipients ? 0 : ((unsubscribes / r.recipients) * 100).toFixed(1),
								viewStatsHRef = '';

							// if lite version	
							if ( newsmanL10n.newsmanVersionClass === 'newsman-pro-version' ) {
								viewStatsHRef = 'href="'+NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=stats&id='+r.id+'"';
							}

							$([
								'<div emlid="'+r.id+'" id="newsman-eml-'+r.id+'" class="newsman-email">',
									'<div class="newsman-email-general">',
										'<div class="newsman-email-subject">',
											'<a href="'+NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=edit&id='+r.id+'">'+(r.subject || newsmanL10n.mbNoSubject)+'</a>',
										'</div>',
										'<div class="newsman-email-to">',
											formatTo(r.to),
										'</div>',
										'<ul class="newsman-email-bottom-btns">',
											'<li style="display:none;" class="newsman-email-start-sending"><a href="#"><i class="newsman-icon newsman-icon-play"></i> '+newsmanL10n.mbStart+'</a></li>',
											'<li style="display:none;" class="newsman-email-stop-sending"><a href="#"><i class="newsman-icon newsman-icon-stop"></i> '+newsmanL10n.mbStop+'</a></li>',
											'<li><a href="'+NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=edit&id='+r.id+'"><i class="newsman-icon newsman-icon-edit"></i> '+newsmanL10n.mbEdit+'</a></li>',
											'<li><a href="'+NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=compose-from-email&id='+r.id+'"><i class="newsman-icon newsman-icon-envelope"></i> '+newsmanL10n.mbDuplicate+'</a></li>',
											'<li>',
												'<a target="_blank" href="'+r.publicURL+'"><i class="newsman-icon newsman-icon-external-link"></i> '+newsmanL10n.viewInBrowser+'</a>',
											'</li>',
											'<li class="newsman-email-delete">',
												'<a href="#"><i class="newsman-icon newsman-icon-trash"></i> '+newsmanL10n.mbDelete+'</a>',
											'</li>',
										'</ul>',
										'<ul class="newsman-email-meta">',
											'<li class="newsman-email-created">',
												'<i class="newsman-icon newsman-icon-time"></i> '+formatTS(r.created),
											'</li>',
											'<li class="newsman-email-status">',
												'<span class="newsman-tbl-emails-status" id="newsman-eml-'+r.id+'-status"></span>',
											'</li>',
											'<li>&nbsp;</li>',											
											'<li class="newsman-email-view-stats '+newsmanL10n.newsmanVersionClass+'">',
												'<a '+viewStatsHRef+'><i class="newsman-icon newsman-icon-bar-chart"></i> '+newsmanL10n.viewStats+'</a>',
											'</li>',
										'</ul>',
									'</div>',
									'<div class="newsman-email-stats">',
										'<div class="newsman-email-sent newsman-email-stats-card">',
											'<span class="num">'+formatSent(r)+'</span>',
											'<span class="lbl">'+newsmanL10n.mbSent+'</span>',
											'<span class="extra-lbl">'+r.sent+' / '+r.recipients+'</span>',											
										'</div>',									
										'<div class="newsman-email-opens newsman-email-stats-card">',
											'<span class="num">'+opens+'</span>',
											'<span class="lbl">'+newsmanL10n.mbOpens+'</span>',
											'<span class="extra-lbl">'+opensPCT+'%</span>',
										'</div>',
										'<div class="newsman-email-clicks newsman-email-stats-card">',
											'<span class="num">'+clicks+'</span>',
											'<span class="lbl">'+newsmanL10n.mbClicks+'</span>',
											'<span class="extra-lbl">'+clicksPCT+'%</span>',											
										'</div>',
										'<div class="newsman-email-unsubscribes newsman-email-stats-card">',
											'<span class="num">'+unsubscribes+'</span>',
											'<span class="lbl">'+newsmanL10n.mbUnsubscribes+'</span>',
											'<span class="extra-lbl">'+unsubscribesPCT+'%</span>',											
										'</div>',
									'</div>',
								'</div>'
							].join('')).appendTo(tbody);

							enableInlineStartStopButtons(st, r.id);

							$('#newsman-eml-'+r.id+'-status').append(formatStatus(st, fdate));
						});
					} else {
						noData();
					}

					//debugger;
					// if lite version	
					if ( newsmanL10n.newsmanVersionClass === 'newsman-lite-version' ) {
						$('.newsman-email-view-stats a').tipsy({
							html: true,
							title: function() { return newsmanL10n.viewStatsHint; },
							delayIn: 500,
							delayOut: 3000,
							gravity: 's'
						});
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

				$('.radio-links a.current').removeClass('current');
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

			//TODO: rewrite for new UI

			$('#newsman-btn-stop').click(function(e){
				showLoading('Stopping...');
				var ids = [];
				$('.newsman-email.checked').each(function(i, el){
					var emlId = $(el).closest('.newsman-email').attr('emlid');
					ids.push( parseInt(emlId, 10) );
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
				}).fail(NEWSMAN.ajaxFailHandler).always(function(){
					hideLoading();
				});
			});

			//TODO: rewrite for new UI
			$('#newsman-btn-start').click(function(e){
				showLoading('Resuming...');
				var ids = [];
				$('.newsman-email.checked').each(function(i, el){
					var emlId = $(el).closest('.newsman-email').attr('emlid');
					ids.push( parseInt(emlId, 10) );
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
				}).fail(NEWSMAN.ajaxFailHandler).always(function(){
					hideLoading();
				});
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
						var url = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-templates#/newtemplate';
						$('<tr><td style="text-align: center;">'+newsmanL10n.youDontHaveAnyTemplates+' <a href="'+url+'">'+newsmanL10n.createOne+'</a></td></tr>').appendTo(tbody);
					}

				}).fail(NEWSMAN.ajaxFailHandler);

				showModal('#newsman-modal-compose', function(mr){
					return true;
				});
			});

			$('#newsman-btn-delete').click(function(e){
				var ids = [];

				$('.newsman-email.checked').each(function(i, el){
					var emlId = $(el).closest('.newsman-email').attr('emlid');
					ids.push( parseInt(emlId, 10) );
				});

				if ( !ids.length ) {
					showMessage(newsmanL10n.pleaseMarkEmailsForDeletion);
				} else {
					showDeleteDialog('#newsman-modal-delete', {
						messages: {
							areYouSureYouWantToDeleteXSelectedItems: 'Are you sure you want to delete <b>{x}</b> selected emails?',
							areYouSureYouWantToDeleteXItemsMatchedSearchQSearchQuery: 'Are you sure you want to delete <b>{x}</b> emails matched <b>"{q}"</b> search query?',
							areYouSureYouWantToDeleteXItems: 'Are you sure you want to delete <b>{x}</b> emails?'
						},
						vars: {
							x: ids.length,
							q: ''
						},
						getCount: function(done){
							done(null, ids.length);
						},
						result: function(mr, xmr){
							if ( mr === 'ok' ) {
								$.ajax({
									type: 'POST',
									url: ajaxurl,
									data: {
										ids: ids+'',
										action: 'newsmanAjDeleteEmails'
									}
								}).done(function(data){

									showMessage(newsmanL10n.youHaveSuccessfullyDeletedSelectedEmails, 'success');
									getEmails();

								}).fail(NEWSMAN.ajaxFailHandler);
							}
							return true;
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
						window.location = NEWSMAN_BLOG_ADMIN_URL+'/admin.php?page=newsman-mailbox&action=edit&id='+data.id;
					} else {
						getEmails();
					}
				}).always(function(){
					hideLoading();
				});


			});

		})();
	});

	/*******    Email Stats   **********/

	newsmanPage('#newsman-email-stats', function(){

		if ( typeof NEWSMANEXT !== 'undefined' && NEWSMANEXT.initEmailStatsPage) {
			NEWSMANEXT.initEmailStatsPage();
		}

	});

	/*******    Manage Templates   **********/

	newsmanPage('#newsman-templates', function() {
		(function(){

			//var email = $('#newsman-email-search').val();
			var pageState = {
				show: 'my-templates',
				pg: 1,
				ipp: 15,
				total: null,
				listId: null
			};

			$(document).on('click', 'a.restore-tpl', function(e){
				e.preventDefault();
				e.stopPropagation();

				if ( !confirm(newsmanL10n.areYouSureYouWantToRestoreStockTemplate) ) {
					return;
				}

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						action: 'newsmanAjReinstallStockTemplate',
						name: $(this).attr('tpl-name')
					}
				}).done(function(data){
					getTemplates();
				}).fail(NEWSMAN.ajaxFailHandler);
			});

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
				action: NEWSMAN_PLUGIN_URL+'/wpnewsman-upload',
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
					var tbody = $('#newsman-mailbox').empty();
					$('<tr><td colspan="5" class="blank-row">'+newsmanL10n.youHaveNoTemplatesYet+'</td></tr>').appendTo(tbody);
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

					var assignedListLbl = ( r.assigned_list > 0 && r.assigned_list_name ) ? '<span class="label label-info">'+r.assigned_list_name+'</span>' : '';

					var stock = ['digest', 'basic'];

					var reinstall = '';
					if ( stock.indexOf(r.name) >= 0 ) {
						reinstall = ' | <a class="restore-tpl" tpl-name="'+r.name+'" href="#restore">'+newsmanL10n.sRestore+'</a>';
					}
					

					var row = $(['<tr>',
							'<td><input value="'+r.id+'" type="checkbox"'+(r.system ? ' disabled="disabled"' : '')+'></td>',
							'<td>',
								'<a href="'+editURL+'" class="newsman-template-name">'+icon+' '+r.name+'</a>',
								assignedListLbl,
								!r.system ? '<div class="newsman-inline-controls"><a class="newsman-duplicate-tpl" href="#">'+newsmanL10n.copy+'</a> | <a href="'+editURL+'">'+newsmanL10n.sEdit+'</a> | <a class="newsman-delete-tpl" href="#">'+newsmanL10n.sDelete+'</a> | <a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates&action=download&id='+r.id+'">'+newsmanL10n.sExport+'</a>'+reinstall+'</div>':'',
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
						pageState.total = data.count;
						$('.newsman-tbl-controls .pagination')
							.newsmanPagination('setPageCount', pageCount);
					}
						
				}).fail(NEWSMAN.ajaxFailHandler);
			}

			// SYSTEM TEMPLATES ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

			var gotSystemEmailTemplates = false;

			function getSystemTemplates(callback) {
				callback = callback || function(){};

				if ( gotSystemEmailTemplates ) {
					callback();
					return;
				}

				var data = {
					action: 'newsmanAjGetSystemTemplates'
				};

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: data
				}).done(function(data){
					gotSystemEmailTemplates = true;
					$('#tabs-header .dropdown-menu').empty();

					getLanuages(function(err, langs){
						if ( data.lists && data.lists.length ) {
							$(data.lists).each(function(i, list){
								addSystemTemplatesTab(list, langs);
							});
						}
						callback();
					});

				}).fail(function(){
					NEWSMAN.ajaxFailHandler.apply(this, arguments);
					callback();
				});
			}

			function makeTabId(listName) {
				return 'tab-'+listName
								.replace(/^[^a-z0-9]*/ig, '')
								.replace(/[^a-z0-9]*$/ig, '')
								.replace(/[^a-z0-9]+/ig, '-')
								.toLowerCase();
			}

			function capitalize(str) {
				return str[0].toUpperCase()+str.substr(1);
			}

			function addSystemTemplatesTab(list, langs) {
				var menuUl = $('#tabs-header .dropdown-menu'),
					tc = $('#tabs-container');

				var listName = list['default'] ? newsmanL10n.defaultSystemTemplates : capitalize(list.listName),
					id = list['default'] ? 'system-default' : 'system-'+list.listId;

				$('#'+id).remove();

				function getTabelBody() {
					var out = [],
						icon = '<i class="icon-cog" rel="tooltip" title="'+newsmanL10n.systemTemplateCannotBeDeleted+'"></i>';

					$(list.templates).each(function(i, lst){
						var editURL = NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-templates&action=edit&id='+lst.id;
						out.push('<tr><td><a href="'+editURL+'" class="newsman-template-name">'+icon+' '+lst.name+'</a></td><td>'+getSysTplDescription(lst.system_type)+'</td></tr>');
					});
					return out.join('');
				}

				function getLangSwitchButton() {
					if ( !NEWSMAN_WPML_MODE ) { return ''; }
					var langList = '';
					$(langs).each(function(i, l){
						langList += '<li><a locale="'+l.locale+'">'+l.native+'</a></li>';
					});

					return [
						'<div class="btn-group" style="margin-left: 15px;">',
						  '<a class="btn btn-small dropdown-toggle" data-toggle="dropdown" href="#">',
						    'Load default templates ',
						    '<span class="caret"></span>',
						  '</a>',
						  '<ul class="dropdown-menu">',
						  	langList,
						  '</ul>',
						'</div>'					
					].join('');
				}


				$('<li><a href="#'+id+'" data-toggle="tab">'+listName+'</a></li>').appendTo(menuUl);

				var panel = $(['<div class="tab-pane fade list-pane" id="'+id+'" listid="'+list.listId+'">',
					'<h4>'+listName,
						getLangSwitchButton(),
					'</h4>',
					'<table class="table table-striped table-bordered">',
						'<thead>',
							'<tr>',
								'<th scope="col">'+newsmanL10n.name+'</th>',
							'</tr>',
						'</thead>',
						'<tbody>',
							getTabelBody(),
						'</tbody>',
					'</table>',
				'</div>'].join('')).appendTo(tc);

				$('a[locale]', panel).click(function(e){
					e.preventDefault();

					var listId = $(this).closest('.list-pane').attr('listid'),
						locale = $(this).attr('locale');

					loadTranslatedSystemEmailTemplates(listId, locale);
				});

			}

			function loadTranslatedSystemEmailTemplates(listId, locale) {

				showLoading();
				
				$.ajax(ajaxurl, {
					type: 'post',
					data: {
						listId: listId,
						loc: locale,
						action: 'newsmanAjInstallSystemEmailTemplatesForList'
					}
				}).done(function(data){

					gotSystemEmailTemplates = false;

					getSystemTemplates(function(){
						$('#tabs-header a[href="#system-'+listId+'"]').tab('show');
						hideLoading();
					});

				}).fail(function(){
					hideLoading();
					NEWSMAN.ajaxFailHandler.apply(this, arguments);
				})
			}

			function getLanuages(cb) {
				$.ajax(ajaxurl, {
					type: 'post',
					data: {
						action: 'newsmanAjGetAvailableLanuages'
					}
				}).done(function(data){
					cb(null, data.languages)
				}).fail(function(xhr){
					cb(new Error('Some error occured. Response: '+xhr.responseText));
				});
			}

			// /SYSTEM TEMPLATES ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

			function buildPagination(pageCount) {
				$('.newsman-tbl-controls .pagination')
					.newsmanPagination({
						pageCount: pageCount,
						currentPage: pageState.pg,
						pageChanged: function(ev, data) {
							window.location.hash = '#'+pageState.show+'/'+data.page;
						}
					});
			}			

			function r(p) {
				p = p || 1;

				pageState.pg = parseInt(p, 10);		
				getTemplates();
				$('.radio-links a.current').removeClass('current');
			}

			NEWSMAN.on('reloadTemplates', function(){
				getTemplates();
			});

			var router = window.rtr = createRouter({
				'system-default': function() {
					$('.newsman-tbl-controls .pagination').hide();
					getSystemTemplates(function(){
						//debugger;
						$('#tabs-header a[href="#system-default"]').tab('show');	
					});
				},
				'system-:id': function(id) {
					$('.newsman-tbl-controls .pagination').hide();
					getSystemTemplates(function(){
						///debugger;
						$('#tabs-header a[href="#system-'+id+'"]').tab('show');
					});
				},
				"my-templates": function(){
					this.redirect('my-templates/1');
				},
				'my-templates/:p': function(p) {
					getSystemTemplates();
					$('.newsman-tbl-controls .pagination').show();
					r(p);
				}
			});

			router.init('my-templates/1');	

			// Change hash for page-reload			
			$(document, '.nav-tabs a').on('shown', function (e) {
				window.location.hash = e.target.hash;
				e.preventDefault();
			});

		})();
	});

	/*******    Templates Store     ********/

	newsmanPage('#newsman-modal-template-store', function() {

		var currentPage = 1,
			pageCount = 10;

		var dialogContent = $('#newsman-modal-template-store .modal-body'),
			pagination,
			stores,
			disableScrollWatcher = false;

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
				function getTplName(url) {					
					return url.match(/\/([^\/]*?)\.zip$/)[1];
				}

				function getBadge(tpl) {
					var b = '';
					if ( tpl.responsive ) {
						b = '<img class="made-for-newsman" title="Responsive Template Made for WPNewsman" src="'+NEWSMAN_PLUGIN_URL+'/img/responsive.png"> ';
					} else if ( tpl.madeForNewsman ) {
						b = '<img class="made-for-newsman" title="Made for WPNewsman" src="'+NEWSMAN_PLUGIN_URL+'/img/star.png"> ';
					}
					return b;
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

				if ( !err ) {
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

						stores = data.stores;

						var tbody = $('#templates-previews tbody').empty(),
							storeSelectBox = $('#store-selector').empty(),
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

							$('<option value="'+name+'">'+store.title+'</option>').appendTo(storeSelectBox);

							var lastTplIdx = store.templates.length-1;

							$(store.templates).each(function(j, tpl){

								if ( r === 0 ) { html.push('<tr class="newsman-templates-row" id="newsman-store-p-'+p+'">'); }
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
	});

	/*******    Manage Forms/Lists    **********/

	newsmanPage('#newsman-forms', function() {
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
								'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+data.id+'/all"><strong>'+data.name+'</strong></a><div class="newsman-inline-controls"><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+data.id+'"><i class="newsman-icon newsman-icon-edit"></i> '+newsmanL10n.editForm+'</a> | <a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+data.id+'/all"><i class="newsman-icon newsman-icon-group"></i> '+newsmanL10n.viewSubscribers+'</a></div></td>',
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
					showModal('#newsman-modal-delete', function(mr, xmr){
						if ( mr === 'ok' ) {
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
									'<td><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+l.id+'/all"><strong>'+l.name+'</strong></a><div class="newsman-inline-controls"><a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers&action=editlist&id='+l.id+'"><i class="newsman-icon newsman-icon-edit"></i> '+newsmanL10n.editForm+'</a> | <a href="'+NEWSMAN_BLOG_ADMIN_URL+'admin.php?page=newsman-forms&sub=subscribers#/'+l.id+'/all"><i class="newsman-icon newsman-icon-group"></i> '+newsmanL10n.viewSubscribers+'</a></div></td>',
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

				$('.radio-links a.current').removeClass('current');
				$('#newsman-mailbox-'+type).addClass('current');
			}

			getForms();

		})();
	});

	/* Common functionality */

	var currentParticleName;

	function saveParticle(content, particleName) {
		$.ajax({
			type: 'POST',
			url: ajaxurl,
			data: {
				name: particleName,
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
				particleName: currentParticleName,
				save: function(ev, data) {
					saveParticle(data.html, data.particleName);
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
		if ( NEWSMAN.editor ) {
			NEWSMAN.editor.setMode('wysiwyg');
		}

		for (var ins in CKEDITOR.instances) {
			if ( CKEDITOR.instances.hasOwnProperty(ins) ) {
				CKEDITOR.instances[ins].fire('changed');
			}
		}

		var testEmail = $.cookie("newsmanTestEmailAddress") || '';
		$('#test-email-addr').val(testEmail);

		showModal('#newsman-modal-send-test', function(mr){
			var that = this;
			if ( mr == 'ok' ) {
				$('.modal-loading-block', this).show();
				$('.btn[mr="ok"]', this).attr('disabled', 'disabled');

				if (window.NEWSMAN_ENT_TYPE === 'undefined') {
					window.NEWSMAN_ENT_TYPE = 'email';
				}

				testEmail = $('#test-email-addr').val();

				$.cookie("newsmanTestEmailAddress", testEmail);

				$.ajax({
					type: 'POST',
					url: ajaxurl,
					data: {
						entType: NEWSMAN_ENT_TYPE,
						entity: NEWSMAN_ENTITY_ID,
						toEmail: testEmail,
						action: 'newsmanAjSendTestEmail'
					}
				}).done(function(data){
					showMessage(data.msg, 'success', null, null, true);
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

	$('.newsman-ajax-from-set-value').click(function(e){
		var form = $(this).closest('form');
		var elName = $(this).attr('el-name'),
			elValue = $(this).attr('el-value');

		$('<input type="hidden" name="'+elName+'" value="'+elValue+'">').appendTo(form);
	});

	$('.newsman-ajax-from').each(function(i, form){
		$(form).submit(function(e){
			e.preventDefault();
			var action = 
				$(this).attr('action');

			var dataObj = {
				action: 'newsman'+action.replace(/^[a-z]/g, function(c) {
					return c.toUpperCase();
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

	// debug log page

	var debugLogAR;
	var enabledAR = false;
	var debugLogARTimeout;
	function getLog(done) {
		$.ajax({
			type: 'post',
			url: ajaxurl,			
			data: {
				action: 'newsmanAjGetDebugLog'
			}
		}).success(function(data){
			$('#debuglog').val(data.log);			
		}).fail(NEWSMAN.ajaxFailHandler).always(done);
	}
	function runAR() {
		debugLogARTimeout = setTimeout(function(){
			if ( !enabledAR ) { return; }
			getLog(function(){
				if ( !enabledAR ) { return; }
				runAR();
			});
		}, 3000);
	}
	if ( debugLogAR = $('#debug-log-auto-refresh').first() ) {
		debugLogAR.change(function(){
			enabledAR = debugLogAR.prop('checked');
			if ( enabledAR ) {
				runAR();
			} else {
				clearTimeout(debugLogARTimeout);				
			}
		});
	}

	$('#btn-empty-debug-log').click(function(){
		$.ajax({
			type: 'post',
			url: ajaxurl,
			data: {
				action: 'newsmanAjEmptyDebugLog'
			}
		}).success(function(data){
			showMessage(data.msg, 'success');
			getLog();
		});
	});

	$('.newsman-show-tipsy').tipsy();

});