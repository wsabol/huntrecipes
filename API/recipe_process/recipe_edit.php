<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

$bodyClass = "recipePage";
require_once("../../_head.php");


$sel_query = "
	Call spSelectRecipe(".$App->R['recipe_id'].", ".$_SESSION['Login']['id'].");
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

?>
<style>
	.raw-text-block > .row {
    padding: 0 12px;
  }
  
  #divRawTextWrapper > .raw-text-block .textTools > .main-tools {
    margin-top: 6px;
  }
  
  #divRawTextWrapper > .raw-text-block .textTools > .main-tools > .buttonMain {
    display: none;
  }
  
  #divRawTextWrapper > .raw-text-block.prev-block .textWrap {
    color: #888;
  }
  
  #divRawTextWrapper > .raw-text-block.next-block:first-child .textTools > .main-tools > .buttonMarkComplete,
  #divRawTextWrapper > .raw-text-block.next-block:last-child .textTools > .main-tools > .buttonPushBack,
  #divRawTextWrapper > .raw-text-block.prev-block:first-child .textTools > .main-tools > .buttonPushBack {
    display: inline-block;
  }
  
  #ingrTable {
    width: 100%;
    max-width: 100%;
  }
	#ingrTable select {
		height: 34px;
	}
	
  .ingrRow > td {
    padding-bottom: 6px;
  }
  .ingrRow > .tdIngrAmount {
    width: 16%;
  }
  .ingrRow > .tdIngrUnit {
    width: 16%;
  }
  
  textarea {
    resize: vertical;
  }
</style>

<datalist id="IngredientsList" >
  <?php
  $sel_query = "
    SELECT * FROM Ingredient
    ORDER BY name;
  ";
  $results = $App->oDBMY->query( $sel_query );
  while ( $row = $results->fetch_assoc() ) {
    ?><option value="<?=$row['name']?>" ><?php
  }
  $results->free();
  ?>
</datalist>
<datalist id="ChefList" >
  <?php
  $sel_query = "
    SELECT * FROM Chef
    ORDER BY name;
  ";
  $results = $App->oDBMY->query( $sel_query );
  while ( $row = $results->fetch_assoc() ) {
    ?><option value="<?=$row['name']?>" ><?php
  }
  $results->free();
  ?>
