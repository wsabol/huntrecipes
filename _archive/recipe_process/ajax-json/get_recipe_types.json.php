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
  SELECT id, name FROM Ingredient
  ORDER BY name ASC;
";
$results = $App->oDBMY->query($sel_query);

while ($row = $results->fetch_assoc()) {
    array_push($json_results, $row);
}
@$results->free();

//wla($json_results);
echo json_encode($json_results);

$App = "";
?>
