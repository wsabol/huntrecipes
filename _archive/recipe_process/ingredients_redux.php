<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$bodyClass = "recipePage";
require_once("../../_head.php");

if (@$App->R['submit'] == "Submit") {
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
    i.id,
    i.name,
    i.name_plural,
    CASE WHEN i.name_plural = '' THEN 1
    ELSE 0 END incomplete_flag,
    CASE WHEN EXISTS(
      SELECT * FROM Ingredient x
      WHERE x.id != i.id
      AND (
        x.name = i.name
        OR x.name_plural = i.name_plural
      )
    ) THEN 1 ELSE 0 END duplicate_flag,
		IFNULL((
			SELECT count(1) FROM RecipeIngredient riri
			WHERE riri.ingredient_id = i.id
		),0) recipe_count
  FROM `Ingredient` i
  ORDER BY 
    CASE WHEN i.name_plural = '' THEN 1
    ELSE 0 END DESC,
    i.name ASC;
";
$result = $App->oDBMY->query($sel_query);
$Ingr = array();
while ($row = $result->fetch_assoc()) {
    array_push($Ingr, $row);
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
        <h1>Ingredients Redux</h1>
    </header>

    <section id="frmIngredientsRedux" class="three-fourth">

        <table id="ingTable" class="ctable table dataTable">
            <thead>
            <tr>
                <th>Name</th>
                <th>Plural</th>
                <th>#</th>
                <th>Combine</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php
            for ($i = 0; $i < count($Ingr); $i++) {
                ?>
            <tr class="<?= ($Ingr[$i]['incomplete_flag'] == 1 ? "warning" : ($Ingr[$i]['duplicate_flag'] == 1 ? "danger" : ($Ingr[$i]['recipe_count'] == 0 ? "info" : ""))) ?>">
                <td>
                    <?= $Ingr[$i]['name'] ?> <?= ($Ingr[$i]['incomplete_flag'] == 1 ? "INCM" : "") ?> <?= ($Ingr[$i]['duplicate_flag'] == 1 ? "DUPL" : "") ?> <?= ($Ingr[$i]['recipe_count'] == 0 ? "NORCP" : "") ?>
                </td>
                <td>
                    <?= $Ingr[$i]['name_plural'] ?>
                </td>
                <td>
                    <?= $Ingr[$i]['recipe_count'] ?>
                </td>
                <td>
                    <label for="combine<?= $Ingr[$i]['id'] ?>">
                        <input type="checkbox" id="combine<?= $Ingr[$i]['id'] ?>" class="combine-check"
                               data-id="<?= $Ingr[$i]['id'] ?>" value=1>
                    </label>
                </td>
                <td>
                    <span class="btn btn-sm btn-primary btnIngrEdit" data-id="<?= $Ingr[$i]['id'] ?>"><i
                            class="fa fa-edit"></i></span>
                    <span class="btn btn-sm btn-success btnIngrView" data-id="<?= $Ingr[$i]['id'] ?>"><i
                            class="fa fa-search"></i></span>
                </td>
                </tr><?php
            }
            ?>
            </tbody>
        </table>
        <span id="btnCombine" class="button">
			Combine
		</span>
    </section>

    <aside id="divEditIngr" class="one-fourth">
    </aside>

</div>
<!--//wrap-->
<script>
    $(function () {

        $('.dataTable').dataTable({
            ordering: false
        });

        $('.paginate_button').click(function () {
            $('input[type=checkbox]').iCheck({
                checkboxClass: 'icheckbox_flat-blue',
                increaseArea: '20%' // optional
            });
        });

        $('#frmIngredientsRedux').on('click', '.btnIngrEdit', function () {
            LoadDivContent('recipe_process/ingredient_edit', '', 'divEditIngr', {ingredient_id: $(this).data('id')});
        });

        $('#frmIngredientsRedux').on('click', '.btnIngrView', function () {
            LoadDivContent('recipe_process/ingredient_view', '', 'divEditIngr', {ingredient_id: $(this).data('id')});
        });

        $('#btnCombine').click(function () {
            $('#divEditIngr').empty();
            var ingr = [];
            $.each($('.combine-check:checked'), function (i, el) {
                ingr.push($(el).data('id'));
            });
            if (ingr.length > 1) {
                LoadDivContent('recipe_process/ingredient_combine', '', 'divEditIngr', {ingredients: ingr});
            }
        });

    });
</script>
<?php
require_once("../../_footer.php");
?>
