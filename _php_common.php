<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!defined('RECIPES_ROOT')) {
    /** @var string $RECIPES_ROOT Absolute Path to Project Root */
    define('RECIPES_ROOT', __DIR__);
}

// require composer
require_once RECIPES_ROOT . "/vendor/autoload.php";

// app includes
require_once("assets/Application.php");

/* load environment vars */
$dotenv = Dotenv\Dotenv::createImmutable(RECIPES_ROOT);
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD']);
unset($dotenv);

if (!defined('IS_PRODUCTION')) {
    /** @var bool $IS_PRODUCTION Whether on production server */
    define("IS_PRODUCTION", filter_var($_ENV['PRODUCTION'], FILTER_VALIDATE_BOOL));
}

ini_set("display_errors", IS_PRODUCTION ? 0 : 1);

if (empty(@$skip_session_create)) {
    //echo 'session_start';
    @session_start();
}

$App = new Application();

$guestAccessPages = array(
    "/forgot_password.php",
    "/contact.php",
    "/API/v0/contact/contact_callback.php",
    "/",
    "/index.php",
    "/recipe_print.php",
    "/ajax-json/search/ingredient_suggestions.json.php",
    "/reset_password.php"
);

if (@$skip_session_create * 1 == 0) {
    // check for login cookie
    if (@$_SESSION["Login"]["id"] * 1 == 0) {
        CheckCookieLogin();
    }
    //let's check to see if they are already logged in
    if (@$_SESSION["Login"]["id"] * 1 == 0
        && !in_array($_SERVER["PHP_SELF"], $guestAccessPages)
    ) {
        //print_r( $_SERVER );
        //exit;
        // header("Location: /login/?ref=" . @$_SERVER["REQUEST_URI"] . "");
    }
}

function StdLoginRoutine($dbLoginRecord, $rememberme = 0) {
    global $App;

    $upd_query = "
    UPDATE Login
    SET last_login = NOW()
    WHERE id = " . $dbLoginRecord['id'];
    $App->oDBMY->execute($upd_query);
    $body = $dbLoginRecord['username'];
    $body .= "\n" . print_r($dbLoginRecord, 1);
    #mail("4404886576@vtext.com", "PMG Login: " . $dbLoginRecord["user_name"], $body, "from:server@promediagroup.com");
    $_SESSION['Login'] = $dbLoginRecord;
    $_SESSION['User'] = $dbLoginRecord;

    if ($rememberme == 1) {
        setPersistentLogin($dbLoginRecord['id'], $dbLoginRecord['login_hash']);
    }

    #$_SESSION["Login"]["RoleCount"] = 0;
    #$_SESSION["Login"]["Roles"] = "";
}

function setPersistentLogin($login_id, $login_hash) {
    global $App;

    $existing_selector = NULL;
    $uname_token = @$_COOKIE['uname_auth'];
    if (!empty($uname_token)) {
        $tokenData = explode(":", $uname_token, 2);
        $existing_selector = $tokenData[0];
    }

    $selector = substr(generateToken(), 0, 16);
    $validator = generateToken();
    $expires = time() + 3600 * 24 * 360;
    $dExpires = new DateTime();
    $dExpires->setTimestamp($expires);

    setcookie("uname_auth", $selector . ":" . $validator, $expires, '/');

    if ($existing_selector !== NULL) {
        $delExisting = "
			DELETE ls FROM LoginSession ls
			WHERE ls.login_id = " . $login_id . "
			AND ls.selector = '" . $existing_selector . "';
		";
        $App->oDBMY->execute($delExisting);
    }
    $rememberme_query = "
		Call spLoginRememberMe(
			" . $login_id . ",
			'" . $selector . "',
			'" . crypt($validator, $login_hash) . "',
			'" . $dExpires->format('Y-m-d H:i:s') . "'
		);
	";
    //wl($rememberme_query);
    $App->oDBMY->execute($rememberme_query);
    $dExpires = "";
}

function generateToken() {
    /* 40 char random security token */
    return sha1(md5(mt_rand()));
}

