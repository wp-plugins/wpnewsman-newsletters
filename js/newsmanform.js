jQuery(function($){

	$('form[name="newsman-nsltr"]').submit(function(e) {

		var el, v, form = this, errors = {};

		function err(msg, el) {
			if ( errors[msg] ) {
				return;
			} else {
				errors[msg] = 1;
			}
			$(el).closest('.newsman-form-item').addClass('error');						
			$('<div class="error-msg">'+msg+'</div>').prependTo(form);
			e.preventDefault();				
		}

		//reset errors
		$('.newsman-form-item.error', form).removeClass('error');
		$('.error-msg', form).remove();
		errors = {};
		
		// email validation
		el = $('.newsman-form-item.email.newsman-required input[type="text"]', form);
		v = el.val();
		v = v.replace(/^\s+/).replace(/\s+$/);
		if ( !v ) {			
			err('Please fill all the required fields.', el);
		} else if ( !v.match(/^\S+@\S+\.\S+$/) ) {
			err('Please check your email address.', el);
		}

		// text validation
		$('.newsman-form-item.text.newsman-required', form).each(function(i, block){
 			el = $('input[type="text"]', block);
			v = el.val();		
			if ( !v ) {
				err('Please fill all the required fields.', el);
			} 			
		});


		// checkbox validation
		$('.newsman-form-item.checkbox.newsman-required', form).each(function(i, block){
			 el = $('input[type="checkbox"]', block);
			if ( !el.is(':checked') ) {
				err('Please fill all the required fields.', block);
			}					 
		});		


		// radio validation
		$('.newsman-form-item.radio.newsman-required', form).each(function(i, block){
			el = $('input[type="radio"]:checked', block).get(0);

			if ( !el ) {
				err('Please fill all the required fields.', block);
			}				
		})	
	});

});