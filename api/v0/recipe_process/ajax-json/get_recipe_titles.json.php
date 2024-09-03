<?php
$App = "";
require_once('../../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-type: application/json');

$json_results = array();

// get results
$sel_query = "
  SELECT id, title FROM Recipe
  WHERE parent_recipe_id = 0
  ORDER BY date_created DESC;
";
$results = $App->oDBMY->query($sel_query);

array_push($json_results, array('id' => '0', 'title' => 'N/A'));
while ($row = $results->fetch_assoc()) {
    array_push($json_results, $row);
}
@$results->free();

//wla($json_results);
echo json_encode($json_results);

$App = "";
?>
