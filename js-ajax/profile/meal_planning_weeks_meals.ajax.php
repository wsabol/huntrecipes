<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();


$today = new DateTime("today");
$d = new DateTime( $App->R['week_of'] );
$past_week = ( $today >= $d );

$this_week = false;
if ( !$past_week ) {
  $today->add(new DateInterval("P7D"));
  $this_week = ( $today >= $d );
}

$sel_query = "
  Call spSelectWeeksMeals(".$_SESSION['Login']['id'].", '".$App->R['week_of']."');
";
$results = array();
$r = $App->oDBMY->query( $sel_query );
while ( $row = $r->fetch_assoc() ) {
  array_push($results, $row);
}
$r->free();

if ( count($results) === 0 ) {
  ?>
  <div class="alert alert-banner">
    Nothing planned yet.
  </div>
  <?php
} else {
  ?>
  <!--entries-->
  <div class="row">
    <div class="entries full-width _column-entries _column-entries-three">
      <?php for ( $i = 0; $i < count($results); $i++ ) { ?>
        <!--item-->
        <div class="entry one-third _column-entry">
          <figure>
            <img src="<?=$results[$i]['image_filename']?>" alt="" />
            <figcaption><a href="recipe.php?recipe_id=<?=$results[$i]['id']?>"><i class="icon icon-themeenergy_eye2"></i> <span>View recipe</span></a></figcaption>
          </figure>
          <div class="container">
            <h2><a href="recipe.php?recipe_id=<?=$results[$i]['id']?>"><?=htmlspecialchars($results[$i]['title'])?></a></h2> 
            <div class="actions">
              <div data-recipe-id="<?=$results[$i]['id']?>" data-week-of="<?=$App->R['week_of']?>" >
                <div class="meal-plan">
                  <span class="btn <?=( !$past_week ? "btnMealPlanMealRemove" : "" )?> btn-sm btn-<?=( !$past_week ? "danger" : "disabled" )?>">
                    <i class="fa fa-remove"></i> my meals
                  </span>
                </div>
                <div class="likes divSaveToFavorites <?=( $results[$i]['favorite_flag'] == 1 ? "favorite-recipe" : "" )?>"><i class="fa fa-heart"></i><span class="favorite-count"><?=$results[$i]['favorite_count']?></span></div>
              </div>
            </div>
          </div>
        </div>
        <!--item-->
      <?php } ?>
    </div>
  </div>
  <!--//entries-->

  <?php
  $qMealPlanIngr = "
    Call spMealPlanIngredients(
      ".$_SESSION['Login']['id'].",
      '".$App->R['week_of']."'
    );
  ";
  $Ingr = array();
  $rs = $App->oDBMY->query( $qMealPlanIngr );
  while ( $row = $rs->fetch_assoc() ) {
    array_push($Ingr, $row);
  }
  $rs->free();
  ?>
  <table class="ctable table">
    <thead>
      <tr>
        <th>Ingredient</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <? for ( $i = 0; $i < count($Ingr); $i++ ) {
        $value_formatted = friendlyAmount( $Ingr[$i]['total_amount'], $Ingr[$i]['measure_type_id'], $value_decimal );
        ?>
        <tr>
          <td>
            <?=( $value_decimal > 1 ? $Ingr[$i]['name_plural'] : $Ingr[$i]['name'] )?>
            <span class="text-info pointer click-behave btnIngrDetail pull-right" data-ingredient-id="<?=$Ingr[$i]['ingredient_id']?>" ><i class="fa fa-info-circle"></i> </span>
          </td>
          <td><?=$value_formatted?></td>
        </tr>
        <tr class="hidden" >
          <td colspan=2 id="ingrDetail<?=$Ingr[$i]['ingredient_id']?>" >
          </td>
        </tr>
      <? } ?>
    </tbody>
  </table>

  <!-- action bar -->
  <a href="/meal_plan_print.php?week_of=<?=$App->R['week_of']?>" class="button" target="_blank" title="Printer Friendly">
    <i class="fa fa-print"></i>
    <span>View Print Friendly</span>
  </a>
  <?php
  if ( !$this_week ) {
    ?>
    <span class="button btnCopyMealsToCurrentWeek" title="Printer Friendly">
      <i class="fa fa-copy"></i>
      <span>Copy to Current Week</span>
    </span>
    <?php
  }
  ?>
  <!-- ./action bar -->

  <script>
    $(function(){
      
      $('.btnIngrDetail').click(function(){
        var ingredient_id = $(this).data('ingredient-id');
        $(this).parent().parent().next().toggleClass('hidden');
        if ( $.trim($('#ingrDetail'+ingredient_id).html()) == '' ) {
          LoadDivContent('profile/meal_planning_ingredient_detail', '', 'ingrDetail'+ingredient_id, {
            week_of: '<?=$App->R['week_of']?>',
            ingredient_id: ingredient_id
          })
        }
      });
      
      $('.btnCopyMealsToCurrentWeek').click(function(){
        $('.btnCopyMealsToCurrentWeek i').removeClass('fa-copy');
        $('.btnCopyMealsToCurrentWeek i').addClass('fa-spinner');
        
        $.ajax({
          url: '/ajax-json/meal_planning/copy_meals_to_week.json.php',
          type: 'GET',
          data: {
            week_of: '<?=$App->R['week_of']?>'
          },
          success: function( response ){
            console.log(response);
            $('.btnCopyMealsToCurrentWeek i').removeClass('fa-spinner');
            $('.btnCopyMealsToCurrentWeek i').addClass('fa-copy');
          }
        });
      });
      
    });
  </script>

  <?php
}

$App = "";
?>