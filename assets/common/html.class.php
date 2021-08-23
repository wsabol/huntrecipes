<?
class clsHTML {
	
	public $ui;
	function __construct($pui) {	
		$this->ui = $pui;
	}
	function RemoveHTMLTagsFromString($sstring) {
		$ret_val = $sstring;
		
		$aryTags = explode("<", $ret_val);
		$tag_count = count($aryTags);
		$tag_counter = 0;
		#dbp("tag_count: " . $tag_count);
		
		while($tag_counter < $tag_count){	
			$temp_string = $aryTags[$tag_counter];
			$pos = strpos($temp_string, ">");
			if (strlen($pos)>0){
				$temp_string = "<" . substr($temp_string, 0, $pos) . ">";
				$ret_val = str_replace($temp_string, " ",  $ret_val);
				#dbp("temp_string: " . $temp_string);
				#exit;
			}
			$tag_counter++;
		}
		$pos = strpos("  ", $ret_val);
		while(strlen($pos)>0){
			$ret_val = str_replace("  ", " ", $ret_val);
			$pos = strpos("  ", $ret_val);
		}
		return $ret_val;
		
	}
	function ExtractHyperLinkItemsFromString($App, $html, &$oLinkItems, $opt_append_to_collection = 0, $opt_start_text = "", $opt_end_text = "", $opt_href_must_include_text = "") {
		$html = str_replace("<A ", "<a ", $html);
		$html = str_replace("</A>", "</a>", $html);
		#$html = str_replace("<A ", "<a ", $html):
		#$html = str_replace("<A ", "<a ", $html):
		
		if($append_to_collection==0) {
			$oLinkItems = "";
		}
		if($opt_start_text =="") {
			$opt_start_text = "<!--StartText-->";
			$html = $opt_start_text . $html;

		}
		if(strlen($opt_end_text) =="") {
			$opt_end_text = "<!--EndText-->";
			$html = $html . $opt_end_text;
		}
		$links = $html;
		$links_found = 0;
		$myFile = "testFile.txt";
		$fh = fopen($myFile, 'w') or die("can't open file");
		fwrite($fh, $html);
		fclose($fh);
		
		
		$pos = strpos($links, $opt_start_text);
		#$this->ui->dbp("ExtractLinks_pos_1: " . $pos);
		
		if (strlen($pos)>0) {
			$pos++;
			$links = substr($links, $pos);
			$pos = strpos($links, $opt_end_text);
			#$this->ui->dbp("ExtractLinks_pos_2: " . $pos);
			#$this->ui->dbp("   end_text: " . $opt_end_text);
		
			
			if (strlen($pos)>0) {
				$links = substr($links, 0, $pos);
				$aryLinks = explode("href=", $links);
				$link_count = count($aryLinks);
				#$this->ui->dbp("  link_count " . $link_count);
				$loop_counter = 2;
				#let's dedup the links with this array class item
				$oArray = new clsArray($this->ui);
				while($loop_counter < $link_count) {
					
					$aryLinks[$loop_counter] = str_replace("<span class=\"text\">", "", $aryLinks[$loop_counter]);
					$aryLinks[$loop_counter] = str_replace("</span>", "", $aryLinks[$loop_counter]);
					$aryLinks[$loop_counter] = str_replace("&amp;", "&", $aryLinks[$loop_counter]);
					$link_item["item_text"] = $aryLinks[$loop_counter];
					$link_item["item_href"] = $aryLinks[$loop_counter];
					#$ui->dbp("link_item: " . $link_item["item_href"]);
					#exit;
					#$ui->dbp("pos: " . $pos );
					$link_found = 0;
					
					
					$pos = strpos($link_item["item_href"], chr(34));
					if (strlen($pos)>0) {
						$pos = $pos + 1;
						$link_item["item_href"] = substr($link_item["item_href"], $pos);
						$pos = strpos($link_item["item_href"], chr(34));
						#$ui->dbp("item_href: " . $link_item["item_href"] );
						if (strlen($pos)>0) {
							$link_item["item_href"] = substr($link_item["item_href"], 0, $pos);
							#$ui->dbp("item_href: " . $link_item["item_href"] );
							$link_found = 1;
						}
					}
					if ($link_found==1) {
						$pos = strpos($link_item["item_text"], ">");
						if (strlen($pos)>0) {
							$pos = $pos + 1;
							$link_item["item_text"] = substr($link_item["item_text"], $pos);
							
							$pos = strpos(strtolower($link_item["item_text"]), "</a");
							#$ui->dbp("item_text: " . $link_item["item_text"] );
							if (strlen($pos)>0) {
								#print_r($link_item);
								#exit;
							
								$link_item["item_text"] = substr($link_item["item_text"], 0, $pos);
								$pos = strpos(strtolower($link_item["item_text"]), "<img");
								if (strlen($pos)==0) {
									#print_r($link_item);
									#exit;
								
									#$ui->dbp("item_text: " . $link_item["item_text"] );
									$link_found = 1;
									$link_item["item_text"] = str_replace(chr(9), "", $link_item["item_text"]);
									$link_item["item_text"] = str_replace(chr(13), "", $link_item["item_text"]);
									$link_item["item_text"] = str_replace(chr(10), "", $link_item["item_text"]);
									$link_item["item_text"] = trim($link_item["item_text"]);
									
									$link_item["item_href"] = str_replace(chr(9), "", $link_item["item_href"]);
									$link_item["item_href"] = str_replace(chr(13), "", $link_item["item_href"]);
									$link_item["item_href"] = str_replace(chr(10), "", $link_item["item_href"]);
									#check for a pound sign in the href if there is one then take left of that
									$left_of_pos = strpos($link_item["item_href"], "#");
									if (strlen($left_of_pos)>0) {
										$link_item["item_href"] = substr($link_item["item_href"], 0, $left_of_pos);
										
									}
									$left_of_pos = strpos($link_item["item_href"], "?");
									if (strlen($left_of_pos)>0) {
										$link_item["item_href"] = substr($link_item["item_href"], 0, $left_of_pos);
										
									}
									#$link_item["item_href"] = "http://www.candydirect.com" . trim($link_item["item_href"]);
									$allow_link = 1;
									if (strlen($opt_href_must_include_text)>0){
										$allow_link = 0;
										$instr_pos = strpos(strtolower($link_item["item_href"] ), strtolower($opt_href_must_include_text));
										if (strlen($instr_pos)>0) {$allow_link = 1;}
									}
									if($allow_link ==1 ) {
										if($oArray->AddItemToArray($link_item, $oLinkItems, true,  "item_href")==1) {
											$links_found++;
											#$this->ui->dbp("adding link_item: " . $link_item["item_href"]);
										}
										#$oLinkItems[($loop_counter-1)] = $link_item;
									}
									
								}
							}
						}
					}
					//exit;
					$loop_counter++;
				}
				$oArray = "";
				//exit;
				
			}
		}
		
		return $links_found;
		
	}
	function StripInvalidCharacters($sstring) {
		//$this->ui->dbp("sstring: " . $sstring);
		
		$ret_val = "";
		$ret_val = $sstring;
		$ret_val = str_replace(chr(9), " ", $ret_val);
		$ret_val = str_replace(chr(10), " ", $ret_val);
		$ret_val = str_replace(chr(13), " ", $ret_val);
		$ret_val = str_replace("\n", " ", $ret_val);
		$ret_val = str_replace("\l", " ", $ret_val);
		$ret_val = trim($ret_val);
		
		$strlen = strlen($ret_val);
		$pos=0;
		$new_word = "";
		while ($pos < $strlen) {
			$schar = substr($ret_val, $pos, 1);
			$nchar = ord($schar);
			if (
				($nchar >= 48 && $nchar <= 57)  ||
				($nchar >= 97 && $nchar <= 122)  ||
				($nchar >= 65 && $nchar <= 90)  ||
				($schar==" ") ||
				($schar=="_") ||
				($schar==".") ||
				($schar=="-") ||
				($schar=="/") ||
				($schar=="#") ||
				($schar=="'") ||
				($schar=="$") 
				) {
				$new_word.=$schar;
				#$$this->ui->dbp($schar . " " . ord($schar));
			
			}
			$pos++;	
		}
		$ret_val = $new_word;
		$ret_val = str_replace("_", " ", $ret_val);


		$pos = strpos($ret_val, "  ");
		while (strlen($pos)>0) {
			$ret_val = str_replace("  ", " ", $ret_val);
			$pos = strpos($ret_val, "  ");				
		}
		
		#exit;
		$ret_val = trim($ret_val);
		return $ret_val;
	}
	function ExtractSpecialCharacters(&$sstring) {
		$line_length = strlen($sstring);
		$x = 0;
		$aryAmps = split("&", $sstring);
		$items_removed = 0;
		while ($x < count($aryAmps)) {
			$item = $aryAmps[$x];
			$pos = strpos($item, ";");
			if (strlen($pos)>0) {
				
				$amp_internals = substr($item, 0, $pos);
				
				$amp_item = "&" . $amp_internals . ";";
				#$this->ui->dbp("amp_item: " . $amp_item);
				if ( strlen($amp_item) > 3) {
					//dbp("amp_internals: " . $amp_internals);
					if ($amp_internals!='') {
						#$this->ui->dbp("amp_item: " . $amp_item);
						$sstring = str_replace($amp_item, " ", $sstring);
						#$this->ui->dbp("sstring: " . $sstring);
						$items_removed = 1;
						#exit;
						$error_count++;	
					}
					else {
						//dbp("It is safe");
					}
		
				}
				
			}
			$x++;
		}
		return $items_removed;
	}
	function GetValueFromXMLItemByName($ui, $xml_item, $item_name, $xml_parent_name = "") {
		$ret_val = "";
		if (strlen($xml_parent_name)>0) {
			$pos = strpos("<" . $xml_item . ">", "<" . $xml_parent_name . ">");
			if (strlen($pos)>0) {
				$ret_val = $xml_item;
				$pos++;
				$ret_val = substr($ret_val, ($pos));
				$pos = strpos($ret_val , ">");
				$pos++;
				$ret_val = substr($ret_val, ($pos));
				$pos = strpos($ret_val , "</" . $xml_parent_name);
				$ret_val = substr($ret_val, 0, $pos);
				$xml_item = $ret_val;
				$ret_val = "";
				#$ui->dbp("  PARENT ret_val: " . $ret_val);
				
			}
		}
		
		$pos = strpos("<" . $xml_item . ">", "<" . $item_name . ">");
		if (strlen($pos)==0) {
			$pos = strpos("<" . $xml_item . " ", "<" . $item_name . " ");
		}
		if (strlen($pos)>0) {
			$ret_val = $xml_item;
			$pos++;
			$ret_val = substr($ret_val, ($pos));
			$pos = strpos($ret_val , ">");
			$pos++;
			$ret_val = substr($ret_val, ($pos));
			$pos = strpos($ret_val , "</");
			$ret_val = substr($ret_val, 0, $pos);
			
			#$ui->dbp("  ret_val: " . $ret_val);
			
		}
		if ($pos==0) {
			$pos = strpos($xml_item, $item_name . "=");
			if (strlen($pos)>0) {
				$ret_val = $xml_item;
				$pos++;
				$ret_val = substr($ret_val, ($pos));
				$pos = strpos($ret_val , "=");
				$pos++;
				$ret_val = substr($ret_val, ($pos));
				$pos = strpos($ret_val , ">");
				$ret_val = substr($ret_val, 0, $pos);
				#$ui->dbp("  ret_val: " . $ret_val);
				if (substr($ret_val, 0, 1) == chr(34)) {
					$ret_val = substr($ret_val, 1, strlen($ret_val));
					$pos = strpos($ret_val, chr(34));
					$ret_val = substr($ret_val, 0, $pos );
					#$ui->dbp("  ret_val: " . $ret_val);
				}
				$ret_val = str_replace(chr(34), "", $ret_val);
				#$ui->dbp("  ret_val: " . $ret_val);
				
				
			}
		}
		$ret_val = $this->XMLDecode($ret_val);
		//
		$ret_val = str_replace("<!CDATA[[", "", $ret_val);
		$ret_val = str_replace("]]", "", $ret_val);
		
		$ret_val = trim($ret_val);
		
		return $ret_val;
		
	}
	function XMLDecode($ret_val) {
		$ret_val = str_replace("&amp;", "&", $ret_val);
		$ret_val = str_replace("&AMP;", "&", $ret_val);
		return $ret_val;
	}
}
?>