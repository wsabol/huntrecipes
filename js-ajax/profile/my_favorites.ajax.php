<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');
@session_write_close();

$sel_query = "
  Call spSelectLoginFavorites('".$_SESSION['Login']['id']."');
";
$results = array();
$r = $App->oDBMY->query( $sel_query );
while ( $row = $r->fetch_assoc() ) {
  array_push($results, $row);
}

if ( count($results) === 0 ) {
  ?>
  <div class="alert alert-banner">
    No favorites yet.
  </div>
  <?php
} else {
  ?>
  <!--entries-->
  <div class="content row">
    <div id="profile-favorites" class="entries full-width _column-entries _column-entries-three">
      <?php
      for ( $i = 0; $i < count($results); $i++ ) {
        ?>
        <!--item-->
        <div class="entry one-third _column-entry">
          <figure>
            <img src="<?=$results[$i]['image_filename']?>" alt="" />
            <figcaption><a href="recipe.php?recipe_id=<?=$results[$i]['id']?>"><i class="icon icon-themeenergy_eye2"></i> <span>View recipe</span></a></figcaption>
          </figure>
          <div class="container">
            <h2><a href="recipe.php?recipe_id=<?=$results[$i]['id']?>"><?=htmlspecialchars($results[$i]['title'])?></a></h2> 
            <div class="actions">
              <div data-recipe-id="<?=$results[$i]['id']?>" >
                <div class="likes divSaveToFavorites <?=( $results[$i]['favorite_flag'] == 1 ? "favorite-recipe" : "" )?>"><i class="fa fa-heart"></i><span class="favorite-count"><?=$results[$i]['favorite_count']?></span></div>
              </div>
            </div>
          </div>
        </div>
        <!--item-->
        <?php
      }
      ?>
    </div>
  </div>
  <!--//entries-->
  <script>
    $(function(){
      
      $('#profile-favorites').on('click', '.btnSaveToMealsWeeksMenu', function(e){
        var recipe_id = $(this).parent().parent().parent().data('recipe-id');
        //console.log(recipe_id);
        LoadDivContent('weeks_for_meals_dropdown', '', 'r'+recipe_id+'WeekMenu', { recipe_id: recipe_id });
      });

    });
  </script>
  <?php
}

$App = "";
?>
