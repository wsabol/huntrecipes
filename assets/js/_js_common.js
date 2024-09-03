
//
// $('#element').donetyping(callback[, timeout=1000])
// Fires callback when a user has finished typing. This is determined by the time elapsed
// since the last keystroke and timeout parameter or the blur event--whichever comes first.
//   @callback: function to be called when even triggers
//   @timeout:  (default=1000) timeout, in ms, to to wait before triggering event if not
//              caused by blur.
// Requires jQuery 1.7+
//
;(function($){
	$.fn.extend({
			donetyping: function(callback,timeout){
					timeout = timeout || 1e3; // 1 second default timeout
					var timeoutReference,
							doneTyping = function(el){
									if (!timeoutReference) return;
									timeoutReference = null;
									callback.call(el);
							};
					return this.each(function(i,el){
							var $el = $(el);
							// Chrome Fix (Use keyup over keypress to detect backspace)
							// thank you @palerdot
							$el.is(':input') && $el.on('keyup keypress paste',function(e){
									// This catches the backspace button in chrome, but also prevents
									// the event from triggering too preemptively. Without this line,
									// using tab/shift+tab will make the focused element fire the callback.
									if (e.type=='keyup' && e.keyCode!=8) return;
									
									// Check if timeout has been set. If it has, "reset" the clock and
									// start over again.
									if (timeoutReference) clearTimeout(timeoutReference);
									timeoutReference = setTimeout(function(){
											// if we made it here, our timeout has elapsed. Fire the
											// callback
											doneTyping(el);
									}, timeout);
							}).on('blur',function(){
									// If we can, fire the event since we're leaving the field
									doneTyping(el);
							});
					});
			}
	});
})(jQuery);

function LoadDivContent( module_name_to_load, input_form, target_object, url_params ){
	console.log("input_form: " +input_form);
	
	var formData = {};
	if ( typeof input_form.length != 'undefined' ) {
		if ( input_form.length > 0 ) {
			$.each($( "#" + input_form ).serializeArray(), function(_, kv) {
				formData[kv.name] = kv.value;
			});
		}
	}
	if ( typeof url_params === 'object' ) {
		$.extend( url_params, formData );
	} else {
		url_params = formData;
	}
	console.log(url_params);

	var url = '/js-ajax/' + module_name_to_load + ".ajax.php";
	console.log("url: " + url );
	
	$('#' + target_object + '').html( '<img src="/assets/images/loading.gif" />' );
	$.ajax({
		type: "POST",
		url: url,
		data: url_params,
		dataType: "html",
		error: function(returnval) {
			console.log("returnval: " + returnval );
		},
		success: function(data){
			//DON'T DO NOTHING
			$('#' + target_object + '').html(data);
		}
	});
}

String.prototype.ucwords = function() {
	var str = this.toLowerCase();
	return str.replace(/(^([a-zA-Z\p{M}]))|([ -][a-zA-Z\p{M}])/g,
		function($1){
			return $1.toUpperCase();
		});
}

String.prototype.isEmail = function() {
  var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
  return regex.test(this);
}

