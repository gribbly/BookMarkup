#!/usr/local/bin/php -q
<?php		
	/*------------------------------------------------------------------------
	MopLog.php
		Simple logging system for and by Cam
		$verbose 1-3 set by host script
		$log object supplied by host script
			pass file name to MopLog_Init()
	------------------------------------------------------------------------*/
	
	
	/*------------------------------------------------------------------------
	PUBLIC
	------------------------------------------------------------------------*/
	//settings
	$frequency = 100; //adjust MopLog_m frequency
	
	//call these
	function MopLog($msg) { MopLog_go($msg, 0); }		//always displayed
	function MopLog_v1($msg) { MopLog_go($msg, 1); }
	function MopLog_v2($msg) { MopLog_go($msg, 2); }
	function MopLog_v3($msg) { MopLog_go($msg, 3); }
	
	function MopLog_m($msg) {
		//metered logging - call as often as
		//you like MopLog_m prints every nth call		
		global $frequency;
		static $meterCount = 0;
		if( $meterCount-- < 0 ) {
			MopLog_go($msg, 1);
			$meterCount = $frequency;
		}
	}
		
	function MopLog_lf($n = 1) { 
		//blank line (line feed)
		$i = 0;
		while( $i++ < $n ) {
			//echo "\n";
			MopLog_tofile("\n");
		}
	}

	function MopLog_i($msg, $v) {
		if($v >= $GLOBALS['verbose']){ 
			//echo "\t$msg\n";
		}
		MopLog_tofile("\t$msg");
	}
	
	//setup & teardown
	function MopLog_Init($f) {
		//open log file for writing
		$GLOBALS['log'] = fopen($f, "w");
		if ( ! $GLOBALS['log'] ) { 
			echo "MopLog.php - ERROR: Couldn't open $f\n";
		}
		
		MopLog_tofile(`date`);
		//echo "MopLog.php - Init with verbose level ".$GLOBALS['verbose']."\n";
	}
	
	function MopLog_DeInit() {
		if ( $GLOBALS['log'] ) { fclose($GLOBALS['log']); }
	}
	

	/*------------------------------------------------------------------------
	PRIVATE
	------------------------------------------------------------------------*/
	function MopLog_go($msg, $v) { 
		//log to screen if $verbose permits
		if ( $v <= $GLOBALS['verbose'] ) {
			if(is_array($msg)) {
				foreach($msg as $key => $value) {
					MopLog_tofile("$key => $value");
				}
			}
			else {			
				MopLog_tofile($msg);
			}
		}
	}
	
	function MopLog_tofile($msg) {
		if( $GLOBALS['log'] ) {
			$rv = fwrite( $GLOBALS['log'], $msg."\n");
			//echo "MopLog_tofile() - $rv\n";
		}
		else{
			echo $GLOBALS['scriptName']." - WARNING! Couldn't log to file...\n";
		}
	}
?>