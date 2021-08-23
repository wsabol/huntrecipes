<?
class clsUI {
		function __construct () {
			
		}
		function GetCmdInputValue($aryInput, $needed_parameter){
			$ret_val = "";
			
			$count = count($aryInput);
			$counter = 0;
			while($counter < $count){
				$Item = $aryInput[$counter];
				#dbp("Item($counter):" . $Item);
				
				$equal_pos = strpos($Item, "=");
				if (strlen($equal_pos)>0){
					$name = trim(substr($Item, 0, $equal_pos));
					$equal_pos++;
					$value = trim(substr($Item, $equal_pos));
					#dbp("name: " . $name);
					#dbp("value: " . $value);
					if ("/" . $needed_parameter==$name){
						return $ret_val = $value;
						break;
					}
					
				}
				$counter++;
			}
			return $ret_val;
		}

		function cls($out = TRUE) {
			$clearscreen = chr(27)."[H".chr(27)."[2J";
			if ($out) print $clearscreen;
			else return $clearscreen;
		}
		function dbp($sstring = "") {
			echo $sstring . "\n";
		}
		function DisplayProgressBar ($total_records, $current_pos) {
			if ($current_pos>0) {
				$current_progress = (($current_pos / $total_records) *100) / 2;
				$pcnt_format = number_format($current_progress,2) * 2;
				$this->dbp("Processed( $current_pos of $total_records) $pcnt_format%");
				$i=1;
				echo "|";
				while($i <=50) {
					if ($i <=$current_progress) {
						echo "*";
					}
					else {
						echo " ";
					}
					$i++;
				}
				echo "|";
				$this->dbp();
				
			}
		}
		
}
class clsCommandLine {
		function __construct () 
				{	
				}
		function cls($out = TRUE) {
			$clearscreen = chr(27)."[H".chr(27)."[2J";
			if ($out) print $clearscreen;
			else return $clearscreen;
		}
		function dbp($sstring = "") {
			echo $sstring . "\n";
		}
		function DisplayProgressBar ($total_records, $current_pos) {
			if ($current_pos>0) {
				$current_progress = (($current_pos / $total_records) *100) / 2;
				$pcnt_format = number_format($current_progress,2) * 2;
				$this->dbp("Processed( $current_pos of $total_records) $pcnt_format%");
				$i=1;
				echo "|";
				while($i <=50) {
					if ($i <=$current_progress) {
						echo "*";
					}
					else {
						echo " ";
					}
					$i++;
				}
				echo "|";
				$this->dbp();
				
			}
		}
		
}
?>