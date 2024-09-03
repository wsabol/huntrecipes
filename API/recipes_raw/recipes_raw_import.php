<?php
// ALREADY IMPORTED
exit;

$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');

// empty table
$emptySQL = "
  TRUNCATE TABLE RecipesRaw;
";
$App->oDBMY->execute( $emptySQL );

// read file
$fObj = fopen("recipes_main_raw.txt", "r");
$line_number = 0;
$i = 0;

while ( !feof($fObj) ) {
  $line = fgets($fObj);
  if ( $line !== false ) {
    $line = trim($line);
    $line_number++;
    //wl("Import: ".$line);

    $pushSQL = "
      INSERT INTO RecipesRaw(
        line_number,
        line_text
      ) VALUES (
        ".$line_number.",
        '".$App->oDBMY->escape_string($line)."'
      );
    ";
    $App->oDBMY->execute( $pushSQL );
  }

  $line = "";
  $i++;
}
fclose($fObj);

echo ("Lines imported: ".$line_number);

