<?php
$App = "";
require_once('../../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-type: application/json');

$json_results = array();

// get results
if (@$App->R['first_line_number'] * 1 > 0) {
    $sel_query = "
    SELECT * FROM RecipesRaw
    WHERE line_number < " . @$App->R['first_line_number'] * 1 . "
    ORDER BY line_number DESC
  ";
} else {
    $sel_query = "
    SELECT * FROM RecipesRaw
    WHERE cleared_flag = 1
    ORDER BY line_number DESC
  ";
}
$results = $App->oDBMY->query($sel_query);

$empty_line_counter = 0;
while ($row = $results->fetch_assoc()) {

    $row['line_text'] = trim($row['line_text'], " \t\n\r\0\x0B\f");
    $row['line_text'] = utf8_encode($row['line_text']);

    if (strlen(trim($row['line_text'])) == 0 && count($json_results) > 0) {
        $empty_line_counter++;
    }

    array_unshift($json_results, $row);

    if ($empty_line_counter == 2) {
        break;
    }
}
$results->free();

echo json_encode($json_results);

$App = "";
?>
