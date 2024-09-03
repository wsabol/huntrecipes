<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$sel_query = "
	Call spSelectRecipe(".$App->R['recipe_id'].", ".(@$_SESSION['Login']['id']*1).");
";
//wl($sel_query);
$result = $App->oDBMY->query($sel_query);
$Recipe = $result->fetch_assoc();
$result->free();

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
				$Recipe['ingredient_count'] += $cRow['ingredient_count'];
			}
		}
	}
	$pResult->free();
	$Recipe['child_count'] = count($RecipeChildren);
}


// build site
$pageTitle = $Recipe['title'];
$siteImage = "http://huntrecipes.willsabol.com/".$Recipe['image_filename'];
$bodyClass = "recipePage";
require_once('_head.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	<? //wla($Recipe); ?>
	<? //wla($RecipeIngredients); ?>
	<!--row-->
	<div class="row">
		<header class="s-title row">
			<div class="two-third">
				
				<h1><?=$Recipe['title']?></h1>
				<!--breadcrumbs-->
				<nav class="breadcrumbs">
					<ul>
						<li><a href="#">Recipes</a></li>
						<li><a href="browse.php?type_id=<?=$Recipe['type_id']?>"><?=$Recipe['type']?></a></li>
						<?php if ( $Recipe['course_id']*1 > 0 ) { ?>
							<li><a href="browse.php?course_id=<?=$Recipe['course_id']?>"><?=$Recipe['course']?></a></li>
						<?php } ?>
						<?php if ( $Recipe['cuisine_id']*1 > 0 ) { ?>
							<li><a href="browse.php?cuisine_id=<?=$Recipe['cuisine_id']?>"><?=$Recipe['cuisine']?></a></li>
						<?php } ?>
					</ul>
				</nav>
				<!--//breadcrumbs-->
				
			</div>
			
			<div class="one-third <?=( str_ends($Recipe['image_filename'], "generic_recipe.jpg") ? "hidden" : "" )?>">
				<div class="image">
					<img src="<?=$Recipe['image_filename']?>" width="585" alt="<?=$Recipe['title']?>" />
				</div>
			</div>
			
		</header>
		<!--content-->
		<section class="content recipe full-width">
			<?php
			$eff_ingredient_count = $Recipe['ingredient_count'] + 2 * $Recipe['child_count'];
			//wl($eff_ingredient_count);
			$main_column_count = 3 - $Recipe['child_count'];
			if ( $main_column_count == 3 ) {
				if ( $eff_ingredient_count < 17 ) {
					$main_column_count = 2;
				}
			} elseif ( $main_column_count == 2 ) {
				if ( $eff_ingredient_count < 11 ) {
					$main_column_count = 1;
				}
			}
			
			$column_max = ceil($eff_ingredient_count / $main_column_count);
			if ( $main_column_count == 3 ) {
				$column_max = ( $column_max < 8 ? 8 : $column_max );
			} elseif ( $main_column_count == 2 ) {
				$column_max = ( $column_max < 10 ? 10 : $column_max );
			}
			
			if ( $eff_ingredient_count % $column_max == 1 ) {
				$column_max--;
			}
			
			$column_fraction = ( $Recipe['child_count'] > 1 || $main_column_count == 3 ? "third" : "half" );
			?>
			<div class="row">
				<!--ingredient-columns-->
				<article class="one-<?=$column_fraction?>">
					<dl class="ingredients">
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
								<dl class="ingredients">
								<?php
							}
							/*$fAmount = new Fraction($RecipeIngredients[$i]['amount']);
							$val_formatted = $fAmount->toString();*/
							
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
							<dt class="ingrAmount <?=( $value_decimal == 0 ? 'no-amount' : '' )?>" data-org-amount="<?=$RecipeIngredients[$i]['general_measure_amount']?>" data-measure-type-id="<?=$RecipeIngredients[$i]['measure_type_id']?>" >
								<?=( $value_decimal == 0 ? '&nbsp;' : $val_formatted )?>
							</dt>
							<dd class="ingrName" data-name="<?=$RecipeIngredients[$i]['raw_ingredient_name']?>" data-name-plural="<?=$RecipeIngredients[$i]['raw_ingredient_name_plural']?>" >
								<?= $iName.( $RecipeIngredients[$i]['optional_flag'] == 1 ? ' <span style="font-style: italic">optional</span>' : "" )?>
							</dd>
							<?php
						}
						//$fAmount = "";
						?>
					</dl>
				</article>
				<!--//ingredient-columns-->
				
				<!--child ingredient-columns-->
				<?php
				for ( $c = 0; $c < count($RecipeChildren); $c++ ) {
					?>
					<article class="one-<?=$column_fraction?>">
						<p class="child-recipe-title">
							<?=$RecipeChildren[$c]['title']?>
						</p>
						<dl class="ingredients">
							<?php
							$cIngredients = $RecipeChildren[$c]['ingredients'];
							for ( $i = 0; $i < count($cIngredients); $i++ ) {
								/*$fAmount = new Fraction($cIngredients[$i]['amount']);
								$val_formatted = $fAmount->toString();
								?><dt <?=( $val_formatted == '0' ? 'class="no-amount"' : '' )?> ><?=( $val_formatted == '0' ? '&nbsp;' : $val_formatted )?> <?=$cIngredients[$i]['measure_abbr']?></dt><?php
								?><dd><?=$cIngredients[$i]['ingredient'].( $cIngredients[$i]['optional_flag'] == 1 ? " (optional)" : "" )?></dd><?php*/
									
								$val_formatted = friendlyAmount(
									$cIngredients[$i]['general_measure_amount'],
									$cIngredients[$i]['measure_type_id'],
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
								<dt class="ingrAmount <?=( $value_decimal == 0 ? 'no-amount' : '' )?>" data-org-amount="<?=$cIngredients[$i]['general_measure_amount']?>" data-measure-type-id="<?=$cIngredients[$i]['measure_type_id']?>" >
									<?=$val_formatted?>
								</dt>
								<dd class="ingrName" data-name="<?=$cIngredients[$i]['raw_ingredient_name']?>" data-name-plural="<?=$cIngredients[$i]['raw_ingredient_name_plural']?>" >
									<?=$iName.( $cIngredients[$i]['optional_flag'] == 1 ? ' <span style="font-style: italic">optional</span>' : "" )?>
								</dd>
								<?php
							}
							//$fAmount = "";
							?>
						</dl>
					</article>
					<?php
				}
				?>
				<!--//child ingredient-columns-->
				
			</div>
			
			<!-- action bar -->
			<div class="row">
				<div class="share full-width">
					<ul class="boxed five">
						<li class="facebook-background">
							<a id="btnShareOnFacebook" href="#" title="Facebook">
								<i class="fa fa-facebook"></i>
								<span>Share on Facebook</span>
							</a>
						</li>
						<li class="pinterest-background">
							<a id="bPinOnPinterest" href="https://www.pinterest.com/pin/create/button/" title="Pinterest"
								 data-pin-do="buttonPin"
								 data-pin-custom="true"
								 data-pin-media="http://huntrecipes.willsabol.com/<?=$Recipe['image_filename']?>"
								 >
								<i class="fa fa-pinterest"></i>
								<span>Pin to Board</span>
							</a>
						</li>
						<li class="liSaveToFavorites <?=( $Recipe['favorite_flag'] == 1 ? "favorite-recipe" : "" )?> medium-brown" >
							<a id="btnSaveToFavorites<?=( @$_SESSION['Login']['id']*1 == 0 ? "NoLogin" : "" )?>" href="<?=( @$_SESSION['Login']['id']*1 == 0 ? "/login.php?ref=".urlencode("/recipe.php?recipe_id=".$App->R['recipe_id']) : "#" )?>" title="Favorites">
								<i class="fa fa-heart"></i>
								<span><?=( $Recipe['favorite_flag'] == 1 ? "Favorited" : "Save to Favorites" )?></span>
							</a>
						</li>
						<li class="<?=( $Recipe['in_meal_plan_flag'] == 1 ? "btn-info" : "light" )?>">
							<a id="btnAddToMealPlan<?=( @$_SESSION['Login']['id']*1 == 0 ? "NoLogin" : "" )?>" href="<?=( @$_SESSION['Login']['id']*1 == 0 ? "/login.php?ref=".urlencode("/recipe.php?recipe_id=".$App->R['recipe_id']) : "#" )?>" title="Meal Planning">
								<i class="fa fa-calendar"></i>
								<span><?=( $Recipe['in_meal_plan_flag'] == 1 ? "In Meal Plan" : "Add to Meal Plan" )?></span>
							</a>
						</li>
						<li class="medium">
							<a href="/recipe_print.php?recipe_id=<?=$App->R['recipe_id']?>" target="_blank" title="Printer Friendly">
								<i class="fa fa-print"></i>
								<span>View Print Friendly</span>
							</a>
						</li>
					</ul>
				</div>
			</div>
			<!-- ./action bar -->
				
			<div class="row">
				<div class="instructions three-fourth">
					<ol>
						<li><?=str_replace(chr(10), "</li> <li>", $Recipe['instructions'])?></li>
					</ol>

					<!--child instructions-->
					<?php
					for ( $c = 0; $c < count($RecipeChildren); $c++ ) {
						?>
						<p class="child-instructions-title">
							For <?=$RecipeChildren[$c]['title']?>
						</p>
						<ol>
							<li><?=str_replace(chr(10), "</li> <li>", $RecipeChildren[$c]['instructions'])?></li>
						</ol>
						<?php
					}
					?>

				</div>

				<aside class="sidebar one-fourth">
					
					<?php
					if ( $_SESSION['Login']['account_type_id'] == 4 || $Recipe['published_flag'] == 0 && $Recipe['chef_id'] == @$_SESSION['Login']['chef_id'] ) { // developers
						?>

						<div class="widget" style="
							background: transparent;
							-webkit-box-shadow: none;
							-moz-box-shadow: none;
							box-shadow: none;
						" >
							<a class="button btn-block <?=( $Recipe['published_flag'] == 0 && $Recipe['chef_id'] == @$_SESSION['Login']['chef_id'] ? "" : "hidden" )?>"
								 style="background-color: #337ab7;"
								 href="/submit_recipe.php?recipe_id=<?=$App->R['recipe_id']?>" >Edit Recipe</a>

							<?php
							if ( $_SESSION['Login']['account_type_id'] == 4 ) { // developers
								?>
								<a class="button btn-block" href="/API/v0/recipe_process/recipe_edit.php?recipe_id=<?=$App->R['recipe_id']?>" >Dev Edit Recipe</a>
								<?php
							}
							?>
						</div>
						<?php
					}
					?>
						
					<div>
						<?php
						if ( $Recipe['chef_id']*1 > 1 ) {
							?>
							<dl class="user">
								<a class="no-style" href="chef_profile.php?chef_id=<?=$Recipe['chef_id']?>" >
									<dt>Chef</dt>
									<dd><?=$Recipe['chef']?></dd>
								</a>
							</dl>
							<?php
						}
						?>
						<dl class="basic">
							<dt>Category</dt>
							<dd><?=$Recipe['type']?></dd>
							<?php
							if ( $Recipe['course_id']*1 > 0 ) {
								?>
								<dt>Course</dt>
								<dd><?=$Recipe['course']?></dd>
								<?php
							}

							if ( $Recipe['serving_count']*1 > 0 ) {
								$fServingCount = new Fraction($Recipe['serving_count']);
								?>
								<dt>Makes <i class="fa fa-edit pull-right" style="margin-top: 10px"></i></dt>
								<dd>
									<input type="text" style="max-width: 40%" id="input-serving" data-org-serving="<?=$Recipe['serving_count']?>" value="<?=$fServingCount->toString()?>" >
									<?=$Recipe['serving_measure']?>
								</dd>
								<?php
								$fServingCount = "";
							}
							?>
						</dl>
					</div>
					
					<?php
					$selFavs = "
						SELECT
							lrf.login_id,
							l.name,
							l.profile_picture
						FROM LoginRecipeFavorite lrf
						JOIN Login l
						ON l.id = lrf.login_id
						WHERE lrf.recipe_id = ".$App->R['recipe_id']."
						ORDER BY RAND()
						LIMIT 9;
					";
					$result = $App->oDBMY->query($selFavs);
					$Favs = array();
					while ( $row = $result->fetch_assoc() ) {
						array_push($Favs, $row);
					}
					@$result->free();
					?>
					<div class="widget <?=( count($Favs) === 0 ? "hidden" : "" )?>">
						<h3>Members who liked this recipe</h3>
						<ul class="boxed">
							<?php
							for ( $i = 0; $i < 9; $i++ ) {
								$row = "";
								if ( $i < count($Favs) ) {
									$row = $Favs[$i];
									?>
									<li><div class="avatar"><a href="#"><img src="<?=$row['profile_picture']?>" alt="" /><span><?=$row['name']?></span></a></div></li>
									<?php
								} else {
									?>
									<li><div class="avatar"><a href="#"><img src="" alt="" /><span></span></a></div></li>
									<?php
								}
							}
							?>
						</ul>
					</div>
					
					<div class="widget">
						<!--<h3>Advertisment</h3>
						<a href="#"><img src="assets/images/advertisment.jpg" alt="" /></a>-->
						<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
						<!-- HuntRecipesRecipe -->
						<ins class="adsbygoogle"
								 style="display:inline-block;width:270px;height:350px"
								 data-ad-client="ca-pub-1405981854222899"
								 data-ad-slot="7713811766"></ins>
						<script>
						(adsbygoogle = window.adsbygoogle || []).push({});
						</script>
					</div>
					
				</aside>
			</div>
				
		</section>
		<!--//content-->
		
	</div>
	<!--//row-->
</div>
<!--//wrap-->
<script>
	
	window.fbAsyncInit = function() {
		FB.init({
			appId   : '1722798604684723',
			oauth   : true,
			status  : true, // check login status
			cookie  : true, // enable cookies to allow the server to access the session
			xfbml   : true // parse XFBML
		});
  };
	
	(function() {
		var e = document.createElement('script');
		e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
		e.async = true;
		$('body').append(e);
	}());
	
	function updateIngredientAmounts() {
		var test = new Fraction( $('#input-serving').val() );
		if ( test.decimal === undefined ) {
			var reset = new Fraction( $('#input-serving').data('org-serving') );
			$('#input-serving').val( org.toString() );
		}
		
		var newServ = new Fraction( $('#input-serving').val() );
		var orgServ = new Fraction( $('#input-serving').data('org-serving') );
		
		var multiplier = newServ.decimal / orgServ.decimal;
		$.each( $('.ingredients .ingrAmount:not(.no-amount)'), function(i, el){
			var measure_type_id = $(el).data('measure-type-id');
			var val = new Fraction( $(el).data('org-amount') );
			
			var newVal = friendlyAmount(val.decimal * multiplier, measure_type_id);
			console.log(newVal);
			$(el).text( newVal.formatted );
			
			var $ingrName = $(el).next();
			var ingrs = $ingrName.text().split('; ');
			if ( (newVal.decimal > 1 && measure_type_id != 2) || (newVal.decimal == 0 && measure_type_id == 0) ) {
				ingrs[0] = $ingrName.data('name-plural');
			} else {
				ingrs[0] = $ingrName.data('name');
			}
			$ingrName.text( ingrs.join('; ') );
		});
	}
	
	$(function(){
		
		$('#btnShareOnFacebook').click(function(e){
			e.preventDefault();
			
			FB.ui({
				method: 'share',
				mobile_iframe: true,
				href: window.location.href
			}, function(response){});
		});
		
		$('#btnSaveToFavorites').click(function(e){
			e.preventDefault();
			var $btn = $(this);
			
			var favorite_flag = 1;
			if ( $(this).parent().hasClass('favorite-recipe') ) {
				// already saved remove from table
				favorite_flag = 0;
			}
			
			$(this).find('i').addClass('fa-spin fa-fw');
			$.ajax({
				url: '/ajax-json/spFavoriteRecipe.json.php',
				type: 'GET',
				data: {
					recipe_id: <?=$App->R['recipe_id']?>,
					favorite_flag: favorite_flag
				},
				success: function( response ) {
					//console.log(response);
					if ( response.success == 1 && favorite_flag == 1 ) {
						$btn.parent().addClass('favorite-recipe');
						$btn.find('span').text('Favorited');
					} else if ( response.success == 1 && favorite_flag == 0 ) {
						$btn.parent().removeClass('favorite-recipe');
						$btn.find('span').text('Save to favorites');
					} else {
						console.log(response.query);
					}
					
					$btn.find('i').removeClass('fa-spin fa-fw');
				}
			});
			
			return false;
		});
		
		$('#btnAddToMealPlan').click(function(e){
			e.preventDefault();
			var $btn = $(this);
			
			var serving_count = 0;
			if ( $('#input-serving').length > 0 ) {
				var sCnt = new Fraction( $('#input-serving').val() );
				serving_count = sCnt.decimal;
			}
			
			var refresh_flag = 0;
			if ( serving_count > 0 && $(this).parent().hasClass('btn-info') ) {
				refresh_flag = 1;
			}
			
			var remove = 0;
			if ( serving_count === 0 && $(this).parent().hasClass('btn-info') ) {
				// already saved remove from table
				remove = 1;
			}
			
			$(this).find('i').addClass('fa-spin fa-fw');
			$.ajax({
				url: '/ajax-json/meal_planning/spAddRecipeToMealPlan.json.php',
				type: 'GET',
				data: {
					recipe_id: <?=$App->R['recipe_id']?>,
					serving_count: serving_count,
					remove: remove
				},
				success: function( response ) {
					//console.log(response);
					
					if ( response.success == 1 && remove == 0 ) {
						$btn.parent().removeClass('light');
						$btn.parent().addClass('btn-info');
						$btn.find('span').text('In Meal Plan');
					} else if ( response.success == 1 && remove == 1 ) {
						$btn.parent().addClass('light');
						$btn.parent().removeClass('btn-info');
						$btn.find('span').text('Add to Meal Plan');
					} else {
						console.log(response.query);
					}
					
					$btn.find('i').removeClass('fa-spin fa-fw');
					if ( refresh_flag ) {
						window.location.reload();
					}
				}
			});
			
			return false;
		});
		
		$('#input-serving').blur(function(){
			updateIngredientAmounts( $(this) );
		});
		
		$('#input-serving').keyup(function(e){
			if ( e.which == 13 ) {
				updateIngredientAmounts( $(this) );
			}
		});
		
	});
</script>
<script
    type="text/javascript"
    async defer
    src="//assets.pinterest.com/js/pinit.js"
></script>
<?php
require_once('_footer.php');
$App = "";
?>

