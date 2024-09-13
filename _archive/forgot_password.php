<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

require_once('_user_contact.php');

if ( @$_SESSION['Login']['id'] > 0 ) {
  // you are already logged in
  header('Location: /');
  exit;
}

$usernameValidation = true;
$emailValidation = true;
$mailSuccess = null;

if ( @$App->R['action'] == "recover" ) {
  
  // check username
  $selUser = "
    SELECT count(1) icount FROM Login
    WHERE username = '".$App->oDBMY->escape_string($App->R['username'])."';
  ";
  $result = $App->oDBMY->query($selUser);
  if ( !!$result ) {
    $icount = $result->fetch_assoc();
    if ( $icount['icount'] == 0 ) {
      $usernameValidation = false;
    }
    $result->free();
  }
  
  // check email
  $selUser = "
    SELECT count(1) icount FROM Login
    WHERE email = '".$App->oDBMY->escape_string($App->R['email'])."';
  ";
  $result = $App->oDBMY->query($selUser);
  if ( !!$result ) {
    $icount = $result->fetch_assoc();
    if ( $icount['icount'] == 0 ) {
      $emailValidation = false;
    }
    $result->free();
  }
  
  if ( $usernameValidation && $emailValidation ) {
    $mailSuccess = false;
    
    // add user to do
    $lookup_query = "
      SELECT * FROM Login
      WHERE username = '".$App->oDBMY->escape_string($App->R['username'])."'
      AND email = '".$App->oDBMY->escape_string($App->R['email'])."';
    ";
    $result = $App->oDBMY->query($lookup_query);
    if ( !!$result ) {
      $userData = $result->fetch_assoc();
      $result->free();
      $mailSuccess = cntResetPassword( $App, $App->R['email'], $userData['id'], $userData['username'], $userData['name'] );
      /*if ( $mailSuccess ) {
        header('Location: /reset_password.php?uname='.$userData['name'].'auth='.md5(sha1($userData['name'].$_SERVER['REMOTE_ADDR'])) );
      }*/
    }
  }
  
}

$bodyClass = "";
// require_once("_head.php");

//wla($App->R);
?>
<!--wrap-->
<div class="wrap clearfix">
  <?php
  if ( @$App->R['action'] == "recover" ) {

    if ( !$usernameValidation ) {
      $totalValidation = 0;
      ?>
      <div class="alert alert-warning" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        The username you provided is not found in our records.
      </div>
      <?php
    }
    if ( !$emailValidation ) {
      $totalValidation = 0;
      ?>
      <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        The email you provided is not found in our records.
      </div>
      <?php
    }
    if ( $mailSuccess === false ) {
      ?>
      <div class="alert alert-danger" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Unexpected mail error.
      </div>
      <?php
    } elseif ( $mailSuccess === true ) {
      ?>
      <div class="alert alert-success" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Your password reset link has been emailed to you.
      </div>
      <?php
    }
  }
  
  ?>
  <!--row-->
  <div class="row">
  <!--content-->
    <section class="content center full-width">
      <div class="modal container">
        <h3>Forgot Password</h3>
        <form id="frmForgotPassword" action="forgot_password.php" method="post" onsubmit="return ValidateForgotPassword()" >
          <input type="hidden" name="action" value="recover" />

          <div class="f-row">
            <input class="form-control" type="email" id="email" name="email" placeholder="Your email" value="<?=@$App->R['email']?>" required />
          </div>
          <div class="f-row">
            <input class="form-control" type="text" id="username" name="username" placeholder="Your username" value="<?=@$App->R['username']?>" required />
          </div>
          <div class="f-row bwrap">
            <input type="submit" />
          </div>
        </form>
        <p><a href="/forgot_username.php">Forgotten username?</a></p>
        <p>Already have an account yet? <a href="/login/">Log in.</a></p>
      </div>
    </section>
    <!--//content-->
  </div>
  <!--//row-->
</div>
<!--//wrap-->
<script>
  
  $(function(){
    
  });
  
  function ValidateForgotPassword() {
    if ( !$('#email').val().isEmail() ) {
      return false;
    }
    return true;
  }
  
</script>
<?php
$App = "";
require_once('_footer.php');
?>
