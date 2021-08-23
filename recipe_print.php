<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

if ( @$App->R['recipe_id']*1 < 1 ) {
	header('Location: /error404.php');
	exit;
}

$sel_query = "
	Call spSelectRecipe(".$App->R['recipe_id'].", ".(@$_SESSION['Login']['id']*1).");
";
$result = $App->oDBMY->query($sel_query);
$Recipe = $result->fetch_assoc();
$result->free();
if ( !is_array($Recipe) ) {
	header('Location: /error404.php');
	exit;
}

$sel_query = "
	Call spSelectRecipeIngredients(".$App->R['recipe_id'].");
";
$result = $App->oDBMY->query($sel_query);
$RecipeIngredients = array();
while ( $row = $result->fetch_assoc() ) {
	array_push($RecipeIngredients, $row);
}
$result->free();

/* CHILDREN */
$RecipeChildren = array();
if ( $Recipe['child_count'] > 0 ) {
	$par_query = "
		SELECT id FROM Recipe WHERE parent_recipe_id = ".$App->R['recipe_id'].";
	";
	$pResult = $App->oDBMY->query($par_query);
	while ( $pRow = $pResult->fetch_assoc() ) {
		$chl_query = "
			Call spSelectRecipe(".$pRow['id'].", ".(@$_SESSION['Login']['id']*1).");
		";
		$cResult = $App->oDBMY->query($chl_query);
		if ( !!$cResult ) {
			$cRow = $cResult->fetch_assoc();
			$cResult->free();
			
			$cRow['ingredients'] = array();
			$ching_query = "
				Call spSelectRecipeIngredients(".$pRow['id'].");
			";
			$ciResult = $App->oDBMY->query($ching_query);
			if ( !!$ciResult ) {
				while ( $ciRow = $ciResult->fetch_array() ) {
					array_push($cRow['ingredients'], $ciRow);
				}
				array_push($RecipeChildren, $cRow);
			}
		}
	}
	$pResult->free();
	$Recipe['child_count'] = count($RecipeChildren);
}


