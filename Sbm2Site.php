<?php
/*----------------------------------------------------------------------------
Sbm2Site.php - convert input sbm into a nice-looking HTML site

----------------------------------------------------------------------------*/

class Sbm2Site {
	
	function __construct($docTitle, $displayTitle, $sessionFolder, $inputFileName, $outputDir) {	
		require_once("PHPDebug.php");
		$this->debug = new PHPDebug();
		$this->debug->debug("Sbm2Site - init");
		$this->debug->debug("Sbm2Site - output dir: $outputDir");
		
		$this->docTitle = $docTitle;
		$this->displayTitle = $displayTitle;
		$this->sessionFolder = $sessionFolder;
		$this->inputFileName = $sessionFolder.$inputFileName;
		$this->tocTableFileName = $sessionFolder.$docTitle.".toctable";
		$this->discoveredStylesFileName = $sessionFolder.$docTitle.".styles";
		$this->discoveredAssetsFileName = $sessionFolder.$docTitle.".assets";
		$this->missingAssetsFileName = $sessionFolder.$docTitle.".missingassets";
		$this->outputDir = $outputDir;
		
	   	$log = "nil";
		require_once("MopLog.php");
		MopLog_Init("Sbm2Site.log");
		
		//read config file
		$iniFileName = "Sbm2Site.ini";
		$this->ini_array = parse_ini_file($iniFileName);
		MopLog("$iniFileName entries...");
		foreach($this->ini_array as $key => $value){			
			MopLog("$key => $value");
		}
		$this->debug->debug("Read ".count($this->ini_array)." entries from $iniFileName...");
		
		//create tag table
		$sbmTagTableFileName = $this->ini_array['TagTable'];
		$this->sbmTags = array();

		$fpIn = fopen($sbmTagTableFileName, "r");
		if($fpIn) {
			while (($ln = fgets($fpIn)) !== false) {
				$tag = explode('|', trim($ln));
				$tag_nice = array();
				if(array_key_exists(0, $tag)) { $tag_nice['sbmTag'] = $tag[0]; }
				if(array_key_exists(1, $tag)) { $tag_nice['comment'] = $tag[1]; }
				if(array_key_exists(2, $tag)) { $tag_nice['htmlOpen'] = $tag[2]; }
				if(array_key_exists(3, $tag)) { $tag_nice['htmlClose'] = $tag[3]; }
				if(array_key_exists(4, $tag)) { $tag_nice['dir'] = $tag[4]; }
				$this->sbmTags[] = $tag_nice;
			}
		}
		else {
			MopLog("ERROR: Couldn't open $sbmTagTableFileName");
		}
		fclose($fpIn);
		$this->debug->debug("Read ".count($this->sbmTags)." entries from $sbmTagTableFileName...");
		
		//recreate toc table from disk
		$this->tocTable = array();
		$this->tocTable = unserialize(file_get_contents($this->tocTableFileName));

		//recreate discovered styles table from disk
		$this->discoveredStyles = array();
		$this->discoveredStyles = unserialize(file_get_contents($this->discoveredStylesFileName));
		
		//recreate discovered styles table from disk
		$this->discoveredAssets = array();
		$this->discoveredAssets = unserialize(file_get_contents($this->discoveredAssetsFileName));
		
		//missing assets table
		$this->missingAssets = array();
		$this->missingVariantAssets = array();
		
		//variant asset tables
		$this->variantVideoAssets = array();
		$this->variantAudioAssets = array();

		/*----------------------------------------------------------------------------
			START: Asset management
		----------------------------------------------------------------------------*/
		$bFullNukeOutputDir = true; //set this to true to disable incremental update of assets
		
		if($bFullNukeOutputDir) {
			//totally remove $outputDir - start with a 100% clean slate
			MopLog("FULL nuke of $outputDir");
			if($outputDir) {
				$tmp = escapeshellarg($outputDir);
				MopLog("nuking $tmp");
				$command = "rm -rfv $tmp 2>&1 1> /dev/null";
				MopLog($command);
				MopLog(shell_exec($command));
			}
		}
		else {
			//only nuke "light" assets (i.e.: html, css = yes. Movies, pics, audio = no)
			MopLog("TACTICAL nuke of $outputDir - only clean light assets (*.html)");
			$tmp = escapeshellarg($outputDir);
			MopLog("cleaning $tmp");
			$command = "rm $tmp/*.html 2>&1 1> /dev/null";
			MopLog($command);
			MopLog(shell_exec($command));			
		}
		
		//make output dir if it doesn't exist
		if(file_exists($outputDir) == false) {
			MopLog("Attempting to create site folder: $outputDir");
			mkdir($outputDir, 0777);
			MopLog("Created site folder: $outputDir");
		}
		else {
			$this->debug->debug("ERROR: Couldn't create $outputDir");
		}		
		
		$this->LogOutputSnapshot($outputDir);
		
		//ensure standard directory tree exists...
		MopLog("ensure standard directory tree exists...");
		if(!file_exists($outputDir."Images")) { mkdir($outputDir."Images", 0777); }
		if(!file_exists($outputDir."Video")) { mkdir($outputDir."Video", 0777); }	
		//if(!file_exists($outputDir."Posters")) { mkdir($outputDir."Posters", 0777); }	
		if(!file_exists($outputDir."Audio")) { mkdir($outputDir."Audio", 0777); }
		$this->LogOutputSnapshot($outputDir);
		
		//copy images from source doc into images
		MopLog("copy all embedded images to 'images'");
		if(!file_exists($outputDir."images")) { mkdir($outputDir."images", 0777); }
		$src = escapeshellarg($this->sessionFolder."images/");
		$dest = escapeshellarg($this->outputDir."images/");
		$this->SyncFolders($src, $dest);
		$this->LogOutputSnapshot($outputDir);

		//copy all from Snippets/Images into Images
		MopLog("copy all images from Snippets/Images to 'Images'");
		$src = escapeshellarg($this->ini_array['SnippetsDir']."Images/");
		$dest = escapeshellarg($this->outputDir."Images/");
		$this->SyncFolders($src, $dest);
		$this->LogOutputSnapshot($outputDir);

		//copy all from Snippets/Audio into Audio
		MopLog("copy all audio from Snippets/Audio into 'Audio'");
		$src = escapeshellarg($this->ini_array['SnippetsDir']."Audio/");
		$dest = escapeshellarg($this->outputDir."Audio/");
		$this->SyncFolders($src, $dest);
		$this->LogOutputSnapshot($outputDir);
		
		//copy all from Snippets/Video into Video
		MopLog("copy video from Snippets/Video into 'Video'");
		$src = escapeshellarg($this->ini_array['SnippetsDir']."Video/");
		$dest = escapeshellarg($this->outputDir."Video/");
		$this->SyncFolders($src, $dest);
		$this->LogOutputSnapshot($outputDir);		
		
		//copy all discovered Assets into the appropriate location
		MopLog("copy discovered assets...");
		MopLog_lf();
		foreach($this->discoveredAssets as $k => $asset) {
			$asset = str_replace("\"", "", $asset);
			MopLog("Attempting: $asset");
			
			//first determine file type using extension
			$ext = substr($asset, strrpos($asset, ".") + 1);
			$ext = strtolower($ext);
			
			switch($ext) {
				case "gif":
				case "jpg":
				case "png":
					MopLog_i("$asset is an IMAGE with extension '$ext'", 1);
					$this->CopyFile($asset, $outputDir."Images/", false);
				break;
				
				case "ogv":
				case "webm":
				case "mp4":
					MopLog_i("$asset is VIDEO with extension '$ext'", 1);
					$this->CopyFile($asset, $outputDir."Video/", false);
					
					//add other variants for next step
					switch($ext) {
						case "ogv":
							$this->variantVideoAssets[] = $this->TrimExtension($asset)."webm";
							$this->variantVideoAssets[] = $this->TrimExtension($asset)."mp4";
						break;
						case "webm":
							$this->variantVideoAssets[] = $this->TrimExtension($asset)."ogv";
							$this->variantVideoAssets[] = $this->TrimExtension($asset)."mp4";
						break;
						case "mp4":
							$this->variantVideoAssets[] = $this->TrimExtension($asset)."webm";
							$this->variantVideoAssets[] = $this->TrimExtension($asset)."ogv";
						break;
					}
					
					//copy poster for this asset
					$assetPoster = substr($asset, 0, strrpos($asset, ".") + 1)."jpg";
					MopLog_i("Copy poster: $assetPoster", 1);
					$this->CopyFile($assetPoster, $outputDir."Video/", false);
					
				break;
				
				case "ogg":
				case "mp3":
					MopLog_i("$asset is AUDIO with extension '$ext'", 1);
					$this->CopyFile($asset, $outputDir."Audio/", false);
					
					//add other variants for next step
					switch($ext) {
						case "ogg":
							$this->variantAudioAssets[] = $this->TrimExtension($asset)."mp3";
						break;
						case "mp3":
							$this->variantAudioAssets[] = $this->TrimExtension($asset)."ogg";
						break;
					}					
				break;
				
				default:
					MopLog_i("WARNING: Unknown extension '$ext' encountered. Asset: 'asset'", 1);

			}
			MopLog_lf();
		}
		$this->LogOutputSnapshot($outputDir);
		
		//copy all variant Assets into the appropriate location
		MopLog("copy variant assets...");
		MopLog_lf();
		foreach($this->variantVideoAssets as $k => $asset) {
			MopLog("Attempting: $asset");
			$this->CopyFile($asset, $outputDir."Video/", true);
		}
		foreach($this->variantAudioAssets as $k => $asset) {
			MopLog("Attempting: $asset");
			$this->CopyFile($asset, $outputDir."Audio/", true);
		}	
		MopLog_lf();
		$this->LogOutputSnapshot($outputDir);
		
		/*----------------------------------------------------------------------------
			END: Asset management
		----------------------------------------------------------------------------*/		
	}

