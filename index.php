<?php
	$sid = "default";
	if(array_key_exists('PHPSESSID', $_COOKIE)) {
		$sid = $_COOKIE['PHPSESSID'];
	}
	else {
		session_start();
		$sid = htmlspecialchars(session_id());
	}
	
	$sessionFolder = "Sessions/$sid/";
	if(file_exists($sessionFolder) == false) {
		mkdir($sessionFolder, 0777);
	}
	
	if(file_exists($sessionFolder) == true) {
		setcookie("SerinetteToolsSessionFolder", $sessionFolder);
	}
	else {
		//ERROR: Session folder doesn't exist
	}
	
	$sessionDoc = "$sessionFolder/SessionDoc";
	if(file_exists($sessionDoc) == true) {
		$fpIn = fopen($sessionDoc, "r");
		$doc = fread($fpIn, filesize($sessionDoc));
		setcookie("SerinetteToolsSessionDoc", $doc);
		fclose($fpIn);
	}
	if(file_exists("index.log") == true) {
		$command = "rm ".escapeshellarg("index.log");
		$output = shell_exec($command);
	}
	$indexLog = fopen("index.log.html", "w");
	fwrite($indexLog, "<!DOCTYPE HTML>\n<html>\n<head>\n<title>index.log</title>\n</head>\n<body>\n");
	fwrite($indexLog, "index.php - START\n");
	fwrite($indexLog, "index.php - session folder is <a href='$sessionFolder'>$sessionFolder</a>\n");
?>
<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<!-- adapted from instructions at http://code.google.com/apis/accounts/docs/OAuth2.html -->
	<!-- note that we are using v2.0 of the Google Docs API -->
	
	<link href='http://fonts.googleapis.com/css?family=Meddon|Sue+Ellen+Francisco&v2' rel='stylesheet' type='text/css'>
	
	<link rel="stylesheet" href="../../css/basic.css">
	
	<style>
.HelpText {
	font-family:"Courier New", Courier, monospace;
	font-size:default;
}
table
{
	font-family:"Courier New", Courier, monospace;
	font-size:small;
	border-collapse:collapse;
	width:100%;
}
table,th,td
{
	border: 1px solid grey;
	padding:15px;
	vertical-align:top;
}	
	
	</style>
		
	<script src="../../Libs/jquery.js"></script>
	<script type="text/javascript">
		function getHashVar(v) { 
			//document.write("getHashVar - testing " + v + "<br\>\n");
			var query = window.location.hash.substring(1);
			var vars = query.split("&"); 
			for (var i=0;i<vars.length;i++) { 
				var pair = vars[i].split("="); 
				if (pair[0] == v) {
					//document.write("getHashVar - returning " + pair[1] + "<br\>\n");
					return pair[1]; 
				} 
			}			
			//document.write("getHashVar - returning false<br\>\n");
			return false;
		}
	
		function setCookie(c_name,value,exdays) {
			var exdate=new Date();
			exdate.setDate(exdate.getDate() + exdays);
			var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
			document.cookie=c_name + "=" + c_value;
		}	
	
		function getCookie(c_name) {
			var i,x,y,ARRcookies=document.cookie.split(";");
			for (i=0;i<ARRcookies.length;i++)
			{
				x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
				y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
				x=x.replace(/^\s+|\s+$/g,"");
				if (x==c_name)
				{
					return unescape(y);
				}
			}
		}
		
		function Reveal(){
			//console.log("Reveal()");
			$(".reveal").fadeOut(0);
			$(".reveal").fadeIn(1000);
			$(".revealSlower").fadeOut(0);
			$(".revealSlower").fadeIn(2000);		
			$(".revealSlowest").fadeOut(0);
			$(".revealSlowest").fadeIn(4000);
		}
		
		function ToggleSbmConsoles(){
			//$('.sbmConsole').toggle("slow");
			$('.sbmDevControls').toggle("slow");
		}
	</script>

	<title>Serinette BookMarkup Tool</title>
