(function($){
	if ( typeof $ !== 'undefined' &&
		 $.event &&
		 $.event.special && 
		 $.event.special.blur && 
		 $.event.special.blur.trigger ) {

		var oldTrigger = $.event.special.blur.trigger;
		$.event.special.blur.trigger = function(){
			try {
				oldTrigger.apply(this, arguments);
			} catch(e) {

			}
		};
	}
})(jQuery)
