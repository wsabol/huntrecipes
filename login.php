<?php

//this makes the connection to the database and any other necessary items
@session_start();
require_once("_php_common.php");
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', '1');

require_once('_user_contact.php');

if ( @$_SESSION['Login']['id'] > 0 ) {
	header('Location: /');
	exit;
}

$regSuccess = -1;
if ( isset($App->R['registration_auth']) && isset($App->R['uname']) ) {
	$reqSuccess = 0;
	if ( $App->R['registration_auth'] == md5(sha1($App->R['uname'].$_SERVER['REMOTE_ADDR'])) ) {
		// account confirmed
		$upd_query = "
			UPDATE Login
			SET account_status_id = 2
			WHERE username = '".$App->R['uname']."';
		";
		#wla( $sel_query ) ;
		$success = $App->oDBMY->execute( $upd_query );
		if ( $success ) {
			$regSuccess = 1;
			$App->R['username'] = $App->R['uname'];
		} else {
			$sql = "SELECT name FROM Login WHERE username = '".$App->R['uname']."';";
			$result = $App->oDBMY->query( $sql );
			$uData = $result->fetch_assoc();
			$result->free();
			cntRegistrationAuth( $App->R['uname'], $uData['name'] );
		}
	}
}

?>
<?php
$bodyClass = "";
require_once('_head.php');
?>

<!--wrap-->
<p id="fb_root">
</p>
<div class="wrap clearfix">
	<?php
	if ( $regSuccess === 0 ) {
		?>
		<div class="alert alert-warning" role="alert">
			<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h3>
				Whoops
			</h3>
			We were unable to verify your account.<br>
			The authentication email has been resent. Please use the link in this most recent email to login.
		</div>
		<?php
	}
	?>
	<!--row-->
	<div class="row">
	<!--content-->
		<section class="content center full-width">
			<div class="modal container">
				<h3>Login</h3>
					
				<div class="f-row">
					<p class="help-block"><span class="text-danger" id="logon_error" ></span></p>
					<input type="text" class="form-control" placeholder="Your username" id="username" name="username" required value="<?=@$App->R['username']?>" >
				</div>
				<div class="f-row">
					<input type="password" class="form-control" placeholder="Your password" id="password" name="password" required value="<?=@$App->R['password']?>" >
				</div>

				<div class="f-row">
					<label>
						<input type="checkbox" name="rememberme" value="1" <?=( @$App->R['rememberme'] == 1 ? "checked" : "" )?> >
						Remember me next time
					</label>
				</div>

				<input type="hidden" name="ref" value="<?=$App->R['ref']?>" >
				<div class="f-row bwrap">
					<img src="assets/images/loading.gif" id="imgLoader" class="hidden" >
					<input type="submit" id="cmdLogin" value="login" >
				</div>
				
				<p><a href="/forgot_password.php">Forgotten password?</a></p>
				<p>Dont have an account yet? <a href="/register.php">Sign up.</a></p>
			</div>
		</section>
		<!--//content-->
	</div>
	<!--//row-->
</div>
<!--//wrap-->

<script>
	
	$(document).ready(function(){
		
		$('input[type=checkbox]').iCheck({
			checkboxClass: 'icheckbox_flat-blue',
			increaseArea: '20%' // optional
		});
		
		$('#cmdLogin').click(function(e){
			var elogin = btoa( $('#username').val()+';'+$('#password').val() );
			$('#imgLoader').removeClass('hidden');
			$('#cmdLogin').addClass('hidden');
			
			$.post('/API/login/std_login.json.php', {
				'elogin': elogin,
				'rememberme': ( $('input[name=rememberme]').is(':checked') ? 1 : 0 )
			},
			function( response ) {
				console.log(response);
				if ( response.success == 1 ) {
					var new_url = '<?=@$App->R["ref"]?>';
					if( new_url.length == 0 ) {
						new_url = '/';
					}
					window.location = new_url;
				} else if ( response.success == 0 ) {
					$('#imgLoader').addClass('hidden');
					$('#cmdLogin').removeClass('hidden');
					$('#logon_error').text(response.logon_error);
				} else {
					// hmm?
					$('#imgLoader').addClass('hidden');
					$('#cmdLogin').removeClass('hidden');
				}
			});
		});
		
	});
	
</script>

<?php
$App = "";
require_once('_footer.php');
?>