<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

header('Content-Type: application/json');

$json_results = array();
$json_results['results'] = array();
$App->R['q'] = $App->oDBMY->prepstring(@$App->R['q']);

$ingr_query = "
  select
    ri.ingredient_id,
    max(i.name) name,
    0 AS selected
  FROM (
   SELECT 
    count(1) ingr_count,
    re.id recipe_id
   FROM Recipe re
   JOIN RecipeIngredient ri
   ON ri.recipe_id = re.id
   INNER JOIN (
    SELECT x.id AS ingr_search_id
    FROM Ingredient x
    WHERE FIND_IN_SET(x.id, '".@$App->R['ingrList']."')
    OR '".@$App->R['ingrList']."' = ''
   ) i
   ON i.ingr_search_id = ri.ingredient_id
   WHERE 1=1
   AND re.published_flag = 1
   AND (CASE WHEN ".(@$App->R['type_id']*1)." = 0 THEN 1 WHEN re.type_id = ".(@$App->R['type_id']*1)." THEN 1 ELSE 0 END) = 1
   AND (CASE WHEN ".(@$App->R['course_id']*1)." = 0 THEN 1 WHEN re.course_id = ".(@$App->R['course_id']*1)." THEN 1 ELSE 0 END) = 1
   AND (CASE WHEN ".(@$App->R['cuisine_id']*1)." = 0 THEN 1 WHEN re.cuisine_id = ".(@$App->R['cuisine_id']*1)." THEN 1 ELSE 0 END) = 1
   AND (CASE WHEN ".(@$App->R['chef_id']*1)." = 0 THEN 1 WHEN re.chef_id = ".(@$App->R['chef_id']*1)." THEN 1 ELSE 0 END) = 1
   AND (
    UPPER(re.title) LIKE CONCAT('%', UPPER('".$App->R['q']."'), '%')
    OR UPPER(re.instructions) LIKE CONCAT('%', UPPER('".$App->R['q']."'), '%')
    OR EXISTS(
     SELECT * FROM RecipeIngredient rix
     JOIN Ingredient ix
     ON ix.id = rix.ingredient_id
     WHERE rix.recipe_id = re.id AND UPPER(ix.name) LIKE CONCAT('%', UPPER('".$App->R['q']."'), '%')
    )
   )
   GROUP BY re.id
  ) grp_init
  INNER JOIN Recipe r
  ON r.id = grp_init.recipe_id
  JOIN RecipeIngredient ri
  ON ri.recipe_id = r.id
  JOIN Ingredient i
  ON i.id = ri.ingredient_id
  WHERE 1=1
  AND grp_init.ingr_count = LENGTH('".@$App->R['ingrList']."') - LENGTH(REPLACE('".@$App->R['ingrList']."', ',', '')) + 1 OR '".@$App->R['ingrList']."' = ''
  AND ri.ingredient_id NOT IN('".@$App->R['ingrList']."')
  GROUP BY ri.ingredient_id
  ORDER BY RAND()
  LIMIT ".$App->R['result_limit'].";
";
$json_results['query'] = $ingr_query;
$result = $App->oDBMY->query( $ingr_query );
if ( !!$result ) {
  while ( $row = $result->fetch_assoc() ) {
    array_push($json_results['results'], $row);
  }
  $result->free();
}

$ingr_selection = "
  select
    i.id AS ingredient_id,
    i.name,
    1 AS selected
  FROM Ingredient i
  WHERE 1=1
  AND i.id IN('".@$App->R['ingrList']."');
";
$json_results['query2'] = $ingr_selection;
$result = $App->oDBMY->query( $ingr_selection );
if ( !!$result ) {
  while ( $row = $result->fetch_assoc() ) {
    array_push($json_results['results'], $row);
  }
  $result->free();
}

echo json_encode($json_results);

$App = "";
?>
