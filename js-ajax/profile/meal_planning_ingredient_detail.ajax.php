<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();


$qMealPlanIngrDet = "
  Call spMealPlanIngredientDetail(
    ".$_SESSION['Login']['id'].",
    '".$App->R['week_of']."',
    ".$App->R['ingredient_id']."
  );
";
$IngrDetail = array();
$rs = $App->oDBMY->query( $qMealPlanIngrDet );
while ( $row = $rs->fetch_assoc() ) {
  array_push($IngrDetail, $row);
}
$rs->free();
//wla($IngrDetail);

?>
<table class="table bootstrap-reset">
  <thead>
    <tr>
      <th>Recipe</th>
      <th>Amount</th>
      <th>Prep</th>
    </tr>
  </thead>
  <tbody>
    <? for ( $i = 0; $i < count($IngrDetail); $i++ ) {
      $value_formatted = friendlyAmount( $IngrDetail[$i]['general_amount'], $IngrDetail[$i]['measure_type_id'], $value_decimal );
      ?>
      <tr>
        <td>
          <?=$IngrDetail[$i]['title']?>
        </td>
        <td><?=$value_formatted?></td>
        <td><?=$IngrDetail[$i]['ingredient_prep']?></td>
      </tr>
    <? } ?>
  </tbody>
</table>
<?php
$App = "";
?>