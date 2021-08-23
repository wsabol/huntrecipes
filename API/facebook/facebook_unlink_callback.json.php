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
		SET facebook_user_id = '0',
		facebook_access_token = ''
		WHERE id = ".$_SESSION['Login']['id']."
	";
	$success = $App->oDBMY->execute($lookup_query);
	if ( $success ) {
		$json_results['success'] = 1;
		$_SESSION['Login']['facebook_user_id'] = 0;
		$_SESSION['Login']['facebook_access_token'] = '';
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