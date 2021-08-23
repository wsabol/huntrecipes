<?php
$App = "";
if( @$skip_session_create * 1 == 0 ) {
	//echo 'session_start';
	@session_start();
}
//echo getcwd();

require_once("assets/_Application_1.0_class.php");
require_once("API/fraction/fraction.php");

$Log = ""; $Log["name"] = "App:Create Start";$Log["time"] = time();$Log["page_at_time"] = $Log["time"] - @$_SESSION["Page"]["start_time"];@$_SESSION["Page"]["BuildLog"][] = $Log;
$App = new Application( "web" ) ;
$Log = ""; $Log["name"] = "App:Create Done";$Log["time"] = time();$Log["page_at_time"] = $Log["time"] - @$_SESSION["Page"]["start_time"];@$_SESSION["Page"]["BuildLog"][] = $Log;
//print_r( $_SESSION["Login"] );

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
	"/index.html",
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

function DaysBetweenTwoDates( $start_date, $end_date ) {
	 $end_time = strtotime( $end_date ); // or your date as well
     $your_date = strtotime($start_date);
     $datediff = $end_time - $your_date;
     return floor($datediff/(60*60*24));
}
function PrintError( $message ) {
	?>
    
    <div class="alert alert-error">
       <button class="close" data-dismiss="alert"></button>
       <strong>Error!</strong> <?=$message?>.
    </div>
    <?php
}
function GetFileListInFolder( $folder_path, &$files ) {
	$files = "";
	$file_count = 0;
	
	$dh  = opendir( $folder_path );
	while (false !== ($filename = readdir($dh))) {
		if( $filename != ".." && $filename != "." ) {
			if( !is_dir( $folder_path . $filename ) ) {
				$aryFile = explode( ".", $filename );
				$extension = strtolower( $aryFile[count($aryFile)-1] );
				if( 
					   $extension != "xls" 
					&& $extension != "xlsx" 
				) {
					$files[] = $filename;	
				}
				$file_count++;
			}
		}
	}
	closedir( $dh ) ;
	if( $file_count > 0 ) {
		return true;
	}
	else{
		return false;
	}
}
function GetSystemTime(){
	$exec_string = "date \"+%Y-%m-%d %H:%M:%S\"";
	$ret_val = exec($exec_string);
	return $ret_val;
}
function GetSystemDate(){
	$exec_string = "date \"+%Y-%m-%d\"";
	$ret_val = exec($exec_string);
	return $ret_val;
}
function GetSystemTimeHourOfDay(){
	$current_hour= 0;
	$current_time = GetSystemTime();
	$current_hour = $current_time;
	$pos = strpos( $current_hour , " " );
	$pos++;
	$current_hour = substr( $current_hour, $pos);
	$pos = strpos( $current_hour , ":" );
	$current_hour = substr( $current_hour, 0, $pos ) * 1 ;
	
	return $current_hour;
}
function GetSystemTimeUnix(){
	$exec_string = "date +%s";
	$ret_val = exec($exec_string);
	return $ret_val;
}
function PadString($string, $needed_length = 7, $character_to_pad_with = "0"){
	if(strlen($character_to_pad_with)==0) {
		$character_to_pad_with = "0";
	}
	while(strlen($string) < $needed_length) {
		$string = $character_to_pad_with . $string;
	}
	return $string;
}
function RemovePadding($sstring, $padded_character_to_remove) {
	while(substr($sstring, 0, 1) ==$padded_character_to_remove) {
		$sstring = substr($sstring, 1);
		#dbpc("sstring: " . $sstring);
		if(strlen($sstring)==0) {
			break;
		}
	}
	return $sstring;
}
function wl($sstring) {//Debug the string to the screen with trailing br
	echo($sstring . "<br/>");
}
function dbpc($sstring) {
	print $sstring . "\n";
}
function wla($aArray) {//Debug the string to the screen with trailing br
	echo(str_replace(chr(10), chr(10) . "<br>", str_replace(chr(9), "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" . chr(9) , print_r($aArray, 1) )) . chr(13) . chr(10));
}
function wlt( $aArray ) {
	?><textarea style="width:400px; height:400px;"><?
	echo( print_r( $aArray, 1) ); 
	?></textarea><?
}
function IsProcessRunning($process_name) {
	$ret_val = 0;
	///usr/bin/php ./sync_account.php
	$exec_string = "ps -ef |grep \"" . $process_name . "\"";
	
	$shl_ret = exec($exec_string, $aryResponse);
	//print($shl_ret);
	//print_r($aryResponse);
	$count = count($aryResponse);
	$counter = 0;
	$itterations_found = 0;
	
	while($counter < $count) {
		$item = $aryResponse[$counter];
		$pos_of_grep = strpos($item, "grep ");
		if (strlen($pos_of_grep)==0){
			$itterations_found++;
		}
		$counter++;
	}
	if($itterations_found==1) {
		return 0;
	}
	else {
		return 1;
	}
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

function isUpper( $testStr ) {
	return !preg_match("/[a-z]/", $testStr);
}
function hasArrayContent( $testArr ) {
	for ($i = 0; $i < count($testArr); $i++) {
		if ( is_array($testArr[$i]) ) {
			if (hasArrayContent($testArr[$i])) {
				return TRUE;
			}
		} elseif (strlen(trim($testArr[$i])) > 0) {
			return TRUE;
		}
	}
	return FALSE;
}
function index_count( $arr, $target ) {
	$count = 0;
	for ($i = 0; $i < count($arr); $i++) {
		if ($arr[$i] == $target) {
			$count++;
		}
	}

	return $count;
}
function explode_clean( $separator, $str, $limit = -1 ) {
	if ( $limit == -1 ) {
		$arr = explode( $separator, $str );
	} else {
		$arr = explode( $separator, $str, $limit );
	}
	for ( $i = 0; $i < count($arr); $i++ ) {
		$arr[$i] = trim($arr[$i], " \t\n\r\0\x0B\xC2\xA0");
		if ( $arr[$i] == "" ) {
			array_splice($arr, $i, 1);
			$i--;
		}
	}

	return $arr;
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

?>