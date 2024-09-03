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
require_once("API/fraction/fraction.php");

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
//echo getcwd();

$App = new Application();

$guestAccessPages = array(
	"/login.php",
	"/API/login/std_login.json.php",
	"/API/facebook/facebook_login_callback.json.php",
	"/forgot_password.php",
	"/register.php",
	"/contact.php",
	"/API/contact/contact_callback.php",
	"/errorPage.php",
	"/API/facebook/facebook_registration_callback.json.php",
	"/",
	"/index.php",
	"/recipe.php",
	"/recipe_print.php",
	"/browse.php",
	"/ajax-json/search/spRecipeSearchResults.json.php",
	"/ajax-json/search/ingredient_suggestions.json.php",
	"/error404.php",
	"/featured_history.php",
	"/chef_profile.php",
	"/reset_password.php"
);

if( @$skip_session_create * 1 == 0 ) {
	// check for login cookie
	/*if( @$_SESSION["Login"]["id"] * 1 == 0 ) {
		CheckFacebookLogin();
	}*/
	if( @$_SESSION["Login"]["id"] * 1 == 0 ) {
		CheckCookieLogin();
	}
	//let's check to see if they are already logged in
	if( @$_SESSION["Login"]["id"] * 1 == 0 
		&& !in_array( $_SERVER["PHP_SELF"], $guestAccessPages )
	) {
		//print_r( $_SERVER );
		//exit;
		header("Location: /login.php?ref=" . @$_SERVER["REQUEST_URI"] . "");
	}
}

$_SESSION['Measure'] = array();
$qMeasure = "
	SELECT * FROM Measure
";
$rs = $App->oDBMY->query( $qMeasure );
while ( $row = $rs->fetch_assoc() ) {
	if ( $row['abbr'] == "c" ) {
		$row['frac'] = array("1/4", "1/3", "1/2", "2/3", "3/4");
		$row['frac_perm'] = 1;
	} elseif ( $row['abbr'] == "tsp" ) {
		$row['frac'] = array("1/4", "1/2", "3/4");
		$row['frac_perm'] = 1;
	} elseif ( $row['abbr'] == "tbsp" ) {
		$row['frac'] = array("1/4", "1/2", "3/4");
		$row['frac_perm'] = 0;
	} else {
		$row['frac'] = array();
		$row['frac_perm'] = 0;
	}
	array_push( $_SESSION['Measure'], $row );
}
$rs->free();
unset($qMeasure);
unset($rs);
unset($row);

