<?
function IsDateBetween($App, $check_date, $start_date, $end_date) {
	$sel_query = "
	SELECT (CASE WHEN '$check_date' BETWEEN '$start_date' AND '$end_date' THEN 1
			ELSE 0
			END) is_between
	";
	
	$got_data = $App->oDB->GetMSRS($App->oDB->db, $sel_query, $rsDBTemp, $rsDBTempProperties);
	if($got_data==1) {
		if($rowDBTemp = mssql_fetch_array($rsDBTemp)) {	
			/*
			if($rowDBTemp["is_between"]==1){
				$App->ui->dbp("sel_query: " . $sel_query);
				$App->ui->dbp("is_between: " . $rowDBTemp["is_between"]);
				exit;
			}
			*/
			return $rowDBTemp["is_between"];
		}
	}
	else {
		$App->ui->dbp("Critical Error: Cannot Continue.\nWe could not get the date and time from the Database Server");
		exit;
	}
}
function AreDatesTheSame($App, $date_one, $date_two) {
	$date_one = str_replace(chr(39), "", $date_one);
	$date_two = str_replace(chr(39), "", $date_two);
	
	$sel_query = "
	SELECT (CASE WHEN CAST('$date_one' AS DATETIME) = CAST('$date_two' AS DATETIME) THEN 1
			ELSE 0
			END) are_dates_the_same
			, CAST('$date_one' AS DATETIME) date_one_converted
			, CAST('$date_two' AS DATETIME) date_two_converted
	";
	$got_data = $App->oDB->GetMSRS($App->oDB->db, $sel_query, $rsDBTemp, $rsDBTempProperties);
	if($got_data==1) {
		
		if($rowDBTemp = mssql_fetch_array($rsDBTemp)) {	
			#print_r($rowDBTemp);
			/*
			if($rowDBTemp["is_between"]==1){
				$App->ui->dbp("sel_query: " . $sel_query);
				$App->ui->dbp("is_between: " . $rowDBTemp["is_between"]);
				exit;
			}
			*/
			return $rowDBTemp["are_dates_the_same"];
		}
	}
	else {
		$App->ui->dbp("Critical Error: Cannot Continue.\nWe could not get the date and time from the Database Server");
		exit;
	}
}
function GetDBDate($App, $time_offset = -3) {
	$sel_query = "
	SELECT DATEADD(hour, $time_offset, GETDATE()) current_date_time
	";
	$got_data = $App->oDB->GetMSRS($App->oDB->db, $sel_query, $rsDBTemp, $rsDBTempProperties);
	if($got_data==1) {
		if($rowDBTemp = mssql_fetch_array($rsDBTemp)) {	
			return $rowDBTemp["current_date_time"];
		}
	}
	else {
		$App->ui->dbp("Critical Error: Cannot Continue.\nWe could not get the date and time from the Database Server");
		exit;
	}
}
function DateDiff($App, $interval, $start_date, $end_date, $time_offset = -3) {
	$sel_query = "
	SELECT DATEDIFF($interval, '$start_date', '$end_date') date_diff
	";
	$got_data = $App->oDB->GetMSRS($App->oDB->db, $sel_query, $rsDBTemp, $rsDBTempProperties);
	if($got_data==1) {
		if($rowDBTemp = mssql_fetch_array($rsDBTemp)) {	
			return $rowDBTemp["date_diff"];
		}
	}
	else {
		$App->ui->dbp("Critical Error: Cannot Continue.\nWe could not get the date and time from the Database Server");
		exit;
	}
}
function Decify($sstring, $decimal_places) {
	if ($decimal_places>0) {
		$decimal_places++;
	}
	$period_pos = strpos($sstring, ".");
	if (strlen($period_pos)>0) {
		$period_pos = $period_pos +$decimal_places;	
		$sstring = substr($sstring, 0,  $period_pos); 
	}
	return $sstring;
}

class clsDB {
	private static $dbhost = "127.0.0.1";
	private static $dbuser = "ugxqmsfznznts"; 
	private static $dbpass = "9iowzpoxdiex"; 
	private static $dbname = "saboldru_recipes"; 
	private static $db;

	public static function connect() {          
		self::$db = new mysqli(self::$dbhost,  self::$dbuser, self::$dbpass, self::$dbname);        
		if (self::$db->connect_errno) {
			die("Database mysqli failed: " .
				self::$db->connect_error . " (" . 
				self::$db->connect_errno . ")"      
			);
			return false;
		} else {
			self::clean();
			return true;
		}
	}

	public static function query( $query_str ) {
		self::clean();
		$mysqliResult = self::$db->query ( $query_str ) or die ( print "MySQL error: " . self::$db->errno . " : " .  self::$db->error . "\n" . $query_str);
		return $mysqliResult;
	}
	
	public static function execute( $query_str ) {
		self::clean();
		$success = self::$db->real_query( $query_str ) or die ( print "MySQL error: " . self::$db->errno . " : " .  self::$db->error . "\n" . $query_str);
		return $success;
	}
	
	public static function clean( $print_results = false ) {
		do { 
    	$result = self::$db->use_result();
			if ( $print_results && $result !== false ) {
				$data = $result->fetch_assoc();
				print_r( $data );
				print(chr(10));
			}
		}while( self::$db->more_results() && self::$db->next_result() );
	}
	
	public static function close() {
		if (isset(self::$db)) self::$db->close();
	}
	
	public static function argArrayToString( $params ) {
		$arguments = "";
		foreach ( $params as $p ) {
			if ( gettype($p) == "integer" ) {
				$arguments .= "" . $p;
			} elseif ( gettype($p) == "NULL" ) {
				$arguments .= "NULL";
			} else {
				$arguments .= "'" . self::prepstring($p) . "'";
			}
			$arguments .= ", \n";
		}
		$arguments = trim($arguments, " ,\n");
		return $arguments;
	}
	
	
	public static function prepstring($string_to_fix_for_db_queries) {
		$string_to_fix_for_db_queries = str_replace("\\", "\\\\", trim($string_to_fix_for_db_queries));
		$return = str_replace(chr(39), chr(39).chr(39), trim($string_to_fix_for_db_queries));
		return $return;
	}
	
	function GetSystemTime(){
		$exec_string = "date \"+%Y-%m-%d %H:%M:%S\"";
		$ret_val = exec($exec_string);
		return $ret_val;
	}
	
	function GetUnixTime(){
		$exec_string = "date \"+%s\"";
		#echo $exec_string;
		$ret_val = exec($exec_string);
		return $ret_val;
	}
}

?>
