
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


Number.prototype.formatMoney = function(c, d, t){
	var n = this;
	c = isNaN(c = Math.abs(c)) ? 2 : c; 
	d = d === undefined ? "." : d; 
	t = t === undefined ? "," : t;
	var s = n < 0 ? "-" : ""; 
	var i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))); 
	var j = (j = i.length) > 3 ? j % 3 : 0;
	return s + '$' + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

String.prototype.formatMoney = function(c, d, t){
	var n = parseFloat(this);
	c = isNaN(c = Math.abs(c)) ? 2 : c; 
	d = d === undefined ? "." : d; 
	t = t === undefined ? "," : t;
	var s = n < 0 ? "-" : ""; 
	var i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))); 
	var j = (j = i.length) > 3 ? j % 3 : 0;
	return s + '$' + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

function parseMoney( target ) {
	return parseFloat(target.replace(/,/g, '').replace(/[$]/g, ''));
}

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

function inDataList( datalistId, target ) {
	if ( $('#'+datalistId).length === 0 ) {
		console.log(datalistId+' does not exists!');
		return false;
	}
	var dlOptions = document.getElementById( datalistId ).options;
	
	for ( var i = 0; i < dlOptions.length; i++ ) {
		if ( dlOptions[i].value == target ) {
			return true;
		}
	}
	return false;
}

function async_call(callback) {
	window.setTimeout(callback, 0);
}

String.prototype.isDate = function(){
	var target = this;
	var d = new Date( target );
	return !isNaN(d.getDate());
};

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

