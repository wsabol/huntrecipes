<?php
$App = "";
require_once('../../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('../../API/PHPMailer/sendemail.function.php');

if ( $_SESSION['Login']['chef_id']*1 === 0 ) {
  if ( @$App->R['action'] == "chef-apply" ) {
    $msgBody = '
      Login: '.print_r($_SESSION['Login'], true).'
      Reply Email: '.$App->R['email'].'
      Their Story: '.$App->R['story'].'
    ';
    //wl($msgBody);
    $additional_headers = "From: HuntRecipes < contact@willsabol.com >\n";
    $additional_headers .= "X-Sender: HuntRecipes < contact@willsabol.com >\n";
    $additional_headers .= 'X-Mailer: PHP/' . phpversion();
    $additional_headers .= "X-Priority: 1\n"; // Urgent message!
    $additional_headers .= "Return-Path: contact@willsabol.com\n"; // Return path for errors
    $additional_headers .= "MIME-Version: 1.0\r\n";
    $additional_headers .= "Content-Type: text/html; charset=iso-8859-1\n";
    
    $mailSuccess = SendEmail("contact@willsabol.com", "Will Sabol", "wsabol39@gmail.com", array(), array(), "", "", "HuntRecipes - Chef Application", $msgBody);
    if ( !$mailSuccess ) $mailSuccess = mail( 'wsabol39@gmail.com', "HuntRecipes - Chef Application", $msgBody, $additional_headers );
    
    $qUpdLogin = "
      UPDATE Login
      SET chef_app_pending = 1
      WHERE id = ".$_SESSION['Login']['id']."
    ";
    $App->oDBMY->execute( $qUpdLogin );
    $_SESSION['Login']['chef_app_pending'] = 1;
  }
  @session_write_close();
  ?>
  <div class="container box">
    <h3>Apply to be a Chef</h3>
    <? if ( $_SESSION['Login']['chef_app_pending'] == 0 ) { ?>
      <p>
        We'd love for more Hunt's to join the team of chefs here at HuntRecipes. Once you submit this application form,
        you'll be notified via email when the chef profile is setup and you'll be able to finish your chef profile and
        submit recipes all you want. So please tell us - How do you related to the Hunt's? How did you get here?
      </p>
      <div class="f-row" >
        <input name="email" class="form-control" type="email" placeholder="Email" value="<?=$_SESSION['Login']['email']?>" >
      </div>
      <div class="f-row" >
        <textarea class="form-control" name="story" data-field="story" rows="4" placeholder="How do you related to the Hunt's? How did you get here?" ></textarea>
      </div>
      <div class="f-row bwrap text-center">
        <span class="button" id="btnChefApplication" >
          Submit
        </span>
      </div>
    <? } else { ?>
      <p>
        Your application is in our inbox. You'll recieve an email soon!
      </p>
      <p>
        - Will
      </p>
    <? } ?>
  </div>
  <script>
    $(function(){
      $('#btnChefApplication').click(function(){
        LoadDivContent( 'profile/chef_portal', '', 'chef-portal', {
          action: 'chef-apply',
          email: $('#chef-portal input[name=email]').val(),
          story: $('#chef-portal textarea[name=story]').val()
        });
      });
    });
  </script>
  <?php
}
else {
  $sel_query = "
    Call spSelectChefProfile(".$_SESSION['Login']['chef_id'].");
  ";
  $result = $App->oDBMY->query($sel_query);
  $Chef = $result->fetch_assoc();
  $result->free();
  ?>
  <div class="cwrap my_account">

    <table class="ctable" >
      <tr class="form-table-row">
        <th>Words of Wisdom</th>
        <td>
          <span class="pointer read-setting <?=( trim($Chef['wisdom']) == "" ? "hidden" : "" )?>"><?=trim($Chef['wisdom'])?></span>
          <span class="write-setting <?=( trim($Chef['wisdom']) == "" ? "" : "hidden" )?>">
            <input type="text" style="width: 96% !important;" class="form-control form-control-inline" id="write-setting-wisdom" data-field="wisdom" autocomplete="off" value="<?=$Chef['wisdom']?>" placeholder="advice for cooking or for life" >
            <!--<span class="label label-success pointer"><i class="fa fa-save"></i></span>
            <span class="label label-danger pointer"><i class="fa fa-remove"></i></span>-->
            <span class="help-inline"></span>
        </td>
      </tr>
    </table>

    <dl class="basic">
      <dt>Favorite cusine</dt>
      <dd>
        <span class="pointer read-setting <?=( trim($Chef['favorite_cuisine']) == "" ? "hidden" : "" )?>"><?=$Chef['favorite_cuisine']?></span>
        <span class="write-setting <?=( trim($Chef['favorite_cuisine']) == "" ? "" : "hidden" )?>">
          <input type="text" class="form-control form-control-inline" id="write-setting-favorite_cuisine" data-field="favorite_cuisine" autocomplete="off" value="<?=$Chef['favorite_cuisine']?>" placeholder="ex: Thai, Mexican" >
          <!--<span class="label label-success pointer"><i class="fa fa-save"></i></span>
          <span class="label label-danger pointer"><i class="fa fa-remove"></i></span>-->
          <span class="help-inline"></span>
        </span>
      </dd>
      <dt>Favorite spices</dt>
      <dd>
        <span class="pointer read-setting <?=( trim($Chef['favorite_spices']) == "" ? "hidden" : "" )?>"><?=$Chef['favorite_spices']?></span>
        <span class="write-setting <?=( trim($Chef['favorite_spices']) == "" ? "" : "hidden" )?>">
          <input type="text" class="form-control form-control-inline" id="write-setting-favorite_spices" data-field="favorite_spices" autocomplete="off" value="<?=$Chef['favorite_spices']?>" placeholder="ex: Chilli, Oregano, Basil" >
          <!--<span class="label label-success pointer"><i class="fa fa-save"></i></span>
          <span class="label label-danger pointer"><i class="fa fa-remove"></i></span>-->
          <span class="help-inline"></span>
        </span>
      </dd>
    </dl>
    
    <div class="container box" style="margin-bottom: 22px">
      <h2>
        My Story
      </h2>
      <p class="lead">
        How do you related to the Hunt's? How did you get here?
      </p>
      <textarea class="form-control write-setting" id="write-setting-story" data-field="story" rows="4" ><?=trim($Chef['story'])?></textarea>
    </div>

  </div>

  <div class="cwrap" style="padding-bottom: 100px;">
    <header class="s-title">
      <h2 class="ribbon bright">Your Recipes</h2>
    </header>
    <?php
    $sel_query = "
      Call spSelectChefPortalRecipes(".$_SESSION['Login']['id'].");
    ";
    $results = array();
    $r = $App->oDBMY->query( $sel_query );
    while ( $row = $r->fetch_assoc() ) {
      array_push($results, $row);
    }

    if ( count($results) === 0 ) {
      ?>
      <div class="alert alert-banner">
        No recipes yet.
      </div>
      <?php
    } else {
      ?>
      <div class="row">
        <!--entries-->
        <div class="entries full-width _column-entries _column-entries-three">
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
                    <? if ( $results[$i]['published_flag'] == 1 ) { ?>
                      <div class="meal-plan">
                        <span class="btnSaveToMealsGroup btn-group">
                          <span class="btn btnSaveToMealsMain btn-sm btn-<?=( $results[$i]['in_meal_plan_flag'] == 1 ? "info" : "theme" )?>">
                            <i class="fa fa-<?=( $results[$i]['in_meal_plan_flag'] == 1 ? "check" : "plus" )?>"></i> my meals
                          </span>
                          <span class="btn btn-sm btn-<?=( $results[$i]['in_meal_plan_flag'] == 1 ? "info" : "theme" )?> dropdown-toggle btnSaveToMealsWeeksMenu" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                          </span>
                          <ul id="r<?=$results[$i]['id']?>WeekMenu" class="dropdown-menu">
                          </ul>
                        </span>
                      </div>
                      <div class="likes divSaveToFavorites <?=( $results[$i]['favorite_flag'] == 1 ? "favorite-recipe" : "" )?>"><i class="fa fa-heart"></i><span class="favorite-count"><?=$results[$i]['favorite_count']?></span></div>
                    <? } elseif ( $results[$i]['published_flag'] == 0 ) { ?>
                      <div class="meal-plan">
                        <a class="btn btn-sm btn-primary" href="/submit_recipe.php?recipe_id=<?=$results[$i]['id']?>" >
                          <i class="fa fa-pencil"></i> edit
                        </a>
                      </div>
                    <? } ?>
                  </div>
                </div>
              </div>
            </div>
            <!--item-->
            <?php
          }
          ?>
        </div>
        <!--//entries-->
      </div>
      <script>
        
        $(function(){

          $('#chef-portal').on('click', '.btnSaveToMealsWeeksMenu', function(e){
            var recipe_id = $(this).parent().parent().parent().data('recipe-id');
            //console.log(recipe_id);
            LoadDivContent('weeks_for_meals_dropdown', '', 'r'+recipe_id+'WeekMenu', { recipe_id: recipe_id });
          });
          
          $('#chef-portal .read-setting').click(function(){
            $(this).parent().find('.write-setting').removeClass('hidden');
            $(this).parent().find('.write-setting > input, .write-setting > select').focus();
            $(this).addClass('hidden');
          });

          /*'.write-setting > .label-danger').click(function(){
            $(this).parent().parent().find('.read-setting').removeClass('hidden');
            $(this).parent().find('.help-inline').text('');
            $(this).parent().addClass('hidden');
          });

          $('.write-setting > .label-success').click(function(){
            //console.log('.write-setting > .label-success).click');
            updateLoginInfo( $(this).parent().find('input,select') );
          });*/

          $('#chef-portal .write-setting > input, .write-setting > select').blur(function(){
            console.log('.write-setting > input).blur');
            updateChefInfo( $(this) );
          });

          $('#chef-portal .write-setting > input').keyup(function(e){
            if ( e.which == 13 ) {
              //console.log('.write-setting > input).keyup');
              updateChefInfo( $(this) );
            }
          });

          $('#chef-portal .write-setting > select').change(function(e){
            //console.log('.write-setting > select).change');
            updateChefInfo( $(this) );
          });
          
          $('#chef-portal textarea.write-setting').blur(function(){
            updateChefInfo( $(this) );
          });

          $('#chef-portal textarea.write-setting').change(function(e){
            updateChefInfo( $(this) );
          });
          
        });
        
        function updateChefInfo( $input ) {
          //console.log($input);
          var field = $input.data('field');
          var value = $input.val();
          //console.log(field);
          //console.log(value);
          $input.parent().find('.help-inline').text('');

          //var $i = $input.parent().find('.fa-save');
          //$i.removeClass('fa-save');
          //$i.addClass('fa-spinner');

          $.ajax({
            url: '/ajax-json/profile/write_chef_field.json.php',
            type: 'GET',
            data: {
              field: field,
              value: value
            },
            success: function( response ) {
              console.log(response);
              var input_field = response.input_data.field;
              var input_value = $.trim(response.input_data.value);

              //var $i = $('#write-setting-'+input_field).parent().find('.fa-spinner');
              //$i.removeClass('fa-spinner');
              //$i.addClass('fa-save');

              if ( response.success == 1 ) {
                
                if ( input_field == 'story' ) {
                  $('#write-setting-'+input_field).val(input_value);
                } else {
                  $('#write-setting-'+input_field).val(input_value);
                  $('#write-setting-'+input_field).parent().parent().find('.read-setting').text(input_value);

                  if ( input_value.length > 0 ) {
                    $('#write-setting-'+input_field).parent().parent().find('.read-setting').removeClass('hidden');
                    $('#write-setting-'+input_field).parent().addClass('hidden');
                  }
                }
              } else {
                $('#write-setting-'+input_field).parent().find('.help-inline').text('Unexpected DB Error');
              }
            }
          });
        }
        
      </script>
      <?php
    }
    ?>
  </div>
  <?php
}

$App = "";
?>