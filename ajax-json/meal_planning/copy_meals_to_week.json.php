<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-Type: application/json');


if ( !isset($App->R['week_of']) || @$App->R['week_of'] == "" ) {
  return;
}

$wkStart = new DateTime();
while ( $wkStart->format("N") != $_SESSION['Login']['week_start_day_of_week'] ) {
  $wkStart->add(new DateInterval("P1D"));
}
$current_week_of = $wkStart->format("Y-m-d");

$json_results = array();
$json_results['input_data'] = $App->R;
$json_results['success'] = 0;

$sel_query = "
  SELECT
    recipe_id,
    week_of,
    serving_count
  FROM LoginMealPlanning
  WHERE login_id = ".$_SESSION['Login']['id']."
  AND week_of = '".$App->R['week_of']."';
";
$results = $App->oDBMY->query( $sel_query );
while ( $Meal = $results->fetch_assoc() ) {
  $upd_query = "
    Call spAddRecipeToMealPlan(
      ".$_SESSION['Login']['id'].",
      ".$Meal['recipe_id'].",
      '".$current_week_of."',
      ".$Meal['serving_count']."
    );
  ";
  $json_results['query'] = $upd_query;
  $success = $App->oDBMY->execute( $upd_query );
  if ( $success ) {
    $json_results['success'] = 1;
  } else {
    $json_success['success'] = 0;
  }
}

echo json_encode($json_results);

$App = "";
?>