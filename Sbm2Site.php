<?php
/*----------------------------------------------------------------------------
Sbm2Site.php - convert input sbm into a nice-looking HTML site

----------------------------------------------------------------------------*/

class Sbm2Site {
	
	function __construct($docTitle, $sessionFolder, $inputFileName, $outputDir) {	
		require_once("PHPDebug.php");
		$this->debug = new PHPDebug();
		$this->debug->debug("Sbm2Site - init");
		$this->debug->debug("Sbm2Site - output dir: $outputDir");
		
		$this->docTitle = $docTitle;
		$this->sessionFolder = $sessionFolder;
		$this->inputFileName = $sessionFolder.$inputFileName;
		$this->tocTableFileName = $sessionFolder.$docTitle.".toctable";
		$this->discoveredStylesFileName = $sessionFolder.$docTitle.".styles";
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

		//nuke $outputDir - we always start with a 100% clean slate
		if($outputDir) {
			$tmp = escapeshellarg($outputDir);
			MopLog("removing $tmp");
			$command = "rm -rfv $tmp 2>&1 1> /dev/null";
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
			$this->debug->debug("ERROR: Site folder didn't get nuked for some reason: $outputDir");
		}
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".escapeshellarg($outputDir)));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();
		
		//re-create standard directory tree
		MopLog("re-create standard directory tree");
		if(!file_exists($outputDir."Images")) { mkdir($outputDir."Images", 0777); }	
		//if(!file_exists($outputDir."Movies")) { mkdir($outputDir."Movies", 0777); }	
		//if(!file_exists($outputDir."Posters")) { mkdir($outputDir."Posters", 0777); }	
		//if(!file_exists($outputDir."Audio")) { mkdir($outputDir."Audio", 0777); }
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".escapeshellarg($outputDir)));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();		

		//copy all images from Snippets into Images
		MopLog("copy all images from Snippets into Images - rsync method");
		$src = $this->ini_array['SnippetsDir']."Images/";
		$dest = $this->outputDir."Images/";
		$src = escapeshellarg($src);
		$dest = escapeshellarg($dest);
		
		MopLog("pwd: ".shell_exec("pwd"));
		MopLog("src: ".$src);
		MopLog(shell_exec("ls -alR ".escapeshellarg($src)));
		MopLog("dest: ".$dest);
		MopLog(shell_exec("ls -alR ".escapeshellarg($dest)));
		
		$command = "rsync -vr $src $dest";
		//$command = "cp -v $src $dest 2>&1 1> /dev/null";
		MopLog($command);
		$rsyncOutput = shell_exec($command);
		MopLog($rsyncOutput);
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".escapeshellarg($outputDir)));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();
				
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
		
		$this->debug->debug("Read ".count($this->tocTable)." entries from $this->tocTableFileName...");
		foreach($this->tocTable as $entry){
			$this->debug->debug($entry['sectionName']);
		}
	}

	function StartNewHtmlPage($pageName) {
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
			
			/*
			//if necessary, copy the image file to the standard place in output dir
			if(file_exists($this->outputDir."Images/".$this->ini_array['BgImage']) == false) {
				$src = $this->ini_array['SnippetsDir']."Images/".$this->ini_array['BgImage'];
				$dest = $this->outputDir."Images/";

				$command = "cp '$src' '$dest'";
				$this->debug->debug($command);
				shell_exec($command);
			}
			else{
				$this->debug->debug("Skippping file copy because ".$this->outputDir."Images/".$this->ini_array['BgImage']." exists");
				$this->debug->debug(file_exists($this->outputDir."Images/".$this->ini_array['BgImage']));
			}
			*/
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
		$contents = str_replace("@@title", $pageName, $contents);

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
	}
	
	function process() {
		$fpIn = fopen($this->inputFileName, "r");
		
		if($fpIn) {
			
			//create the first page
			$this->StartNewHtmlPage($this->ini_array['StartPage']);
			$this->startLink = $this->outputDir.str_replace(".html", "", $this->ini_array['StartPage']).".html";
			fwrite($this->fpCurrent, "<h1>$this->docTitle</h1>\n");
			$dateStamp = date('l jS \of F Y h:i:s A');
			fwrite($this->fpCurrent, "<p>Generated: $dateStamp</p>\n");
			
			//checking for local storage and set up scoring
			fwrite($this->fpCurrent, "<script>\n");
			fwrite($this->fpCurrent, "if('localStorage' in window && window['localStorage'] !== null) {\n");
			fwrite($this->fpCurrent, "\tdocument.write(\"<p>Browser supports Web Storage (that's good!)</p>\");\n");
			fwrite($this->fpCurrent, "\tlocalStorage['mop.score'] = ".$this->ini_array['StartScore'].";\n");
			fwrite($this->fpCurrent, "\tdocument.write(\"<p>Current score: \" + localStorage['mop.score'] + \"</p>\");\n");
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
						
						//then it goes: text, id, data
						if(array_key_exists(1, $chunks)) { $text = $chunks[1]; } else { $text = ""; }
						if(array_key_exists(2, $chunks)) { $id = $chunks[2]; } else { $id = ""; }
						if(array_key_exists(3, $chunks)) { $param = $chunks[3]; } else { $param = ""; }
						
						//$text or $id might contain data. If so, move it to $param.
						if(strpos($text, "@@data") !== false) {
							MopLog_i("Found @@data tag in text field - converting to param...");
							$param = $text;
							$text = "";
						}
						if(strpos($id, "@@data") !== false) {
							MopLog_i("Found @@data tag in id field - converting to param...");
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
						
						//special handling for @@button
						//@@button|Go to Section 2|Section 2
						if($type == "@@button"){							

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
			echo "<p><button onclick=\"window.location='index.php'\">Start Over</button> <- Return to start and choose a new document...</p>\n";
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