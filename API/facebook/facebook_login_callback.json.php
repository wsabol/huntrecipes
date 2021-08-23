<?php
/**
TODO
handle access token expiration / saving
***/

$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

$json_results['success'] = 0;

$lookup_query = "
  SELECT username FROM Login
  WHERE facebook_user_id = '".$App->R['facebook_user_id']."'
";
#wla( $lookup_query ) ;
$result = $App->oDBMY->query($lookup_query);
if ( !!$result ) {
  $foundUser = $result->fetch_assoc();
  $result->free();
		
	if ( strlen($foundUser['username']) > 0 ) {
		$json_results['success'] = 1;
		
		if ( strlen(@$App->R['profile_picture']) > 0 ) {
			$upd_query = "
				UPDATE Login
				SET profile_picture = '".$App->R['profile_picture']."'
				WHERE username = '".$foundUser['username']."'
			";
			#wla( $upd_query ) ;
			$App->oDBMY->execute($upd_query);
		}
		
		// login
		$sel_query = "
			Call spSelectLogin(
				'" . $foundUser['username'] . "'
			);
		";
		//wla( $sel_query ) ;
		$result = $App->oDBMY->query( $sel_query );
		$dbLoginRecord = $result->fetch_assoc();
		StdLoginRoutine( $dbLoginRecord, 1 );
		$json_results['success'] = (@$_SESSION['Login']['id']*1 > 0 ? 1 : 0);
	}
	else {
		$json_results['err_msg'] = "If you already have an account, try logging in regularly. Otherwise, you need to register.";
	}
}
else {
	$json_results['err_msg'] = "If you already have an account, try logging in regularly. Otherwise, you need to register.";
}

echo json_encode($json_results);

$App = "";
?>