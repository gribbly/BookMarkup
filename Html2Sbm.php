<?php

/*----------------------------------------------------------------------------
Html2Sbm.php - convert input html into Serinette BookMarkup (.sbm)

	Sbm tag rules:
		
		* Tag|text|id|@@data''
			tag = sbmTag (@@h1, @@section)
			text = user-visible text (e.g., button label, paragraph text)
			id = link or id (e.g., section name)
			@@data'' = passthrough data (e.g., css styles)
			
		* Order is important. It's tag, text, id, data.
		
		* If you don't have text, leave it blank: tag||id
		
		* @@data just needs to go last - i.e., tag|@@data'whatever' is OK. tag||id|@@data'whatever' is also OK.
		

----------------------------------------------------------------------------*/

class Html2Sbm {
	
	function __construct($docTitle, $sessionFolder, $inputFileName, $outputFileName) {			
		require_once("PHPDebug.php");
		$this->debug = new PHPDebug();
		$this->debug->debug("Html2Sbm - init");
		
	   	$log = "nil";
	   	echo "<p style=\"text-align:right; font-size:xx-small; color: #898989;\">";
		require_once("MopLog.php");
		echo "</p>\n";
		echo "<hr/>\n";
		echo "<h2>$docTitle</h2>\n";
		//echo "<p>Parsing \"$docTitle\"</p>\n";
		MopLog_Init("Html2Sbm.log");
		
		$this->inputFileName = $sessionFolder.$inputFileName;
		$this->outputFileName = $sessionFolder.$outputFileName;
		
		$this->tocTable = array();
		$this->tocTableFileName = $sessionFolder.$docTitle.".toctable";
		
		$this->discoveredStyles = array();
		$this->discoveredStylesFileName = $sessionFolder.$docTitle.".styles";
		
		$this->discoveredAssets = array();
		$this->discoveredAssetsFileName = $sessionFolder.$docTitle.".assets";
		
		$iniFileName = "Html2Sbm.ini";
		$this->ini_array = parse_ini_file($iniFileName, true);
		MopLog("$iniFileName entries...");
		foreach($this->ini_array as $sectionName => $sectionArray){		
			foreach($this->ini_array[$sectionName] as $sectionKey => $sectionValue){
				MopLog("$sectionName => $sectionKey => $sectionValue");
			}
		}
		$this->debug->debug("Read ".count($this->ini_array, 1)." entries from $iniFileName...");
	}
	
	function ExtractAttribs($ln, $needle) {
		MopLog("ExtractAttribs()");
		
		$attribs = trim($ln);

		//trim start
		$start = strpos($attribs, $needle) + strlen($needle);
		$attribs = substr($attribs, $start);
		$attribs = trim($attribs);
		
		//trim end - chomp back to (and including) any ">"
		while(strpos($attribs, ">") !== false) {
			$attribs = substr($attribs, 0, -1);
			//MopLog_i($attribs, 1);
		}
		MopLog_i($attribs, 1);
		
		return $attribs;
	}
	
	function YankData($line, $startTag, $endTag){
		$data = "";
		if(strpos($line, $startTag)){
			$data = substr($line, strpos($line, $startTag) + strlen($startTag)); 	//snip everything up to and including $startTag
		}
		if(strpos($data, $endTag)){
			$data = substr($data, 0, strpos($data, $endTag)); 						//snip $endTag and everything after it
		}	
		return $data;
	}

	function RememberAsset($src) {
		MopLog("RememberAsset() - ".$src);
		
		//MopLog_i($src, 1);
		
		$this->discoveredAssets[] = $src;
	}
	
