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

		//nuke $dest - we always start with a 100% clean slate
		if($dest) {
			$command = "rm -rfv $dest 2>&1 1> /dev/null";
			MopLog($command);
			MopLog(shell_exec($command));
		}
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".escapeshellarg($dest)));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();		
		
		//make output dir if it doesn't exist
		if(file_exists($dest) == false) {
			MopLog("Attempting to create site folder: $dest");
			mkdir($dest, 0777, true);
		}
		else {
			$this->debug->debug("ERROR: Destination folder didn't get nuked for some reason: $dest");
			MopLog("ERROR: Destination folder didn't get nuked for some reason: $dest");
		}
		
		MopLog("***OUTPUT SNAPSHOT START***");
		MopLog(shell_exec("ls -alR ".escapeshellarg($dest)));
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
		MopLog(shell_exec("ls -alR ".escapeshellarg($dest)));
		MopLog("***OUTPUT SNAPSHOT END***");
		MopLog_lf();
		
		echo "<p>DeploySite debug information: <button onclick=\"window.open('DeploySite.log')\">DeploySite.log</button></p>\n";
		echo "<p>Deployed ".$title." to <a href=".$dest." target=\"_new\">link</a></p>\n";
		echo "<p><button onclick=\"window.history.back()\">Back</button><button onclick=\"window.location='index.php'\">Start Over</button></p>\n";
		echo "<hr/>\n";

		
		MopLog("done");
		$this->debug->debug("DeploySite - done");
		return true;
	}
}
?>