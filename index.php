<?php /******************************************************
*===========================================================*
*        - cmik.fm -                                        *
*===========================================================*
*************************************************************
*
* Copyright 2012, Tihomir Kit (kittihomir@gmail.com)
* spilp is distributed under the terms of GNU General Public License v3
* A copy of GNU GPL v3 license can be found in LICENSE.txt or
* at http://www.gnu.org/licenses/gpl-3.0.html
*
********************************************************/ ?>

<!DOCTYPE HTML>
<html>
<head>
	<title>cmik.fm</title>
	<link rel="stylesheet" type="text/css" href="lastfm.css" />
	<link rel="shortcut icon" href="/images/favicon.ico" />
	<script type="text/javascript" src="javascript_libs/jquery-1.7.1.min.js"></script>
	<script type="text/javascript" src="javascript_libs/jquery.cookie.js"></script>
	<script type="text/javascript" src="javascript_libs/tooltip.js"></script>
	<script type="text/javascript" src="navigation.js"></script>
	<script type="text/javascript" src="ajax.js"></script>
	<script type="text/javascript" src="hof.js"></script>
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-11297782-1']);
		_gaq.push(['_setDomainName', 'cmikavac.net']);
		_gaq.push(['_trackPageview']);

		(function() {
			var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
			ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
</head>
<body>



<div id="header"> <!-- HEADER -->
	<a href="index.php" id="site_name">
		<!-- last edited 17/04/2012 -->
		cmik.fm <!-- v1.1.5 -->
	</a>
	<div id="score_box"></div>
	<div id="info_box"></div>
	<div id="menu">
		<div class="menu_box" id="home_link">Home</div>
		<div class="menu_box" id="about_link">About</div>
		<div class="menu_box" id="hof_link">HoF</div>
	</div>
</div> <!-- /HEADER -->



<div id="wrapper"> <!-- WRAPPER -->
	<div id="home">
		<?php include 'home.html'; ?>
	</div>
	<div id="results_holder"></div>
	<div id="about_holder">
		<script type="text/javascript">$("#about_holder").hide();</script>
		<?php include 'about.html'; ?>
	</div>
	<div id="hof_holder">
		<script type="text/javascript">$("#hof_holder").hide();</script>
		<?php include 'hof.html'; ?>
		<script type="text/javascript">
			generateHofTables("GetTop");
			generateHofTables("GetLowest");
		</script>
	</div>
	<div id="error_holder">
		<script type="text/javascript">$("#error_holder").hide();</script>
		<?php include 'error.html'; ?>
	</div>
</div> <!-- /WRAPPER -->



<div id="footer"> <!-- FOOTER -->
	<pre> -- Copyleft 2012., all wrongs reserved  --  <a href="http://cmikavac.net">cmikavac.net</a> :: <a href="mailto:pootzko@gmail.com">pootzko@gmail.com</a>  :: <a href="http://cmikavac.net/2012/03/31/cmik-fm/" target="_blank">comments</a> --  </pre>
</div> <!-- /FOOTER -->



</body>
</html>