	function TrimExtension($f){
		return substr($f, 0, strrpos($f, ".")+1);
	}
	
	function CopyFile($s, $d, $bVariant) {
		MopLog_i("pwd: ".trim(shell_exec("pwd")), 1);
		MopLog_i("src: ".$s, 1);
		MopLog_i("dest: ".$d, 1);
		
		$s = "../".$s;
		if (file_exists($s)) {
			$s = escapeshellarg($s);
			$d = escapeshellarg($d);
			$command = "rsync -vc $s $d";
			MopLog($command);
			$rsyncOutput = shell_exec($command);
			MopLog($rsyncOutput);		
		} 
		else {
			if(!$bVariant) {
				MopLog("ASSET ERROR: can't find asset $s");
				$this->missingAssets[] = str_replace("../", "", $s);
			}
			else {
				MopLog("ASSET ERROR: can't find variant asset $s");
				$this->missingVariantAssets[] = str_replace("../", "", $s);				
			}
		}	
	}

	function SyncFolders($s, $d){
		MopLog_i("pwd: ".trim(shell_exec("pwd")), 1);
		MopLog_i("src: ".$s, 1);
		//MopLog(shell_exec("ls -alR ".escapeshellarg($s)));
		MopLog_i("dest: ".$d, 1);
		//MopLog(shell_exec("ls -alR ".escapeshellarg($d)));
		
		$command = "rsync -vcr $s $d";
		MopLog($command);
		$rsyncOutput = shell_exec($command);
		MopLog($rsyncOutput);		
	}
	