	function process() {
		if($this->inputFileName && $this->outputFileName) {
		
			$this->debug->debug("Html2Sbm::process() - input: $this->inputFileName output: $this->outputFileName");
			MopLog("Html2Sbm::process() - input: $this->inputFileName output: $this->outputFileName");

			//first let's ensure our input html is relatively clean
			$command = "tidy -q -w -o '".$this->inputFileName.".tidy' '".$this->inputFileName."'";
			MopLog($command);
			shell_exec($command);
			
			$fpIn = fopen($this->inputFileName.".tidy", "r");
			$fpOut = fopen($this->outputFileName, "w");
			
			//if(0){
			if($fpIn && $fpOut){
				MopLog("Parsing $this->inputFileName.tidy");
				echo "<div class=\"sbmConsole\">\n";				
				$parseMode = "preamble";

				while($ln = fgets($fpIn)) {
				
					//bail if we're not authorized
					if(strpos($ln, "Error 401") !== false) {
						echo "</div>\n";
						echo "<p>Authentication error - you probably need to re-authenticate. Try starting over.</p>\n";
						MopLog("Error 401");
						return false;
					}
					else {
						trim($ln);
						
						MopLog_lf(1);
						MopLog("******************************************************");
						MopLog("MODE: $parseMode");
						MopLog("LINE: $ln");
						
						$outLn = "";
						$bSuppressLine = false;	//output should be skipped (e.g., empty paragraph)
						$bNoParagraph = false; //don't interpret this line as paragraph (because it's already something else, like a button)
						$attribsFromEnclosingParagraph = ""; //some tags (like img) are nested inside a paragraph. We want to collect the attributes from the enclosing paragraph too
						
						//extract styles
						if(strpos($ln, "<style") !== false) {
							$parseMode = "style";
						}
						
						if(strpos($ln, "</style") !== false) {
							$parseMode = "preamble";
						}
						
						if($parseMode == "style") {
							$styles = explode("}", $ln);
							if(count($styles > 1)){
								//we found the css style line
								foreach($styles as $style) {
									$chunks = explode("{", $style);
									if(array_key_exists(0, $chunks) && array_key_exists(1, $chunks)) {
										$index = trim($chunks[0]);
										$this->discoveredStyles[$index] = trim($chunks[1]);
									}
								}
							}
						}
						
						//look through ini_array for tags that appear in this line
						foreach($this->ini_array as $searchTag) {
							if(strpos($ln, $searchTag['needle']) !== false) {

								MopLog("MATCH: ".$searchTag['needle']);
								
								$sbmTag = $searchTag['sbmtag'];
																				
								MopLog("ADD: $sbmTag");
								
								switch($sbmTag) {
									case "@@section":
										MopLog("AS: section");
										$id = trim(strip_tags($ln));
										
										//different "autobutton" handling depending on $parseMode
										switch($parseMode){
											case "preamble":
												$outLn = "@@begin|\n".$outLn."$sbmTag||$id\n";
											break;
											case "section":
												$outLn = "@@continue|\n".$outLn."$sbmTag||$id\n";
											break;
											default:
												$outLn = $outLn."$sbmTag|$text\n";
										}
												
										//now change $parseMode...
										$parseMode = "section";
										MopLog("MODE: $parseMode");
										
										//enter details we know in $tocTable
										$entry = array();
										$entry['googleDocH1Id'] = YankData($ln, " id=\"h.", "\"");
										$entry['googleDocBookmarkId'] = "don't know yet";
										$entry['sectionName'] = $id;
										$entry['buttonLabel'] = "don't know yet";
										$this->tocTable[] = $entry;
										
										MopLog_i($this->tocTable[count($this->tocTable)-1]['googleDocH1Id'], 1);
										MopLog_i($this->tocTable[count($this->tocTable)-1]['googleDocBookmarkId'], 1);
										MopLog_i($this->tocTable[count($this->tocTable)-1]['sectionName'], 1);
										MopLog_i($this->tocTable[count($this->tocTable)-1]['buttonLabel'], 1);
									break;
									
									case "@@button":
										MopLog("AS: button");
										$bNoParagraph = true;
										
										$chunks = explode("|", trim(strip_tags($ln)));
										
										if(array_key_exists(1, $chunks)) { $link = $chunks[1]; }
										if(array_key_exists(2, $chunks)) { $label = $chunks[2]; }
	
										$outLn = $outLn."$sbmTag|$label|$link\n";
										//MopLog("OUTLINE: $outLn");
									break;
									
									case "@@scorebutton":
										MopLog("AS: scorebutton");
										$bNoParagraph = true;
										
										$chunks = explode("|", trim(strip_tags($ln)));
										
										if(array_key_exists(1, $chunks)) { $link = $chunks[1]; }
										if(array_key_exists(2, $chunks)) { $label = $chunks[2]; }
										if(array_key_exists(3, $chunks)) { $data = $chunks[3]; }
	
										$outLn = $outLn."$sbmTag|$label|$link|$data\n";
										//MopLog("OUTLINE: $outLn");
									break;
									
									case "@@p":
										MopLog("AS: paragraph");
										if($bNoParagraph == true) {
											MopLog("ignoring due to prior match");
										}
										else {
											//@@todo @@hack
											//experimental - allow <span> tags through
											
											$text = trim(strip_tags($ln, '<span>'));
											
											MopLog("text (spans preserved): ".$text);
											
											//$text = trim(strip_tags($ln));
											//MopLog("text (stripped): ".$text");
											
											$outLn = $outLn."$sbmTag|".$text."\n";
										}										
									break;
									
									case "@@img":
										MopLog("AS: image");
										$bNoParagraph = true;
										$text = trim(strip_tags($ln,'<img>'));
										$text = str_replace("<img ", "", $text);
										$text = str_replace(">", "", $text);
										
										$src = $this->YankData($ln, 'src=', ' width=');
										$this->RememberAsset($src);
										
										$outLn = $outLn."$sbmTag|@@data'".$text."'\n";
										//$outLn = $outLn."$sbmTag|@@data'src=".$src."'\n"; //use this version to strip width and height, if desired
										MopLog("OUTLINE: $outLn");
									break;

									default:
										MopLog("AS: default");
										$bNoParagraph = true;
										$text = trim(strip_tags($ln));
										
										$outLn = $outLn."$sbmTag|".$text."\n";
										//MopLog("OUTLINE: $outLn");
								}
								
								//preserve tag attributes if instructed by ini
								if($searchTag['keepattribs'] && bNoParagraph == false) {
									MopLog("Keeping attributes for $sbmTag");

									$attribs = $this->ExtractAttribs($ln, $searchTag['needle']);
									
									$attribs = "@@data'$attribs'";
									MopLog_i("attribs: ".$attribs, 1);	

									$outLn = trim($outLn)."|".$attribs."\n";
								}
							}
						}
						
						if($outLn) {
							if($parseMode != "section") {
								MopLog("Suppressing output because MODE is $parseMode");
							}
							else if( $bSuppressLine == true ) {
								MopLog("Suppressing output because suppress flag got set (probably an empty paragraph)");
							}
							else if(strpos($outLn, "@@p||") !== false ) {
								MopLog("Suppressing output because this is an empty paragraph");
							}
							else {
								MopLog("OUTLINE: $outLn");
								echo "<p>$outLn</p>\n";
								fwrite($fpOut, $outLn);
							}	
						}
					}
				}
				fclose($fpIn);
				fclose($fpOut);
								
				//write tocTable to disk
				$fpOut = fopen("$this->tocTableFileName", "w");
				fwrite($fpOut, serialize($this->tocTable));
				fclose($fpOut);
				MopLog("Contents of $this->tocTableFileName");
				foreach($this->tocTable as $key1 => $value1) {
					MopLog("$key1");
					foreach($value1 as $key2 => $value2){
						MopLog_i("$key2 => $value2", 1);
					}
				}				
				
				//write discoveredStyles to disk
				$fpOut = fopen("$this->discoveredStylesFileName", "w");
				fwrite($fpOut, serialize($this->discoveredStyles));
				fclose($fpOut);

				echo "</div>\n";
				
				$sbmLink = $this->outputFileName;
				echo "<div class=\"sbmDevControls\">\n";
				echo "<p>Html2Sbm debug information: <button onclick=\"window.open('Html2Sbm.log')\">Html2Sbm.log</button><button onclick=\"window.open('$this->inputFileName.tidy')\">Input HTML</button>\n";
				echo "</div>\n";
				
				return true;			
			}
		}
		else {
			return false;
		}
	}
	
	function __destruct() {
	
	}
}
?>