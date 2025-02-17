<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

/** built site **/
$bodyClass = ( @$_SESSION['Login']['id']*1 == 0 ? "home" : "" );
require_once('_head.php');

/* recipe of the day */
$rotd_query = "
	Call spSelectRecipe(
		(SELECT recipe_id FROM RecipeOfTheDay ORDER BY day DESC LIMIT 1),
		".(@$_SESSION['Login']['id']*1)."
	);
";
$RecipeOTD = array();
$result = $App->oDBMY->query($rotd_query);
if ( !!$result ) {
	$RecipeOTD = $result->fetch_assoc();
	$result->free();
}
$RotdDesc = array($RecipeOTD['type']);
if ( $RecipeOTD['course_id']*1 > 0 ) {
	array_push( $RotdDesc, $RecipeOTD['course'] );
}
if ( $RecipeOTD['chef_id']*1 > 1 ) {
	array_push( $RotdDesc, '<a href="chef_profile.php?chef_id='.$RecipeOTD['chef_id'].'" >'.$RecipeOTD['chef_id'].'</a>' );
}

/* chef of the day */
$cotd_query = "
	Call spSelectChefProfile(
		(SELECT chef_id FROM ChefOfTheDay ORDER BY day DESC LIMIT 1)
	);
";
$ChefOTD = array();
$result = $App->oDBMY->query($cotd_query);
if ( !!$result ) {
	$ChefOTD = $result->fetch_assoc();
	$result->free();
}


