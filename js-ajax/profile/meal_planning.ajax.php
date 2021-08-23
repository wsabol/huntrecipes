<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$weeks = array();

$wkStart = new DateTime( date('Y/m/d', strtotime($_SESSION['Login']['date_created'])) );
while ( $wkStart->format("N") != $_SESSION['Login']['week_start_day_of_week'] ) {
  $wkStart->add(new DateInterval("P1D"));
}

$wkLast = new DateTime("tomorrow");
while ( $wkLast->format("N") != $_SESSION['Login']['week_start_day_of_week'] ) {
  $wkLast->add(new DateInterval("P1D"));
}
$wkCurrent = new DateTime( $wkLast->format("Y/m/d") );
$wkLast->add(new DateInterval("P14D"));

$wkEnd = new DateTime($wkStart->format('Y/m/d'));
$wkEnd->add(new DateInterval("P6D"));

while ( $wkStart <= $wkLast ) {
  array_push( $weeks, array(
    'week_of'=>$wkStart->format('Y-m-d'),
    'full_week'=>$wkStart->format('M j')." - ".$wkEnd->format('M j'),
    'current_week'=>( $wkStart == $wkCurrent ? 1 : 0 )
  ));
  
  $wkStart->add(new DateInterval("P7D"));
  $wkEnd->add(new DateInterval("P7D"));
}
//wla($weeks);

$wkCurrent = $weeks[count($weeks) - 3];

?>

<div class="alert alert-banner meal-weeks-banner" >
  <!--<div class="row">
    <div class="one-fourth no-padding">
      <a href="#" class="wkPrev week-page" >
        <h2 class="text-center no-padding">
          <i class="fa fa-angle-left"></i>
        </h2>
      </a>
    </div>
    <div class="one-half no-padding">
      <h2 id="week-title" class="text-center no-padding">
        <?=$wkCurrent['full_week']?>
      </h2>
    </div>
    <div class="one-fourth no-padding">
      <a href="#" class="wkNext week-page" >
        <h2 class="text-center no-padding">
          <i class="fa fa-angle-right"></i>
        </h2>
      </a>
    </div>
  </div>-->
	<table style="width: 100%">
		<tr>
			<td class="wkPrev week-page pointer click-behave" >
				<h2 class="text-center no-padding">
					<i class="fa fa-angle-left"></i>
				</h2>
			</td>
			<td>
				<h2 id="week-title" class="text-center no-padding">
					<?=$wkCurrent['full_week']?>
				</h2>
			</td>
			<td class="wkNext week-page pointer click-behave">
				<h2 class="text-center no-padding">
					<i class="fa fa-angle-right"></i>
				</h2>
			</td>
		</tr>
	</table>
</div>
<div id="divMealPlanningWeeksMeals" >
  
</div>
<script>
  $(function(){
    
    var weeks = <? echo json_encode($weeks); ?>;
    var wkCurrent = <?=count($weeks) - 3?>;
    //console.log(weeks);
    LoadDivContent('profile/meal_planning_weeks_meals', '', 'divMealPlanningWeeksMeals', { week_of: weeks[wkCurrent].week_of });
    
    $('.wkNext').click(function(e){
      e.preventDefault();
      if ( $(this).hasClass('disabled') ) {
        return;
      }
      
      wkCurrent++;
      $('#week-title').text( weeks[wkCurrent].full_week );
      LoadDivContent('profile/meal_planning_weeks_meals', '', 'divMealPlanningWeeksMeals', { week_of: weeks[wkCurrent].week_of });
      
      if ( wkCurrent == weeks.length - 1 ) {
        $(this).addClass('disabled');
      } else {
        $('.week-page').removeClass('disabled');
      }
    });
    
    $('.wkPrev').click(function(e){
      e.preventDefault();
      if ( $(this).hasClass('disabled') ) {
        return;
      }
      
      wkCurrent--;
      $('#week-title').text( weeks[wkCurrent].full_week );
      LoadDivContent('profile/meal_planning_weeks_meals', '', 'divMealPlanningWeeksMeals', { week_of: weeks[wkCurrent].week_of });
      
      if ( wkCurrent == 0 ) {
        $(this).addClass('disabled');
      } else {
        $('.week-page').removeClass('disabled');
      }
    });
    
    $('#divMealPlanningWeeksMeals').on('click', '.btnMealPlanMealRemove', function(e){
      var recipe_id = $(this).parent().parent().data('recipe-id');
      var week_of = $(this).parent().parent().data('week-of');

      $.ajax({
        url: '/ajax-json/meal_planning/spAddRecipeToMealPlan.json.php',
        type: 'GET',
        data: {
          recipe_id: recipe_id,
          week_of: week_of,
          remove: 1
        },
        success: function( response ){
          if ( response === null ) {
            console.log('spAddRecipeToMealPlan null response');
          } else if ( response.success === 0 ) {
            console.log('spAddRecipeToMealPlan sql error: ' + response.query);
          } else {
            // success
            LoadDivContent('profile/meal_planning_weeks_meals', '', 'divMealPlanningWeeksMeals', { week_of: weeks[wkCurrent].week_of });
            LoadDivContent( 'profile/my_favorites', '', 'favorites', {} );
						LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {} );
          }
        }
      });
    });
    
  });
</script>
<?php
$App = "";
?>