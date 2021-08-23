<?
$App = "";
require_once('_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

if ( @$_SESSION['Login']['chef_id']*1 == 0 ) {
	header('Location: /error404.php');
	exit;
}


function recipeIngredientRecurse( &$App, $recipe_id, &$dbOutput, $oIngr, $i = 0 ) {
  if ( $i >= count( $oIngr ) ) {
    return true;
  }
  if ( $i === 0 ) {
    $dbOutput = array();
  }
  
  $update_ingr_query = "
    CALL spRecipeIngredientUpdate(
      " . $oIngr[$i]->recipe_ingredient_id . ",
      " . $recipe_id . ",
      " . @$oIngr[$i]->unit_id * 1 . ",
      '" . $App->oDBMY->prepstring($oIngr[$i]->ingredient_name) . "',
      '" . $App->oDBMY->prepstring($oIngr[$i]->ingredient_prep) . "',
      " . $oIngr[$i]->amount * 1 . ",
      " . @$oIngr[$i]->optional_flag * 1 . "
    );
  ";
  $result = $App->oDBMY->query( $update_ingr_query );
  if ( $result ) {
    $resultArr = $result->fetch_assoc();
    $resultArr['query'] = $update_ingr_query;
    array_push( $dbOutput, $resultArr );
    $result->free();
    
    return recipeIngredientRecurse( $App, $recipe_id, $dbOutput, $oIngr, $i + 1 );
  } else {
    return false;
  }
}


$sel_query = "
	SELECT * FROM Measure
	ORDER BY name;
";
$results = $App->oDBMY->query( $sel_query );
$Measure = array();
while ( $row = $results->fetch_assoc() ) {
	array_push($Measure, $row);
}
$results->free();

/** built site **/
$bodyClass = ( @$_SESSION['Login']['id']*1 == 0 ? "home" : "" );
require_once('_head.php');
?>
<datalist id="IngredientsList" >
	<?php
	$sel_query = "
		SELECT * FROM Ingredient
		ORDER BY name;
	";
	$results = $App->oDBMY->query( $sel_query );
	while ( $row = $results->fetch_assoc() ) {
		?><option value="<?=htmlspecialchars ($row['name'])?>" ><?php
	}
	$results->free();
	?>
</datalist>
<datalist id="ingrUnitList" >
  <?php
  $sel_query = "
    SELECT * FROM Measure
    ORDER BY name;
  ";
  $results = $App->oDBMY->query( $sel_query );
  $Measure = array();
  while ( $row = $results->fetch_assoc() ) {
    array_push($Measure, $row);
    ?><option class="<?=$row['name']." ".$row['abbr']." ".$row['abbr_alt']?>" value="<?=$row['id']?>" data-name="<?=$row['name']?>" ><?php
  }
  $results->free();
  ?>
</datalist>
<!--wrap-->
<div class="wrap clearfix">
	
	<!--row-->
	<div class="row">
		<header class="s-title">
			<h1><?=( @$App->R['recipe_id']*1 == 0 ? 'Add a new' : 'Edit' )?> recipe</h1>
		</header>

		<!--content-->
		<section class="content full-width">
			
			<?php
			if ( @$App->R['submit_status'] == "Discard Draft" ) {
				$update_query = "
					UPDATE Recipe
					SET published_flag = -1
					WHERE id = " . @$App->R['recipe_id'] * 1 . ";
				";
				$result = $App->oDBMY->execute( $update_query );
				unset( $App->R['recipe_id'] );
				
				?>
				<div class="alert alert-info alert-dismissible" role="alert">
  				<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					Draft Discarded.
				</div>
				<?
			}
			elseif ( @$App->R['submit_status'] == "Save Recipe" ) {
				$App->R['ingredientsList'] = @json_decode( $App->R['ingredientsList'] );
				//wla($App->R);
				
				if ( $App->R['image_filename'] == '' ) {
					$App->R['image_filename'] = "generic_recipe.jpg";
				}
				
				// update cleared_flag
				$update_query = "
					CALL spRecipeUpdate(
						" . @$App->R['recipe_id'] * 1 . ",
						'" . $App->oDBMY->prepstring($App->R['title']) . "',
						'" . $App->oDBMY->prepstring($App->R['chef_name']) . "',
						'" . $App->oDBMY->prepstring($App->R['recipe_type_name']) . "',
						" . $App->R['course_id'] . ",
						" . $App->R['cuisine_id'] . ",
						'" . $App->oDBMY->prepstring($App->R['instructions']) . "',
						'" . $App->oDBMY->prepstring("assets/images/recipes/".$App->R['image_filename']) . "',
						" . $App->R['serving_count']*1 . ",
						" . $App->R['serving_measure_id'] . ",
						" . @$App->R['parent_recipe_id']*1 . ",
						" . @$App->R['published_flag']*1 . "
					);
				";
				$result = $App->oDBMY->query( $update_query );
				if ( $result ) {
					$oRecipe = $result->fetch_assoc();
					$json_results['output_data'] = $oRecipe;
					$result->free();

					$delIngr = "
						DELETE FROM RecipeIngredient
						WHERE recipe_id = ".$oRecipe['id'].";
					";
					$App->oDBMY->execute( $delIngr );

					$sql_success = recipeIngredientRecurse( $App, $oRecipe['id'], $outputResults, $App->R['ingredientsList'] );
				} else {
					$sql_success = false;
				}

				if ( $sql_success ) {
					?>
					<div class="alert alert-success alert-dismissible" role="alert">
  					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h1>Success!</h1>
						<p>Your recipe has been <?=( @$App->R['published_flag']*1 == 1 ? 'published' : 'saved' )?>.
							<?=( @$App->R['published_flag']*1 == 1 ? 'View your new recipe <a href="/recipe.php?recipe_id='.(@$App->R['recipe_id']*1).'" >HERE</a>' : '' )?></p>
					</div>
					<?
					if ( @$App->R['published_flag'] == 1 ) {
						unset($App->R['recipe_id']);
					}
				} else {
					?>
					<div class="alert alert-warning alert-dismissible" role="alert">
  					<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<strong>Shenanigans!</strong>	Something went wrong.
					</div>
					<?
				}
			}
			
			$sel_query = "
				Call spSelectRecipe(".(@$App->R['recipe_id']*1).", ".$_SESSION['Login']['id'].");
			";
			$result = $App->oDBMY->query($sel_query);
			$Recipe = @$result->fetch_assoc();
			@$result->free();
			
			if ( $Recipe['published_flag'] != 0 ) {
				$Recipe = array();
				$App->R['recipe_id'] = 0;
			}
			
			$sel_query = "
				Call spSelectRecipeIngredients(".(@$App->R['recipe_id']*1).");
			";
			$result = $App->oDBMY->query($sel_query);
			$RecipeIngredients = array();
			while ( $row = @$result->fetch_assoc() ) {
				array_push($RecipeIngredients, $row);
			}
			@$result->free();
			
			//wla($Recipe);
			?>
			<div class="submit_recipe container">
				<form id="frmRecipeSubmit" action="" method="POST" enctype="multipart/form-data" >
					<?
					$chef_lookup = "
						SELECT name FROM Chef where id = ".$_SESSION['Login']['chef_id'].";
					";
					$results = $App->oDBMY->query( $chef_lookup );
					$oChef = $results->fetch_assoc();
					$results->free();
					?>
					<input name="chef_name" id="rChef" type="hidden" value="<?=$oChef['name']?>" >
					<input name="recipe_type_name" id="rTypeName" type="hidden" value="" >
					<input name="instructions" id="rInstructions" type="hidden" value="" >
					<input name="recipe_id" id="recipe_id" type="hidden" value="<?=(@$Recipe['recipe_id']*1)?>" >
					<input name="ingredientsList" id="ingredientsList" type="hidden" value="" >
					<input name="image_filename" id="image_filename" type="hidden" value="<?=substr(@$Recipe['image_filename'], 22)?>" >

					<section>
						<h2>Basics</h2>
						<p>All fields are required.</p>
						<div class="f-row">
							<div class="full"><input type="text" class="form-control" name="title" placeholder="Recipe Title" value="<?=htmlspecialchars(@$Recipe['title'])?>" required ></div>
						</div>
						<div class="f-row">
							<div class="third">
								<select class="form-control" id="rType" required >
									<option value="">Select a Category</option>
									<?php
									$sel_query = "
										SELECT * FROM RecipeType
										ORDER BY name;
									";
									$results = $App->oDBMY->query( $sel_query );
									while ( $row = $results->fetch_assoc() ) {
										?><option value="<?=$row['id']?>" <?=( @$Recipe['type_id']*1 == $row['id'] ? "selected" : "" )?> ><?=$row['name']?></option><?php
									}
									$results->free();
									?>
								</select>
							</div>
							<div class="third">
								<select class="form-control" id="rCourse" name="course_id" required >
									<option value="">Select a Course</option>
									<?php
									$sel_query = "
										SELECT * FROM Course
										ORDER BY name;
									";
									$results = $App->oDBMY->query( $sel_query );
									while ( $row = $results->fetch_assoc() ) {
										?><option value="<?=$row['id']?>" <?=( $Recipe['course_id'] == $row['id'] ? "selected" : "" )?> ><?=$row['name']?></option><?php
									}
									$results->free();
									?>
									<option value="0" <?=( @$Recipe['course_id']*1 == 0 ? "selected" : "" )?> >Not Applicable</option>
								</select>
							</div>
							<div class="third">
								<select class="form-control" id="rCuisine" name="cuisine_id" required >
									<option value="">Select a Cuisine</option>
									<?php
									$sel_query = "
										SELECT * FROM Cuisine
										ORDER BY name;
									";
									$results = $App->oDBMY->query( $sel_query );
									while ( $row = $results->fetch_assoc() ) {
										?><option value="<?=$row['id']?>" <?=( @$Recipe['cuisine_id']*1 == $row['id'] ? "selected" : "" )?> ><?=$row['name']?></option><?php
									}
									$results->free();
									?>
									<option value="0" <?=( @$Recipe['cuisine_id']*1 == 0 ? "selected" : "" )?> >Not Applicable</option>
								</select>
							</div>
						</div>
						<div class="f-row">
							<?php
							$fAmount = new Fraction(@$Recipe['serving_count']*1);
							$val_formatted = $fAmount->toString();
							$fAmount = "";
							?>
							<div class="third"><input type="text" id="rServingCount" name="serving_count" class="form-control" placeholder="Yield / Serving Count" value="<?=( $val_formatted == 0 ? '' : $val_formatted )?>" ></div>
							<div class="third">
								<select id="rServingUnit" name="serving_measure_id" class="form-control" >
									<?php
									for ( $i = 0; $i < count($Measure); $i++ ) {
										?><option value="<?=$Measure[$i]['id']?>" <?=( @$Recipe['serving_measure_id']*1 == 0 ? "selected" : "" )?> ><?=$Measure[$i]['name']?></option><?php
									}
									?>
								</select>
							</div>
						</div>
					</section>

					<section id="ingredients">
						<h2>Ingredients</h2>
						<?php
						for ( $i = 0; $i < count($RecipeIngredients); $i++ ) {
							$fAmount = new Fraction(@$RecipeIngredients[$i]['amount']*1);
							$val_formatted = $fAmount->toString();
							?>
							<div class="f-row ingredient">
								<div class="third">
									<input class="form-control inIngrName" type="text" list="IngredientsList" placeholder="Grocery / Ingredient" required="" value="<?=htmlspecialchars(@$RecipeIngredients[$i]['raw_ingredient_name'])?>" >
								</div>
								<div class="fourth">
									<input class="form-control inIngrPrep" type="text" placeholder="Ingredient Prep" value="<?=htmlspecialchars($RecipeIngredients[$i]['ingredient_prep'])?>" >
								</div>
								<div class="small">
									<input class="form-control inIngrAmount" type="text" placeholder="Quantity" value="<?=( $val_formatted == 0 ? '' : $val_formatted )?>" >
								</div>
								<div class="small">
									<select class="form-control inIngrUnit" >
										<?php
										for ( $j = 0; $j < count($Measure); $j++ ) {
											?><option value="<?=$Measure[$j]['id']?>" <?=( @$RecipeIngredients[$i]['measure_id'] == $Measure[$j]['id'] ? "selected" : "" )?> ><?=$Measure[$j]['name']?></option><?php
										}
										?>
									</select>
								</div>
								<div class="small">
									<label>
										<input type="checkbox" class="inIngrOpt" value="1" <?=( @$RecipeIngredients[$i]['optional_flag'] == 1 ? "checked" : "" )?> > Optional
									</label>
								</div>
								<button class="remove">-</button>
							</div>
							<?php
						}
						$fAmount = "";
						?>
						<div class="f-row full">
							<button class="add">Add an ingredient</button>
						</div>
					</section>	

					<section id="instructions">
						<h2>Instructions <span>(enter instructions, each step at a time)</span></h2>
						<?php
						$oInstr = explode(chr(10), @$Recipe['instructions']);
						for ( $i = 0; $i < count($oInstr); $i++ ) {
							?>
							<div class="f-row instruction">
								<div class="full"><input type="text" class="form-control inInstr" placeholder="Instructions" value="<?=$oInstr[$i]?>" ></div>
								<button class="remove">-</button>
							</div>
							<?php
						}
						?>
						<div class="f-row full">
							<button class="add">Add a step</button>
						</div>
					</section>

					<section>
						<h2>Photo</h2>
						<div class="f-row">
							<div class="third">
								<input type="file" id="rImageFile" name="rImageFile" <?=( @$Recipe['image_filename'] == "" ? "required" : "" )?> >
							</div>
							<div class="third">
								<figure class="<?=( @$Recipe['image_filename'] == "" ? "hidden" : "" )?>" >
									<img id="rImage" src="<?=@$Recipe['image_filename']?>" alt="<?=@$Recipe['title']?>" >
								</figure>
							</div>
						</div>
						
					</section>	

					<section>
						<h2>Status <span>(would you like to further edit this recipe or are you ready to publish it?)</span></h2>
						<div class="f-row full">
							<label for="r1">
								<input type="radio" id="r1" name="published_flag" value="0" <?=( @$Recipe['published_flag'] === '0' ? "checked" : "" )?> required >
								I am still working on it
							</label>
						</div>
						<div class="f-row full">
							<label for="r2">
								<input type="radio" id="r2" name="published_flag" value="1" <?=( @$Recipe['published_flag'] === '1' ? "checked" : "" )?> required >
								I am ready to publish this recipe
							</label>
						</div>
					</section>

					<div class="f-row full">
						<input type="submit" name="submit_status" class="button" value="Save Recipe" >
						<input type="submit" name="submit_status" class="pull-right button <?=( @$Recipe['recipe_id']*1 == 0 ? "hidden" : "" )?>" value="Discard Draft" style="background-color: #d9534f; margin-left: 12px;" >
						<a class="pull-right button <?=( @$Recipe['recipe_id']*1 == 0 ? "hidden" : "" )?>" style="background-color: #337ab7;" href="/recipe.php?recipe_id=<?=@$Recipe['recipe_id']?>" >Preview</a>
					</div>
				</form>
			</div>
			
		</section>
		<!--//content-->
	</div>
	<!--//row-->
</div>
<!--//wrap-->
<script>
	
	function addIngredient() {
		var $newIngr = $('<div>').addClass('f-row ingredient');
		$newIngr.append(
			$('<div>').addClass('third').append(
				$('<input>').addClass('form-control inIngrName')
					.attr('type', 'text')
					.attr('list', 'IngredientsList')
					.attr('placeholder', 'Grocery / Ingredient')
					.prop('required', true)
			)
		);
		$newIngr.append(
			$('<div>').addClass('fourth').append(
				$('<input>').addClass('form-control inIngrPrep')
					.attr('type', 'text')
					.attr('placeholder', 'Ingredient Prep')
			)
		);
		$newIngr.append(
			$('<div>').addClass('small').append(
				$('<input>').addClass('form-control inIngrAmount')
					.attr('type', 'text')
					.attr('placeholder', 'Quantity')
			)
		);
		$newIngr.append(
			$('<div>').addClass('small').append(
				$('<select>').addClass('form-control inIngrUnit')
			)
		);
		$newIngr.append(
			$('<div>').addClass('small').append(
				$('<label>')
					.append(
						$('<input>').addClass('inIngrOpt')
							.attr('type', 'checkbox')
							.attr('value', '1')
					)
					.append(' Optional')
			)
		);
		
		$.each( $('#ingrUnitList > option'), function(i, opt){
			$newIngr.find('.inIngrUnit').append('<option value="'+$(opt).val()+'" >'+$(opt).data('name')+'</option>');
		});
		$newIngr.append(
			$('<button>').addClass('remove').text('-')
		);
		
		$newIngr.insertBefore( $('#ingredients .add').parent() );
		/*
		<div class="f-row ingredient">
			<div class="large"><input type="text" class="form-control inIngrName" list="IngredientsList" placeholder="Ingredient" ></div>
			<div class="small"><input type="text" class="form-control inIngrAmount" placeholder="Quantity" ></div>
			<div class="third">
				<select class="form-control inIngrUnit" >

				</select>
			</div>
			<button class="remove">-</button>
		</div>
		*/
		
		$('.inIngrOpt').iCheck({
			checkboxClass: 'icheckbox_flat-blue',
			increaseArea: '20%' // optional
		});
	}
	
	function addInstruction() {
		var instr_count = $('#instructions .f-row.instruction').length;
		var $newInstr = $('<div>').addClass('f-row instruction');
		$newInstr.append(
			$('<div>').addClass('full').append(
				$('<input>').addClass('form-control inInstr')
					.attr('type', 'text')
					.data('count', instr_count)
					.attr('placeholder', 'Instructions')
					.prop('required', true)
			)
		);
		$newInstr.append(
			$('<button>').addClass('remove').text('-')
		);
		
		$newInstr.insertBefore( $('#instructions .add').parent() );
		/*
		<div class="f-row instruction">
			<div class="full"><input type="text" class="form-control inInstr" placeholder="Instructions" /></div>
			<button class="remove">-</button>
		</div>
		*/
	}
	
	$(function(){
		if ( $('.f-row.ingredient').length === 0 ) {
			addIngredient();
		}
		if ( $('.f-row.instruction').length === 0 ) {
			addInstruction();
		}
		$('.inIngrOpt').iCheck({
			checkboxClass: 'icheckbox_flat-blue',
			increaseArea: '20%' // optional
		});
		
		$('#ingredients').on('click', '.remove', function(e){
			e.preventDefault();
			$(this).parent().remove();
		});

		$('#ingredients').on('click', '.add', function(e){
			e.preventDefault();
			addIngredient();
		});
		
		$('#instructions').on('click', '.remove', function(e){
			e.preventDefault();
			$(this).parent().remove();
		});

		$('#instructions').on('click', '.add', function(e){
			e.preventDefault();
			addInstruction();
		});
		
		$('#rImageFile').change(function(e){
			// file upload
			var fileSelect = document.getElementById('rImageFile');
			// Get the selected files from the input.
			var file = fileSelect.files[0];
			console.log(file);
			// Create a new FormData object.
			var formData = new FormData();
			// Check the file type.
			if ( typeof file !== 'undefined' ) {
				if ( file.type.match('image.*') ) {
					// Add the file to the request.
					formData.append('rImageFile', file, file.name);
					for (var p of formData) {
						console.log(p);
					}
					// Set up the request.
					var xhr = new XMLHttpRequest();
					// Open the connection.
					xhr.open('POST', '/API/recipe_process/ajax-json/recipe_image_upload.json.php', true);
					// Set up a handler for when the request finishes.
					xhr.onload = function ( ) {
						console.log(xhr);
						if (xhr.status === 200) {
							// File(s) uploaded.
							var resp = JSON.parse(this.response);
							console.log('Server got:', resp);
							if ( resp.errors.length === 0 ) {
								$('#image_filename').val(resp.image_filename);
								$('#rImage').attr('src', 'assets/images/recipes/'+resp.image_filename);
							}
						} else {
							alert('An error occurred in uploading the file!');
						}
					};
					// Send the Data.
					xhr.send(formData);
				}
			}
		});
		
		$('#frmRecipeSubmit').submit(function(e){
			// input validation
			$.each( $('.form-control'), function(i, el){
				$(el).parent().removeClass('has-error');
			});
			
			var has_error = false;
			$.each( $('.inIngrAmount'), function(i, el){
				var b = new Fraction( $(el).val() );
				if ( isNaN(b.decimal) && $(el).val() != '' ) {
					$(el).parent().addClass('has-error');
					has_error = true;
				}
			});
			if ( has_error ) {
				alert('Ingredient amount must be a decimal or fraction (ex. 1.5 or 1-1/2) or blank if no specific amount can be given.');
			}
			$('#rTypeName').val( $('#rType option:selected').text() );
			
			var rInstructions = '';
			$.each( $('.inInstr'), function(i, el){
				rInstructions = rInstructions + ( i === 0 ? '' : '\n' ) + $(el).val();
			});
			if ( rInstructions.length > 2000 ) {
				$('.inInstr').parent().addClass('has-error');
				alert('Recipe instructions longer than 2000 characters total.');
				has_error = true;
			} else {
				$('#rInstructions').val( rInstructions );
			}
			if ( has_error ) {
				e.preventDefault();
				return false;
			}
			
			var oIngrList = [];
			$.each( $('#ingredients .f-row.ingredient'), function(i, row){
				var b = new Fraction( $(this).find('.inIngrAmount').val() );
				oIngrList.push({
					recipe_ingredient_id: 0,
					amount: ( $(this).find('.inIngrAmount').val() == '' ? 0 : b.decimal ),
					unit_id: $(this).find('.inIngrUnit').val(),
					ingredient_name: $(this).find('.inIngrName').val(),
					ingredient_prep: $(this).find('.inIngrPrep').val(),
					optional_flag: ( $(this).find('.inIngrOpt').is(':checked') ? 1 : 0 )
				});
			});
			$('#ingredientsList').val( JSON.stringify(oIngrList) );
			
		});
		
	});
	
</script>
<?php

require_once('_footer.php');
$App = "";
?>
