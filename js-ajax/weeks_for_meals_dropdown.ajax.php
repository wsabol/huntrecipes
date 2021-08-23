<?php
$App = "";
require_once('../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$wkStart = new DateTime("tomorrow");
while ( $wkStart->format("N") != $_SESSION['Login']['week_start_day_of_week'] ) {
  $wkStart->add(new DateInterval("P1D"));
}
$wkEnd = new DateTime($wkStart->format('Y/m/d'));
$wkEnd->add(new DateInterval("P6D"));

$num_weeks = 3;
$wk = 0;
$assoc['in_meal_plan_flag'] = 0;

do {
  $assoc['in_meal_plan_flag'] = 0;
  $qWeek = "
    SELECT 
      CASE
        WHEN EXISTS(
          SELECT * 
          FROM LoginMealPlanning lmp 
          WHERE lmp.recipe_id = ".(@$App->R['recipe_id']*1)."
          AND lmp.login_id = ".$_SESSION['Login']['id']."
          AND lmp.week_of = '".$wkStart->format("Y-m-d")."'
        )
        THEN 1 ELSE 0 END in_meal_plan_flag
  ";
  //wl($qWeek);
  $rs = $App->oDBMY->query( $qWeek );
  $assoc = $rs->fetch_assoc();
  $rs->free();
  ?>
  <li>
    <a data-week-of="<?=$wkStart->format("Y-m-d")?>" class="btnSaveToMealsSpecific" href="#">
      <?=$wkStart->format("M j")?> - <?=$wkEnd->format("M j")?> <?=( @$assoc['in_meal_plan_flag'] == 1 ? '<i class="fa fa-check pull-right"></i>' : "" )?>
    </a>
  </li>
  <?php
  $wkStart->add(new DateInterval("P7D"));
  $wkEnd->add(new DateInterval("P7D"));
  $wk++;
} while ( $wk < $num_weeks );

$App = "";
?>