/*
Copyright (c) 2012 Alex Ladyga ( neocoder@gmail.com )

MIT License

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

(function($){

	$.widget("neo.multis", {
		options: {
			autoOpen: true
		},
		
		_create: function(){

			this.availableItems = [];
			this.availableOptionsCount = 0;

			this.getData = this.options.getData;
			
			// remember this instance
			$.neo.multis.instances.push(this.element);

			// creating missing html layout

			this.options.preFillItems = this.options.preFillItems || [];
			var that = this;
			//debugger;
			$('li[data-value]', this.element).each(function(i, el){
				that.options.preFillItems.push( $(el).attr('data-value') );
				$(el).hide();
			});

			if ( !$('.multis-items', this.element).get(0) ) {
				$([
					'<ul class="multis-items">',
						'<li class="multis-input"><input type="text"></li>',
					'</ul>'
				].join('')).appendTo(this.element);
			}

			if ( !$('.multis-options', this.element).get(0) ) {
				$('<ul class="multis-options"></ul>').appendTo(this.element);
			}
		},
		
		_init: function(){

			var that = this;
		
			this.input = $('.multis-input input', this.element);
			this.inputLi = $('.multis-input', this.element);
			this.optionsList = $('.multis-options', this.element);
			this.itemsList = $('.multis-items', this.element);

			this.addItems(this.options.preFillItems);

			$('.multis-items', this.element).click(function() {
				that.input.focus();
			});

			/**
			 * Input event handlers
			 */
			$(this.input)
			.focus(function(){
				that.openOptions();
			})
			.blur(function(){
				that.closeOptions();
			})
			.keydown(function(e){
				if ( e.keyCode == 8 ) { //backspace 
					if ( that.input.val() === '' ) {
						that.inputLi.prev().remove();
						e.preventDefault();
						that.onChange();
						that.loadAvailable();
					}					
				}
			})
			.keypress(function(e){				
				if ( e.keyCode == 13 ) { // enter
					that.addItem(that.input.val());
					that.input.val('');
					e.preventDefault();
				} 
			});

		},

		onChange: function() {
			this._trigger('change');
			this.element.trigger('change');
		},

		addItem: function(text) {
			if ( !text ) { return; }
			var that = this;
			var item = $('<li>'+text+'<a class="closebutton" href="#">×</a></li>').insertBefore(this.inputLi);

			$('.closebutton', item).click(function(e){
				$(this).closest('li').remove();
				that.onChange();
			});	
			that.onChange();
		},

		addItems: function(arrText) {
			var that = this;
			$(arrText).each(function(i, text){
				that.addItem(text);
			});
		},

		getItems: function() {
			var items = [];
			$('.multis-items li', this.element).not('.multis-input').each(function(i, li){
				items.push( li.firstChild.textContent );
			});

			return items;
		},

		optionsLiClick: function(li) {			
			this.addItem($('.multis-opt-name', li).text());
		},

		loadAvailable: function(done) {
			var that = this;

			done = done || function(){};

			function fillList() {
				var usedItems = that.getItems();
				that.optionsList.empty();
				that.availableOptionsCount = 0;
				$(that.availableItems).each(function(i, text){
					if ( usedItems.indexOf(text.name || text) === -1 ) { // if not yet used
						that.availableOptionsCount += 1;
						var li;
						if ( typeof text !== 'string' ) {
							li = $('<li><span class="multis-opt-name">'+text.name+'</span><span class="multis-opt-count">'+text.count+'</span></li>').appendTo(that.optionsList);	
						} else {
							li = $('<li><span class="multis-opt-name">'+text+'</span></li>').appendTo(that.optionsList);	
						}
						
						li.click(function(){
							that.optionsLiClick(this);
						});
					}
				});
			}

			if ( !this.availableItems.length && this.getData ) {
				this.getData('', function(data){
					that.availableItems = data;
					fillList();
					done();
				});
			} else {
				fillList();
				done();
			}
		},

		openOptions: function() {
			var that = this;
			that.loadAvailable(function(){
				if ( that.availableOptionsCount > 0 ) {
					that.optionsList.fadeIn('fast');
				}				
			});			
		},

		closeOptions: function() {
			this.optionsList.fadeOut('fast');
		},

		destroy: function(){
			// remove this instance from $.ui.mywidget.instances
			var element = this.element,
				position = $.inArray(element, $.neo.multis.instances);
		 
			// if this instance was found, splice it off
			if(position > -1){
				$.neo.multis.instances.splice(position, 1);
			}
			
			// call the original destroy method since we overwrote it
			$.Widget.prototype.destroy.call( this );
		},

		_getOtherInstances: function(){
			var element = this.element;
		
			return $.grep($.neo.multis.instances, function(el){
				return el !== element;
			});
		}
		
		// _setOption: function(key, value){
		// 	this.options[key] = value;
			
		// 	switch(key){
		// 		case "something":
		// 			// perform some additional logic if just setting the new
		// 			// value in this.options is not enough. 
		// 			break;
		// 	}
		// }
	});

	$.extend($.neo.multis, {
		instances: []
	});

})(jQuery);