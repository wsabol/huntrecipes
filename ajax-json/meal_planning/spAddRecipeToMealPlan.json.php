<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-Type: application/json');

$wkStart = new DateTime();
while ( $wkStart->format("N") != $_SESSION['Login']['week_start_day_of_week'] ) {
  $wkStart->add(new DateInterval("P1D"));
}

if ( !isset($App->R['week_of']) || @$App->R['week_of'] == "" ) {
  $App->R['week_of'] = $wkStart->format("Y-m-d");
}

$App->R['this_week_flag'] = ( $wkStart->format("Y-m-d") == $App->R['week_of'] ? 1 : 0 );
$wkStart = "";

$json_results = array();
$json_results['input_data'] = $App->R;
$json_results['success'] = 0;

$upd_query = "";
if ( isset($App->R['remove']) && @$App->R['remove']*1 == 1 ) {
  $upd_query = "
    DELETE FROM LoginMealPlanning
    WHERE login_id = ".$_SESSION['Login']['id']."
    AND recipe_id = ".$App->R['recipe_id']."
    AND week_of = '".$App->R['week_of']."';
  ";
} else {
  $upd_query = "
    Call spAddRecipeToMealPlan(
      ".$_SESSION['Login']['id'].",
      ".$App->R['recipe_id'].",
      '".$App->R['week_of']."',
      ".(@$App->R['serving_count']*1)."
    );
  ";
}
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