<?php
/*----------------------------------------------------------------------------
Credits.php - create credits page from collected @@assetcredit tags

----------------------------------------------------------------------------*/

class Credits {
	function __construct($docTitle, $displayTitle, $sessionFolder) {	
	   	$log = "nil";
	   	echo "<p style=\"text-align:right; font-size:xx-small; color: #898989;\">";
		require_once("MopLog.php");
		echo "</p>\n";
		MopLog_Init("Credits.log");
		
		echo "<h2>Credits</h2>\n";
		
		//recreate discovered asset credits array from disk
		$this->discoveredAssetCreditsFileName = $sessionFolder.$docTitle.".assetcredits";
		$this->discoveredAssetCredits = array();
		$this->discoveredAssetCredits = unserialize(file_get_contents($this->discoveredAssetCreditsFileName));
		
		//write out credits
		foreach($this->discoveredAssetCredits as $credit){
			echo "<p>".$credit."</p>\n";
		}
		
		MopLog("done");
		return true;
	}
}
?>