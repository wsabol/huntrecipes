<?php
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

$Type = array();
$Course = array();
$Cuisine = array();
$Chef = array();

$query = "SELECT * FROM RecipeType ORDER BY name";
$r = $App->oDBMY->query( $query );
while ( $row = $r->fetch_assoc() ) {
	array_push( $Type, $row );
}
$r->free();

$query = "SELECT * FROM Course ORDER BY name";
$r = $App->oDBMY->query( $query );
while ( $row = $r->fetch_assoc() ) {
	array_push( $Course, $row );
}
$r->free();

$query = "SELECT * FROM Cuisine ORDER BY name";
$r = $App->oDBMY->query( $query );
while ( $row = $r->fetch_assoc() ) {
	array_push( $Cuisine, $row );
}
$r->free();

$query = "SELECT * FROM Chef WHERE id > 1 ORDER BY name";
$r = $App->oDBMY->query( $query );
while ( $row = $r->fetch_assoc() ) {
	array_push( $Chef, $row );
}
$r->free();


$search_query = "
	Call spRecipeSearchResultParams(
		'".$App->oDBMY->escape_string(@$App->R['q'])."',
		".(@$App->R['type_id']*1).",
		".(@$App->R['course_id']*1).",
		".(@$App->R['cuisine_id']*1).",
		".(@$App->R['chef_id']*1).",
		'".@$App->R['ingrList']."'
	);
";
//wl($search_query);
$r = $App->oDBMY->query( $search_query );
$SearchParams = $r->fetch_assoc();
$r->free();


/** built site **/
$bodyClass = "";
require_once('_head.php');

?>
<style>
		
	ul.search-params {
		margin-bottom: 6px;
	}
	ul.search-params:not(.ingr-search-wrapper) li:first-child {
		font-style: italic;
	}
	ul.search-params li {
		padding-top: 0px;
		border: none;
	}
	ul.search-params li:before {
		content: none;
	}
	ul.search-params li i {
		padding-right: 8px;
	}
	