function CheckCookieLogin() {
    global $App;

    $uname_token = @$_COOKIE['uname_auth'];
    if (!empty($uname_token)) {
        $tokenData = explode(":", $uname_token, 2);
        //wla($tokenData);
        //exit;
        if (count($tokenData) == 2) {
            $qValidator = "
				SELECT l.username, l.login_hash, ls.hashed_validator
				FROM LoginSession ls
				JOIN Login l
				ON l.id = ls.login_id
				WHERE selector = '" . $tokenData[0] . "'
				AND expires > NOW();
			";
            //wl($qValidator);
            $result = $App->oDBMY->query($qValidator);

            $result_count = 0;
            $username = "";
            while (($row = $result->fetch_assoc()) && $username == "") {
                $result_count++;
                if (hash_equals(crypt($tokenData[1], $row['login_hash']), trim($row['hashed_validator']))) {
                    $username = $row['username'];
                }
            }
            $result->free();

            if ($username != "") {
                // mimic login query from login.html
                $sel_query = "
					Call spSelectLogin(
						'" . $username . "'
					);
				";
                #la( $sel_query ) ;
                $result = $App->oDBMY->query($sel_query);
                StdLoginRoutine($result->fetch_assoc(), 1);
                $result->free();

            } else {
                // session tokens missing in db
                // possible threats are sent here as well.

                resetLoginCookie();
                $qDelToken = "
					DELETE ls FROM LoginSession ls
					WHERE selector = '" . $tokenData[0] . "';
				";
                $App->oDBMY->execute($qDelToken);
            }
        } else {
            // bad uname_auth cookie
            resetLoginCookie();
        }

    }

}

function resetLoginCookie() {
    setcookie(session_name(), '', time() - 3600);
    setcookie("uname_auth", '', time() - 3600, '/');
}

/**
 * BASIC HELPER FUNCTIONS
 **/

function str_begins($testStr, $target) {
    return strpos($testStr, $target) === 0;
}

function SendEmail($from_address, $to_name, $to_address, $cc_recipients, $bcc_recipients, $replyto_name, $replyto_address, $subject, $html_body, $attachment_count = 0, $aryAttachments = null) {
    //dbpc(" -> Sending email to [" . $to_name . "]");

    //Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    //Tell PHPMailer to use SMTP
    $mail->isSMTP();

    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //Ask for HTML-friendly debug output
    $mail->Debugoutput = 'html';
    //Set the hostname of the mail server
    $mail->Host = $_ENV['MAIL_HOST'];
    //Set the SMTP port number - likely to be 25, 465 or 587
    $mail->Port = 465;
    //Whether to use SMTP authentication
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    //Username to use for SMTP authentication
    $mail->Username = $_ENV['MAIL_USERNAME'];;
    //Password to use for SMTP authentication
    $mail->Password = $_ENV['MAIL_PASSWORD'];
    //Set reply-to address
    if ($replyto_address != "") {
        $mail->addReplyTo($replyto_address, $replyto_name);
    }
    //Set who the message is to be sent to
    $mail->addAddress($to_address, $to_name);
    //loop cc's
    for ($cci = 0; $cci < count($cc_recipients); $cci++) {
        if (isset($cc_recipients[$cci]['address']) && isset($cc_recipients[$cci]['name'])) {
            $mail->addCC($cc_recipients[$cci]['address'], $cc_recipients[$cci]['name']);
        } elseif (isset($cc_recipients[$cci]['address'])) {
            $mail->addCC($cc_recipients[$cci]['address'], '');
        }
    }
    //loopb cc's
    for ($bcci = 0; $bcci < count($bcc_recipients); $bcci++) {
        if (isset($bcc_recipients[$bcci]['address']) && isset($bcc_recipients[$bcci]['name'])) {
            $mail->addBCC($bcc_recipients[$bcci]['address'], $bcc_recipients[$bcci]['name']);
        } elseif (isset($bcc_recipients[$bcci]['address'])) {
            $mail->addBCC($bcc_recipients[$bcci]['address'], '');
        }
    }
    //Set the subject line
    $mail->Subject = $subject;
    // html body
    $mail->IsHTML(true);
    $mail->msgHTML($html_body);

    //Attach an image file
    if ($attachment_count > 0) {
        $attachment_counter = 0;
        while ($attachment_counter < $attachment_count) {
            $mail->addAttachment($aryAttachments[$attachment_counter]);
            $attachment_counter++;
        }
    }

    //send the message, check for errors
    if (!$mail->send()) {
        echo "\nMailer Error: " . $mail->ErrorInfo;
        return false;
    } else {
        //echo "\nMessage sent!";
        return true;
    }

}
