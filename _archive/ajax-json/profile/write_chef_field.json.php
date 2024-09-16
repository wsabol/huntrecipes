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
  UPDATE Chef
  SET ".$App->R['field']." = '".$App->oDBMY->escape_string($App->R['value'])."'
  WHERE login_id = ".$_SESSION['Login']['id'].";
";
#wl($sel_query);
$success = $App->oDBMY->execute( $upd_query );
if ( $success ) {
  $json_results['success'] = 1;
}

echo json_encode($json_results);

$App = "";
?>
