<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: application/json');

function checkUniqueUsername($seed) {
    $lookup_query = "
		SELECT count(1) icount FROM Login
		WHERE username = '" . $seed . "';
	";
    $result = $App->oDBMY->query($lookup_query);
    $icount = $result->fetch_assoc();
    $result->free();
    if ($icount['icount'] == 0) {
        return $seed;
    } else {
        return checkUniqueUsername($seed . 'u');
    }
}

$json_results['success'] = 0;

if (isset($App->R['email']) && isset($App->R['name'])) {

    if (@$_SESSION['Login']['id'] * 1 == 0) {
        $lookup_query = "
			SELECT count(1) icount FROM Login
			WHERE email = '" . $App->R['email'] . "'
			OR facebook_user_id = '" . $App->R['facebook_user_id'] . "';
		";
        $result = $App->oDBMY->query($lookup_query);
        $icount = $result->fetch_assoc();
        $result->free();
        if ($icount['icount'] == 0) {
            // new user

            // get unique username
            $username = checkUniqueUsername($App->R['facebook_user_id']);

            $new_query = "
				INSERT INTO Login (
					name,
					email,
					username,
					password,
					account_status_id,
					facebook_user_id,
					facebook_access_token,
					profile_picture
				) VALUES (
					'" . $App->oDBMY->escape_string($App->R['name']) . "',
					'" . $App->oDBMY->escape_string($App->R['email']) . "',
					'" . $App->oDBMY->escape_string($username) . "',
					'" . $App->oDBMY->escape_string(md5($App->R['facebook_user_id'])) . "',
					2,
					'" . $App->R['facebook_user_id'] . "',
					'" . $App->R['facebook_access_token'] . "',
					'" . $App->R['facebook_picture'] . "'
				);
			";
            $success = $App->oDBMY->execute($new_query);
            if ($success) {
                // login
                $sel_query = "
					Call spSelectLogin(
						'" . $App->R['facebook_user_id'] . "'
					);
				";
                #wla( $sel_query ) ;
                $result = $App->oDBMY->query($sel_query);
                $dbLoginRecord = $result->fetch_assoc();
                StdLoginRoutine($dbLoginRecord, 1);
                $json_results['success'] = (@$_SESSION['Login']['id'] * 1 > 0 ? 1 : 0);
            } else {
                $json_results['err_msg'] = "DB error: " . $new_query;
            }
        } else {
            $json_results['err_msg'] = "User already exists. Try logging in.";
        }
    } else {
        $json_results['err_msg'] = "You are already logged in?";
    }
} else {
    $json_results['err_msg'] = "Invalid registration parameters.";
}

echo json_encode($json_results);

$App = "";
?>
