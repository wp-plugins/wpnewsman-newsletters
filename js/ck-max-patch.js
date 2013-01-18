CKEDITOR.dom.element.prototype.addClass = (function(){
	var original = CKEDITOR.dom.element.prototype.addClass;
	return function(className) {
		if ( className === 'cke_maximized' ) {
			console.log('MAXIMIZE');
			console.log(this);
			jQuery(this.$).css({ top: '-28px' });

			var content = jQuery('.cke_contents', this.$);
			//console.log('h1: '+content.height());
			content.css({ height: (content.height()-56)+'px' });

			//console.log('h2: '+content.height());
		}
		return original.apply(this, arguments);
	};	
}());

CKEDITOR.dom.window.prototype.getViewPaneSize = (function(){
	var original = CKEDITOR.dom.window.prototype.getViewPaneSize;
	return function() {
		var r = original.apply(this, arguments);

		r.height -= 28;

		return r;
	};	
}());