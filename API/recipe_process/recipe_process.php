<?php
$App = "";
$skip_session_create = 1;
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

$bodyClass = "recipePage";
require_once("../../_head.php");

?>
<?php

// percent_done
$sel_query = "
select 
  count(*)
  ,sum(rr.cleared_flag)
  ,sum(rr.cleared_flag) / count(*) * 100 cleared_prec
  ,rc.days
  ,ceil(rc.days * ( 1/(sum(rr.cleared_flag) / count(*)) - 1)) daysremaining
  ,DATE_ADD( CONVERT(rc.maxdate, DATE), INTERVAL ceil(rc.days * ( 1/(sum(rr.cleared_flag) / count(*)) - 1)) DAY ) finishdate
from RecipesRaw rr
,(
  select
  min(date_created) mindate
  ,max(date_created) maxdate
  ,datediff( max( date_created ), min( date_created ) ) days
  from Recipe
) rc
";
$results = $App->oDBMY->query( $sel_query );
$data = $results->fetch_assoc();
$results->free();
$percent_done = round( $data['cleared_prec'], 0 );

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
    <h1>Recipe Input <small><?=$percent_done?>% done /// projected_finish_date: <?=$data['finishdate']?></small></h1>
  </header>
  
  <form id="recipe-process" >
    <div class="row">
      <div class="one-half">
        <div class="form-group">
          <label for="rTitle">Recipe Title</label>
          <input type="text" class="form-control" id="rTitle" placeholder="Title">
        </div>
      </div>
      <div class="one-sixth">
        <div class="form-group">
          <label for="rChef">Chef</label>
          <input type="text" class="form-control" id="rChef" list="ChefList" placeholder="Name">
        </div>
      </div>
      <div class="one-sixth">
        <div class="form-group">
          <label for="rType">Recipe Type</label>
          <input type="text" class="form-control" id="rType" list="RecipeTypeList" placeholder="Cookies">
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
              ?><option value="<?=$row['id']?>" ><?=$row['name']?></option><?php
            }
            $results->free();
            ?>
          </select>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="one-third" id="rIngredients">
        <div class="form-group">
          <label for="rParent">Parent Recipe</label>
          <select class="form-control" id="rParent">
            
          </select>
        </div>
      </div>
      <div class="one-sixth">
        <div class="form-group">
          <label for="rServingCount">Yields</label>
          <input type="text" id="rServingCount" name="rServingCount" value="0" class="form-control" >
        </div>
      </div>
      <div class="one-sixth">
        <div class="form-group">
          <label for="rServingUnit">Yield Unit</label>
          <select id="rServingUnit" name="rServingUnit" class="form-control" >
            <?php
            for ( $i = 0; $i < count($Measure); $i++ ) {
              ?><option value="<?=$Measure[$i]['id']?>"><?=$Measure[$i]['name']?></option><?php
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
      <div class="full-width">
        <div class="form-group">
          <label for="rInstr">Instructions</label>
          <textarea id="rInstr" class="form-control" placeholder="Instructions" ></textarea>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="full-width text-center">
        <span id="buttonRecipeSubmit" class="button">Submit</span>
      </div>
    </div>
  </form>
  
  <hr>
  
  <div class="container box">
    <div class="row">
      <div class="full-width">
        <span class="button buttonPrevText" >PREV</span>
        <span class="button buttonNextText" >NEXT</span>
      </div>
    </div>
    <div class="row">
      <div class="full-width">
        <hr>
        <div id="divRawTextWrapper">

        </div>
      </div>
    </div>
    <div class="row">
      <div class="full-width">
        <span class="button buttonPrevText" >PREV</span>
        <span class="button buttonNextText" >NEXT</span>
      </div>
    </div>
  </div>
</div>
<!--//wrap-->

<script>
  
  var disableBufferPulls = false;
  
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
  
  function getLastLineNumber() {
    var line_number = null;
    if ( $('.lineText').length > 0 ) {
      $('.lineText').each(function(i){
        if ( $(this).data('line-number') > line_number || line_number === null ) {
          line_number = $(this).data('line-number');
        }
      });
    }
    return line_number;
  }
  
  function getFirstLineNumber() {
    var line_number = null;
    if ( $('.lineText').length > 0 ) {
      $('.lineText').each(function(i){
        if ( $(this).data('line-number') < line_number || line_number === null ) {
          line_number = $(this).data('line-number');
        }
      });
    }
    return line_number;
  }
  
  function getNextText() {
    if ( disableBufferPulls ) {
      return;
    }
    disableBufferPulls = true;
    var line_number = getLastLineNumber();
    
    $.ajax({
      url: '/API/recipe_process/ajax-json/get_next_text_packet.json.php',
      type: 'GET',
      data: {
        last_line_number: line_number
      },
      success: function( response ) {
        if ( response.length === 0 ) {
          console.log('0 length response');
          return;
        }
        
        var pId = $('.raw-text-block').length + 1;
        while ( $('#rawText'+pId).length > 0 ) {
          pId++;
        }
        
        $('#divRawTextWrapper').append('<div id="rawText'+pId+'" class="raw-text-block next-block" data-pid="'+pId+'" ></div>');
        var $packet = $('#rawText'+pId);
        $packet.append('<div class="row"></div>');
        $packet.append( '<hr>' );
        
        var $main = $('#rawText'+pId+' > .row');
        $main.append('<div class="textWrap one-half"></div>');
        $main.append('<div class="textTools one-half"></div>');
        
        var $tools = $('#rawText'+pId+' .textTools');
        var $text = $('#rawText'+pId+' .textWrap');
        renderTextTools( $tools, false );
        
        for ( var i = 0; i < response.length; i++ ) {
          $text.append( '<span class="lineText '+( $.trim(response[i].line_text).length === 0 ? 'hidden' : '' )+'" data-line-number="'+response[i].line_number+'" >'+response[i].line_text+'</span>'+( $.trim(response[i].line_text).length === 0 ? '' : '<br>' ) );
        }
      },
      complete: function() {
        disableBufferPulls = false;
      }
    });
  }
  
  function getPrevText() {
    if ( disableBufferPulls ) {
      return;
    }
    disableBufferPulls = true;
    
    var line_number = getFirstLineNumber();
    
    $.ajax({
      url: '/API/recipe_process/ajax-json/get_prev_text_packet.json.php',
      type: 'GET',
      data: {
        first_line_number: line_number
      },
      success: function( response ) {
        if ( response.length === 0 ) {
          console.log('0 length response');
          return;
        }
        
        var pId = 1;
        while ( $('#rawText'+pId).length > 0 ) {
          pId--;
        }
        
        $('#divRawTextWrapper').prepend('<div id="rawText'+pId+'" class="raw-text-block prev-block" data-pid="'+pId+'" ></div>');
        var $packet = $('#rawText'+pId);
        /*$packet.append( '<hr>' );
        $packet.append('<div class="textWrap"></div>');
        var $text = $('#rawText'+pId+' .textWrap');
        var $tools = $('#rawText'+pId+' .textTools');
        renderTextTools( $tools, true );*/
        $packet.append('<div class="row"></div>'); 
        
        var $main = $('#rawText'+pId+' > .row');
        $main.append('<div class="textWrap two-third"></div>');
        $main.append('<div class="textTools one-third"></div>');
        
        var $tools = $('#rawText'+pId+' .textTools');
        var $text = $('#rawText'+pId+' .textWrap');
        renderTextTools( $tools, true );
        
        for ( var i = 0; i < response.length; i++ ) {
          $text.append( '<span class="lineText '+( $.trim(response[i].line_text).length === 0 ? 'hidden' : '' )+'" data-line-number="'+response[i].line_number+'" >'+response[i].line_text+'</span>'+( $.trim(response[i].line_text).length === 0 ? '' : '<br>' ) );
        }
        
        $packet.append( '<hr>' );
      }
      ,complete: function() {
        disableBufferPulls = false;
      }
    });
  }
  
  function renderTextTools( $divTools, $prev_flag ) {
    $divTools.append('<span class="button buttonIngrParse" >Parse Ingredients</span> ');
    $divTools.append('<span class="button buttonInstr" >Copy to Instructions</span> ');
    $divTools.append('<div class="main-tools" ></div>');
    $divTools.children('.main-tools').append('<span class="button buttonPushBack buttonMain" >Push back to buffer</span>');
    if ( !$prev_flag ) {
      $divTools.children('.main-tools').append(' <span class="button buttonMarkComplete buttonMain" >Mark as Complete</span>');
    }
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
  
  function resetRecipeParentSelect() {
    $('#rParent').empty();
    $.ajax({
      url: '/API/recipe_process/ajax-json/get_recipe_titles.json.php',
      type: 'GET',
      success:  function(recipes) {
        //console.log(recipes.length);
        for ( var i = 0; i < recipes.length; i++ ) {
          $('#rParent').append('<option value="'+recipes[i].id+'" >'+recipes[i].title+'</option>');
        }
        $('#rParent').val(0);
      }
    });
  }
  
  function resetIngredientDatalist() {
    $('#IngredientsList').empty();
    $.ajax({
      url: '/API/recipe_process/ajax-json/get_ingredients.json.php',
      type: 'GET',
      success:  function(ingredients) {
        //console.log(recipes.length);
        for ( var i = 0; i < ingredients.length; i++ ) {
          $('#IngredientsList').append('<option value="'+ingredients[i].name+'" >');
        }
      }
    });
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
    $('#rInstr').val('');
  }
  
  $(document).ready(function(){
    getNextText();
    resetRecipeParentSelect();
    resetIngredientDatalist();
    
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
    
    $('.buttonNextText').click(function(){
      getNextText();
    });
    
    $('.buttonPrevText').click(function(){
      getPrevText();
    });
    
    $('#divRawTextWrapper').on('click', '.buttonIngrParse', function(){
      var pId = $(this).parent().parent().parent().data('pid');
      
      var $lines = $('#rawText'+pId+' .textWrap > .lineText:not(.hidden)');
      
      $.each( $lines, function(i, line){
        var words = $(line).text().split(' ');
        var amount = '';
        var unit_id = '';
        var name = '';
        
        for ( var j = 0; j < words.length; j++ ) {
          if ( words[j].length === 0 ) {
            words.splice(j, 1);
            j--;
          }
        }
        
        var k = 0;
        while ( k < words.length ) {
          if ( words[k].match(/[A-Z]/i) === null ) {
            amount = $.trim( amount + ' ' + words[k] );
            k++;
          } else {
            break;
          }
        }
        if ( k < words.length ) {
          words[k] = words[k].replace('.', '').toLowerCase();
          if ( words[k][words[k].length - 1] == 's' ) {
            words[k] = words[k].substring(0, words[k].length - 1);
          }
          if ( valueOfDataListByClass( "ingrUnitList", words[k] ) !== false ) {
            unit_id = valueOfDataListByClass( "ingrUnitList", words[k] );
            k++;
          }
        }
        while ( k < words.length ) {
          name = $.trim( name + ' ' + words[k] );
          k++;
        }
        
        addIngredient( amount, unit_id, name );
      });
      
    });
    
    $('#divRawTextWrapper').on('click', '.buttonInstr', function(){
      var pId = $(this).parent().parent().parent().data('pid');
      //console.log(pId);
      
      var $lines = $('#rawText'+pId+' .textWrap > .lineText:not(.hidden)');
      var packetText = '';
      
      $.each( $lines, function(i, line){
        packetText = packetText + $(line).text() + ' ';
      });
      
      $('#rInstr').val( packetText );
    });
    
    $('#divRawTextWrapper').on('click', '.buttonPushBack', function(){
      var pId = $(this).parent().parent().parent().parent().data('pid');
      $('#rawText'+pId).remove();
    });
    
    $('#divRawTextWrapper').on('click', '.buttonMarkComplete', function(){
      var pId = $(this).parent().parent().parent().parent().data('pid');
      
      var $lines = $('#rawText'+pId+' .textWrap > .lineText');
      var lineNumArray = [];
      
      $.each( $lines, function(i, line){
        lineNumArray.push($(line).data('line-number'));
      });
      
      // set packet lines cleared_flag = 1
      $.ajax({
        url: '/API/recipe_process/ajax-json/mark_text_packet_as_complete.json.php',
        type: 'GET',
        data: {
          lineNumbers: lineNumArray
        },
        success: function( response ){
          console.log(response);
          if ( response.success == 1 ) {
            // success
            $('#rawText'+pId).remove();
            
            if ( $('.raw-text-block').length === 0 ) {
              getNextText();
            }
          }
        }
      });
      
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
            } else {
              alert('An error occurred in uploading the file!');
            }
          };
          // Send the Data.
          xhr.send(formData);
        }
      }
      
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
        id: 0,
        title: $('#rTitle').val(),
        chef_name: $('#rChef').val(),
        recipe_type_name: $('#rType').val(),
        course_id: $('#rCourse').val(),
        serving_count: ( $('#rServingCount').val() == '' ? 0 : fCnt.decimal ),
        serving_measure_id: $('#rServingUnit').val(),
        instructions: $('#rInstr').val(),
        ingredientsList: oIngrList,
        image_filename: ( typeof file !== 'undefined' ? 'assets/images/recipes/'+file.name : 'assets/images/recipes/generic_recipe.jpg' ),
        parent_recipe_id: $('#rParent').val()
      }
      console.log(oRecipe);
      
      $.ajax({
        url: '/API/recipe_process/ajax-json/recipe_submit.json.php',
        type: 'GET',
        data: oRecipe,
        success: function( response ) {
          console.log(response);
          if ( response.success == 1 ) {
            alert('Success');
            resetForm();
            resetRecipeParentSelect();
            resetIngredientDatalist();
          } else {
            console.log(response.err_msg);
          }
        }
      });
      
    });
    
  });
  
</script>
<?php
require_once("../../_footer.php");
?>
