<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

if ( @$App->R['action'] == 'combine' ) {
  $types = explode(",", $App->R['ingredients_str']);
  $new_id = min($ingredients);
  $ingredients = array_diff($ingredients, [$new_id]);
  $App->R['ingredients_str'] = implode(",", $ingredients);
  
  $upd_query = "
    UPDATE RecipeIngredient
    SET ingredient_id = ".$new_id."
    WHERE ingredient_id IN(".$App->R['ingredients_str'].");
  ";
  $App->oDBMY->execute($upd_query);
  
  $del_query = "
    DELETE FROM Ingredient
    WHERE id IN(".$App->R['ingredients_str'].");
  ";
  $App->oDBMY->execute($del_query);
  
  $upd_query = "
    UPDATE Ingredient
    SET name = '".$App->R['name']."',
    name_plural = '".$App->R['name_plural']."'
    WHERE id = ".$new_id."
  ";
  $App->oDBMY->execute($upd_query);
  
} elseif ( @$App->R['action'] == 'close' ) {
  // nothing
} else {
  
  $sel_query = "
    SELECT * FROM Ingredient WHERE id IN (".implode(",", $App->R['ingredients']).");
  ";
  //wl($sel_query);
  $result = $App->oDBMY->query($sel_query);
  $Ingr = array();
  while ( $row = $result->fetch_assoc() ) {
    array_push($Ingr, $row);
  }
  ?>

  <div class="row">
    <div class="container">
      <h3>Combine</h3>
      <table class="table table-striped">
        <tbody>
          <?php
          for ( $i = 0; $i < count($Ingr); $i++ ) {
            ?>
            <tr><td><?=$Ingr[$i]['name']?></td><td><?=$Ingr[$i]['name']?></td></tr>
            <?php
          }
          ?>
        </tbody>
      </table>

      <input type="hidden" id="ingredients_str" value="<?=implode(",", $App->R['ingredients'])?>" >
      <div class="f-row">
        <label for="new_name">Name</label>
        <input type="text" class="form-control" id="new_name" name="new_name" placeholder="Name" autocomplete="off" value="<?=str_replace('"', '&QUOT;', $Ingr[0]['name'])?>" required />
      </div>
      <div class="f-row">
        <label for="new_name">Name Plural</label>
        <input type="text" class="form-control" id="new_name_plural" name="new_name_plural" placeholder="Name Plural" autocomplete="off" value="<?=str_replace('"', '&QUOT;', $Ingr[0]['name_plural'])?>" required />
      </div>
      <div class="f-row bwrap">
        <span class="button btn-block" id="finalize" >FINALIZE</span>
        <span class="button btn-block" id="close" >Close</span>
      </div>
    </div>
  </div>
  <script>
    $(function(){
      $('#finalize').click(function(){
        LoadDivContent('recipe_process/ingredient_combine', '', 'divEditIngr', {
          action: 'combine',
          ingredients_str: $('#ingredients_str').val(),
          name: $('#new_name').val(),
          name_plural: $('#new_name_plural').val()
        });
      });
      
      $('#close').click(function(){
        LoadDivContent('recipe_process/ingredient_combine', '', 'divEditIngr', {
          action: 'close'
        });
      });
    });
  </script>
  <?php
}

$App = "";
?>