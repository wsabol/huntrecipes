<?php

//this makes the connection to the database and any other necessary items
@session_start();
require_once("../../_php_common.php");
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', '1');

header('Content-Type: application/json');

$response = array();
$response['success'] = 0;
$response['logon_error'] = "";

if ( !isset($App->R['elogin']) ) {
	$response['success'] = -1;
}


$dLogin = explode(";", base64_decode($App->R['elogin']), 2);
$App->R["username"] = $dLogin[0];
$App->R["password"] = @$dLogin[1];

if( 
  strlen( $App->R["username"] ) > 0 
  &&
  strlen( $App->R["password"] ) > 0 
	&&
	$response['success'] == 0
){
  //let's try to see if this user exists and check to see if the password entered matches the password in the db
  $sel_query = "
    Call spSelectLogin(
      '" . $App->R["username"] . "'
    );
  ";
  #wla( $sel_query ) ;
  $result = $App->oDBMY->query( $sel_query );
  if ( !!$result ) {
    $row = $result->fetch_assoc();
    $result->free();

    #wla( $row ) ;
    if( $row["password"] == sha1($App->R["password"].$row['login_hash']) ) {
      #wla( $row ) ;
      #exit;

      if ( $row['account_status_id'] == 2 ) {
				$response['logon_error'] = "Access Denied";
				
			} else {
        StdLoginRoutine( $row, @$App->R['rememberme']*1 );

        //wl($new_url);
        //print_r($_SESSION);
        //exit;
        //header("Location: $new_url");
        $response['success'] = 1;
      }
    }
    else { //password is wrong
      $response['logon_error'] = "Username/Password provided do not match what we have on record";
    }

  }
  else {
    //no recordset returned
		$response['logon_error'] = "Error logging in.";
  }
}
else {
  $response['success'] = -1;
}

echo json_encode($response);

$App = "";
?>