	function LogOutputSnapshot($dir){
		MopLog_lf();
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".escapeshellarg($dir)));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();	
	}	

	function StartNewHtmlPage($pageName) {
		MopLog("Starting new HTML page: $pageName");
		$sectionName = str_replace(".html", "", $pageName);
		$this->currentSection = $sectionName;
		$this->debug->debug("Set current section to: $sectionName");
		$this->fpCurrent = fopen($this->outputDir."$sectionName.html", "w");
		
		$htmlHeaderFileName = $this->ini_array['SnippetsDir']."Header.html";
		$fpIn = fopen($htmlHeaderFileName, "r");
		$contents = fread($fpIn, filesize($htmlHeaderFileName));
		fclose($fpIn);
		
		$styleSheetFileName = $this->ini_array['SnippetsDir']."Style.css";
		$fpIn = fopen($styleSheetFileName, "r");
		$styleSheet = fread($fpIn, filesize($styleSheetFileName));

		//insert values from ini file into style sheet snippet
		if(array_key_exists("BgColor", $this->ini_array)) {
			$styleSheet = str_replace("@@bgcolor", $this->ini_array['BgColor'], $styleSheet);
		}
		else {
			$styleSheet = str_replace("@@bgcolor", "red", $styleSheet);
		}
		if(array_key_exists("BgImage", $this->ini_array)) {
			$this->debug->debug("BgImage");
			
			$styleSheet = str_replace("@@bgurl", "Images/".$this->ini_array['BgImage'], $styleSheet);
		}
		else {
			$styleSheet = str_replace("@@bgurl", "", $styleSheet);
		}
		
		//append discovered styles (classes only)
		$append = "/* class styles from Google Doc */\n";
		foreach($this->discoveredStyles as $key => $value) {
			if(strpos($key, ".") === 0) {
				$append = $append."$key{ $value }\n";
			}
		}
		$styleSheet = $styleSheet."\n".$append;
		
		//insert constructed style sheet into header
		$contents = str_replace("@@mop_style_sheet", $styleSheet, $contents);
		fclose($fpIn);
		
		//title
		$contents = str_replace("@@title", $this->displayTitle, $contents);

		fwrite($this->fpCurrent, $contents."\n");
		
		//insert body start
		$htmlBodyStartFileName = $this->ini_array['SnippetsDir']."BodyStart.html";
		$fpIn = fopen($htmlBodyStartFileName, "r");
		$contents = fread($fpIn, filesize($htmlBodyStartFileName));
		fwrite($this->fpCurrent, $contents."\n");
	}
	
