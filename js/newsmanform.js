jQuery(function($){

	$('.newsman-form-url').val(window.location.href);

	$('form[name="newsman-nsltr"]').submit(function(e) {
		var el, v, form = this, errors = {};

		function err(msg, el) {
			if ( errors[msg] ) {
				return;
			} else {
				errors[msg] = 1;
			}
			$(el).closest('.newsman-form-item').addClass('newsman-form-error');						
			$('<div class="error-msg">'+msg+'</div>').prependTo(form);
			e.preventDefault();				
		}

		//reset errors
		$('.newsman-form-item.newsman-form-error', form).removeClass('newsman-form-error');
		$('.error-msg', form).remove();
		errors = {};
		
		// email validation
		el = $('.newsman-form-item.newsman-form-item-email input', form);
		v = el.val();
		v = v.replace(/^\s+/).replace(/\s+$/);
		if ( !v ) {			
			err(newsmanformL10n.pleaseFillAllTheRequiredFields, el);
		} else if ( !v.match(/^\S+@\S+\.\S+$/) ) {
			err(newsmanformL10n.pleaseCheckYourEmailAddress, el);
		}

		// text validation
		$('.newsman-form-item.newsman-form-item-text.newsman-required', form).each(function(i, block){
 			el = $('input[type="text"]', block);
			v = el.val();		
			if ( !v ) {
				err(newsmanformL10n.pleaseFillAllTheRequiredFields, el);
			} 			
		});

		// textarea validation
		$('.newsman-form-item.newsman-form-item-textarea.newsman-required', form).each(function(i, block){
 			el = $('textarea', block);
			v = el.val();	
			if ( !v ) {
				err(newsmanformL10n.pleaseFillAllTheRequiredFields, el);
			} 			
		});


		// checkbox validation
		$('.newsman-form-item.newsman-form-item-checkbox.newsman-required', form).each(function(i, block){
			 el = $('input[type="checkbox"]', block);
			if ( !el.is(':checked') ) {
				err(newsmanformL10n.pleaseFillAllTheRequiredFields, block);
			}					 
		});		

		// radio validation
		$('.newsman-form-item.newsman-form-item-radio.newsman-required', form).each(function(i, block){
			el = $('input[type="radio"]:checked', block).get(0);

			if ( !el ) {
				err(newsmanformL10n.pleaseFillAllTheRequiredFields, block);
			}				
		});

		// select validation
		$('.newsman-form-item.newsman-form-item-select.newsman-required', form).each(function(i, block){

			v = $('select', block).val();

			if ( !v || v === 'null' ) {
				err(newsmanformL10n.pleaseFillAllTheRequiredFields, block);
			}				
		});

	});

});