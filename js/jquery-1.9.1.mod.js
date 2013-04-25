(function($){
	if ( typeof $ !== 'undefined' ) {
		var oldTrigger = $.event.special.blur.trigger;
		$.event.special.blur.trigger = function(){
			try {
				oldTrigger.apply(this, arguments);
			} catch(e) {

			}
		};
	}
})(jQuery)
