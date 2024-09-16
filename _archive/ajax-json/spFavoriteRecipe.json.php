<?php
$App = "";
require_once('../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-Type: application/json');

$json_results = array();
$json_results['input_data'] = $App->R;
$json_results['success'] = 0;

$upd_query = "
  Call spFavoriteRecipe(
    ".$_SESSION['Login']['id'].",
    ".$App->R['recipe_id'].",
    ".$App->R['favorite_flag']."
  );
";
$json_results['query'] = $upd_query;
$success = $App->oDBMY->execute( $upd_query );
if ( $success ) {
  $json_results['success'] = 1;
} else {
  $json_success['success'] = 0;
}

echo json_encode($json_results);

$App = "";
?>