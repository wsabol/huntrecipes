<?
class clsIO {
	private $ui;
	private $objDB;
	private $objDBConnection;
	
	function __construct (&$pui) 	{	
		$this->ui = $pui;
	}
	function GetFileList($dir, &$oFiles, $aryFileTypesToAllow, $recursive = 0){
		$this->ui->dbp("Entering dir: " . $dir);
		
		if(is_dir($dir)){
			if($handle = opendir($dir)) {
		 		#itterate each directory item
		 		while(($file = readdir($handle)) !== false)  {
					echo "file: " . $file . "\n";
		  			#if it's a valid vile let's process it.
					if($file != "." && $file != ".." && $file != "Thumbs.db") {
						if(!is_dir($dir . "/" .$file)){
							$oFile = "";
							$oFile->full_path = $dir . "/" .$file;
							$oFile->name = $file;
							$oFile->directory = $dir;
							
							$path_to_file = $dir . "/" .$file;
							
							#Get the file properties from the current tile
							$this->GetFileProperties($oFile->full_path, $oFile);
							$add_file = 1;
							if (count($aryFileTypesToAllow)>0) {
								$add_file = 0;
								$type_count = count($aryFileTypesToAllow);
								$type_counter = 0;
								while($type_counter<$type_count) {
									$stype = $aryFileTypesToAllow[$type_counter];
									
									$pos = strpos($oFile->full_path, $stype);
									if (strlen($pos)>0) {
										$add_file=1;
										break;
									}
									$type_counter++;
								}
							}
							if ($add_file==1) {
								$oFiles[] = $oFile;
							}
							
						}
					}
				}
			}
		
	  		closedir($handle);
		}
	}
	function GetFileProperties($path_to_file, &$oFile) {
		$oFile->file_size = filesize($path_to_file);
		$oFile->extension = $this->GetFileExtension($path_to_file);
	}
	function GetFileExtension($path_to_file) {
		$ret_val = "";
		$pos = strrpos($path_to_file, ".");
		if (strlen($pos)>0) {
			$pos++;
			$ret_val = substr($path_to_file, $pos);
		}
		return $ret_val;
	}
	function FileExists($file_name) {
		$ret_val = 0;
		$file_exists = file_exists($file_name);
		if (strlen($file_exists)>0) {
			return 1;
		}
		else {
			return 0;
		}

	}
	function WriteTextToFile(&$file_name, &$text, $append = 0) {
		$myFile = $file_name;	
		$open_type = "w";
		if ($append==1) {
			$open_type = "a";
		}
		$fh = fopen($myFile, $open_type) or die("can't open file");
		fwrite($fh, $text);
		fclose($fh);
	}
	function GetTextFromFile($file_name, &$file_contents) {
		$got_data = 0;
		$is_file = is_file($file_name); if (strlen($is_file)==0) {$is_file=0;}
		#echo "is_file: " . $is_file . "\n";
		
		$file_contents= "";
		$fh = @fopen($file_name, 'r');
		$file_contents = @fread($fh, filesize($file_name));
		@fclose($fh);
		
		return $is_file;
		
		
	}
}
?>