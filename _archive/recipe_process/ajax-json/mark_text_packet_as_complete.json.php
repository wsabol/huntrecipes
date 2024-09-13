<?php
$App = "";
require_once('../../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-type: application/json');

$json_results = array();
$aLineNumbers = $App->R['lineNumbers'];
$inClause = implode(", ", $aLineNumbers);

// update cleared_flag
$update_query = "
  UPDATE RecipesRaw
  SET cleared_flag = 1
  WHERE line_number IN (" . $inClause . ");
";
$sql_success = $App->oDBMY->execute($update_query);
$json_results['query'] = $update_query;
$json_results['success'] = ($sql_success ? 1 : 0);

echo json_encode($json_results);

$App = "";
?>
