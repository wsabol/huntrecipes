<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

if ( @$App->R['action'] == 'edit' ) {
  $upd_query = "
    UPDATE RecipeType
    SET name = '".$App->oDBMY->prepstring($App->R['name'])."',
    icon = '".$App->oDBMY->prepstring($App->R['icon'])."'
    WHERE id = ".$App->R['id']."
  ";
  $App->oDBMY->execute($upd_query);
  
} elseif ( @$App->R['action'] == 'delete' ) {
  $del_query = "
    DELETE FROM RecipeType
    WHERE id = ".$App->R['id']."
  ";
  $App->oDBMY->execute($del_query);
  
} elseif ( @$App->R['action'] == 'close' ) {
  // nothing
} else {
  
  $sel_query = "
    SELECT * FROM RecipeType WHERE id = ".$App->R['recipe_type_id'].";
  ";
  //wl($sel_query);
  $result = $App->oDBMY->query($sel_query);
  $Type = $result->fetch_assoc();
  ?>

  <div class="row">
    <div class="container">
      <h3>Edit</h3>
      <input type="hidden" id="recipe_type_id" value="<?=$App->R['recipe_type_id']?>" >
      <div class="f-row">
        <label for="new_name">Name</label>
        <input type="text" class="form-control" id="new_name" name="new_name" placeholder="Name" autocomplete="off" value="<?=str_replace('"', '&QUOT;', $Type['name'])?>" required />
      </div>
      <div class="f-row">
        <label for="new_name">Icon</label>
        <input type="text" class="form-control" id="new_icon" name="new_icon" placeholder="icon-themeenergy_" autocomplete="off" value="<?=$Type['icon']?>" required />
      </div>
      <div class="f-row bwrap">
        <span class="button btn-block" id="finalize" >FINALIZE</span>
        <span class="button btn-block" id="close" >Close</span>
        <span class="button btn-block" id="delete" >Delete</span>
      </div>
    </div>
  </div>
  <script>
    $(function(){
      $('#finalize').click(function(){
        LoadDivContent('recipe_process/recipe_type_edit', '', 'divEditType', {
          action: 'edit',
          id: $('#recipe_type_id').val(),
          name: $('#new_name').val(),
          icon: $('#new_icon').val()
        });
      });
      
      $('#close').click(function(){
        LoadDivContent('recipe_process/recipe_type_edit', '', 'divEditType', {
          action: 'close'
        });
      });
      
      $('#delete').click(function(){
        LoadDivContent('recipe_process/recipe_type_edit', '', 'divEditType', {
          action: 'delete',
          id: $('#recipe_type_id').val()
        });
      });
    });
  </script>
  <?php
}

$App = "";
?>