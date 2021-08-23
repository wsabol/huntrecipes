<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

if ( @$App->R['week_of']*1 < 1 ) {
	header('Location: /error404.php');
	exit;
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

// build site
$bodyClass = "";
require_once('_head_print.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	
	<!--row-->
	<div class="row">
		<header>
			<h1>
				<u>Meal Plan - Week of <?=date("F j, Y", strtotime($App->R['week_of']))?></u>
			</h1>
		</header>
		
		<!--content-->
		<section class="content full-width">
			<ol>
				<? for ( $i = 0; $i < count($results); $i++ ) { ?>
					<li><?=$results[$i]['title']?></li>
				<? } ?>
			</ol>
			
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
			<table class="table">
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
							<td><?=( $value_decimal > 1 ? $Ingr[$i]['name_plural'] : $Ingr[$i]['name'] )?></td>
							<td><?=$value_formatted?></td>
						</tr>
					<? } ?>
				</tbody>
			</table>
		</section>
		<!--//content-->
	</div>
	<!--//row-->
</div>
<!--//wrap-->
<script>
</script>
<?php
require_once('_footer_print.php');
$App = "";
?>

