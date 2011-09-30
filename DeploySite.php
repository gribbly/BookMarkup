<?php
/*----------------------------------------------------------------------------
DeploySite.php - use rsync to deploy a site from one location to another

----------------------------------------------------------------------------*/

class DeploySite {
	function __construct($src, $title) {	
		require_once("PHPDebug.php");
		$this->debug = new PHPDebug();
		
		$src = escapeshellarg($src);
		
		$this->debug->debug("DeploySite - src: $src");
		$this->debug->debug("DeploySite - title: $title");
		
	   	$log = "nil";
	   	echo "<p style=\"text-align:right; font-size:xx-small; color: #898989;\">";
		require_once("MopLog.php");
		echo "</p>\n";
		MopLog_Init("DeploySite.log");
		
		echo "<h2>Deploy</h2>\n";
		
		//read config file
		$iniFileName = "DeploySite.ini";
		$this->ini_array = parse_ini_file($iniFileName);
		MopLog("$iniFileName entries...");
		foreach($this->ini_array as $key => $value){			
			MopLog("$key => $value");
		}
		$this->debug->debug("Read ".count($this->ini_array)." entries from $iniFileName...");
		
		if(array_key_exists("DeployRoot", $this->ini_array)) {
			//assemble actual destination path:
			$dest = $this->ini_array['DeployRoot'].$title."/";
			$dest = escapeshellarg($dest);
			$this->debug->debug("DeploySite - dest: $dest");
		}
		else {
			$this->debug->debug("ERROR: Couldn't find DeployRoot in DeploySite.ini");
			return false;
		}
		
		MopLog("src: ".$src);
		MopLog("dest: ".$dest);
		MopLog("pwd: ".shell_exec("pwd"));

		//nuke $dest - we always start with a 100% clean slate
		if($dest) {
			$command = "rm -rfv $dest 2>&1 1> /dev/null";
			MopLog($command);
			MopLog(shell_exec($command));
		}
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".$dest));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();		
		
		//make output dir if it doesn't exist
		if(file_exists($dest) == false) {
			MopLog("Attempting to create site folder: $dest");
			//MopLog("mkdir ".$dest);
			MopLog(shell_exec("mkdir -pv $dest 2>&1 1> /dev/null"));
		}
		else {
			$this->debug->debug("ERROR: Destination folder didn't get nuked for some reason: $dest");
			MopLog("ERROR: Destination folder didn't get nuked for some reason: $dest");
		}
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".$dest));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();		

		/*
		MopLog("pwd: ".shell_exec("pwd"));
		MopLog("src: ".$src);
		MopLog(shell_exec("ls -alR ".escapeshellarg($src)));
		MopLog("dest: ".$dest);
		MopLog(shell_exec("ls -alR ".escapeshellarg($dest)));
		*/
		
		$command = "rsync -vr $src $dest";
		MopLog($command);
		$rsyncOutput = shell_exec($command);
		MopLog($rsyncOutput);
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".$dest));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();
		
		if(array_key_exists("UrlRoot", $this->ini_array)) {
			//assemble shareable url:
			$shareUrl = $this->ini_array['UrlRoot'].$title."/";
			//$shareUrl = urlencode($shareUrl);
			$this->debug->debug("DeploySite - shareUrl: ".$shareUrl);
			MopLog("shareUrl: ".$shareUrl);
		}
		else{
			$this->debug->debug("ERROR: Couldn't find UrlRoot in DeploySite.ini");
			echo "ERROR: Couldn't find UrlRoot in DeploySite.ini\n";
		}
		echo "<p class=\"sbmDevControls\">DeploySite debug information: <button onclick=\"window.open('DeploySite.log')\">DeploySite.log</button></p>\n";
		
		echo "<p>Deployed \"$title\" to <a href=\"$shareUrl\" target=\"_new\">$shareUrl</a></p>\n";
		
		echo "<p><button onclick=\"window.history.back()\">Back</button></p>\n";
		echo "<hr/>\n";

		
		MopLog("done");
		$this->debug->debug("DeploySite - done");
		return true;
	}
}
?>