if ( @$_SESSION['Login']['id']*1 == 0 ) {
	?>
	<!--intro-->
	<div class="intro">
		<figure class="bg"><img src="/assets/images/intro.jpg" alt="" /></figure>

		<!--wrap-->
		<div class="wrap clearfix">
			<!--row-->
			<div class="row">
				<article class="three-fourth text">
					<h1>Welcome to HuntRecipes!</h1>
					<p>The goal of this project is to bring the <strong>Summers with the Hunts</strong> family reunion cookbook to life in the digital age.
						The fantastic meals we shared along with a host of others are here online as a living library of Hunt recipes that can grow as our clan grows.
						You can expand on old recipes, share new ones, and bring your unique tastes and experiences to the table. 
					</p>
					<p>You don't have to make an account to access the recipes, however, members enjoy additional featurers like submitting recipes to be added, 
						saving favorites recipes, weekly meal planning.</p>
					<a href="register.php" class="button white more medium">Join our community <i class="fa fa-chevron-right"></i></a>
					<p>Already a member? Click <a href="login.php">here</a> to login.</p>
				</article>

				<!--search recipes widget-->
				<div class="one-fourth">
					<!--<div class="widget container">
						<div class="textwrap">
							<h3>Search for recipes</h3>
							<p>All you need to do is enter an ingredient, a dish or a keyword. </p>
							<p>You can also select a specific category from the dropdown.</p>
							<p>There’s sure to be something tempting for you to try.</p> 
							<p>Enjoy!</p>
						</div>
						
					</div>-->
				</div>
				<!--//search recipes widget-->
			</div>
			<!--//row-->
		</div>
		<!--//wrap-->
	</div>
	<!--//intro-->
	<?php
    exit;
}
?>
<!--wrap-->
<div class="wrap clearfix">
	<!--row-->
	<div class="row">

		<!--content-->
		<section class="content three-fourth">
			<!--cwrap-->
			<div class="cwrap">
				<!--entries-->
				<div class="entries row">
					<!--featured recipe-->
					<div class="featured two-third">
						<header class="s-title">
							<h2 class="ribbon">Recipe of the Day</h2>
						</header>
						<article class="entry">
							<figure>
								<img src="<?=$RecipeOTD['image_filename']?>" alt="<?=$RecipeOTD['title']?>" />
								<figcaption><a href="recipe.php?recipe_id=<?=$RecipeOTD['recipe_id']?>"><i class="icon icon-themeenergy_eye2"></i> <span>View recipe</span></a></figcaption>
							</figure>
							<div class="container">
								<h2><a href="recipe.php?recipe_id=<?=$RecipeOTD['recipe_id']?>"><?=$RecipeOTD['title']?></a></h2>
								<p><?=implode(" | ", $RotdDesc)?></p>
								<div class="actions">
									<div>
										<a href="recipe.php?recipe_id=<?=$RecipeOTD['recipe_id']?>" class="button">See the full recipe</a>
										<div class="more"><a href="featured_history.php">See past recipes of the day</a></div>
									</div>
								</div>
							</div>
						</article>
					</div>
					<!--//featured recipe-->
					
					<!--featured member-->
					<div class="featured one-third">
						<header class="s-title">
							<h2 class="ribbon star">Featured Chef</h2>
						</header>
						<article class="entry">
							<figure>
								<img src="<?=$ChefOTD['profile_picture']?>" alt="" />
								<figcaption><a href="chef_profile.php?chef_id=<?=$ChefOTD['chef_id']?>"><i class="icon icon-themeenergy_eye2"></i> <span>View Chef</span></a></figcaption>
							</figure>
							<div class="container">
								<h2><a href="chef_profile.php?chef_id=<?=$ChefOTD['chef_id']?>"><?=$ChefOTD['name']?></a></h2>
								<blockquote><i class="fa fa-quote-left"></i> <?=$ChefOTD['wisdom']?></blockquote>
								<div class="actions">
									<div>
										<a href="browse.php?chef_id=<?=$ChefOTD['chef_id']?>" class="button">Check out <?=( $ChefOTD['male_flag'] == 1 ? "his" : "her" )?> recipes</a>
										<div class="more"><a href="featured_history.php">See past featured members</a></div>
									</div>
								</div>
							</div>
						</article>
					</div>
					<!--//featured member-->
				</div>
				<!--//entries-->
			</div>
			<!--//cwrap-->

			<!--cwrap-->
			<div class="cwrap">
				<header class="s-title">
					<h2 class="ribbon bright">Top Recipes</h2>
				</header>
				<?php
				$selTop = "
					CALL spSelectTopRecipes(6, ".(@$_SESSION['Login']['id']*1).");
				";
				$TopRecipes = array();
				$result = $App->oDBMY->query($selTop);
				while ( $row = $result->fetch_assoc() ) {
					array_push( $TopRecipes, $row );
				}
				@$result->free();
				?>
				<div class="row">
					<!--entries-->
					<div class="entries full-width _column-entries _column-entries-three">

						<? for ( $i = 0; $i < count($TopRecipes); $i++ ) { ?>
							<!--item-->
							<div class="entry one-third _column-entry">
								<figure>
									<img src="<?=$TopRecipes[$i]['image_filename']?>" alt="" />
									<figcaption><a href="recipe.php?recipe_id=<?=$TopRecipes[$i]['id']?>"><i class="icon icon-themeenergy_eye2"></i> <span>View recipe</span></a></figcaption>
								</figure>
								<div class="container">
									<h2><a href="recipe.php?recipe_id=<?=$TopRecipes[$i]['id']?>"><?=$TopRecipes[$i]['title']?></a></h2> 
									<div class="actions">
										<div data-recipe-id="<?=$TopRecipes[$i]['id']?>" >
											<div class="meal-plan">
												<? if ( @$_SESSION['Login']['id']*1 > 0 ) { ?>
													<span class="btnSaveToMealsGroup btn-group">
														<span class="btn btnSaveToMealsMain btn-sm btn-<?=( $TopRecipes[$i]['in_meal_plan_flag'] == 1 ? "info" : "theme" )?>">
															<i class="fa fa-<?=( $TopRecipes[$i]['in_meal_plan_flag'] == 1 ? "check" : "plus" )?>"></i> my meals
														</span>
														<span class="btn btn-sm btn-<?=( $TopRecipes[$i]['in_meal_plan_flag'] == 1 ? "info" : "theme" )?> dropdown-toggle btnSaveToMealsWeeksMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
															<span class="caret"></span>
															<span class="sr-only">Toggle Dropdown</span>
														</span>
														<ul id="r<?=$TopRecipes[$i]['id']?>WeekMenu" class="dropdown-menu">
														</ul>
													</span>
												<? } ?>
											</div>
											<div class="likes divSaveToFavorites <?=( $TopRecipes[$i]['favorite_flag'] == 1 ? "favorite-recipe" : "" )?>"><i class="fa fa-heart"></i><span class="favorite-count"><?=$TopRecipes[$i]['favorite_count']?></span></div>
										</div>
									</div>
								</div>
							</div>
							<!--item-->
						<? } ?>	
					</div>
					<!--//entries-->
				</div>
				<div class="quicklinks">
					<a href="javascript:void(0)" class="button scroll-to-top">Back to top</a>
				</div>
			</div>
			<!--//cwrap-->
			
		</section>
		<!--//content-->


		<!--right sidebar-->
		<aside class="sidebar one-fourth">
			<div class="widget">
				<h3>Recipe Categories</h3>
				<ul class="boxed">
					<?php
					$qRecipeCat = "
						Call spSelectTopRecipeCategories();
					";
					$result = $App->oDBMY->query( $qRecipeCat );
					$cat_counter = 0;
					$class = "";
					while ( $rCat = $result->fetch_assoc() ) {
						if ( $cat_counter % 4 == 0 ) {
							$class = "light";
						} elseif ( $cat_counter % 4 == 2 ) {
							$class = "dark";
						} else {
							$class = "medium";
						}
						?>
						<li class="<?=$class?>"><a href="browse.php?<?=$rCat['ctype']?>_id=<?=$rCat['entity_id']?>" title="<?=$rCat['name']?>"><i class="icon <?=$rCat['icon']?>"></i> <span><?=$rCat['name']?></span></a></li>
						<?php
						$cat_counter++;
					}
					?>
				</ul>
			</div>

			<div class="widget">
				<!--<h3>Advertisment</h3>
				<a href="#"><img src="/assets/images/advertisment.jpg" alt="" /></a>-->
				<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<!-- HuntRecipesHome -->
				<ins class="adsbygoogle"
						 style="display:inline-block;width:270px;height:350px"
						 data-ad-client="ca-pub-1405981854222899"
						 data-ad-slot="3702414569"></ins>
				<script>
				(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
			</div>
		</aside>
	</div>
	<!--//right sidebar-->
</div>
<!--//wrap-->
<script>
	
	$(function(){
		
		$('.btnSaveToMealsWeeksMenu').click(function(e){
			var recipe_id = $(this).parent().parent().parent().data('recipe-id');
			//console.log(recipe_id);
			LoadDivContent('weeks_for_meals_dropdown', '', 'r'+recipe_id+'WeekMenu', { recipe_id: recipe_id });
		});
		
	});
	
</script>
<?php
$App = "";
require_once('_footer.php');
?>


