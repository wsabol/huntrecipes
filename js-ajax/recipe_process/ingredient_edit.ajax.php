<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

if ( @$App->R['action'] == 'edit' ) {
  $upd_query = "
    UPDATE Ingredient
    SET name = '".$App->oDBMY->prepstring($App->R['name'])."',
    name_plural = '".$App->oDBMY->prepstring($App->R['name_plural'])."'
    WHERE id = ".$App->R['id']."
  ";
  $App->oDBMY->execute($upd_query);
  
} elseif ( @$App->R['action'] == 'delete' ) {
  $del_query = "
    DELETE FROM Ingredient
    WHERE id = ".$App->R['id']."
  ";
  $App->oDBMY->execute($del_query);
  
} elseif ( @$App->R['action'] == 'close' ) {
  // nothing
} else {
  
  $sel_query = "
    SELECT * FROM Ingredient WHERE id = ".$App->R['ingredient_id'].";
  ";
  //wl($sel_query);
  $result = $App->oDBMY->query($sel_query);
  $Ingr = $result->fetch_assoc();
  ?>

  <div class="row">
    <div class="container">
      <h3>Edit</h3>
      <input type="hidden" id="ingredient_id" value="<?=$App->R['ingredient_id']?>" >
      <div class="f-row">
        <label for="new_name">Name</label>
        <input type="text" class="form-control" id="new_name" name="new_name" placeholder="Name" autocomplete="off" value="<?=str_replace('"', '&QUOT;', $Ingr['name'])?>" required />
      </div>
      <div class="f-row">
        <label for="new_name">Name Plural</label>
        <input type="text" class="form-control" id="new_name_plural" name="new_name_plural" placeholder="Name Plural" autocomplete="off" value="<?=str_replace('"', '&QUOT;', $Ingr['name_plural'])?>" required />
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
        LoadDivContent('recipe_process/ingredient_edit', '', 'divEditIngr', {
          action: 'edit',
          id: $('#ingredient_id').val(),
          name: $('#new_name').val(),
          name_plural: $('#new_name_plural').val()
        });
      });
      
      $('#close').click(function(){
        LoadDivContent('recipe_process/ingredient_edit', '', 'divEditIngr', {
          action: 'close'
        });
      });
      
      $('#delete').click(function(){
        LoadDivContent('recipe_process/ingredient_edit', '', 'divEditIngr', {
          action: 'delete',
          id: $('#ingredient_id').val()
        });
      });
    });
  </script>
  <?php
}

$App = "";
?>