</head>
<body>
<div class="centered" id="logobox">
	<img src="../../images/logo_alpha_tiny.png"></img>
</div>
<ul class="topNav">
	<li><a href="index.php">Home</a></li>
	<li><a href="Browser.php">File Browser</a></li>
</ul>
<br/>
<br/>
<h1 style="text-align:center">BookMarkup Tool</h1>
<p style="text-align:right"><button type="button" onclick='ToggleSbmConsoles()'>Show Dev Stuff</button>
<button onclick="window.open('index.log.html')">index.log</button></p>
<script type="text/javascript">	
	var n = "SerinetteToolsOauth2AccessToken";
	var c = getCookie(n);

	if(c){
		//document.write("<p>Found cookie: " + c + "</p>\n");
		var r = getHashVar("reauth");
		if(r) {
			document.write("<p>Forcing reauthorization</p>\n");
			document.write("<p>Revoking cookie: " + c + "</p>\n");
			setCookie("SerinetteToolsOauth2AccessToken", "", -3600); //unset token cookie
			
			setTimeout(function () { window.location='index.php'; },200);
		}
		else {
			//document.write("<p>Didn't find reauth in hashtag</p>\n");
		}
	}
	else {
		//does hash contain access_token?
		var t = getHashVar("access_token"); 
		if(!t){
			
			//does hash contain an error?
			var e = getHashVar("error");
			if(e) {
				document.write("<p>Uh oh. Something went wrong with authentication. Please try again...</p><br/>");
				document.write("<p><button type=\"button\" onclick=\"window.location.assign('index.php#reauth=true')\">Try Again</button></p>");
			}
			
			//send user off to get an access token...
			document.write("<hr/>");
			document.write("<p>I need permission to access your Google Docs account.</p>");
			document.write("<p>Click the \"Authenticate\" button to visit Google's permission page.</p>");
			document.write("<p>Please click <strong>Allow Access</strong> when prompted.</p>");
			document.write("<ul>\n");
			document.write("<li>Authentication lasts for approximately one hour.\n");
			document.write("<li>If you've authenticated before, you may not see the Google permission page - that's normal.\n");
			document.write("<li>BookMarkup Tool will not alter, delete, share or do anything else unpleasant to your docs!\n");
			document.write("</ul>\n");
			
			//if we find "BookMarkup_dev" in the URL, we set the redirect URI accordingly. Otherwise default to public tool URI.
			if(document.URL.indexOf("BookMarkup_dev") != -1) {
				document.write("<p><button type=\"button\" onclick=\"window.location=\'https://accounts.google.com/o/oauth2/auth?client_id=693213664167.apps.googleusercontent.com&redirect_uri=http://www.serinette.com/Tools/BookMarkup_dev/index.php&scope=http://docs.google.com/feeds/&response_type=token\'\">Authenticate</button></p>");		
			}
			else {
				document.write("<p><button type=\"button\" onclick=\"window.location=\'https://accounts.google.com/o/oauth2/auth?client_id=693213664167.apps.googleusercontent.com&redirect_uri=http://www.serinette.com/Tools/BookMarkup/index.php&scope=http://docs.google.com/feeds/&response_type=token\'\">Authenticate</button></p>");			
			}
			document.write("<hr/>");
		}
		else {			
			//write access token to cookie
			setCookie(n, t, 1);
			c = getCookie(n);
			
			window.location.reload();
		}
	}	
