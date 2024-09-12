<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

$week_days = getDaysOfWeekArray();
/*wla($_SESSION['Login']);
wla($week_days);
wl($week_days[$_SESSION['Login']['week_start_day_of_week']]['name']);
exit;*/

$Tabs = array("favorites", "chef-portal", "settings");
if ( !in_array(@$App->R['tabid'], $Tabs) ) {
	$App->R['tabid'] = "favorites";
}

$bodyClass = "";
require_once('_head.php');
?>

<!--wrap-->
<div class="wrap clearfix">

	<!--content-->
	<section class="content" style="width: 100%">
		<!--row-->

		<!--//row-->
	</section>
	<!--//content-->
</div>
<!--//wrap-->
<script>
	
	var weekday = new Array(7);
	weekday[0] = "Sunday";
	weekday[1] = "Monday";
	weekday[2] = "Tuesday";
	weekday[3] = "Wednesday";
	weekday[4] = "Thursday";
	weekday[5] = "Friday";
	weekday[6] = "Saturday";
	
	window.fbAsyncInit = function() {
		FB.init({
			appId   : '1722798604684723',
			oauth   : true,
			status  : true, // check login status
			cookie  : true, // enable cookies to allow the server to access the session
			xfbml   : true // parse XFBML
		});
  };
	
	$(function(){
		
		// load content
		LoadDivContent( 'profile/my_favorites', '', 'favorites', {} );
		LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {} );
		
		$('#settings .read-setting').click(function(){
			$(this).parent().find('.write-setting').removeClass('hidden');
			$(this).parent().find('.write-setting > input, .write-setting > select').focus();
			$(this).addClass('hidden');
		});
		
		/*'.write-setting > .label-danger').click(function(){
			$(this).parent().parent().find('.read-setting').removeClass('hidden');
			$(this).parent().find('.help-inline').text('');
			$(this).parent().addClass('hidden');
		});
		
		$('.write-setting > .label-success').click(function(){
			//console.log('.write-setting > .label-success).click');
			updateLoginInfo( $(this).parent().find('input,select') );
		});*/
		
		$('#settings .write-setting > input, .write-setting > select').blur(function(){
			console.log('.write-setting > input).blur');
			updateLoginInfo( $(this) );
		});
		
		$('#settings .write-setting > input').keyup(function(e){
			if ( e.which == 13 ) {
				//console.log('.write-setting > input).keyup');
				updateLoginInfo( $(this) );
			}
		});
		
		$('#settings .write-setting > select').change(function(e){
			//console.log('.write-setting > select).change');
			updateLoginInfo( $(this) );
		});
		
	});
	
	function updateLoginInfo( $input ) {
		//console.log($input);
		var field = $input.data('field');
		var value = $input.val();
		//console.log(field);
		//console.log(value);
		$input.parent().find('.help-inline').text('');

		var error = '';
		if ( field == 'username' ) {
			// validate
		}
		if ( field == 'email' && !value.isEmail() ) {
			// validate
		}

		if ( error == '' ) {
			//var $i = $input.parent().find('.fa-save');
			//$i.removeClass('fa-save');
			//$i.addClass('fa-spinner');
			
			$.ajax({
				url: '/ajax-json/profile/write_settings_field.json.php',
				type: 'GET',
				data: {
					field: field,
					value: value
				},
				success: function( response ) {
					console.log(response);
					var input_field = response.input_data.field;
					var input_value = response.input_data.value;
					var input_value_formatted = input_value;
					
					//var $i = $('#write-setting-'+input_field).parent().find('.fa-spinner');
					//$i.removeClass('fa-spinner');
					//$i.addClass('fa-save');
					
					if ( response.success == 1 ) {
						
						if ( input_field == 'week_start_day_of_week' ) {
							input_value_formatted = weekday[input_value];
						}
						
						$('#write-setting-'+input_field).val(input_value);
						$('#write-setting-'+input_field).parent().parent().find('.read-setting').text(input_value_formatted);
						
						$('#write-setting-'+input_field).parent().parent().find('.read-setting').removeClass('hidden');
						$('#write-setting-'+input_field).parent().addClass('hidden');
					} else {
						$('#write-setting-'+input_field).parent().find('.help-inline').text('Unexpected DB Error');
					}
				}
			});
		}
	}
	
</script>
<?php
require_once('_footer.php');
$App = "";
?>
