<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$sel_query = "
	Call spSelectChefProfile(".$App->R['chef_id'].");
";
$result = $App->oDBMY->query($sel_query);
$Chef = $result->fetch_assoc();
$result->free();


// build site
$pageTitle = "Chef ".$Chef['name'];
$bodyClass = "";
require_once('_head.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	
	<div class="row" >
		<section class="content recipe full-width">

			<!--cwrap-->
			<div class="cwrap">
				<header class="s-title">
					<h1>Chef <?=$Chef['name']?></h1>
				</header>
				<!--row-->
				<section class="row">
					<!--profile left part-->
					<div class="my_account one-third">
						<figure>
							<img id="profile_picture" src="<?=$Chef['profile_picture']?>" alt="<?=$Chef['name']?>" width="100%" />
						</figure>
						<div class="container">
							<p>Recipes submitted: <?=$Chef['recipe_count']?></p>
							<a class="button btn-block" href="browse.php?chef_id=<?=$App->R['chef_id']?>" >
								<?=( $Chef['male_flag'] == 1 ? "His" : "Her" )?> Recipes
							</a>
						</div>
					</div>
					<!--//profile left part-->

					<!--two-third-->
					<article class="two-third tab-content">

						<table class="ctable" >
							<tr>
								<th>Words of Wisdom</th>
								<td style="font-size: 18px;"><i class="fa fa-quote-left"></i> <?=$Chef['wisdom']?></td>
							</tr>
						</table>

						<dl class="basic">
      				<dt>Favorite cusine</dt>
							<dd><?=$Chef['favorite_cuisine']?></dd>
							<dt>Favorite spices</dt>
							<dd><?=$Chef['favorite_spices']?></dd>
							<dt>Recipes submitted</dt>
							<dd><?=$Chef['recipe_count']?></dd>
						</dl>
						
						<? if ( trim($Chef['story']) != "" ) { ?>
							<div class="container box">
								<p class="lead">
									My Story
								</p>
								<p>
									<?=trim($Chef['story'])?>
								</p>
							</div>
						<? } ?>
				
					</article>
					<!--//one-third-->
				</section>
			</div>

			<?php
			if ( $Chef['login_id']*1 > 0 ) {
				?>
				<div class="cwrap">

					<header class="s-title">
						<h2 class="ribbon bright"><?=( $Chef['male_flag'] == 1 ? "His" : "Her" )?> Favorites</h2>
					</header>
					<?php
					if ( $Chef['favorite_count']*1 === 0 ) {
						?>
						<div class="alert alert-banner">
							No favorites yet.
						</div>
						<?php
					} else {
						?>
						<section class="row">
							<div class="entries full-width">
								<?php
								$sel_query = "
									Call spSelectLoginFavorites('".$_SESSION['Login']['id']."');
								";
								$ChefFavorites = array();
								$r = $App->oDBMY->query( $sel_query );
								while ( $row = $r->fetch_assoc() ) {
									array_push($ChefFavorites, $row);
								}

								for ( $i = 0; $i < count($ChefFavorites); $i++ ) {
									?>
									<!--item-->
									<div class="entry one-fourth">
										<figure>
											<img src="<?=$ChefFavorites[$i]['image_filename']?>" alt="" />
											<figcaption><a href="recipe.php?recipe_id=<?=$ChefFavorites[$i]['id']?>"><i class="icon icon-themeenergy_eye2"></i> <span>View recipe</span></a></figcaption>
										</figure>
										<div class="container">
											<h2><a href="recipe.php?recipe_id=<?=$ChefFavorites[$i]['id']?>"><?=htmlspecialchars($ChefFavorites[$i]['title'])?></a></h2> 
											<div class="actions">
												<div data-recipe-id="<?=$ChefFavorites[$i]['id']?>" >
													<div class="meal-plan">
														<span class="btnSaveToMealsGroup btn-group">
															<span class="btn btnSaveToMealsMain btn-sm btn-<?=( $ChefFavorites[$i]['in_meal_plan_flag'] == 1 ? "info" : "theme" )?>">
																<i class="fa fa-<?=( $ChefFavorites[$i]['in_meal_plan_flag'] == 1 ? "check" : "plus" )?>"></i> my meals
															</span>
															<span class="btn btn-sm btn-<?=( $ChefFavorites[$i]['in_meal_plan_flag'] == 1 ? "info" : "theme" )?> dropdown-toggle btnSaveToMealsWeeksMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
																<span class="caret"></span>
																<span class="sr-only">Toggle Dropdown</span>
															</span>
															<ul id="r<?=$ChefFavorites[$i]['id']?>WeekMenu" class="dropdown-menu">
															</ul>
														</span>
													</div>
													<div class="likes divSaveToFavorites <?=( $ChefFavorites[$i]['favorite_flag'] == 1 ? "favorite-recipe" : "" )?>"><i class="fa fa-heart"></i><span class="favorite-count"><?=$ChefFavorites[$i]['favorite_count']?></span></div>
												</div>
											</div>
										</div>
									</div>
									<!--item-->
									<?php
								}
								?>
							</div>
						</section>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
		</section>
		<!--//content-->
	</div>
</div>
<!--//wrap-->
<?php
require_once('_footer.php');
$App = "";
?>


