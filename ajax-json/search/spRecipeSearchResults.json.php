<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-Type: application/json');

$json_results = array();
$json_results['results'] = array();

$search_query = "
  Call spRecipeSearchResults(
    '".$App->oDBMY->escape_string(@$App->R['q'])."',
		".(@$App->R['type_id']*1).",
		".(@$App->R['course_id']*1).",
		".(@$App->R['cuisine_id']*1).",
		".(@$App->R['chef_id']*1).",
		'".@$App->R['ingrList']."',
		".(@$_SESSION['Login']['id']*1).",
		".$App->R['page_num']."
  );
";
$json_results['query'] = $search_query;
$result = $App->oDBMY->query( $search_query );
if ( !!$result ) {
  while ( $row = $result->fetch_assoc() ) {
    array_push($json_results['results'], $row);
  }
  $result->free();
}

echo json_encode($json_results);

$App = "";
?>
