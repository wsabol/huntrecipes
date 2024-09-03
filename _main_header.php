<!--header-->
<header class="head" role="banner">
	<!--wrap-->
	<div class="wrap clearfix">
		<a href="/" title="HuntRecipes" class="logo"><img src="/assets/images/ico/huntr_logo_transparent_scale.png" alt="HuntRecipes" /></a>
		
		<nav class="main-nav" role="navigation" id="menu">
			<ul>
				<!--<li><a href="/" title="Home"><span>Home</span></a></li>
				<li><a href="#" title="Recipes"><span>Recipes</span></a>
					<ul class="sub-menu">
						<li><a href="/recipes2.php" title="Recipes 2">Recipes 2</a></li>
						<li><a href="/recipes.php" title="Recipe">Recipe</a></li>
					</ul>
				</li>-->
				<?php
				if ( @$_SESSION['Login']['id'] > 0 ) {
					?>
					<li class="light"><a href="/profile.php" title="My account"><i class="icon fa fa-user"></i> <span>My account</span></a>
						<ul class="sub-menu">
							<li><a href="/profile.php?tabid=favorites" title="Favorites">Favorites</a></li>
							<li><a href="/profile.php?tabid=chef-portal" title="Chef Portal">Chef Portal</a></li>
							<li><a href="/profile.php?tabid=settings" title="Settings">Settings</a></li>
						</ul>
					</li>
					
					<?php
					if ( @$_SESSION['Login']['chef_id']*1 > 0 ) { // developer
						?>
						<li class="medium"><a href="/submit_recipe.php" title="Submit a recipe"><i class="icon icon-themeenergy_fork-spoon"></i> <span>Submit a recipe</span></a></li>
						<?php
					}
					
					if ( @$_SESSION['Login']['account_type_id']*1 == 4 ) { // developer
						?>
						<li class="dark"><a onclick="if($('.slicknav_nav').hasClass('slicknav_hidden')) $('#headDevTools').toggleClass('hidden');" href="#" title="Developer Tools"><i class="icon fa fa-gears"></i> <span>Developer Tools</span></a>
							<ul class="sub-menu">
								<li><a href="/API/v0/recipe_process/recipe_process.php" target="_blank" title="Recipe Process">Recipe Process</a></li>
								<li><a href="/API/v0/recipe_process/servings_redux.php" target="_blank" title="Servings Redux">Servings Redux</a></li>
								<li><a href="/API/v0/recipe_process/ingredients_redux.php" target="_blank" title="Ingredients Redux">Ingredients Redux</a></li>
								<li><a href="/API/v0/recipe_process/recipe_type_redux.php" target="_blank" title="Recipe Type Redux">Recipe Type Redux</a></li>
								<li><a href="/API/v0/SocialChefDemo/HTML/" target="_blank" title="SocialChef Demo">SocialChef Demo</a></li>
							</ul>
						</li>
						<?php
					}
				}
				else {
					?>
					<li class="light"><a href="/login.php" title="Login"><i class="icon fa fa-sign-in"></i> <span>Login</span></a></li>
					<?php
				}
				?>
			</ul>
		</nav>
			
		
		<div id="top-search" class="input-group input-group-append">
			<input type="search" id="q" placeholder="Find a recipe" value="<?=trim(@$App->R['q'])?>" >
			<span id="btnHeadSearch" class="input-group-addon btn btn-default"><i class="fa fa-search"></i></span>
		</div>
		
	</div>
	<!--//wrap-->
	
</header>
<!--//header-->

<?php
if ( @$_SESSION['Login']['account_type_id']*1 == 4 ) { // developer
	?>
	<div id="headDevTools" class="devtools wrap clearfix hidden">
		<div class="btn-group pull-right" role="group" >
			<a class="no-style btn btn-default" href="/API/v0/recipe_process/recipe_process.php" target="_blank" title="Recipe Process">Recipe Process</a>
			<a class="no-style btn btn-default" href="/API/v0/recipe_process/servings_redux.php" target="_blank" title="Servings Redux">Servings Redux</a>
			<a class="no-style btn btn-default" href="/API/v0/recipe_process/ingredients_redux.php" target="_blank" title="Ingredients Redux">Ingredients Redux</a>
			<a class="no-style btn btn-default" href="/API/v0/recipe_process/recipe_type_redux.php" target="_blank" title="Recipe Type Redux">Recipe Type Redux</a>
			<a class="no-style btn btn-default" href="/API/v0/SocialChefDemo/HTML/" target="_blank" title="SocialChef Demo">SocialChef Demo</a>
		</div>
	</div>
	<?php
}
?>
