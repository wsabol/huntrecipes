<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

require_once('_user_contact.php');

if ( @$_SESSION['Login']['id']*1 > 0 ) {
  // you are already logged in
  header('Location: /');
  exit;
}

$usernameValidation = true;
$emailValidation = true;
$userSuccess = true;
$mailSuccess = true;

if ( @$App->R['action'] == "register" ) {
  
  // check username
  $selUser = "
    SELECT count(1) icount FROM Login
    WHERE username = '".$App->oDBMY->escape_string($App->R['username'])."'
		AND account_status_id = 2;
  ";
  $result = $App->oDBMY->query($selUser);
  if ( !!$result ) {
    $icount = $result->fetch_assoc();
    if ( $icount['icount'] > 0 ) {
      $usernameValidation = false;
    }
    $result->free();
  }
  
  // check email
  $selUser = "
    SELECT count(1) icount FROM Login
    WHERE email = '".$App->oDBMY->escape_string($App->R['email'])."'
		AND account_status_id = 2;
  ";
  $result = $App->oDBMY->query($selUser);
  if ( !!$result ) {
    $icount = $result->fetch_assoc();
    if ( $icount['icount'] > 0 ) {
      $emailValidation = false;
    }
    $result->free();
  }
  
  if ( $usernameValidation && $emailValidation ) {
    // add user to do
		$login_hash = substr(generateToken(), 0, 12);
		
    $new_query = "
      INSERT INTO Login (
        name,
        email,
        username,
        password,
				login_hash
      ) VALUES (
        '".$App->oDBMY->escape_string($App->R['name'])."',
        '".$App->oDBMY->escape_string($App->R['email'])."',
        '".$App->oDBMY->escape_string($App->R['username'])."',
        '".sha1($App->R['password'].$login_hash)."',
				'".$login_hash."'
      );
    ";
		//wl($new_query);
    $success = $App->oDBMY->execute($new_query);
    if ( $success ) {
      $mailSuccess = cntRegistrationAuth( $App->R['email'], $App->R['username'], $App->R['name'] );
    }
  }
  
}

$bodyClass = "";
require_once("_head.php");

//wla($App->R);
?>
<!--wrap-->
<div class="wrap clearfix">
  <?php
  $totalValidation = -1;
  if ( @$App->R['action'] == "register" ) {

    if ( !$usernameValidation ) {
      $totalValidation = 0;
      ?>
      <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Username already taken.
      </div>
      <?php
    }
    if ( !$emailValidation ) {
      $totalValidation = 0;
      ?>
      <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Email already taken.
      </div>
      <?php
    }
    if ( !$userSuccess ) {
      $totalValidation = 0;
      ?>
      <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Unexpected error creating your account.
      </div>
      <?php
    }
    if ( $mailSuccess ) {
      $totalValidation = 1;
    } else {
      $totalValidation = 0;
      ?>
      <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Unexpected error creating your account.
      </div>
      <?php
    }
  }
  
  if ( $totalValidation == 1 ) {
    $_REQUEST = "";
    ?>
    <!--row-->
    <div class="row">
      <!--content-->
      <section class="content center full-width">
        <div class="modal container" style="padding-bottom: 17px">
          <h3>Congrats!</h3>
          <p>Please check your email to confirm your account. Deliciousness is waiting...</p>
        </div>
      </section>
    </div>
    <?php
  }
  else {
    ?>
    <!--row-->
    <div class="row">
    <!--content-->
      <section class="content center full-width">
        <div class="modal container">
          <h3>Register</h3>
          <form id="frmRegister" action="register.php" method="post" onsubmit="return ValidateRegistration()" >
            <input type="hidden" name="action" value="register" />

            <div class="f-row">
              <input type="text" class="form-control" id="name" name="name" placeholder="Your name" value="<?=@$App->R['name']?>" required />
            </div>
            <div class="f-row">
              <input type="email" class="form-control" id="email" name="email" placeholder="Your email" value="<?=@$App->R['email']?>" required />
            </div>
            <div class="f-row">
              <input type="text" class="form-control" id="username" name="username" placeholder="Your username" autocomplete="off" value="<?=@$App->R['username']?>" required />
            </div>
            <div class="f-row">
              <input type="password" class="form-control" id="password" name="password" placeholder="Your password" autocomplete="off" required />
              <span class="help-block password-error hidden">Passwords must be at least 8 characters long, and include capital and lowercase letters and numbers</span>
            </div>
            <div class="f-row">
              <input type="password" class="form-control" id="password2" placeholder="Retype password" autocomplete="off" required />
              <span class="help-block password-error hidden">Passwords don't match</span>
            </div>
            <div class="f-row bwrap">
              <input type="submit" value="Register" />

							<p id="register_error" class="help-block"></p>
            </div>
          </form>
          <p>Already have an account yet? <a href="login.php">Log in.</a></p>
        </div>
      </section>
      <!--//content-->
    </div>
    <!--//row-->
    <?php
  }
  ?>
</div>
<!--//wrap-->
<script>
  
  window.fbAsyncInit = function() {
		FB.init({
			appId   : '1722798604684723',
			oauth   : true,
			status  : true, // check login status
			cookie  : true, // enable cookies to allow the server to access the session
			xfbml   : true // parse XFBML
		});
  };
	
	function fb_register(){
		FB.login(function(response) {

			if (response.authResponse) {
				console.log('Welcome!  Fetching your information.... ');
				//console.log(response); // dump complete info
				var access_token = response.authResponse.accessToken; //get access token
				var user_id = response.authResponse.userID; //get FB UID
				console.log('Registering in as FB user:'+user_id);
        
			} else {
				//user hit cancel button
				console.log('User cancelled login or did not fully authorize.');
			}
		}, {
			scope: 'public_profile,email'
		});
	}
  
  $(function(){
    
    $('#password, #password2').donetyping(function(){
      checkPassword();
    }, 100);
    
    $('#password, #password2').change(function(){
      checkPassword();
    });
    
  });
  
  function checkPassword() {
    $('#password').parent().removeClass('has-error');
    $('#password').parent().find('.help-block').addClass('hidden');
    $('#password2').parent().removeClass('has-error');
    $('#password2').parent().find('.help-block').addClass('hidden');

    if ( $('#password').val().length > 0 && $('#password2').val().length > 0 && $('#password').val() !== $('#password2').val() ) {
      $('#password').parent().addClass('has-error');
      $('#password2').parent().addClass('has-error');
      $('#password2').parent().find('.password-error').removeClass('hidden');
    }

    var correctPw = ( $('#password').val().length >= 8 ) && /[0-9]/.test($('#password').val()) && /[A-Z]/.test($('#password').val()) && /[a-z]/.test($('#password').val());

    if ( $('#password').val().length > 0 && !correctPw  ) {
      $('#password').parent().addClass('has-error');
      $('#password').parent().find('.password-error').removeClass('hidden');
    } else {
      
    }
  }
  
  function ValidateRegistration() {
    if ( $('#password').parent().hasClass('has-error') ||
          $('#password2').parent().hasClass('has-error') ||
          !$('#email').val().isEmail() ) {
      return false;
    }
    return true;
  }
  
</script>
<?php
$App = "";
require_once('_footer.php');
?>