</script>
<?php
	$docListRawFileName = $sessionFolder."doclist_raw.html";
	$docListFileName = $sessionFolder."doclist.csv";
	$t = ""; //token
	$okToGo = false;
		
	if (array_key_exists("SerinetteToolsOauth2AccessToken", $_COOKIE)) {
	
		$t = $_COOKIE["SerinetteToolsOauth2AccessToken"];
		
		//if we have a docname, send it off for processing. Otherwise display document list.
		$uri = $_SERVER['REQUEST_URI']; 	
		if(strpos($uri, "docname")) {
			$queryString = parse_url($uri, PHP_URL_QUERY);
			IndexLog("Query: $queryString\n");
			
			$queries = explode("&", $queryString);
			
			$processDoc = "";
			$processMode = "";
			foreach($queries as $query){
				$chunks = explode("=", $query);
				
				if($chunks[0] == "docname"){
					$processDoc = urldecode($chunks[1]);
				}
				if($chunks[0] == "make"){
					$processMode = "make";
				}
				if($chunks[0] == "open"){
					$processMode = "open";
				}
				if($chunks[0] == "deploy") {
					$processMode = "deploy";
				}
			}

			$fpOut = fopen("$sessionFolder/SessionDoc", "w"); //write a pref to text file, so we can set cookie next page refresh
			fwrite($fpOut, $processDoc);
			fclose($fpOut);
			
			IndexLog("We're going to $processMode $processDoc\n");
			//echo "We're going to $processMode $processDoc";
			
			$fp = fopen($docListFileName, "r");
			if($fp) {
				while($ln = fgets($fp)) {
					list($title, $id, $src, $alt, $etag) = explode(",",trim($ln));
					if($title === $processDoc) {
						if($processMode == "open") {
							IndexLog("Opening $processDoc\n");
							$alt = trim(addslashes($alt));
							echo "<script type=\"text/javascript\">window.open('$alt')</script>";
							echo "<script type=\"text/javascript\">window.location='index.php'</script>";
						}
						else if($processMode == "deploy") {
							$src = $sessionFolder.$title."/";
							
							//echo "<p>src: ".$src."</p>\n";
							//echo "<p>title: ".$title."</p>\n";
							
							require_once("DeploySite.php");
							$deploySite = new DeploySite($src, $title);
						}
						else if($processMode == "make"){							
							$bDevSkipDownload = true; //will be ignored if we're not in dev site
							$bDevSkipDownload = false; //will be ignored if we're not in dev site

							
							IndexLog("title: $title");
							IndexLog("etag: $etag");

							$dlFileName = $sessionFolder.$title.".zip";
														
							if(!$bDevSkipDownload && strpos($_SERVER['PHP_SELF'], "BookMarkup_dev") !== false){
								IndexLog("Downloading $dlFileName\n");
	
								//$src = $src."&oauth_token=$t"; //defaults to html if exportFormat is omitted
								$src = $src."&exportFormat=zip"."&oauth_token=$t";
								$etagHeader = "If-None-Match: \"$etag\""; //http://code.google.com/apis/documents/docs/2.0/developers_guide_protocol.html#RetrievingCached
								IndexLog("final src for cURL: $src\n");
	
								$ch = curl_init($src);
								curl_setopt($ch, CURLOPT_HEADER, 0);
								curl_setopt($ch, CURLOPT_HTTPHEADER, array($etagHeader));
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
								$result = curl_exec($ch);
								$info = curl_getinfo($ch);
								if( $result === false || $info['http_code'] != 200) {
									IndexLog("Did NOT download ".$dlFileName." because we got response code: ".$info['http_code']."\n");
								}
								else {
									IndexLog("Downloaded ".$dlFileName." because we got response code: ".$info['http_code']."\n");
									file_put_contents($dlFileName, $result);
								}
								curl_close($ch);
							}
							else {
								IndexLog("Download of ".$dlFileName." skipped becaused dev flag is set...\n");
							}
							
							//now we unzip file (even if we didn't download)...
							
							$htmlFileName = $sessionFolder.$title.".html";
							
							//clean previous versions
							if(file_exists($htmlFileName)) {
								$command = "rm ".escapeshellarg($htmlFileName);
								IndexLog($command."\n");
								$output = shell_exec($command);
							}
							if(file_exists("$sessionFolder"."images")) {
								$command = "rm -rf ".escapeshellarg("$sessionFolder"."images");
								IndexLog($command."\n");
								$output = shell_exec($command);
							}

							//check that it really is a zip file.  If something went wrong (e.g., authentication has expired) we will get an HTML file instead.
							$dlFileName = escapeshellarg($dlFileName);
							$bZipFile = false;
							$command = "file ".$dlFileName;
							IndexLog($command."\n");
							$output = shell_exec($command);
							if(strpos($output, "Zip archive data") !== false) {
								IndexLog("confirmed zip: $dlFileName\n");
								$bZipFile = true;
							}
							if($bZipFile) {
								$command = "unzip -o $dlFileName -d $sessionFolder";
								IndexLog($command."\n");
								shell_exec($command);
								
								IndexLog("rename: $htmlFileName\n");
								rename(str_replace(" ", "", $htmlFileName), $htmlFileName); //exporting as zip removes spaces from html filename. Here we replace them.
								
								require_once("Html2Sbm.php");
								$html2Sbm = new Html2Sbm($title, $sessionFolder, $processDoc.".html", $processDoc.".sbm");
								
								$displayTitle = $html2Sbm->process();
								
								if (!$displayTitle) {
									echo "<p>Error (401): Authentication has expired - #2</p>\n";
									echo "<p><button type=\"button\" onclick=\"window.location.assign('index.php#reauth=true')\">Try Again</button></p>";	
								}
								else {
									require_once("Sbm2Site.php");
									//note: Be careful! Fifth param (outputDir) in call to Sbm2Site will get nuked!
									$sbm2Site = new Sbm2Site($title, $displayTitle, $sessionFolder, $processDoc.".sbm", $sessionFolder.$title."/");
									$sbm2Site->process();
								}
							}
							else {
								//downloaded file is not a zip. This is very likely because authentication has expired (so our "zip" file is actually an HTML error page...)
								IndexLog("ERROR: $dlFileName is not a zip file...\n");
								echo "<p>Authentication has expired - you need to re-authenticate. Please click on the Home link (above) to start over.</p>\n";
							}							
							fclose($fp);
						}
						break;
					}
				}
			}
			else {
				echo "<p>Sorry, an internal error occurred.</p>\n";
				IndexLog("Error: couldn't read from $docListFileName\n");	
				echo "<p><button type=\"button\" onclick=\"window.location='index.php'\">Try Again</button></p>";
			}
		}
		else {
			IndexLog("No document specified...\n");
			//echo "No document specified...";
			
			//use curl to download document list from Google Docs
			$ch = curl_init("https://docs.google.com/feeds/documents/private/full/-/document?oauth_token=$t");			
			$fp = fopen($docListRawFileName, "w");
			if($fp) {
				curl_setopt($ch, CURLOPT_FILE, $fp);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('GData-Version: 2.0'));
				curl_exec($ch);
				curl_close($ch);
			}
			else {
				echo "<p>Sorry, an internal error occurred.</p>\n";
				IndexLog("Error: couldn't write to $docListRawFileName\n");
			}
			fclose($fp);
			
			$fp = fopen($docListRawFileName, "r");
			$fp2 = fopen($docListFileName, "w");
			if($fp && $fp2) {
				$entries = fread($fp, filesize($docListRawFileName));
				if(strpos($entries, "Error 401")) {
					$okToGo = false;
					echo "<p>Error (401): Authentication has expired - #1</p>\n";
					//echo "<p><button type=\"button\" onclick=\"window.location.href('index.php?reauth=true')\">Try Again</button></p>";
					echo "<p><a href=\"index.php#reauth=true\" target=\"_self\">Try Again</a></p>";
					echo "<p>If you get stuck, try manually reloading the page - it's a bug, sorry =] If you're really stuck, close and re-open your browser (all windows/tabs) and try again.</p>";
					//echo "<p><a href='http://www.google.com'>Try Again</a></p>";		
				}
				else {
					$entries = explode("<entry", $entries);
					$okToGo = true;
				}
			}
			else {
				echo "<p>Sorry, an internal error occurred.</p>\n";
				IndexLog("Error: couldn't read from $docListRawFileName (or couldn't write to $docListFileName)\n");
			}
			fclose($fp);
						
			if($okToGo === true){		
				//doc chooser goes here:
				echo "<h2>Choose Document</h2>\n";
				echo "<form id='doclist'>\n";
				echo "<p>\n<select id=\"doclistSelect\" name=\"docname\">\n";
								
				foreach($entries as $entry){	
					//skip first line
					if(strpos($entry, "<feed xmlns")){
						continue;
					}
									
					$etag = trim(YankData($entry, "gd:etag='&quot;", "&quot;'>"));
					$id = trim(YankData($entry, "<id>", "</id>"));
					$title = trim(YankData($entry, "<title>", "</title>"));	
					$src = trim(YankData($entry, "<content type='text/html' src='", "'/>"));
					$alt = trim(YankData(trim($entry), "<link rel='alternate' type='text/html' href='", "'/>"));
					
					if(array_key_exists('SerinetteToolsSessionDoc', $_COOKIE)){
						if($title == $_COOKIE['SerinetteToolsSessionDoc']){
							echo "\t<option selected = \"selected\" value=\"$title\">$title</option>\n";
						}
						else{
							echo "\t<option value=\"$title\">$title</option>\n";
						}
					}
					else {
						echo "\t<option value=\"$title\">$title</option>\n";
					}
					IndexLog(htmlentities("$title,$etag")."\n");
					fwrite($fp2, "$title,$id,$src,$alt,$etag\n");
				}
				echo "</select>\n";
				echo "<input type=\"submit\" name=\"make\" value=\"Make eBook\">\n";
				echo "<input type=\"submit\" name=\"open\" value=\"Open\">\n</p>\n";
				echo "</form>\n";
				
				//do help
				echo "<hr/>\n";
				echo "<h2>Help</h2>\n";
				echo "<p class=\"HelpText\">Most standard HTML formatting options are supported - text size/color, bold, italic, ordered and unordered lists, etc. Simply format the source doc the way you want it, and most formatting will be preserved or translated.</p>\n";
				echo "<p class=\"HelpText\">An important exception is the \"Heading 1\" style, which is used to create new sections. For user-visible headings use \"Heading 2\" and below.</p>\n";
				echo "<p class=\"HelpText\">BookMarkup recognizes the following special tags in a source doc:</p>\n";
				echo "<table>\n";
				
				$help_array = parse_ini_file("Html2Sbm.ini", true);
				foreach($help_array as $help_item) {
					//print_r($help_item);
					if(array_key_exists('needle', $help_item)) {
						if(strpos($help_item['needle'], '@@') !== false) {
							echo "<tr>\n";
							echo "\t<td>\n";
							echo trim(htmlspecialchars($help_item['needle']))."\n";
							echo "\t</td>\n";
							echo "\t<td>\n";
							if(array_key_exists('help', $help_item)) {
								echo trim(htmlspecialchars($help_item['help']))."\n";
							}
							else {
								echo("<em>No help available for this tag</em>");
							}
							echo "\t</td>\n";
							echo "\t<td>\n";
							if(array_key_exists('help_example', $help_item)) {
								echo trim(htmlspecialchars("Syntax: ".$help_item['help_example']))."\n";
							}
							else {
								echo("<em>No help available for this tag</em>");
							}
							echo "\t</td>\n";
							
							echo("</tr>\n");
						}
					}
				}
				
				echo "</table>";
				
			}
			fclose($fp2);
		}
	}
	else {
		//echo "<p>Authentication required!</p>";
	}
	
	IndexLog("index.php - DONE\n");
	IndexLog("</body>\n</html>\n");
	fclose($indexLog);

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
	
	function IndexLog($msg){
		global $indexLog;
		fwrite($indexLog, "<p>$msg</p>");
	}
?>
<script>
	$(document).ready(function(){
		Reveal();
		$('.sbmConsole').hide();
		$('.sbmDevControls').hide();
		$('.sbmDevControls').hide();
	});
	
	<!-- @@mop_helper_scripts|standard mop helper scripts -->

	function MopReload() {
		window.location.reload()
	}
</script>
</body>
</html>