function friendlyAmount( $gen_amt, $measure_type_id, &$value_decimal ) {
  $value_decimal = 0;
	if ( $gen_amt <= 0 ) {
    return "";
  }
  
  if ( $measure_type_id == 0 ) {
    $f0 = new Fraction( $gen_amt );
    $value_decimal = $f0->decimal;
    return $f0->toString();
  }
  
  $Measure = array();
  for ( $i = 0; $i < count($_SESSION['Measure']); $i++ ) {
    if ( $_SESSION['Measure'][$i]['measure_type_id'] == $measure_type_id ) {
      array_push( $Measure, $_SESSION['Measure'][$i] );
    }
  }
  usort($Measure, function( $a, $b ) {
		$a0 = floatval($a['general_unit_conversion']);
		$b0 = floatval($b['general_unit_conversion']);
		if ( $a0 < $b0 ) return 1;
		if ( $a0 > $b0 ) return -1;
		return 0;
	});
  $value = "";
  $tmp_value = "";
  $frac = array();
	
  for ( $i = 0; $i < count($Measure) && $gen_amt > 0.0001; $i++ ) {
    $convert = $gen_amt / $Measure[$i]['general_unit_conversion'];
    $frac = $Measure[$i]['frac'];
		
    if ( abs(floor($convert) - $convert) < 0.0001 || 
				( count($frac) > 0 && $Measure[$i]['frac_perm'] == 1 )
			) {
      $f1 = new Fraction($convert);
      $tmp_value = $f1->toString();
      $values = explode("-", $tmp_value, 2);
      $vEnd = $values[count($values) - 1];
      if ( !in_array($vEnd, $frac) && strpos($vEnd, '/') ) {
				$floor = 0;
				$f_vEnd = new Fraction($vEnd);
				
				for ( $j = 0; $j < count($frac); $j++ ) {
					$f_test = new Fraction($frac[$j]);
					if ( $f_vEnd->decimal < $f_test->decimal ) {
						break;
					} else {
						$floor = $f_test->decimal;
					}
				}
				$f_test = "";
      	$f1 = new Fraction( $floor );
      }
      
      if ( $f1->toString() != "0" ) {
        if ( strlen($value) === 0 ) {
          $value_decimal = $f1->decimal;
        }
        $value .= ( strlen($value) > 0 ? " + " : "" ).$f1->toString()." ".$Measure[$i]['abbr'];
        $gen_amt = ( $convert - $f1->decimal ) * $Measure[$i]['general_unit_conversion'];
      }
    } elseif ( $i == count($Measure) - 1 ) {
			
			if ( $measure_type_id == 1 ) {
				$f1 = new Fraction( round($convert) );
				if ( $f1->toString() != "0" ) {
					$value .= ( strlen($value) > 0 ? " + " : "" ).$f1->toString()." ".$Measure[$i]['abbr'];
					if ( $value_decimal === 0 ) {
						$value_decimal = $f1->decimal;
					}
				}
			}
			else {
				$f1 = new Fraction( $convert );
				if ( $f1->decimal !== 0 ) {
					$value .= ( strlen($value) > 0 ? " + " : "" ).round($f1->decimal, 2)." ".$Measure[$i]['abbr'];
					if ( $value_decimal === 0 ) {
						$value_decimal = $f1->decimal;
					}
				}
			}
			
    }
  }
  
  return $value;
}

function StdLoginRoutine( $dbLoginRecord, $rememberme = 0 ) {
	global $App;
	
	$upd_query = "
    UPDATE Login
    SET last_login = NOW()
    WHERE id = " . $dbLoginRecord['id'];
  $App->oDBMY->execute( $upd_query );
  $body = $dbLoginRecord['username'];
  $body.="\n". print_r( $dbLoginRecord, 1);
  #mail("4404886576@vtext.com", "PMG Login: " . $dbLoginRecord["user_name"], $body, "from:server@promediagroup.com");
  $_SESSION['Login'] = $dbLoginRecord;

  if ( $rememberme == 1 ) {
		setPersistentLogin( $dbLoginRecord['id'], $dbLoginRecord['login_hash'] );
	}
	
	#$_SESSION["Login"]["RoleCount"] = 0;
	#$_SESSION["Login"]["Roles"] = "";
}

