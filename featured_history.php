<?
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 


$qRecipes = "
	SELECT
		rd.recipe_id,
		r.title,
		r.image_filename
	FROM RecipeOfTheDay rd
	JOIN Recipe r
	ON r.id = rd.recipe_id
	WHERE r.published_flag = 1
	ORDER BY rd.day DESC;
";
$Recipes = array();
$result = $App->oDBMY->query($qRecipes);
while ( $row = $result->fetch_assoc() ) {
	array_push( $Recipes, $row );
}
$result->free();

$qChef = "
	SELECT
		cd.chef_id,
		c.name,
		IFNULL(l.profile_picture, '/assets/images/users/generic_avatar.jpg') profile_picture
	FROM ChefOfTheDay cd
	JOIN Chef c
	ON c.id = cd.chef_id
	LEFT JOIN Login l
	ON l.id = c.login_id
	ORDER BY cd.day DESC;
";
$Chefs = array();
$result = $App->oDBMY->query($qChef);
while ( $row = $result->fetch_assoc() ) {
	array_push( $Chefs, $row );
}
$result->free();


/** built site **/
$bodyClass = "";
require_once('_head.php');
?>
<!--wrap-->
<div class="wrap clearfix">
	<!--row-->
	<div class="row">
		<header class="s-title">
			<h1>Featured History</h1>
		</header>
		
		<!--content-->
		<section class="content full-width">
			<!--row-->
			<div class="row">
				
				<div class="one-half">
					<div class="widget">
						<h3>Recipes of the Day</h3>
						<ul class="articles_latest">
							<? for ( $i = 0; $i < count($Recipes); $i++ ) { ?>
								<li>
									<a href="recipes.php?recipe_id=<?=$Recipes[$i]['recipe_id']?>">
										<img src="<?=$Recipes[$i]['image_filename']?>" alt="<?=$Recipes[$i]['title']?>" />
										<h6><?=$Recipes[$i]['title']?></h6>
									</a>
								</li>
							<? } ?>
						</ul>
					</div>
				</div>
				
				<div class="one-half">
					<div class="widget">
						<h3>Chefs of the Day</h3>
						<ul class="articles_latest">
							<? for ( $i = 0; $i < count($Chefs); $i++ ) { ?>
								<li>
									<a href="chef_profile.php?chef_id=<?=$Chefs[$i]['chef_id']?>">
										<img src="<?=$Chefs[$i]['profile_picture']?>" alt="<?=$Chefs[$i]['name']?>" />
										<h6><?=$Chefs[$i]['name']?></h6>
									</a>
								</li>
							<? } ?>
						</ul>
					</div>
				</div>
				
			</div>
			<!--//row-->
		</section>
		<!--//content-->
	</div>
	<!--//row-->
</div>
<!--//wrap-->
<?php
$App = "";
require_once('_footer.php');
?>


