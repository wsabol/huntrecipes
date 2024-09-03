<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
//@session_write_close(); 

header('Content-Type: application/json');

$json_results = array();
$json_results['input_data'] = $App->R;
$json_results['success'] = 0;

$upd_query = "
  UPDATE Login
  SET ".$App->R['field']." = '".$App->oDBMY->escape_string($App->R['value'])."'
  WHERE id = ".$_SESSION['Login']['id'].";
";
#wl($sel_query);
$success = $App->oDBMY->execute( $upd_query );
if ( $success ) {
  $json_results['success'] = 1;
  if ( isset($_SESSION['Login'][$App->R['field']]) ) {
    $_SESSION['Login'][$App->R['field']] = $App->R['value'];
    
    if ( $App->R['field'] == "password" || $App->R['field'] == "username" ) {
      $sql = "
        UPDATE LoginSession
        SET session_hash = NULL
        WHERE login_id = ".$_SESSION['Login']['id']."
        AND user_agent_id = ".$_SESSION['Login']['user_agent_id']."
      ";
      @$App->oDBMY->execute( $sql );

      // clear cookies
      @setcookie("uname", '', time() - 3600, '/', '.recipes.willsabol.com');
    }
  }
}

echo json_encode($json_results);

$App = "";
?>
