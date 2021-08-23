<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

$json_results['success'] = 0;

if ( @$_SESSION['Login']['id']*1 > 0 ) {
	$lookup_query = "
		UPDATE Login
		SET facebook_user_id = '".$App->R['facebook_user_id']."',
		facebook_access_token = '".$App->R['facebook_access_token']."',
		profile_picture = '".$App->R['facebook_picture']."'
		WHERE id = ".$_SESSION['Login']['id']."
	";
	$success = $App->oDBMY->execute($lookup_query);
	if ( $success ) {
		$json_results['success'] = 1;
		$_SESSION['Login']['facebook_user_id'] = $App->R['facebook_user_id'];
		$_SESSION['Login']['facebook_access_token'] = $App->R['facebook_access_token'];
		$_SESSION['Login']['profile_picture'] = $App->R['facebook_picture'];
	}
	else {
		$json_results['err_msg'] = "Unexpected DB Error.";
	}
}
else {
	$json_results['err_msg'] = "You are not logged in?!?!!";
}

echo json_encode($json_results);

$App = "";
?>