// build site
$bodyClass = "recipePage";
require_once('_head_print.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	<? //wla($Recipe); ?>
	<? //wla($RecipeIngredients); ?>
	<!--row-->
	<div class="row">
		<!--content-->
		<section class="content recipe full-width">
			<?php
			$main_column_count = 3 - $Recipe['child_count'];
			if ( $main_column_count == 3 ) {
				if ( $Recipe['ingredient_count'] < 17 ) {
					$main_column_count = 2;
				}
			} elseif ( $main_column_count == 2 ) {
				if ( $Recipe['ingredient_count'] < 11 ) {
					$main_column_count = 1;
				}
			}
			
			$column_max = ceil($Recipe['ingredient_count'] / $main_column_count);
			if ( $main_column_count == 3 ) {
				$column_max = ( $column_max < 8 ? 8 : $column_max );
			} elseif ( $main_column_count == 2 ) {
				$column_max = ( $column_max < 10 ? 10 : $column_max );
			}
			
			if ( $Recipe['ingredient_count'] % $column_max == 1 ) {
				$column_max--;
			}
			
			$column_fraction = ( $Recipe['child_count'] > 1 || $main_column_count == 3 ? "third" : "half" );
			?>
			<div class="row">
				<article class="one-<?=$column_fraction?>">
					<h1><u><?=$Recipe['title']?></u></h1>
					
					<dl class="dl-horizontal" >
						<?php
						$multiplier = 1;
						if ( $Recipe['org_serving_count'] > 0 ) {
							$multiplier = $Recipe['serving_count'] / $Recipe['org_serving_count'];
						}
						
						for ( $i = 0; $i < count($RecipeIngredients); $i++ ) {
							if ( $i > 0 && $i % $column_max === 0 ) {
								?>
								</dl>
							</article>
							<article class="one-<?=$column_fraction?>">
								<dl class="dl-horizontal" >
								<?php
							}
							
							$val_formatted = friendlyAmount(
								$RecipeIngredients[$i]['general_measure_amount'] * $multiplier,
								$RecipeIngredients[$i]['measure_type_id'],
								$value_decimal
							);
							
							$iName = "";
							if ( $value_decimal > 1 && $RecipeIngredients[$i]['measure_type_id'] != 2 || $value_decimal == 0 AND $RecipeIngredients[$i]['measure_type_id'] == 0 ) {
								$iName = $RecipeIngredients[$i]['raw_ingredient_name_plural'];
							} else {
								$iName = $RecipeIngredients[$i]['raw_ingredient_name'];
							}
							if ( $RecipeIngredients[$i]['ingredient_prep'] != "" ) {
								$iName .= "; ".$RecipeIngredients[$i]['ingredient_prep'];
							}
							
							?>
							<dt><?=( $value_decimal == 0 ? '&nbsp;' : $val_formatted )?></dt>
							<dd><?=$iName.( $RecipeIngredients[$i]['optional_flag'] == 1 ? " (optional)" : "" )?></dd><?php
						}
						?>
					</dl>
				</article>
				
				<!--child ingredient-columns-->
				<?php
				for ( $c = 0; $c < count($RecipeChildren); $c++ ) {
					?>
					<article class="one-<?=$column_fraction?>">
						<h1>
							<u style="font-size: 24px;"><?=$RecipeChildren[$c]['title']?></u>
						</h1>
						<dl class="dl-horizontal">
							<?php
							$cIngredients = $RecipeChildren[$c]['ingredients'];
							for ( $i = 0; $i < count($cIngredients); $i++ ) {
								$val_formatted = friendlyAmount(
									$cIngredients[$i]['general_measure_amount'] * $multiplier,
									$cIngredients[$i]['measure_type_id'],
									$value_decimal
								);

								$iName = "";
								if ( $value_decimal > 1 && $cIngredients[$i]['measure_type_id'] != 2 || $value_decimal == 0 AND $cIngredients[$i]['measure_type_id'] == 0 ) {
									$iName = $cIngredients[$i]['raw_ingredient_name_plural'];
								} else {
									$iName = $cIngredients[$i]['raw_ingredient_name'];
								}
								if ( $cIngredients[$i]['ingredient_prep'] != "" ) {
									$iName .= "; ".$cIngredients[$i]['ingredient_prep'];
								}

								?>
								<dt><?=( $value_decimal == 0 ? '&nbsp;' : $val_formatted )?></dt>
								<dd><?=$iName.( $cIngredients[$i]['optional_flag'] == 1 ? " (optional)" : "" )?></dd><?php
							}
							?>
						</dl>
					</article>
					<?php
				}
				?>
				<!--//child ingredient-columns-->
				
			</div>
			
			<div class="row">
				<div class="full-width">
					<p>
						<?=str_replace(chr(10), "<br>", $Recipe['instructions'])?></li>
					</p>
					<!--child instructions-->
					<?php
					for ( $c = 0; $c < count($RecipeChildren); $c++ ) {
						?>
						<p class="child-instructions-title">
							-- <?=$RecipeChildren[$c]['title']?> --
						</p>
						<p>
							<?=str_replace(chr(10), "<br>", $RecipeChildren[$c]['instructions'])?></li>
						</p>
						<?php
					}
					?>
				</div>
			</div>
			
			<div class="row">
				<div class="full-width">
					<?php
					if ( $Recipe['chef_id']*1 > 1 ) {
						?>
						<p class="no-padding">Chef // <?=$Recipe['chef']?></p>
						<?php
					}
					?>
					<p class="no-padding">Category // <?=$Recipe['type']?></p>
					<?php
					if ( $Recipe['course_id']*1 > 0 ) {
						?>
						<p class="no-padding">Course // <?=$Recipe['course']?></p>
						<?php
					}
					if ( $Recipe['serving_count']*1 > 0 ) {
						$fServingCount = new Fraction($Recipe['serving_count']);
						?>
						<p class="no-padding">Makes // <?=$fServingCount->toString()?> <?=$Recipe['serving_measure']?></p>
						<?php
						$fServingCount = "";
					}
					?>
				</div>
			</div>
			<!--//recipe-->
				
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