function setPersistentLogin( $login_id, $login_hash ) {
	global $App;
	
	$existing_selector = NULL;
	$uname_token = @$_COOKIE['uname_auth']; 
	if (!empty($uname_token)) {
		$tokenData = explode(":", $uname_token, 2);
		$existing_selector = $tokenData[0];
	}
	
	$selector = substr(generateToken(), 0, 16);
	$validator = generateToken();
	$expires = time() + 3600*24*360;
	$dExpires = new DateTime();
	$dExpires->setTimestamp( $expires );
	
	setcookie("uname_auth", $selector.":".$validator, $expires, '/');
	
	if ( $existing_selector !== NULL ) {
		$delExisting = "
			DELETE ls FROM LoginSession ls
			WHERE ls.login_id = ".$login_id."
			AND ls.selector = '".$existing_selector."';
		";
		$App->oDBMY->execute( $delExisting );
	}
	$rememberme_query = "
		Call spLoginRememberMe(
			".$login_id.",
			'".$selector."',
			'".crypt($validator, $login_hash)."',
			'".$dExpires->format('Y-m-d H:i:s')."'
		);
	";
	//wl($rememberme_query);
	$App->oDBMY->execute( $rememberme_query );
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
		if ( count($tokenData) == 2 ) {
			$qValidator = "
				SELECT l.username, l.login_hash, ls.hashed_validator
				FROM LoginSession ls
				JOIN Login l
				ON l.id = ls.login_id
				WHERE selector = '".$tokenData[0]."'
				AND expires > NOW();
			";
			//wl($qValidator);
			$result = $App->oDBMY->query( $qValidator );
			
			$result_count = 0;
			$username = "";
			while ( ( $row = $result->fetch_assoc() ) && $username == "" ) {
				$result_count++;
				if ( hash_equals( crypt($tokenData[1], $row['login_hash']), trim($row['hashed_validator']) ) ) {
					$username = $row['username'];
				}
			}
			$result->free();
			
			if ( $username != "" ) {
				// mimic login query from login.html
				$sel_query = "
					Call spSelectLogin(
						'" . $username . "'
					);
				";
				#la( $sel_query ) ;
				$result = $App->oDBMY->query( $sel_query );
				StdLoginRoutine( $result->fetch_assoc(), 1 );
				$result->free();
				
			} else {
				// session tokens missing in db
				// possible threats are sent here as well.
				
				resetLoginCookie();
				$qDelToken = "
					DELETE ls FROM LoginSession ls
					WHERE selector = '".$tokenData[0]."';
				";
				$App->oDBMY->execute( $qDelToken );
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
BASIC HELPER FUNCTIONS
**/

function str_begins( $testStr, $target ) {
	return strpos( $testStr, $target ) === 0;
}

function str_ends( $testStr, $target ) {
	return strpos( $testStr, $target ) === strlen($testStr) - strlen($target);
}

function getDaysOfWeekArray() {
	// 0 - sunday
	$week_day = array();
	for ( $dow = 0; $dow < 7; $dow++ ) {
		$week_day[$dow] = array(
			'name'=> date('l', strtotime("Sunday +".$dow." days")),
			'abbr'=> date('D', strtotime("Sunday +".$dow." days"))
		);
	}
	return $week_day;
}

function SendEmail( $from_address, $to_name, $to_address, $cc_recipients, $bcc_recipients, $replyto_name, $replyto_address, $subject, $html_body, $attachment_count = 0, $aryAttachments = null ){
    //dbpc(" -> Sending email to [" . $to_name . "]");

    //Create a new PHPMailer instance
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    //Tell PHPMailer to use SMTP
    $mail->isSMTP( );

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
    if ( $replyto_address != "" ) {
        $mail->addReplyTo($replyto_address, $replyto_name);
    }
    //Set who the message is to be sent to
    $mail->addAddress($to_address, $to_name);
    //loop cc's
    for ( $cci = 0; $cci < count($cc_recipients); $cci++ ) {
        if ( isset($cc_recipients[$cci]['address']) && isset($cc_recipients[$cci]['name']) ) {
            $mail->addCC($cc_recipients[$cci]['address'], $cc_recipients[$cci]['name']);
        } elseif ( isset($cc_recipients[$cci]['address']) ) {
            $mail->addCC($cc_recipients[$cci]['address'], '');
        }
    }
    //loopb cc's
    for ( $bcci = 0; $bcci < count($bcc_recipients); $bcci++ ) {
        if ( isset($bcc_recipients[$bcci]['address']) && isset($bcc_recipients[$bcci]['name']) ) {
            $mail->addBCC($bcc_recipients[$bcci]['address'], $bcc_recipients[$bcci]['name']);
        } elseif ( isset($bcc_recipients[$bcci]['address']) ) {
            $mail->addBCC($bcc_recipients[$bcci]['address'], '');
        }
    }
    //Set the subject line
    $mail->Subject = $subject;
    // html body
    $mail->IsHTML(true);
    $mail->msgHTML($html_body);

    //Attach an image file
    if( $attachment_count > 0 ) {
        $attachment_counter = 0;
        while( $attachment_counter < $attachment_count ) {
            $mail->addAttachment( $aryAttachments[$attachment_counter]);
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
