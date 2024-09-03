<?php
$App = "";
require_once('../../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

header('Content-type: application/json');

$json_results = array();
$json_results['input_data'] = $App->R;
$sql_success = true;

function recipeIngredientRecurse( &$App, $recipe_id, &$dbOutput, $oIngr, $i = 0 ) {
  if ( $i >= count( $oIngr ) ) {
    return true;
  }
  if ( $i === 0 ) {
    $dbOutput = array();
  }
  
  $update_ingr_query = "
    CALL spRecipeIngredientUpdate(
      " . $oIngr[$i]['recipe_ingredient_id'] . ",
      " . $recipe_id . ",
      " . @$oIngr[$i]['unit_id'] * 1 . ",
      '" . $App->oDBMY->escape_string($oIngr[$i]['ingredient_name']) . "',
      '" . $App->oDBMY->escape_string($oIngr[$i]['ingredient_prep']) . "',
      " . $oIngr[$i]['amount'] * 1 . ",
      " . @$oIngr[$i]['optional_flag'] * 1 . "
    );
  ";
  $result = $App->oDBMY->query( $update_ingr_query );
  if ( $result ) {
    $resultArr = $result->fetch_assoc();
    $resultArr['query'] = $update_ingr_query;
    array_push( $dbOutput, $resultArr );
    $result->free();
    
    return recipeIngredientRecurse( $App, $recipe_id, $dbOutput, $oIngr, $i + 1 );
  } else {
    return false;
  }
}

if ( @$App->R['deleted_flag']*1 == 1 ) {
  $dQuery = "
    UPDATE Recipe
    SET published_flag = -1
    WHERE recipe_id = ".$App->R['id'].";
  ";
  $sql_success = $App->oDBMY->execute( $dQuery );
  
} else {
  
  // update cleared_flag
  $update_query = "
    CALL spRecipeUpdate(
      " . @$App->R['id'] * 1 . ",
      '" . $App->oDBMY->escape_string($App->R['title']) . "',
      '" . $App->oDBMY->escape_string($App->R['chef_name']) . "',
      '" . $App->oDBMY->escape_string($App->R['recipe_type_name']) . "',
      " . $App->R['course_id'] . ",
      " . $App->R['cuisine_id'] . ",
      '" . $App->oDBMY->escape_string($App->R['instructions']) . "',
      '" . $App->oDBMY->escape_string($App->R['image_filename']) . "',
      " . $App->R['serving_count'] . ",
      " . $App->R['serving_measure_id'] . ",
      " . $App->R['parent_recipe_id'] . ",
      " . @$App->R['published_flag']*1 . "
    );
  ";
  $result = $App->oDBMY->query( $update_query );
  $json_results['recipe_query'] = $update_query;

  if ( $result ) {
    $oRecipe = $result->fetch_assoc();
    $json_results['output_data'] = $oRecipe;
    $result->free();

    $delIngr = "
      DELETE FROM RecipeIngredient
      WHERE recipe_id = ".$oRecipe['id'].";
    ";
    $App->oDBMY->execute( $delIngr );

    $sql_success = recipeIngredientRecurse( $App, $oRecipe['id'], $outputResults, $App->R['ingredientsList'] );
    $json_results['output_data']['ingredientsList'] = $outputResults;
  } else {
    $sql_success = false;
  }
  $json_results['success'] = ( $sql_success ? 1 : 0 );
  
}

echo json_encode( $json_results );

$App = "";
?>