	function EndCurrentHtmlPage() {
		$htmlBodyEndFileName = $this->ini_array['SnippetsDir']."BodyEnd.html";
		$fpIn = fopen($htmlBodyEndFileName, "r");
		$contents = fread($fpIn, filesize($htmlBodyEndFileName));
		fclose($fpIn);
		
		$helperScriptsFileName = $this->ini_array['SnippetsDir']."HelperScripts.js";
		$fpIn = fopen($helperScriptsFileName, "r");
		$helperScripts = fread($fpIn, filesize($helperScriptsFileName));
		$contents = str_replace("@@mop_helper_scripts", $helperScripts, $contents);
		fclose($fpIn);

		fwrite($this->fpCurrent, $contents."\n");
		
		fclose($this->fpCurrent);
		MopLog("Ended current HTML page");
	}
	
	function process() {
		$fpIn = fopen($this->inputFileName, "r");
		
		if($fpIn) {
			
			//create the first page
			$this->StartNewHtmlPage($this->ini_array['StartPage']);
			$this->startLink = $this->outputDir.str_replace(".html", "", $this->ini_array['StartPage']).".html";
			fwrite($this->fpCurrent, "<h1>$this->displayTitle</h1>\n");
			$dateStamp = date('l jS \of F Y h:i:s A');
			fwrite($this->fpCurrent, "<p>Generated: $dateStamp</p>\n");
			
			//checking for local storage and set up scoring
			fwrite($this->fpCurrent, "<script>\n");
			fwrite($this->fpCurrent, "if('localStorage' in window && window['localStorage'] !== null) {\n");
			//fwrite($this->fpCurrent, "\tdocument.write(\"<p>Browser supports Web Storage (that's good!)</p>\");\n");
			fwrite($this->fpCurrent, "\tlocalStorage['mop.score'] = ".$this->ini_array['StartScore'].";\n");
			fwrite($this->fpCurrent, "\tlocalStorage['mop.maxScore'] = ".$this->ini_array['MaxScore'].";\n");
			//fwrite($this->fpCurrent, "\tdocument.write(\"<p>Current score: \" + localStorage['mop.score'] + \"</p>\");\n");
			//fwrite($this->fpCurrent, "\tdocument.write(\"<p>Max score: \" + localStorage['mop.maxScore'] + \"</p>\");\n");
			fwrite($this->fpCurrent, "} else {\n");
			fwrite($this->fpCurrent, "\tdocument.writeln(\"<p>Problem: Browser doesn't support Web Storage (scoring won't work)...</p>\");\n");
			fwrite($this->fpCurrent, "}\n");
			fwrite($this->fpCurrent, "</script>\n");

			//main loop
			echo "<div class=\"sbmConsole\">\n";
			while($ln = fgets($fpIn)) {
				trim($ln);
				
				MopLog_lf(1);
				MopLog("******************************************************");
				MopLog("LINE: $ln");
				
				$outLn = "";
				$bTagLine = false;
				
				foreach($this->sbmTags as $tag) {
					if( strpos($ln, $tag['sbmTag']) !== false ) {
						MopLog("MATCH: ".$tag['sbmTag']);
						$bTagLine = true;
						
						$chunks = explode('|', trim($ln));
						
						//first chunk is always type
						if(array_key_exists(0, $chunks)) { $type = $chunks[0]; }
						
						//@@todo - need to separate matching and processing
						//1. iterate through tags and collect matches
						//2. (maybe: sort matches by some sensible criteria)
						//3. iterate through matches and apply transformations
						//This robustly solves the @@p vs @@placeholder problem
						
						//then it goes: text, id, data
						if(array_key_exists(1, $chunks)) { $text = $chunks[1]; } else { $text = ""; }
						if(array_key_exists(2, $chunks)) { $id = $chunks[2]; } else { $id = ""; }
						if(array_key_exists(3, $chunks)) { $param = $chunks[3]; } else { $param = ""; }
						
						//$text or $id might contain data. If so, move it to $param.
						if(strpos($text, "@@data") !== false) {
							MopLog_i("Found @@data tag in text field - converting to param...", 1);
							$param = $text;
							$text = "";
						}
						if(strpos($id, "@@data") !== false) {
							MopLog_i("Found @@data tag in id field - converting to param...", 1);
							$param = $id;
							$id = "";
						}
						
						//clean up $param
						if($param) {
							$param = str_replace('@@data', ' ', $param);
							$param = str_replace("'", "", $param);
						}
						
						MopLog("text: ".$text);
						MopLog("id: ".$id);
						MopLog("param: ".$param);
						
						//special handling for @@section - creates a new section (and therefore page)
						if($type == "@@section") {
							MopLog("section name: $id");
							$this->EndCurrentHtmlPage();
							echo "<p>----------SECTION $id----------</p>\n";
							$this->StartNewHtmlPage($id);
							
							$bDoAutoButton_Continue = true;
						}
						
						//special handling for @@buttons
						//@@button|Go to Section 2|Section 2
						if($type == "@@button" || $type == "@@scorebutton"){							

							//button - convert id to section name
							foreach($this->tocTable as $entry){
								//$this->debug->debug("toc $id: ".$entry['googleDocId']);
								
								if($entry['googleDocId'] == $id) {
									$this->debug->debug("converting $id into ".$entry['sectionName']);
									$id = $entry['sectionName'];
								}	
							}
							
							//user specified a button, so suppress automatic continue button...
							$bDoAutoButton_Continue = false;
							MopLog("Suppressing @@continue autobutton because user specified a @@button");
						}
						
						if($type == "@@begin"){
							$text = "Begin...";
							$id = $this->tocTable[0]['sectionName'];
						}
						
						if($type == "@@continue"){
							$text = "Continue...";
							$id = "";
							
							//autobutton - create a continue button that links to next (linear) section
							$n = 0;
							foreach($this->tocTable as $entry){
								if($entry['sectionName'] == $this->currentSection) {
									if(array_key_exists($n + 1, $this->tocTable)){
										$id = $this->tocTable[$n + 1]['sectionName'];
									}
									else {
										 //note: this means that failed look ups will create a button that links back home
										 //@@hack @@todo: this will break if user changes .ini file
										 $id = "index";
									}
								}
								$n++;
							}
						}

						if($type == "@@image" || $type == "@@video" || $type == "@@audio" ){
							switch($type) {
								case "@@image":
									$param = "$id";
									$id = "Images/$id";
								break;
								case "@@video": 
									$id = substr($id, 0, strrpos($id, "."));
									$param = "$id";
									$id = "Video/$id";
								break;
								case "@@audio":
									$id = substr($id, 0, strrpos($id, "."));
									$param = "$id";
									$id = "Audio/$id";
								break;
							}
						}
						if($type == "@@snippet" ){
							$param = file_get_contents("../".$id);
						}
						if($type == "@@mopplaceholder" ){
							$param = "$id";
							$id = "Images/$id";
						}						
						
						//default handling (for all @@tags)
						if(array_key_exists('htmlOpen', $tag)){
							$outLn = $tag['htmlOpen'];
							//MopLog("$outLn");
						}
						
						if(array_key_exists('htmlClose', $tag)){
							$outLn = $outLn.$tag['htmlClose'];
							//MopLog("$outLn");
						}
						
						if(1){
							$outLn = str_replace('@@id', $id, $outLn);
						}
						if(1){
							$outLn = str_replace('@@text', $text, $outLn);
						}										
						if(1){
							$outLn = str_replace('@@param', $param, $outLn);
						}
						
						if($type == "@@continue" && $bDoAutoButton_Continue == false) {
							MopLog("Suppressing @@continue autobutton");
							//kill auto continue button if flag is set
							$outLn = "";
						}
						
						if($outLn !== "") {
							MopLog("OUTLINE: $outLn");
							echo "<p>".trim(htmlentities($outLn))."</p>\n";
							fwrite($this->fpCurrent, trim($outLn)."\n");
						}
					}
					if($bTagLine == true) {
						continue;
					}
				}
				
				if(!$bTagLine){
					if($ln !== "") {
						echo "<p>".trim(htmlentities($ln))."</p>\n";
						$outLn = "<p>".trim($ln)."</p>\n";
						MopLog("OUTLINE: $outLn");
						fwrite($this->fpCurrent, $outLn."\n");
					}					
				}
			}
			MopLog("done");
			
			echo "</div>\n";
			echo "<div class=\"sbmDevControls\">\n";
			echo "<p>Sbm2Site debug information: <button onclick=\"window.open('Sbm2Site.log')\">Sbm2Site.log</button><button onclick=\"window.open('$this->inputFileName')\">Input SBM</button></p>\n";
			echo "</div>\n";
			echo "<p><button onclick=\"MopReload()\">Update eBook</button> <- Update with latest changes from source doc</p>\n";
			echo "<p><button onclick=\"window.open('$this->startLink')\">View eBook</button> <- Open preview in a new window...</p>\n";
			echo "<form id='deploy'>\n";
			echo "<p><input type=\"submit\" name=\"deploy\" value=\"Deploy eBook\"> <- Deploy eBook to shareable link...</p>\n";
			echo "<input type=\"hidden\" name=\"docname\" value=\"".$this->docTitle."\">\n";
			echo "</form>\n";
			
			if(count($this->missingAssets) > 0 || count($this->missingVariantAssets) > 0){
				//we encountered missing assets during asset management, so report them
				echo "<hr/>\n";
				echo "<div style=\"float:left\"><img src=\"images/warning.png\" style=\"width:64px\"></img></div>\n";
				echo "<div><h2>Errors...</h2></div>\n";
				//echo "<br/>\n";
				foreach($this->missingAssets as $missingAsset) {
					echo "<p class=\"mop_ErrorText\">Couldn't find: $missingAsset (try searching for it in source doc - this is probably a typo...)</p>\n";
				}
				foreach($this->missingVariantAssets as $missingVariantAsset) {
					echo "<p class=\"mop_ErrorText\">Couldn't find: $missingVariantAsset (this is a variant asset)</p>\n";
				}
				if(count($this->missingVariantAssets) > 0) {
					echo "<p class=\"mop_ErrorText\">BookMarkup expects to find <strong>.webm</strong>, <strong>.mp4</strong> and <strong>.ogv</strong> files for all video assets. BookMarkup expects to find <strong>.mp3</strong> and <strong>.ogg</strong> files for all audio assets. You specify any one, BookMarkup will look for the variants automatically. A missing variant asset means that the asset you specified in the source doc was found, but one or more of the required variants is missing. User the File Browser to check that the variants exist, and have the correct extension.</p>\n";
				}
			}
			
			
			echo "<hr/>\n";
			echo "<h2>Preview</h2>\n";
			//echo "<div id=\"mop_Preview\">\n";
			echo "<iframe id=\"mop_Preview\" src=\"$this->startLink\"></iframe>\n";
			//echo "</div>";
			$this->EndCurrentHtmlPage();
			return true;
		}
		else {
			return false;
		}	
	}
}
?>