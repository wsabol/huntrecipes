<?php

function cntRegistrationAuth( $email, $username, $name ) {
  $registration_hash = md5(sha1($username.$_SERVER['REMOTE_ADDR']));
      
  // registration email confirm notice
  $msgBody = '
    Hello Dear User,<br>
    <a href="http://'.$_SERVER['HTTP_HOST'].'/login.php?uname='.$username.'&registration_auth='.$registration_hash.'" >Click Here</a> to authenticate your login.<br>
    <br>
    See you soon '.$name.'!<br>
    <br>
    -Will<br>
  ';
  //wl($msgBody);
  $additional_headers = "From: HuntRecipes < contact@willsabol.com >\n";
  $additional_headers .= "X-Sender: HuntRecipes < contact@willsabol.com >\n";
  $additional_headers .= 'X-Mailer: PHP/' . phpversion();
  $additional_headers .= "X-Priority: 1\n"; // Urgent message!
  $additional_headers .= "Return-Path: contact@willsabol.com\n"; // Return path for errors
  $additional_headers .= "MIME-Version: 1.0\r\n";
  $additional_headers .= "Content-Type: text/html; charset=iso-8859-1\n";
  
  $mailSuccess = SendEmail("contact@willsabol.com", $name, $email, array(), array(), "", "", "HuntRecipes - Account Confirmation", $msgBody);
  if ( !$mailSuccess ) $mailSuccess = mail( $email, "HuntRecipes - Account Confirmation", $msgBody, $additional_headers );
  
  return $mailSuccess;
}

function cntResetPassword( &$App, $email, $login_id, $username, $name ) {
  $reset_token = sha1(mt_rand());
  $expires = new DateTime('now');
  $expires->add(new DateInterval("P1D"));
  
  $storeToken = "
    INSERT INTO PasswordResetToken
    ( hashed_login, token, expires )
    VALUES
    ( '".md5($login_id)."', '".$reset_token."', '".$expires->format('Y-m-d H:i:s')."' );
  ";
  $App->oDBMY->execute($storeToken);
  
  $msgBody = '
    Hello Dear User,<br>
    <a href="http://'.$_SERVER['HTTP_HOST'].'/reset_password.php?login_id='.$login_id.'&reset_token='.$reset_token.'" >Click Here</a> to reset your password.<br>
    This link is good for one day, until '.$expires->format("F d, Y g:iA").'
    <br>
    See you soon '.$name.'!<br>
    <br>
    -Will<br>
  ';
  /*$msgBody = '
    Hello Dear User,<br>
    Your password is <strong>'.$pw.'</strong>
    <br>
    See you soon '.$name.'!<br>
    <br>
    -Will<br>
  ';*/
  //wl($msgBody);
  $additional_headers = "From: HuntRecipes < contact@willsabol.com >\n";
  $additional_headers .= "X-Sender: HuntRecipes < contact@willsabol.com >\n";
  $additional_headers .= 'X-Mailer: PHP/' . phpversion();
  $additional_headers .= "X-Priority: 1\n"; // Urgent message!
  $additional_headers .= "Return-Path: contact@willsabol.com\n"; // Return path for errors
  $additional_headers .= "MIME-Version: 1.0\r\n";
  $additional_headers .= "Content-Type: text/html; charset=iso-8859-1\n";
  
  $mailSuccess = SendEmail("contact@willsabol.com", $name, $email, array(), array(), "", "", "HuntRecipes - Account Confirmation", $msgBody);
  if ( !$mailSuccess ) $mailSuccess = mail( $email, "HuntRecipes - Reset Password", $msgBody, $additional_headers );
  
  return $mailSuccess;
}
