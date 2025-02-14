<?php
$App = "";
$skip_session_create = 1;
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close(); 

$bodyClass = "recipePage";
require_once("../../_head.php");

if ( @$App->R['submit'] == "Submit" ) {
  //wla($App->R);
  
  /*$dbWrite = array();
  $keys = array_keys($App->R);
  for ( $i = 0; $i < count($keys); $i++ ) {
    $wIngr = array();
    if ( str_begins($keys[$i], "write") ) {
      $wIngr['id'] = trim($keys[$i], "write");
      $wIngr['name'] = $App->R["name".$wIngr['id']];
      $wIngr['name_plural'] = $App->R["pluralname".$wIngr['id']];
      $wIngr['prep'] = $App->R["prep".$wIngr['id']];
      array_push($dbWrite, $wIngr);
    }
  }
  
  //wla($dbWrite);
  $sCount = 0;
  for ( $i = 0; $i < count($dbWrite); $i++ ) {
    $updI = "
      Call spIngredientUpdate(
        0,
        '".$App->oDBMY->escape_string(trim($dbWrite[$i]['name']))."',
        '".$App->oDBMY->escape_string(trim($dbWrite[$i]['name_plural']))."'
      );
    ";
    $result = $App->oDBMY->query($updI);
    if ( !!$result ) {
      $sCount++;
      $newI = $result->fetch_assoc();
      $result->free();
      
      $updOld = "
        UPDATE IngredientOld
        SET new_id = ".$newI['id']."
        WHERE id = ".$dbWrite[$i]['id']."
        OR (
          (
            (CASE WHEN locate(',',name) = 0 THEN name ELSE SUBSTRING(name, 1, locate(',',name) - 1) END) = '".$App->oDBMY->escape_string(trim($dbWrite[$i]['name']))."'
            OR (CASE WHEN locate(',',name) = 0 THEN name ELSE SUBSTRING(name, 1, locate(',',name) - 1) END) = '".$App->oDBMY->escape_string(trim($dbWrite[$i]['name_plural']))."'
          )
          AND new_id = 0
        );
      ";
      $App->oDBMY->execute($updOld);
      
      $updRecipeIngredient = "
        UPDATE RecipeIngredient
        SET ingredient_id = ".$newI['id'].",
        ingredient_prep = '".trim($dbWrite[$i]['prep'])."'
        WHERE old_ingredient_id = ".$dbWrite[$i]['id'].";
      ";
      $App->oDBMY->execute($updRecipeIngredient);
      
      $updRecipeIngredient2 = "
        UPDATE RecipeIngredient ri
          SET ri.ingredient_id = (
          SELECT new_id
          FROM IngredientOld i
          WHERE i.id = ri.old_ingredient_id
          AND new_id > 0
        )
        WHERE EXISTS(
          SELECT new_id
          FROM IngredientOld i
          WHERE i.id = ri.old_ingredient_id
          AND new_id > 0
        )
        AND ri.ingredient_id = 0
      ";
      $App->oDBMY->execute($updRecipeIngredient2);
      
      $updRecipeIngredient3 = "
        UPDATE RecipeIngredient ri
        SET ri.ingredient_prep = (
          SELECT CASE WHEN locate(',',i.name) = 0 THEN '' ELSE TRIM(SUBSTRING(i.name, locate(',',i.name) + 1)) END
          FROM IngredientOld i
          WHERE i.id = ri.old_ingredient_id
        )
        WHERE ri.ingredient_prep IS NULL
        AND ri.ingredient_id > 0
      ";
      $App->oDBMY->execute($updRecipeIngredient3);
      
    }
  }
  
  ?>
  <div class="alert alert-info">
    Success updating <?=$sCount?> recipes  
  </div>
  <?php*/
}

$sel_query = "
  SELECT
    rt.id,
    rt.name recipe_type,
		rt.icon,
		(
			SELECT count(1) FROM Recipe r
			WHERE r.type_id = rt.id
		) recipe_count
  FROM `RecipeType` rt
  ORDER BY 
    (
			SELECT count(1) FROM Recipe r
			WHERE r.type_id = rt.id
		) ASC;
";
$result = $App->oDBMY->query( $sel_query );
$Type = array();
while ( $row = $result->fetch_assoc() ) {
  array_push($Type, $row);
}
$result->free();

?>
<style>
  table input[type=text] {
    width: 100%;
  }
</style>
<!--wrap-->
<div class="wrap clearfix">
  <header class="s-title">
    <h1>Recipe Type Redux</h1>
  </header>
  
  <section id="frmRecipeTypeRedux" class="three-fourth">
      
		<table id="ingTable" class="ctable table dataTable">
			<thead>
				<tr>
					<th>Type</th>
					<th>Icon</th>
					<th>Count</th>
					<th>Combine</th>
					<th>Action</th>
				</tr>
			</thead>
			<tbody>
				<?php
				for ( $i = 0; $i < count($Type); $i++ ) {
					?>
					<tr>
						<td>
							<?=$Type[$i]['recipe_type']?>
						</td>
						<td style="font-size: 22px;" >
							<i class="icon <?=$Type[$i]['icon']?>"></i>
						</td>
						<td>
							<?=$Type[$i]['recipe_count']?>
						</td>
						<td>
							<label for="combine<?=$Type[$i]['id']?>">
								<input type="checkbox" id="combine<?=$Type[$i]['id']?>" class="combine-check" data-id="<?=$Type[$i]['id']?>" value=1 >
							</label>
						</td>
						<td>
							<span class="btn btn-sm btn-primary btnTypeEdit" data-id="<?=$Type[$i]['id']?>" ><i class="fa fa-edit"></i></span>
							<span class="btn btn-sm btn-success btnTypeView" data-id="<?=$Type[$i]['id']?>" ><i class="fa fa-search"></i></span>
						</td>
					</tr><?php
				}
				?>
			</tbody>
		</table>
		<span id="btnCombine" class="button" >
			Combine
		</span>
  </section>
  
  <aside id="divEditType" class="one-fourth">
  </aside>
  
</div>
<!--//wrap-->
<script>
  $(function(){
    
    $('.dataTable').dataTable({
      ordering: false
    });
    
    $('.paginate_button').click(function(){
      $('input[type=checkbox]').iCheck({
				checkboxClass: 'icheckbox_flat-blue',
        increaseArea: '20%' // optional
			});
    });
    
		$('#frmRecipeTypeRedux').on('click', '.btnTypeEdit', function(){
      LoadDivContent('recipe_process/recipe_type_edit', '', 'divEditType', { recipe_type_id: $(this).data('id') } );
    });
		
		$('#frmRecipeTypeRedux').on('click', '.btnTypeView', function(){
      LoadDivContent('recipe_process/recipe_type_view', '', 'divEditType', { recipe_type_id: $(this).data('id') } );
    });
		
		$('#btnCombine').click(function(){
			$('#divEditType').empty();
			var type = [];
			$.each( $('.combine-check:checked'), function(i, el){
				type.push( $(el).data('id') );
			});
			if ( type.length > 1 ) {
      	LoadDivContent('recipe_process/recipe_type_combine', '', 'divEditType', { recipe_types: type } );
			}
    });
		
  });
</script>
<?php
require_once("../../_footer.php");
?>
