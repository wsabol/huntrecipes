<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

  
$sel_query = "
  SELECT
    r.id recipe_id,
    r.title
  FROM Recipe r
  WHERE r.type_id =".$App->R['recipe_type_id'].";
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
    <h3>Recipes</h3>
    <table class="table table-striped">
      <tbody>
        <?php
        for ( $i = 0; $i < count($Type); $i++ ) {
          ?>
          <tr><td><?=$Type[$i]['recipe_id']?></td><td><a href="/recipe.php?recipe_id=<?=$Type[$i]['recipe_id']?>" target="_blank"><?=$Type[$i]['title']?></a></td></tr>
          <?php
        }
        ?>
      </tbody>
    </table>
    
    <div class="f-row bwrap">
      <span class="button btn-block" id="close" >Close</span>
    </div>
  </div>
</div>
<script>
  $(function(){
    $('#close').click(function(){
      $('#divEditType').empty();
    });
  });
</script>
<?php

$App = "";
?>