</style>
<!--wrap-->
<div class="wrap clearfix">
	
	<!--row-->
	<div class="row">
		<header class="s-title">
			<h1>Results</h1>
		</header>
		
		<!--content-->
		<section class="content three-fourth pull-right">
			<div class="row">
				<!--entries-->
				<div id="search-results" class="entries full-width _column-entries _column-entries-three">

				</div>
				<!--//entries-->
			</div>
		</section>
		<!--//content-->
		
		<aside class="sidebar one-fourth">
			
			<div class="widget container">
				<ul class="search-params">
					<li><span id="search_result_count"><?=$SearchParams['result_count']?></span> recipe results</li>
					<li id="li_type_id" class="<?=( $SearchParams['type_id'] == 0 ? "hidden" : "" )?>" ><i class="pointer click-behave fa fa-minus-circle"></i> <span><?=$SearchParams['type']?></span> recipes</li>
					<li id="li_course_id" class="<?=( $SearchParams['course_id'] == 0 ? "hidden" : "" )?>" ><i class="pointer click-behave fa fa-minus-circle"></i> for <span><?=$SearchParams['course']?></span> course</li>
					<li id="li_cuisine_id" class="<?=( $SearchParams['cuisine_id'] == 0 ? "hidden" : "" )?>" ><i class="pointer click-behave fa fa-minus-circle"></i> <span><?=$SearchParams['cuisine']?></span> cuisine</li>
					<li id="li_chef_id" class="<?=( $SearchParams['chef_id'] == 0 ? "hidden" : "" )?>" ><i class="pointer click-behave fa fa-minus-circle"></i> <span><?=$SearchParams['chef']?></span>'s recipes</li>
					<li id="li_q" class="<?=( $SearchParams['q'] == "" ? "hidden" : "" )?>" ><i class="pointer click-behave fa fa-minus-circle"></i> with text "<span><?=$SearchParams['q']?></span>"</li>
				</ul>
				<input id="search_type_id" type="hidden" value="<?=$SearchParams['type_id']?>" >
				<input id="search_course_id" type="hidden" value="<?=$SearchParams['course_id']?>" >
				<input id="search_cuisine_id" type="hidden" value="<?=$SearchParams['cuisine_id']?>" >
				<input id="search_chef_id" type="hidden" value="<?=$SearchParams['chef_id']?>" >
				<input id="search_q" type="hidden" value="<?=$SearchParams['q']?>" >
			</div>
			
			<div class="widget">
				<ul class="categories left adv-search-menu">
					
					<li class="treeview">
						<a href="#">Recipe Type</a>
						<ul class="treeview-menu">
							<? for ( $i = 0; $i < count($Type); $i++ ) { ?>
								<li><a href="#" data-field="type_id" data-id="<?=$Type[$i]['id']?>" ><?=$Type[$i]['name']?></a></li>
							<? } ?>
						</ul>
					</li>
					
					<li class="treeview">
						<a href="#">Course</a>
						<ul class="treeview-menu">
							<? for ( $i = 0; $i < count($Course); $i++ ) { ?>
								<li><a href="#" data-field="course_id" data-id="<?=$Course[$i]['id']?>" ><?=$Course[$i]['name']?></a></li>
							<? } ?>
						</ul>
					</li>
					
					<li class="treeview">
						<a href="#">Cuisine</a>
						<ul class="treeview-menu">
							<? for ( $i = 0; $i < count($Cuisine); $i++ ) { ?>
								<li><a href="#" data-field="cuisine_id" data-id="<?=$Cuisine[$i]['id']?>" ><?=$Cuisine[$i]['name']?></a></li>
							<? } ?>
						</ul>
					</li>
					
					<li class="treeview">
						<a href="#">Chef</a>
						<ul class="treeview-menu">
							<? for ( $i = 0; $i < count($Chef); $i++ ) { ?>
								<li><a href="#" data-field="chef_id" data-id="<?=$Chef[$i]['id']?>" ><?=$Chef[$i]['name']?></a></li>
							<? } ?>
						</ul>
					</li>
				</ul>
			</div>
			
			<div class="container recipefinder">
				<h3>Search by ingredients</h3>
				<!--
				<div id="add-search-ingredient-wrapper" class="input-group input-group-append" style="margin-bottom: 10px" >
					<input id="add-search-ingredient" type="text" class="form-control" placeholder="Add ingredient">
					<span class="input-group-addon btn btn-default"><i class="fa fa-plus"></i></span>
				</div>
				-->
				
				<div class="ingredients">
					<ul class="search-params ingr-search-wrapper">
						<!--<li><i class="pointer click-behave fa fa-minus-circle"></i> garlic</li>-->
					</ul>
					<span id="clear-ingr-search" class="btn btn-sm btn-default pull-right hidden">Clear All</span>
				</div>
				
				<div class="ingredients">
					<h3>Do you have?</h3>
					<div class="ingredient-suggestions">
						<!--<a href="#" class="button gold">Olive oil</a>-->
					</div>
					<span id="refresh-ingr-sugg" class="btn btn-sm btn-default pull-right">Refresh</span>
				</div>
				
			</div>
			
			<div class="widget">
				<!--<h3>Advertisment</h3>
				<a href="#"><img src="assets/images/advertisment.jpg" alt="" /></a>-->
				<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
				<!-- HuntRecipesBrowse -->
				<ins class="adsbygoogle"
						 style="display:inline-block;width:270px;height:350px"
						 data-ad-client="ca-pub-1405981854222899"
						 data-ad-slot="3423212966"></ins>
				<script>
				(adsbygoogle = window.adsbygoogle || []).push({});
				</script>
			</div>
			
		</aside>
		
	</div>
	<!--//row-->
