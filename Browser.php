<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<link href='http://fonts.googleapis.com/css?family=Meddon|Sue+Ellen+Francisco&v2' rel='stylesheet' type='text/css'>
	
	<link rel="stylesheet" href="../../../css/basic.css">
	
	<link rel="stylesheet" type="text/css" media="screen" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/themes/smoothness/jquery-ui.css" />
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" ></script> 
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js"></script>
	
	<link rel="stylesheet" type="text/css" media="screen" href="Libs/elfinder-2.0-beta/css/elfinder.min.css">
	<script type="text/javascript" src="Libs/elfinder-2.0-beta/js/elfinder.min.js"></script>

	<!-- Mac OS X Finder style for jQuery UI smoothness theme (OPTIONAL) -->
	<link rel="stylesheet" type="text/css" media="screen" href="Libs/elfinder-2.0-beta/css/theme.css">
	
	<script type="text/javascript">
		function Reveal(){
			//console.log("Reveal()");
			$(".reveal").fadeOut(0);
			$(".reveal").fadeIn(1000);
			$(".revealSlower").fadeOut(0);
			$(".revealSlower").fadeIn(2000);		
			$(".revealSlowest").fadeOut(0);
			$(".revealSlowest").fadeIn(4000);
		}	
	</script>
	
	<title>Serinette BookMarkup Browser</title>
</head>
<body>
<div class="centered" id="logobox">
	<img src="../../images/logo_alpha_tiny.png" style="width:10%; height:10%"></img>
</div>
	<ul class="topNav">
		<li><a href="index.php">Home</a></li>
		<li><a href="Browser.php">File Browser</a></li>
	</ul>
	<br/>
	<br/>
	<h1 style="text-align:center">BookMarkup Browser</h1>
	<script type="text/javascript" charset="utf-8">
		$().ready(function() {
			var elf = $('#elfinder').elfinder({
				url : 'Libs/elfinder-2.0-beta/php/connector.php'  // connector URL (REQUIRED)
			}).elfinder('instance');            
		});
	</script>
	
	<!-- Element where elFinder will be created (REQUIRED) -->
	<div id="elfinder"></div>

	<script>
		$(document).ready(function(){
			Reveal();
		});
		
		<!-- @@mop_helper_scripts|standard mop helper scripts -->

		function MopReload() {
  			window.location.reload()
		}
	</script>
	
	<br/><br/>
	<hr/>
	
<?php	
	echo "<p>Maximum upload size is: ".ini_get('upload_max_filesize')."</p>\n";
?>		

</body>
</html>