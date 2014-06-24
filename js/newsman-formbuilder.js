jQuery(function($){

	function fieldName(str) {
		var s = str.replace(/[\s!@#$%\^&*\(\)?.,]+$/ig, '').replace(/[\s!@#$%\^&*\(\)?.,]+/ig, '-').toLowerCase();
		return foldToASCII(s);
	}	

	// Option elements

	var defaultOptionsLists = {
		countries: { "null": "Select country", "AF":"Afghanistan", "AX":"&Aring;land Islands", "AL":"Albania", "DZ":"Algeria", "AS":"American Samoa", "AD":"Andorra", "AO":"Angola", "AI":"Anguilla", "AQ":"Antarctica", "AG":"Antigua and Barbuda", "AR":"Argentina", "AM":"Armenia", "AW":"Aruba", "AU":"Australia", "AT":"Austria", "AZ":"Azerbaijan", "BS":"Bahamas", "BH":"Bahrain", "BD":"Bangladesh", "BB":"Barbados", "BY":"Belarus", "BE":"Belgium", "BZ":"Belize", "BJ":"Benin", "BM":"Bermuda", "BT":"Bhutan", "BO":"Bolivia, Plurinational State of", "BA":"Bosnia and Herzegovina", "BW":"Botswana", "BV":"Bouvet Island", "BR":"Brazil", "IO":"British Indian Ocean Territory", "BN":"Brunei Darussalam", "BG":"Bulgaria", "BF":"Burkina Faso", "BI":"Burundi", "KH":"Cambodia", "CM":"Cameroon", "CA":"Canada", "CV":"Cape Verde", "KY":"Cayman Islands", "CF":"Central African Republic", "TD":"Chad", "CL":"Chile", "CN":"China", "CX":"Christmas Island", "CC":"Cocos (Keeling) Islands", "CO":"Colombia", "KM":"Comoros", "CG":"Congo", "CD":"Congo, the Democratic Republic of the", "CK":"Cook Islands", "CR":"Costa Rica", "CI":"C&ocirc;te d'Ivoire", "HR":"Croatia", "CU":"Cuba", "CY":"Cyprus", "CZ":"Czech Republic", "DK":"Denmark", "DJ":"Djibouti", "DM":"Dominica", "DO":"Dominican Republic", "EC":"Ecuador", "EG":"Egypt", "SV":"El Salvador", "GQ":"Equatorial Guinea", "ER":"Eritrea", "EE":"Estonia", "ET":"Ethiopia", "FK":"Falkland Islands (Malvinas)", "FO":"Faroe Islands", "FJ":"Fiji", "FI":"Finland", "FR":"France", "GF":"French Guiana", "PF":"French Polynesia", "TF":"French Southern Territories", "GA":"Gabon", "GM":"Gambia", "GE":"Georgia", "DE":"Germany", "GH":"Ghana", "GI":"Gibraltar", "GR":"Greece", "GL":"Greenland", "GD":"Grenada", "GP":"Guadeloupe", "GU":"Guam", "GT":"Guatemala", "GG":"Guernsey", "GN":"Guinea", "GW":"Guinea-Bissau", "GY":"Guyana", "HT":"Haiti", "HM":"Heard Island and McDonald Islands", "VA":"Holy See (Vatican City State)", "HN":"Honduras", "HK":"Hong Kong", "HU":"Hungary", "IS":"Iceland", "IN":"India", "ID":"Indonesia", "IR":"Iran, Islamic Republic of", "IQ":"Iraq", "IE":"Ireland", "IM":"Isle of Man", "IL":"Israel", "IT":"Italy", "JM":"Jamaica", "JP":"Japan", "JE":"Jersey", "JO":"Jordan", "KZ":"Kazakhstan", "KE":"Kenya", "KI":"Kiribati", "KP":"Korea, Democratic People's Republic of", "KR":"Korea, Republic of", "KW":"Kuwait", "KG":"Kyrgyzstan", "LA":"Lao People's Democratic Republic", "LV":"Latvia", "LB":"Lebanon", "LS":"Lesotho", "LR":"Liberia", "LY":"Libyan Arab Jamahiriya", "LI":"Liechtenstein", "LT":"Lithuania", "LU":"Luxembourg", "MO":"Macao", "MK":"Macedonia, the former Yugoslav Republic of", "MG":"Madagascar", "MW":"Malawi", "MY":"Malaysia", "MV":"Maldives", "ML":"Mali", "MT":"Malta", "MH":"Marshall Islands", "MQ":"Martinique", "MR":"Mauritania", "MU":"Mauritius", "YT":"Mayotte", "MX":"Mexico", "FM":"Micronesia, Federated States of", "MD":"Moldova, Republic of", "MC":"Monaco", "MN":"Mongolia", "ME":"Montenegro", "MS":"Montserrat", "MA":"Morocco", "MZ":"Mozambique", "MM":"Myanmar", "NA":"Namibia", "NR":"Nauru", "NP":"Nepal", "NL":"Netherlands", "AN":"Netherlands Antilles", "NC":"New Caledonia", "NZ":"New Zealand", "NI":"Nicaragua", "NE":"Niger", "NG":"Nigeria", "NU":"Niue", "NF":"Norfolk Island", "MP":"Northern Mariana Islands", "NO":"Norway", "OM":"Oman", "PK":"Pakistan", "PW":"Palau", "PS":"Palestinian Territory, Occupied", "PA":"Panama", "PG":"Papua New Guinea", "PY":"Paraguay", "PE":"Peru", "PH":"Philippines", "PN":"Pitcairn", "PL":"Poland", "PT":"Portugal", "PR":"Puerto Rico", "QA":"Qatar", "RE":"R&eacute;union", "RO":"Romania", "RU":"Russian Federation", "RW":"Rwanda", "BL":"Saint Barth&eacute;lemy", "SH":"Saint Helena, Ascension and Tristan da Cunha", "KN":"Saint Kitts and Nevis", "LC":"Saint Lucia", "MF":"Saint Martin (French part)", "PM":"Saint Pierre and Miquelon", "VC":"Saint Vincent and the Grenadines", "WS":"Samoa", "SM":"San Marino", "ST":"Sao Tome and Principe", "SA":"Saudi Arabia", "SN":"Senegal", "RS":"Serbia", "SC":"Seychelles", "SL":"Sierra Leone", "SG":"Singapore", "SK":"Slovakia", "SI":"Slovenia", "SB":"Solomon Islands", "SO":"Somalia", "ZA":"South Africa", "GS":"South Georgia and the South Sandwich Islands", "ES":"Spain", "LK":"Sri Lanka", "SD":"Sudan", "SR":"Suriname", "SJ":"Svalbard and Jan Mayen", "SZ":"Swaziland", "SE":"Sweden", "CH":"Switzerland", "SY":"Syrian Arab Republic", "TW":"Taiwan, Province of China", "TJ":"Tajikistan", "TZ":"Tanzania, United Republic of", "TH":"Thailand", "TL":"Timor-Leste", "TG":"Togo", "TK":"Tokelau", "TO":"Tonga", "TT":"Trinidad and Tobago", "TN":"Tunisia", "TR":"Turkey", "TM":"Turkmenistan", "TC":"Turks and Caicos Islands", "TV":"Tuvalu", "UG":"Uganda", "UA":"Ukraine", "AE":"United Arab Emirates", "GB":"United Kingdom", "US":"United States", "UM":"United States Minor Outlying Islands", "UY":"Uruguay", "UZ":"Uzbekistan", "VU":"Vanuatu", "VE":"Venezuela, Bolivarian Republic of", "VN":"Viet Nam", "VG":"Virgin Islands, British", "VI":"Virgin Islands, U.S.", "WF":"Wallis and Futuna", "EH":"Western Sahara", "YE":"Yemen", "ZM":"Zambia", "ZW":"Zimbabwe" },
		states: { "null": "Select state", "AL":"Alabama","AK":"Alaska","AZ":"Arizona","AR":"Arkansas","CA":"California","CO":"Colorado","CT":"Connecticut","DE":"Delaware", "DC": "District of Columbia", "FL":"Florida","GA":"Georgia","HI":"Hawaiʻi","ID":"Idaho","IL":"Illinois","IN":"Indiana","IA":"Iowa","KS":"Kansas","KY":"Kentucky","LA":"Louisiana","ME":"Maine","MD":"Maryland","MA":"Massachusetts","MI":"Michigan","MN":"Minnesota","MS":"Mississippi","MO":"Missouri","MT":"Montana","NE":"Nebraska","NV":"Nevada","NH":"New Hampshire","NJ":"New Jersey","NM":"New Mexico","NY":"New York","NC":"North Carolina","ND":"North Dakota","OH":"Ohio","OK":"Oklahoma","OR":"Oregon","PA":"Pennsylvania","RI":"Rhode Island","SC":"South Carolina","SD":"South Dakota","TN":"Tennessee","TX":"Texas","UT":"Utah","VT":"Vermont","VA":"Virginia","WA":"Washington","WV":"West Virginia","WI":"Wisconsin","WY":"Wyoming"},
		genders: { "male": "Male", "female": "Female", "wonttell": "Won't tell" }
	};

	$('#newsman_form_g').submit(function(e){
		e.preventDefault();
		return false;
	});

	ko.bindingHandlers.placeholder = {
	    init: function(element, valueAccessor, allBindingsAccessor, viewModel) {
			// init logic
	      	$(element).placeholder();
        	var underlyingObservable = valueAccessor();
	    },
	    update: function(element, valueAccessor, allBindingsAccessor, viewModel) {
	       // update logic
	       var val = ko.utils.unwrapObservable(valueAccessor());	       
	       $(element).attr('placeholder', val);
	       $(element).val('');
	    }
	};	

	var ZCclient = new ZeroClipboard();

	ZCclient.on( "ready", function( readyEvent ) {

	  ZCclient.on( "aftercopy", function( event ) {
	    // `this` === `client`
	    // `event.target` === the element that was clicked
	    //event.target.style.display = "none";
	    var msg = $(event.target).closest('.newsman-field-shortcode').find('.copy-shortcode-done-msg');
	    msg.fadeIn();
	    setTimeout(function() {
	    	msg.fadeOut();
	    }, 1500);
	    //console.warn("Copied text to clipboard: " + event.data["text/plain"] );
	  } );
	} );	

	function buildForm(formDef) {
		var that = {},
			formUl = $('ul.newsman-form').empty().get(0),
			fbPanel = $('#fb-panel').get(0);			

		$(formDef.elements).each(function(i, el){
			normalizeDefinition(el);
		});

		var viewModel = ko.mapping.fromJS(formDef);

		// adding event handlers to each form element
		$(viewModel.elements()).each(function(i, el){
			addHandlers(el);
		});		

		// Addes knockout handlers and observables to the form element definition
		function addHandlers(el) {

			el.name = ko.computed(function() {				
				var v = this.value && this.value(),
					lbl = this.label && this.label(),
					txt, shortcode;

				txt = lbl ? fieldName( lbl ) : fieldName(v || 'unnamed');
				shortcode = '[newsman sub="'+txt+'"]';

				return txt;
			}, el);

			el.shortcode = ko.computed(function() {				
				var txt, shortcode;

				txt = this.name();
				shortcode = '[newsman sub="'+txt+'"]';

				return shortcode;
			}, el);			

			var elType = el.type();

			el.shortcodeAvailable = ko.computed(function(){
				var disabledTypes = ['submit', 'html', 'title'];

				return this.active() && ( disabledTypes.indexOf(el.type()) === -1 );
			}, el);

			el.removeFormItem = function(formEl) {
				var idx = viewModel.elements.indexOf(formEl);
				if ( idx > -1 ) {
					viewModel.elements.splice(idx, 1);
				}				
			};

			if ( elType === 'radio' || elType === 'select' ) {

				$(el.children()).each(function(i){					
					var c = el.children()[i];

					if ( !c.label ) { c.label = ko.observable(''); }

					c.edit = ko.observable(false);
					c.value = ko.computed(function(){
						return this.label();
					}, c);
				});

				el.loadOptionsList = function(el, ev) { 
					ev.preventDefault();

					var listName = $(ev.target).attr('data-list');

					var convEl = $('<div></div>');

					var that = this;

					setTimeout(function() {
						if ( defaultOptionsLists[listName] ) {
							var list = defaultOptionsLists[listName];
							for ( var value in list ) {
								that._addOption(convEl.html(list[value]).text(), value);
							}
						}						
					}, 10);

				};

				el.addOption = function() {
					this._addOption();
				};

				el._addOption = function(label, value) {
					label = label || 'new option';

					var c = {
						label: ko.observable(label),
						edit: ko.observable(false)
					};

					value = ko.observable(value) || ko.computed(function(){
						return this.label();
					}, c);

					c.value = value;

					el.children.push(c);
					return c;
				};

				el.removeOption = function(child) {
					var idx = el.children.indexOf(child);

					if ( idx > -1 ) {
						el.children.splice(idx, 1);
					}
				}
			}

			if ( elType === 'submit' ) {
				el.getSubmitClass = function() {
					var classes = {
						'newsman-button': false,

						'newsman-button-mini': false,
						'newsman-button-small': false,
						'newsman-button-medium': false,
						'newsman-button-large': false,

						'newsman-button-brick': false,
						'newsman-button-pill': false,
						'newsman-button-rounded': false,

						'newsman-button-gray': false,
						'newsman-button-pink': false,
						'newsman-button-blue': false,
						'newsman-button-green': false,
						'newsman-button-turquoise': false,
						'newsman-button-black': false,
						'newsman-button-darkgray': false,
						'newsman-button-yellow': false,
						'newsman-button-purple': false,
						'newsman-button-darkblue': false
					};

					var size =  this.size(),
						color = this.color(),
						style = this.style();

					if ( style !== 'none' ) {

						classes['newsman-button'] = true;
						classes['newsman-button-'+style] = true;

						classes['newsman-button-'+color] = true;

						classes['newsman-button-'+size] = true;
					}

					return classes;
				};
			}

			el.ph = ko.computed(function(){
				return viewModel.useInlineLabels() ? (this.label && this.label()) || this.name() : '';
			}, el);
		}

		function normalizeDefinition(el) {
			el.required = el.required || false;
			el.active = el.active || false;

			if ( el.type === 'select' ) {
				el.children = el.children || [];
			}

			if ( el.type === 'submit' ) {
				el.size = el.size || 'small';
				el.style = el.style || 'rounded';
				el.color = el.color || 'grey';
			}
		}

		viewModel.toggleEdit = function(el, e) {
			var ed = el.edit();
			el.edit(!ed);
			if ( !ed ) {				
				$(e.target).closest('li').find('input[type="text"]').focus();
			}
		};

		viewModel.formItemTpl = function(el){
			var map = {
				'checkbox': 'tpl-newsman-form-el-checkbox',
				'text': 	'tpl-newsman-form-el-text',
				'textarea': 'tpl-newsman-form-el-textarea',
				'email': 	'tpl-newsman-form-el-email',
				'submit': 	'tpl-newsman-form-el-submit',
				'radio': 	'tpl-newsman-form-el-radio',
				'select': 	'tpl-newsman-form-el-select',
				'title': 	'tpl-newsman-form-el-title',
				'html': 	'tpl-newsman-form-el-html',
			};
			return map[el.type()] || 'tpl-newsman-form-el-dummy';
		};

		viewModel.optionsTpl = function(el) {
			var map = {
				'checkbox': 'tpl-newsman-options-checkbox',
				'text': 	'tpl-newsman-options-text',
				'textarea': 'tpl-newsman-options-textarea',
				'email': 	'tpl-newsman-options-text',
				'submit': 	'tpl-newsman-options-submit',
				'radio': 	'tpl-newsman-options-radio',
				'select': 	'tpl-newsman-options-select',
				'title': 	'tpl-newsman-options-title',
				'html': 	'tpl-newsman-options-html',
			};

			return map[el.type()] || 'tpl-newsman-options-dummy';
		};

		viewModel.elClick = function(el, ev){
			var els = viewModel.elements();
			for (var i = 0, l = els.length; i < l; i++) {
				els[i].active(false);
			};
			el.active(true);
		};

		ko.applyBindings(viewModel, fbPanel);

		that.addFormElement = function(type) {

			var elTemplates = {
				'text': {
					type: "text",  label: "Untitled", name: "untitled", value: ""
				},
				'textarea': {
					type: "textarea",  label: "Untitled", name: "untitled", value: ""
				},				
				'checkbox': {
					type: "checkbox", label: "Untitled checkbox", name:"untitled-checkbox", checked: false, value: "1"
				},
				'radio': {
					type: "radio",
					label: "Choose an option",
					name:"choose-an-option",
					checked: 'option-1',
					children: [
						{ label: "option 1", value: "option-1" },
						{ label: "option 2", value: "option-2" }
					]
				},
				'select': {
					type: "select",
					label: "Please select",
					name:"please-select",
					selected: 'option-1',
					children: [
						{ label: 'option 1', value: 'option-1' },
						{ label: 'option 2', value: 'option-2' },
						{ label: 'option 3', value: 'option-3' }
					]
				},
				'submit': {
					type: "submit", value: "Subscribe", size: 'small', color: 'gray', style: 'rounded'
				},
				'title': {
					type: 'title', value: 'Subscription'
				},
				'html': {
					type: 'html', value: '<p style="line-height:1.5em;">Enter your primary email address to get our free newsletter.</p>'
				}
			};

			if ( elTemplates[type] ) {

				var el = JSON.parse(JSON.stringify(elTemplates[type]));

				normalizeDefinition(el);

				var obsEl = ko.mapping.fromJS(el);

				addHandlers(obsEl);

				viewModel.elements.splice( viewModel.elements.length-1, 0, obsEl );

			} else {
				NEWSMAN.showMessage('Form element type "'+type+'" is not defined.', 'error');
			}

		};

		that.toJSON = function() {
			return ko.mapping.toJSON(viewModel);
		};

		that.toJS = function() {
			return ko.mapping.toJS(viewModel);
		};		

		ZCclient.clip($('.btn-copy-shortcode'));

		return that;
	}

	var formString = $('#serialized-form').val(),
		formObj = {};

	try {		
		formObj = JSON.parse(formString);
	} catch(e) {
	}

	window.newsmanFormBuilder = buildForm(formObj);

	$('#btn-load-default-form').click(function(){

		var defForm = NEWSMAN_DEF_FORM;

		window.newsmanFormBuilder = buildForm(defForm);		
	});

	$('#btn-add-field ul a').click(function(e){
		var type = $(this).attr('type');
		newsmanFormBuilder.addFormElement(type);
	});
});