<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

  
$sel_query = "
  SELECT
    round(ri.amount, 1) amount,
    m.name measure,
    ri.recipe_id
  FROM RecipeIngredient ri
  JOIN Measure m
  ON m.id = ri.measure_id
  WHERE ingredient_id =".$App->R['ingredient_id'].";
";
//wl($sel_query);
$result = $App->oDBMY->query($sel_query);
$Ingr = array();
while ( $row = $result->fetch_assoc() ) {
  array_push($Ingr, $row);
}
?>

<div class="row">
  <div class="container">
    <h3>Ingredients</h3>
    <table class="table table-striped">
      <tbody>
        <?php
        for ( $i = 0; $i < count($Ingr); $i++ ) {
          ?>
          <tr><td><?=$Ingr[$i]['amount']?></td><td><?=$Ingr[$i]['measure']?></td><td><a href="/recipe.php?recipe_id=<?=$Ingr[$i]['recipe_id']?>" target="_blank"><?=$Ingr[$i]['recipe_id']?></a></td></tr>
          <?php
        }
        ?>
      </tbody>
    </table>
    
    <div class="f-row bwrap">
      <span class="button btn-block" id="close" >Close</span>
    </div>
  </div>
</div>
<script>
  $(function(){
    $('#close').click(function(){
      $('#divEditIngr').empty();
    });
  });
</script>
<?php

$App = "";
?>