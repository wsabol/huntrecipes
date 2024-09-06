<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

require_once('_user_contact.php');

/*
resetDisplayCode
0 : display form
1 : successful reset, link to login
-1 : error
*/
$resetDisplayCode = -1;
$login_id = -1;

if ( isset($App->R['reset_token']) ) {
  // from email
  // check token/login_id with db
  // if failure, show error
  
  $qLookup = "
    SELECT *
    FROM PasswordResetToken
    WHERE token = '".$App->R['reset_token']."'
    AND expires > NOW();
  ";
  $result = $App->oDBMY->query($qLookup);
  while ( ($row = $result->fetch_assoc()) && ($login_id == -1) ) {
    if ( hash_equals(md5($App->R['login_id']), $row['hashed_login']) ) {
      $login_id = $App->R['login_id'];
    }
  }
  $resetDisplayCode = ( $login_id > 0 ? 0 : -1 );
  $result->free();
  
} elseif ( @$App->R['action'] == "reset" ) {
  
  $resetDisplayCode = 1;
	
	$qLookup = "
    SELECT *
    FROM Login
    WHERE id = ".$App->R['login_id']."
  ";
  $result = $App->oDBMY->query($qLookup);
  if ( $row = $result->fetch_assoc() ) {
    $qUpdate = "
			UPDATE Login
			SET password = '".sha1($App->R["new_password"].$row['login_hash'])."'
			WHERE id = ".$row['id']."
		";
		$result = $App->oDBMY->query($qUpdate);
  }
  
} else {
  header('Location: /');
  exit;
}

$bodyClass = "";
require_once("_head.php");
//wla($App->R);
?>
<!--wrap-->
<div class="wrap clearfix">
  <!--row-->
  <div class="row">
  <!--content-->
    <section class="content center full-width">
      <div class="modal container">
        <h3>Reset Password</h3>
        <? if ( $resetDisplayCode === 0 ) { ?>
          <form id="frmResetPassword" action="reset_password.php" method="post" onsubmit="return ValidateResetPassword()" >
            <input type="hidden" name="action" value="reset" >
            <input type="hidden" name="login_id" value="<?=$App->R['login_id']?>" >

            <!--<div class="f-row">
              <input class="form-control" type="password" id="current_password" name="current_password" placeholder="Current Password" value="" required >
            </div>-->
            <div class="f-row">
              <input type="password" class="form-control" id="new_password" name="new_password" placeholder="New password" autocomplete="off" required >
              <span class="help-block password-error hidden">Passwords must be at least 8 characters long, and include capital and lowercase letters and numbers</span>
            </div>
            <div class="f-row">
              <input type="password" class="form-control" id="new_password2" placeholder="Retype New password" autocomplete="off" required >
              <span class="help-block password-error hidden">Passwords don't match</span>
            </div>
            <div class="f-row bwrap">
              <input type="submit" >
            </div>
          </form>
          <p>Remembered your password? <a href="/login/">Log in.</a></p>
        <? } 
				elseif ( $resetDisplayCode == 1 ) { 
					?>
					<div class="alert alert-success" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						Try to login now with your new password!
					</div>
					<?php
				} else {
					?>
					<div class="alert alert-danger" role="alert">
						<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						Your token has expired
					</div>
					<?php
				} ?>
      </div>
    </section>
    <!--//content-->
  </div>
  <!--//row-->
</div>
<!--//wrap-->
<script>
  
  $(function(){
    
    $('#new_password, #new_password2').donetyping(function(){
      checkPassword();
    }, 100);
    
    $('#new_password, #new_password2').change(function(){
      checkPassword();
    });
    
  });
  
  function checkPassword() {
    $('#new_password').parent().removeClass('has-error');
    $('#new_password').parent().find('.help-block').addClass('hidden');
    $('#new_password2').parent().removeClass('has-error');
    $('#new_password2').parent().find('.help-block').addClass('hidden');

    if ( $('#new_password').val().length > 0 && $('#new_password2').val().length > 0 && $('#new_password').val() !== $('#new_password2').val() ) {
      $('#new_password').parent().addClass('has-error');
      $('#new_password2').parent().addClass('has-error');
      $('#new_password2').parent().find('.password-error').removeClass('hidden');
    }

    var correctPw = ( $('#new_password').val().length >= 8 ) && /[0-9]/.test($('#new_password').val()) && /[A-Z]/.test($('#new_password').val()) && /[a-z]/.test($('#new_password').val());

    if ( $('#new_password').val().length > 0 && !correctPw  ) {
      $('#new_password').parent().addClass('has-error');
      $('#new_password').parent().find('.password-error').removeClass('hidden');
    } else {
      
    }
  }
  
  function ValidateResetPassword() {
    if ( $('#password').parent().hasClass('has-error') ||
          $('#password2').parent().hasClass('has-error') ) {
      return false;
    }
    return true;
  }
  
</script>
<?php
$App = "";
require_once('_footer.php');
?>