</div>
<!--//wrap-->
<script>
	var page_num = -1;
	
	function loadingMoreDiv() {
		$('.content').append(
			$('<div>').attr('id', 'loadMoreDiv')
				.addClass('row text-center')
				.append(
					$('<img>').attr('src', '/assets/images/loading.gif')
						.attr('style', 'margin: auto')
				)
		);
	}
	
	function noMoreLoadDiv() {
		$('.content').append(
			$('<div>').attr('id', 'NoMoreLoadDiv')
				.addClass('alert alert-banner')
				.text('That\'s all folks!')
		)
		.append(
			$('<div>').addClass('quicklinks')
				.html('<a href="javascript:void(0)" class="button scroll-to-top">Back to top</a>')
		);
	}
	
	function addIngredientToSearch( ingr, ingr_id = 0 ) {
		var ingr = $.trim(ingr.toLowerCase());
		var exists = false;
		
		$.each( $('.recipefinder .search-params.ingr-search-wrapper .ingr-search'), function(i, el){
			exists = exists || ( $.trim($(el).text()) == ingr );
		});
		if ( !exists ) {
			$('.recipefinder .search-params').append(
				/*$('<li>').data('ingredient-id', ingr_id)
					.html( '<i class="pointer click-behave fa fa-minus-circle"></i> ' + ingr )*/
				$('<a>').addClass('button gold ingr-search')
					.attr('style', 'background: #FF7B74 !important')
					.attr('href', '#')
					.data('ingredient-id', ingr_id)
					.html(ingr + ' <i class="fa fa-times pull-right" aria-hidden="true"></i>')
			);
		}
	}
	
	function resetResults() {
		$('#search-results').empty();
		page_num = -1;
	}
	
	function resetIngrList() {
		$('.recipefinder .ingredients .ingredient-suggestions').empty();
	}
	
	function loadIngredientSuggestions( limit, args = null ) {
		if ( typeof args.ingrArr === 'undefined' ) {
			args.ingrArr = [];
		}
		if ( typeof args.reload === 'undefined' ) {
			args.reload = false;
		}
		$.ajax({
			url: '/ajax-json/search/ingredient_suggestions.json.php',
			type: 'GET',
			data: {
				q: $('#search_q').val(),
				type_id: $('#search_type_id').val(),
				course_id: $('#search_course_id').val(),
				cuisine_id: $('#search_cuisine_id').val(),
				chef_id: $('#search_chef_id').val(),
				ingrList: args.ingrArr.join(','),
				result_limit: limit
			},
			success: function( response ){
				console.log(response);
				var results = response.results;
				for ( var i = 0; i < results.length; i++ ) {
					if ( results[i].selected == 1 ) {
						addIngredientToSearch( results[i].name, results[i].ingredient_id );
					} else {
						$('.recipefinder .ingredients .ingredient-suggestions').append(
							$('<a>').addClass('button gold ingr-sugg')
								.attr('href', '#')
								.text(results[i].name)
								.data('ingredient-id', results[i].ingredient_id)
						);
					}
				}
				
				if ( $('.search-params.ingr-search-wrapper .ingr-search').length > 0 ) {
					$('#clear-ingr-search').removeClass('hidden');
				} else {
					$('#clear-ingr-search').addClass('hidden');
				}
				
				if ( args.reload ) {
					resetResults();
					loadSearchResults();
				}
			}
		});
	}
	
	function loadSearchResults() {
		// ajax call get data from server and append to the div
		$('.column-entries').css({height: 'auto'});
		
		if ( $('#NoMoreLoadDiv').length > 0 ) {
			$('#NoMoreLoadDiv').remove();
		}
		if ( $('.content .quicklinks').length > 0 ) {
			$('.content .quicklinks').remove();
		}
		loadingMoreDiv();
		
		$.ajax({
			url: '/ajax-json/search/spRecipeSearchResults.json.php',
			type: 'GET',
			data: {
				q: $('#search_q').val(),
				type_id: $('#search_type_id').val(),
				course_id: $('#search_course_id').val(),
				cuisine_id: $('#search_cuisine_id').val(),
				chef_id: $('#search_chef_id').val(),
				ingrList: ( $('.recipefinder .ingredients .ingr-search-wrapper .ingr-search').length > 0 ? getIngredientSearchList() : '' ),
				page_num: page_num + 1
			},
			success: function( response ) {
				console.log(response);
				$('#loadMoreDiv').remove();

				var results = response.results;
				if (  results.length > 0 ) {
					page_num++;
					
					for ( var i = 0; i < results.length; i++ ) {
						if ( i === 0 ) {
							$('#search_result_count').text( results[i].result_count );
						}
						
						$('#search-results').append(
							$('<div>').addClass('entry one-third _column-entry')
								.append(
									$('<figure>')
										.append(
											$('<img>').attr('src', results[i].image_filename)
												.attr('alt', '')
										)
										.append(
											$('<figcaption>').append(
												$('<a>').attr('href', 'recipe.php?recipe_id='+results[i].id)
													.html('<i class="icon icon-themeenergy_eye2"></i> <span>View recipe</span>')
											)
										)
								)
								.append(
									$('<div>').addClass('container')
										.append(
											$('<h2>').append(
												$('<a>').attr('href', 'recipe.php?recipe_id='+results[i].id)
													.text( results[i].title )
											)
										)
										.append(
											$('<div>').addClass('actions')
												.append(
													$('<div>').attr('data-recipe-id', results[i].id)
														.append(
															$('<div>').addClass('likes divSaveToFavorites'+( results[i].favorite_flag == 1 ? ' favorite-recipe' : '' ))
																.html('<i class="fa fa-heart"></i><span class="favorite-count">'+results[i].favorite_count+'</span>')
														)
												)
										)
								)
						);
						
						if ( i % 3 == 2 ) {
							$('#search-results').append(
								$('<div>').addClass('clearfix visible-lg-block')
							);
						} else if ( i % 2 == 1 ) {
							$('#search-results').append(
								$('<div>').addClass('clearfix visible-sm-block')
							);
							$('#search-results').append(
								$('<div>').addClass('clearfix visible-md-block')
							);
						}
					}
				} else {
					noMoreLoadDiv();
					if ( page_num == -1 ) {
						$('#search_result_count').text( 0 );
						$('#NoMoreLoadDiv').html('No results.');
					}
				}
				$('.column-entries').css({height: ($('.column-entries').innerHeight()+80) + 'px'});
			}
		});
	}
	
	$(function(){
		loadIngredientSuggestions(5, {ingrArr: [<?=@$App->R['ingrList']?>], reload: true});
		
		$(window).scroll(function() {
			if ( $('#loadMoreDiv').length > 0 || $('#NoMoreLoadDiv').length > 0 ) {
				return;
			}
			
			if ( $(window).scrollTop() + $(window).height() > $('#search-results').offset().top + $('#search-results').height() ) {
				loadSearchResults();
			}
		});
		
		/* ADV SEARCH */
		$('.adv-search-menu .treeview-menu li a').click(function(e){
			e.preventDefault();
			
			$('#search_'+$(this).data('field')).val( $(this).data('id') );
			$('#li_'+$(this).data('field')+' span').text( $(this).text() );
			$('#li_'+$(this).data('field')).removeClass('hidden');
			
			resetIngrList();
			loadIngredientSuggestions(5, { ingrArr: getIngredientSearchList().split(','), reload: true });
			
			// close tree
			$(this).parent().parent().removeClass('menu-open');
			$(this).parent().parent().attr('style', '');
			$(this).parent().parent().parent().removeClass('active');
		});
		
		$('#add-search-ingredient-wrapper .btn').click(function(){
			addIngredientToSearch( $(this).parent().find('input').val() );
			$(this).parent().find('input').val('');
		});
		
		$('#add-search-ingredient-wrapper input').keyup(function(e){
			if ( e.keyCode == 13 ) {
				addIngredientToSearch( $(this).val() );
				$(this).val('');
			}
		});
		
		$('.search-params:not(.ingr-search-wrapper) li i.fa-minus-circle').click(function(){
			$(this).parent().addClass('hidden');
			$(this).parent().find('span').empty();
			var entity = $(this).parent().attr('id').substring(3);
			if ( entity == 'q' ) {
				$('#q').val('');
				$('#search_'+entity).val('');
			} else {
				$('#search_'+entity).val(0);
			}
			
			resetIngrList();
			loadIngredientSuggestions(5, { ingrArr: getIngredientSearchList().split(','), reload: true });
		});
		
		$('#refresh-ingr-sugg').click(function(){
			resetIngrList();
			loadIngredientSuggestions(5, { ingrArr: getIngredientSearchList().split(','), reload: false });
		});
		
		$('#clear-ingr-search').click(function(){
			$('.search-params.ingr-search-wrapper .ingr-search').remove();
			resetIngrList();
			loadIngredientSuggestions(5, { ingrArr: getIngredientSearchList().split(','), reload: true });
		});
		
		$('.search-params.ingr-search-wrapper').on('click', '.ingr-search', function(e){
			e.preventDefault();
		});
		
		$('.search-params.ingr-search-wrapper').on('click', '.ingr-search i', function(e){
			$(this).parent().remove();
			
			resetIngrList();
			loadIngredientSuggestions(5, { ingrArr: getIngredientSearchList().split(','), reload: true });
		});
		
		$('.recipefinder .ingredients div').on('click', '.ingr-sugg', function(e){
			e.preventDefault();
			addIngredientToSearch( $(this).text(), $(this).data('ingredient-id') );
			$(this).remove();
			loadIngredientSuggestions(1, {reload: true});
		});
		
		
		/* MEAL PLAN */
		$('#search-results').on('click', '.btnSaveToMealsMain', function(e){
			var recipe_id = $(this).parent().parent().parent().data('recipe-id');
			//console.log(recipe_id);
		});
		
		$('#search-results').on('click', '.btnSaveToMealsWeeksMenu', function(e){
			var recipe_id = $(this).parent().parent().parent().data('recipe-id');
			//console.log(recipe_id);
			LoadDivContent('weeks_for_meals_dropdown', '', 'r'+recipe_id+'WeekMenu', { recipe_id: recipe_id });
		});
		
	});
</script>
<?php
require_once('_footer.php');
$App = "";
?>
