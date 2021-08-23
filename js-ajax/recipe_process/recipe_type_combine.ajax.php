<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

if ( @$App->R['action'] == 'combine' ) {
  $types = explode(",", $App->R['types_str']);
  $new_id = min($types);
  $types = array_diff($types, [$new_id]);
  $App->R['types_str'] = implode(",", $types);
  
  $upd_query = "
    UPDATE Recipe
    SET type_id = ".$new_id."
    WHERE type_id IN(".$App->R['types_str'].");
  ";
  $App->oDBMY->execute($upd_query);
  
  $del_query = "
    DELETE FROM RecipeType
    WHERE id IN(".$App->R['types_str'].");
  ";
  $App->oDBMY->execute($del_query);
  
  $upd_query = "
    UPDATE RecipeType
    SET name = '".$App->oDBMY->prepstring($App->R['name'])."',
    icon = '".$App->oDBMY->prepstring($App->R['icon'])."'
    WHERE id = ".$new_id."
  ";
  $App->oDBMY->execute($upd_query);
  
} elseif ( @$App->R['action'] == 'close' ) {
  // nothing
} else {
  
  $sel_query = "
    SELECT * FROM RecipeType WHERE id IN (".implode(",", $App->R['recipe_types']).");
  ";
  //wl($sel_query);
  $result = $App->oDBMY->query($sel_query);
  $Type = array();
  while ( $row = $result->fetch_assoc() ) {
    array_push($Type, $row);
  }
  ?>

  <div class="row">
    <div class="container">
      <h3>Combine</h3>
      <table class="table table-striped">
        <tbody>
          <?php
          for ( $i = 0; $i < count($Type); $i++ ) {
            ?>
            <tr><td><?=$Type[$i]['name']?></td><td><?=$Type[$i]['icon']?></td></tr>
            <?php
          }
          ?>
        </tbody>
      </table>

      <input type="hidden" id="types_str" value="<?=implode(",", $App->R['recipe_types'])?>" >
      <div class="f-row">
        <label for="new_name">Name</label>
        <input type="text" class="form-control" id="new_name" name="new_name" placeholder="Name" autocomplete="off" value="<?=str_replace('"', '&QUOT;', $Type[0]['name'])?>" required />
      </div>
      <div class="f-row">
        <label for="new_icon">Icon</label>
        <input type="text" class="form-control" id="new_icon" name="new_icon" placeholder="icon-themeenergy_" autocomplete="off" value="<?=$Type[0]['icon']?>" required />
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
        LoadDivContent('recipe_process/recipe_type_combine', '', 'divEditType', {
          action: 'combine',
          types_str: $('#types_str').val(),
          name: $('#new_name').val(),
          icon: $('#new_icon').val()
        });
      });
      
      $('#close').click(function(){
        LoadDivContent('recipe_process/recipe_type_combine', '', 'divEditType', {
          action: 'close'
        });
      });
    });
  </script>
  <?php
}

$App = "";
?>