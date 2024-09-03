<?php

use HuntRecipes\User\SessionController;

require_once("includes/common.php");

$sess = new SessionController();

http_response_code(302);

if ($sess->has_user()) {
    header('Location: /home/');
}
else {
    header('Location: /welcome/');
}
