#!/usr/bin/php
<?php
$App = "";
$skip_session_create = 1;
require_once('../_php_common.php');
error_reporting(E_ALL);
ini_set('display_errors', '1');


/** RECIPE OF THE DAY **/
$new_query = "
  INSERT INTO RecipeOfTheDay
  ( day, recipe_id )
  VALUES
  ( CURDATE(), (
      SELECT
        id
      FROM Recipe
      WHERE id NOT IN (
        SELECT x.recipe_id
       FROM RecipeOfTheDay x
       WHERE x.day >= DATE_ADD( CURDATE(), INTERVAL -1 DAY )
      )
      AND published_flag = 1
      ORDER BY RAND() LIMIT 1
    )
  );
";
$App->oDBMY->execute( $new_query );

$clear_query = "
  DELETE FROM RecipeOfTheDay
  WHERE day < DATE_ADD(CURDATE(), INTERVAL -32 DAY);
";
$App->oDBMY->execute( $clear_query );


/** CHEF OF THE DAY **/
$new_query = "
  INSERT INTO ChefOfTheDay
  ( day, chef_id )
  VALUES
  ( CURDATE(), (
      SELECT
        id
      FROM Chef
      WHERE id NOT IN (
        SELECT x.chef_id
       FROM ChefOfTheDay x
       WHERE x.day >= DATE_ADD( CURDATE(), INTERVAL -1 DAY )
      ) AND id > 1
      ORDER BY RAND() LIMIT 1
    )
  );
";
$App->oDBMY->execute( $new_query );

$clear_query = "
  DELETE FROM ChefOfTheDay
  WHERE day < DATE_ADD(CURDATE(), INTERVAL -32 DAY);
";
$App->oDBMY->execute( $clear_query );


// delete session cookies
$del_query = "
  DELETE FROM LoginSession
  WHERE expires < NOW();
";
$App->oDBMY->execute( $del_query );

// delete password reset tokens
$del_query = "
  DELETE FROM PasswordResetToken
  WHERE expires < NOW();
";
$App->oDBMY->execute( $del_query );

$App = "";
?>