<?php
$App = "";
@session_start();
require_once("_php_common.php");
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', '1');

//print_r($_SESSION['Login']['id']);
//print_r($_POST['logout_auth']);

if ( @$_SESSION['Login']['id']*1 > 0 && $_POST['logout_auth'] == 1 ) {
  // clear rememberme
  $uname_token = @$_COOKIE['uname_auth']; 
	if (!empty($uname_token)) {
		$tokenData = explode(":", $uname_token, 2);
	
		$sql = "
			DELETE ls FROM LoginSession ls
			WHERE ls.login_id = ".$_SESSION['Login']['id']."
			AND ls.selector = '".$tokenData[0]."'
		";
  	@$App->oDBMY->execute( $sql );
		
		$tokenData = "";
  }
  
  // clear cookies
  resetLoginCookie();
  
  // clear session
  session_unset();
  session_destroy();
  $_SESSION = array();
}

$App = "";
header('Location: /');

?>