</datalist>
<datalist id="RecipeTypeList" >
  <?php
  $sel_query = "
    SELECT * FROM RecipeType
    ORDER BY name;
  ";
  $results = $App->oDBMY->query( $sel_query );
  while ( $row = $results->fetch_assoc() ) {
    ?><option value="<?=$row['name']?>" ><?php
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
  <header class="s-title">
    <h1>Recipe Edit</h1>
  </header>
  
  <form id="recipe-process" >
    <input id="recipe_id" type="hidden" value="<?=@$App->R['recipe_id']*1?>">
    
		<div class="row">
			<div class="one-third">
				<div class="form-group">
					<label for="rTitle">Recipe Title</label>
					<input type="text" class="form-control" id="rTitle" value="<?=htmlspecialchars(@$Recipe['title'])?>" placeholder="Title">
				</div>
			</div>
			<div class="one-sixth">
				<div class="form-group">
					<label for="rChef">Chef</label>
					<input type="text" class="form-control" id="rChef" value="<?=@$Recipe['chef']?>" list="ChefList" placeholder="Name">
				</div>
			</div>
			<div class="one-sixth">
				<div class="form-group">
					<label for="rType">Recipe Type</label>
					<input type="text" class="form-control" id="rType" value="<?=@$Recipe['type']?>" list="RecipeTypeList" placeholder="Cookies">
				</div>
			</div>
			<div class="one-sixth">
				<div class="form-group">
					<label for="rCourse">Course</label>
					<select class="form-control" id="rCourse" >
						<option value="0">Not Applicable</option>
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
					</select>
				</div>
			</div>
			<div class="one-sixth">
				<div class="form-group">
					<label for="rCuisine">Cuisine</label>
					<select class="form-control" id="rCuisine" >
						<option value="0">Not Applicable</option>
						<?php
						$sel_query = "
							SELECT * FROM Cuisine
							ORDER BY name;
						";
						$results = $App->oDBMY->query( $sel_query );
						while ( $row = $results->fetch_assoc() ) {
							?><option value="<?=$row['id']?>" <?=( $Recipe['cuisine_id'] == $row['id'] ? "selected" : "" )?> ><?=$row['name']?></option><?php
						}
						$results->free();
						?>
					</select>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="one-third" >
				<div class="form-group">
					<label for="rParent">Parent Recipe</label>
					<select class="form-control" id="rParent">
						<option value="0">N/A</option>
						<?php
						$sel_query = "
							SELECT id, title FROM Recipe
							ORDER BY title;
						";
						$results = $App->oDBMY->query( $sel_query );
						while ( $row = $results->fetch_assoc() ) {
							?><option value="<?=$row['id']?>" <?=( $Recipe['parent_recipe_id'] == $row['id'] ? "selected" : "" )?> ><?=htmlspecialchars($row['title'])?></option><?php
						}
						$results->free();
						?>
					</select>
					</select>
				</div>
			</div>
			<div class="one-sixth">
				<div class="form-group">
					<label for="rServingCount">Yields</label>
					<?php
					$fAmount = new Fraction($Recipe['serving_count']);
					$val_formatted = $fAmount->toString();
					$fAmount = "";
					?>
					<input type="text" id="rServingCount" name="rServingCount" value="<?=$val_formatted?>" class="form-control" >
				</div>
			</div>
			<div class="one-sixth">
				<div class="form-group">
					<label for="rServingUnit">Yield Unit</label>
					<select id="rServingUnit" name="rServingUnit" class="form-control" >
						<?php
						for ( $i = 0; $i < count($Measure); $i++ ) {
							?><option value="<?=$Measure[$i]['id']?>" <?=( $Recipe['serving_measure_id'] == $Measure[$i]['id'] ? "selected" : "" )?> ><?=$Measure[$i]['name']?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			<div class="one-third">
				<div class="form-group">
					<label for="rImageFile">Recipe Image</label>
					<input type="file" id="rImageFile" name="rImageFile" class="form-control" >
				</div>
			</div>
		</div>
		<div class="row">
			<div class="full-width">
				<label for="ingrTable">Ingredients</label>
				<table id="ingrTable" class="ctable table">
					<tbody>
						<?php
						for ( $i = 0; $i < count($RecipeIngredients); $i++ ) {
							$fAmount = new Fraction($RecipeIngredients[$i]['amount']);
							$val_formatted = $fAmount->toString();
							?>
							<tr id="row<?=($i+1)?>" class="ingrRow">
								<td class="tdIngrAmount"><input placeholder="Amt" type="text" class="form-control ingrAmount" value="<?=$val_formatted?>"></td>
								<td class="tdIngrUnit">
									<select class="form-control ingrUnit" >
										<?php
										for ( $j = 0; $j < count($Measure); $j++ ) {
											?><option value="<?=$Measure[$j]['id']?>" <?=( $RecipeIngredients[$i]['measure_id'] == $Measure[$j]['id'] ? "selected" : "" )?> ><?=$Measure[$j]['name']?></option><?php
										}
										?>
									</select>
								</td>
								<td><input placeholder="Ingredient" type="text" class="form-control ingrName" value="<?=htmlspecialchars($RecipeIngredients[$i]['raw_ingredient_name'])?>" list="IngredientsList" ></td>
								<td><input placeholder="Prep" type="text" class="form-control ingrPrep" value="<?=htmlspecialchars($RecipeIngredients[$i]['ingredient_prep'])?>" ></td>
								<td style="padding-left: 12px; margin-bottom: 0px;"><label><input type="checkbox" class="ingrOpt" value="1" <?=( $RecipeIngredients[$i]['optional_flag'] == 1 ? "checked" : "" )?> >Opt</label></td>
								<td><span class="btn btn-primary buttonInsertIngr"><i class="fa fa-plus"></i></span> <span class="btn btn-danger buttonRemoveIngr"><i class="fa fa-remove"></i></span></td>
							</tr>
							<?php
						}
						$fAmount = "";
						?>
          </tbody>
        </table>
        <span id="buttonAddIngredient" class="button">
          Add Ingredient
        </span>
        <span id="buttonClearIngredients" class="button">
          Clear All
        </span>
      </div>
    </div>
    <div class="row">
      <div class="three-fourth">
        <div class="form-group">
          <label for="rInstr">Instructions</label>
          <textarea id="rInstr" class="form-control" placeholder="Instructions" ><?=$Recipe['instructions']?></textarea>
        </div>
      </div>
			<div class="one-fourth">
				<div class="widget">
					<section class="container" style="padding-bottom: 17px;" >
						<label>
							<input type="checkbox" id="rDelete" >
							DELETE
						</label>
					</div>
				</div>
			</div>
    </div>
    <div class="row">
      <div class="full-width text-center">
        <span id="buttonRecipeSubmit" class="button">Submit</span>
      </div>
    </div>
  </form>
  
</div>
<!--//wrap-->

<script>

	function addIngredient( amount = '', unit_id = 0, ingrName = '', $insertEl = null ) {
		var $tbody = $('#ingrTable > tbody');
		var rowID = $tbody.children('tr').length + 1;
		while ( $('#ingr'+rowID).length > 0) {
			rowID++;
		}
		if ( $insertEl === null ) {
			$tbody.append('<tr id="ingr'+rowID+'" class="ingrRow"></tr>');
		} else {
			$insertEl.after('<tr id="ingr'+rowID+'" class="ingrRow"></tr>');
		}

		var ingrPrep = '';
		if ( ingrName.includes(', ') ) {
			var arr = ingrName.split(', ');
			ingrName = arr[0];
			ingrPrep = arr.splice(1);
		}

		var $newRow = $('#ingr'+rowID);
		$newRow.append('<td class="tdIngrAmount"><input placeholder="Amt" type="text" class="form-control ingrAmount" value="'+amount+'"></td>');
		//$newRow.append('<td class="tdIngrUnit"><input placeholder="Unit" type="text" class="form-control ingrUnit" list="ingrUnitList" value="'+unit+'"></td>');
		$newRow.append('<td class="tdIngrUnit"><select class="form-control ingrUnit" ></select></td>');
		$.each( $('#ingrUnitList > option'), function(i, opt){
			$newRow.find('.tdIngrUnit > select').append('<option value="'+$(opt).val()+'" >'+$(opt).data('name')+'</option>');
		});
		$newRow.find('.tdIngrUnit > select').val( unit_id );
		$newRow.append('<td><input placeholder="Ingredient" type="text" class="form-control ingrName" value="'+ingrName+'" list="IngredientsList" ></td>');
		$newRow.append('<td><input placeholder="Prep" type="text" class="form-control ingrPrep" value="'+ingrPrep+'" ></td>');
		$newRow.append('<td style="padding-left: 12px; margin-bottom: 0px;"><label><input type="checkbox" class="ingrOpt" value="1">Opt</label></td>');
		$newRow.append('<td><span class="btn btn-primary buttonInsertIngr"><i class="fa fa-plus"></i></span> <span class="btn btn-danger buttonRemoveIngr"><i class="fa fa-remove"></i></span></td>');

		$('input[type=checkbox]').iCheck({
			checkboxClass: 'icheckbox_flat-blue',
			increaseArea: '20%' // optional
		});
	}

	function valueOfDataListByClass( datalistId, target ) {
		if ( $('#'+datalistId).length === 0 ) {
			console.log(datalistId+' does not exists!');
			return false;
		}
		var dlOptions = document.getElementById( datalistId ).options;

		for ( var i = 0; i < dlOptions.length; i++ ) {
			if ( $(dlOptions[i]).hasClass( target ) ) {
				return dlOptions[i].value;
			}
		}
		return false;
	}

	function resetForm() {
		$('#ingrTable > tbody').empty();
		$('#rTitle').val('');
		$('#rChef').val('');
		$('#rType').val('');
		$('#rCourse').val(0);
		$('#rServingCount').val(0);
		$('#rServingUnit').val(0);
		$('#rInstr').val('');
	}

	$(document).ready(function(){

		$('#buttonAddIngredient').click(function(){
			addIngredient();
		});

		$('#buttonClearIngredients').click(function(){
			$('.ingrRow').remove();
		});

		$('#ingrTable').on('click', '.buttonRemoveIngr', function(){
			var $tr = $(this).parent().parent();
			$tr.remove();
		});

		$('#ingrTable').on('click', '.buttonInsertIngr', function(){
			var $tr = $(this).parent().parent();
			addIngredient('', 0, '', $tr);
		});

		$('#buttonRecipeSubmit').click(function(){
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
							
							recipeSubmit(resp.image_filename);
						} else {
							alert('An error occurred in uploading the file!');
						}
					};
					// Send the Data.
					xhr.send(formData);
				}
			} else {
				recipeSubmit('');
			}
		});
		
		function recipeSubmit(file_name) {
			// input validation
			$.each( $('.form-control'), function(i, el){
				$(el).parent().removeClass('has-error');
			});

			var has_error = false;
			$.each( $('.ingrAmount'), function(i, el){
				var b = new Fraction( $(el).val() );
				if ( isNaN(b.decimal) && $(el).val() != '' ) {
					$(el).parent().addClass('has-error');
					has_error = true;
				}
			});
			if ( has_error ) {
				alert('Ingredient amount must be valid values.');
			}
			if ( $('#rTitle').val().length === 0 ) {
				$('#rTitle').parent().addClass('has-error');
				alert('Recipe needs a title');
				has_error = true;
			}
			if ( $('#rType').val().length === 0 ) {
				$('#rType').parent().addClass('has-error');
				alert('Recipe needs a type');
				has_error = true;
			}
			if ( $('#rInstr').val().length > 2000 ) {
				$('#rInstr').parent().addClass('has-error');
				alert('Recipe instructions longer than 2000 characters.');
				has_error = true;
			}
			if ( has_error ) {
				return;
			}

			var oIngrList = [];
			$.each( $('#ingrTable > tbody > tr'), function(i, tr){
				var b = new Fraction( $(this).find('.ingrAmount').val() );
				oIngrList.push({
					recipe_ingredient_id: 0,
					amount: ( $(this).find('.ingrAmount').val() == '' ? 0 : b.decimal ),
					unit_id: $(this).find('.ingrUnit').val(),
					ingredient_name: $(this).find('.ingrName').val(),
					ingredient_prep: $(this).find('.ingrPrep').val(),
					optional_flag: ( $(this).find('.ingrOpt').is(':checked') ? 1 : 0 )
				});
			});

			var fCnt = new Fraction( $('#rServingCount').val() );
			var oRecipe = {
				id: $('#recipe_id').val(),
				title: $('#rTitle').val(),
				chef_name: $('#rChef').val(),
				recipe_type_name: $('#rType').val(),
				course_id: $('#rCourse').val(),
				cuisine_id: $('#rCuisine').val(),
				serving_count: ( $('#rServingCount').val() == '' ? 0 : fCnt.decimal ),
				serving_measure_id: $('#rServingUnit').val(),
				instructions: $('#rInstr').val(),
				ingredientsList: oIngrList,
				image_filename: 'assets/images/recipes/' + ( file_name.length === 0 || typeof file_name == 'undefined' ? 'generic_recipe.jpg' : file_name ),
				parent_recipe_id: $('#rParent').val(),
				delete_flag: ( $('#rDelete').is(':checked') ? 1 : 0 ),
				published_flag: 1
			};
			console.log(oRecipe);

			$.ajax({
				url: '/API/recipe_process/ajax-json/recipe_submit.json.php',
				type: 'GET',
				data: oRecipe,
				success: function( response ) {
					console.log(response);
					if ( response.success == 1 ) {
						//alert('Success');
						window.location.href = '/recipe.php?recipe_id='+response.output_data.id;
					} else {
						console.log(response.err_msg);
					}
				},
				error: function( jqXHR, textStatus, errorThrown ) {
					console.log(jqXHR);
					console.log(textStatus);
					console.log(errorThrown);
				}
			});

		}

	});

</script>
<?php
require_once("../../_footer.php");
?>