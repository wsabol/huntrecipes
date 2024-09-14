<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$bodyClass = "recipePage";
require_once("../../_head.php");

if (@$App->R['submit'] == "Submit") {
    $dbWrite = array();
    $keys = array_keys($App->R);
    for ($i = 0; $i < count($keys); $i++) {
        $wRecipe = array();
        if (str_starts_with($keys[$i], "write")) {
            $wRecipe['id'] = trim($keys[$i], "write");
            $wRecipe['serving_count'] = $App->R["servings" . $wRecipe['id']];
            $wRecipe['serving_measure_id'] = $App->R["unit" . $wRecipe['id']];
            array_push($dbWrite, $wRecipe);
        }
    }

    //wla($dbWrite);
    $sCount = 0;
    for ($i = 0; $i < count($dbWrite); $i++) {
        $upd_query = "
      UPDATE Recipe
      SET serving_count = " . $dbWrite[$i]['serving_count'] . ",
      serving_measure_id = " . $dbWrite[$i]['serving_measure_id'] . "
      WHERE id = " . $dbWrite[$i]['id'] . ";
    ";
        if ($App->oDBMY->execute($upd_query)) {
            $sCount++;
        }
    }

    ?>
    <div class="alert alert-info">
        Success updating <?= $sCount ?> recipes
    </div>
    <?php
}

$sel_query = "
  SELECT id, title, instructions FROM `Recipe`
  WHERE serving_count = 0
  LIMIT 50;
";
$result = $App->oDBMY->query($sel_query);
$recipes = array();
while ($row = $result->fetch_assoc()) {
    array_push($recipes, $row);
}
$result->free();

$sel_query = "
  SELECT id, name, abbr FROM `Measure` ORDER BY name
";
$result = $App->oDBMY->query($sel_query);
$units = array();
while ($row = $result->fetch_assoc()) {
    array_push($units, $row);
}
$result->free();

?>
<!--wrap-->
<div class="wrap clearfix">
    <header class="s-title">
        <h1>Servings Redux</h1>
    </header>

    <form id="frmServingsRedux" action="/API/v0/recipe_process/servings_redux.php" method="post">

        <table class="ctable table">
            <thead>
            <tr>
                <th>Recipe Id</th>
                <th>Title</th>
                <th>Instructions</th>
                <th>Cnt</th>
                <th>Unit</th>
                <th>Write?</th>
            </tr>
            </thead>
            <tbody>
            <?php
            for ($i = 0; $i < count($recipes); $i++) {
                $propServing = 0;
                $propUnit = "";
                if (strpos(strtoupper($recipes[$i]['instructions']), "MAKES") > 0 || strpos(strtoupper($recipes[$i]['instructions']), "YIELD") > 0) {
                    $words = explode(" ", $recipes[$i]['instructions']);
                    for ($j = count($words) - 1; $j >= 0; $j--) {
                        if (is_numeric(trim($words[$j], " .,"))) {
                            $propServing = trim($words[$j], " .,");
                            $propUnit = trim(@$words[$j + 1], " .,");
                            break;
                        }
                    }
                }
                ?>
                <tr>
                <td><?= $recipes[$i]['id'] ?></td>
                <td><?= $recipes[$i]['title'] ?></td>
                <td><?= str_replace(chr(10), "<br>", $recipes[$i]['instructions']) ?></td>
                <td>
                    <input type="text" name="servings<?= $recipes[$i]['id'] ?>" value="<?= $propServing ?>">
                </td>
                <td>
                    <select name="unit<?= $recipes[$i]['id'] ?>">
                        <?php
                        for ($k = 0; $k < count($units); $k++) {
                            $selected = "";
                            if ($propUnit != "" &&
                                ($units[$k]['name'] == $propUnit || $units[$k]['name'] . "s" == $propUnit || $units[$k]['abbr'] == $propUnit)) {
                                $selected = "selected";
                            }
                            ?>
                            <option
                            value="<?= $units[$k]['id'] ?>" <?= $selected ?> ><?= $units[$k]['name'] ?></option><?php
                        }
                        ?>
                    </select>
                </td>
                <td>
                    <label for="write<?= $recipes[$i]['id'] ?>">
                        <input type="checkbox" name="write<?= $recipes[$i]['id'] ?>" value=1>
                    </label>
                </td>
                </tr><?php
            }
            ?>
            </tbody>
        </table>
        <button class="button" type="submit" name="submit" value="Submit">
            Submit
        </button>
        <button class="button pull-right" type="submit" name="submit" value="Refresh">
            Refresh
        </button>
    </form>
</div>
<!--//wrap-->
<?php
require_once("../../_footer.php");
?>
