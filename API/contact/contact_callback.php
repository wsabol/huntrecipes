<?php
require_once('../PHPMailer/sendemail.function.php');

$mailSuccess = SendEmail( "contact@willsabol.com", "Will Sabol", "wsabol39@gmail.com", array(), array(), "", "", "HuntRecipes - Account Confirmation", print_r($_POST, true));
if ( $mailSuccess ) {
  echo "Thank You!";
} else {
  echo "Something terrible has happened.";
}
?>
