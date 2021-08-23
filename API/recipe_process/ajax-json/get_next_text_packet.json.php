<?php
$App = "";
require_once('../../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

header('Content-type: application/json');

$json_results = array();

// get results
if ( @$App->R['last_line_number']*1 > 0 ) {
  $sel_query = "
    SELECT * FROM RecipesRaw
    WHERE line_number > " . @$App->R['last_line_number']*1 . "
    ORDER BY line_number ASC
  ";
} else {
  $sel_query = "
    SELECT * FROM RecipesRaw
    WHERE cleared_flag = 0
    ORDER BY line_number ASC
  ";
}
$results = $App->oDBMY->query( $sel_query );
//wl($sel_query);
//print_r($results);

$empty_line_counter = 0;
$full_line_counter = 0;
while ( $row = $results->fetch_assoc() ) {
  //wla($json_results);

  $row['line_text'] = trim($row['line_text'], " \t\n\r\0\x0B\f");
  $row['line_text'] = utf8_encode( $row['line_text'] );

  if ( strlen(trim($row['line_text'])) === 0 && $full_line_counter > 0 ) {
    $empty_line_counter++;
  }
  if ( strlen(trim($row['line_text'])) > 0 ) {
    $full_line_counter++;
  }

  //wla($row);
  array_push( $json_results, $row );
  //echo json_encode( $json_results );

  if ( $empty_line_counter == 2 && $full_line_counter > 0 ) {
    break;
  }
}
@$results->free();

//wla($json_results);
echo json_encode( $json_results );

$App = "";
?>