$(document).ready(function() {
	checkIfSessionExists();
	
	$("#input").submit(function() {		
		var username = $("#username").attr('value'); 
		var amount = $("#amount").attr('value');
		var ribbon_width = $("#wrapper").width() - 48;
		var total_user_playcount, total_artist_playcount, artist_list, artist_entropy_sum;
		var artist_index = 0, coefficient_index = 0, coefficient_sum = 0, avg_score = 0, avg_counter = 1, error = 0;
		
		
		setSessionTimeout();
		initializeSessionVariables();
		
		
		// change "home" link to "results" link and navigate to results
		state = "#results_holder";
		navigate("#results_holder");
		$("#home_link").empty();
		$("#home_link").append("Results");
		
		
		
		// get and output artist boxes and info for all the artists
		$.ajax({
			type: "POST",
			url:  "handler.php",
			data: "method=GetArtists",
			dataType: "xml",
			success: function(xml) {
				// check if user has enough artists in his library (if not, refuse to cooperate!)
				var real_amount = checkSafeAmount();
				if (real_amount < 5)
					return false;
				
				// get top playcount of all artists in users library
				// used to determine max ribbon width
				top_artist_playcount = $("topartists", xml).find("topplaycount").text();
				
				// returns xml artist nodes as an array of objects
				artist_list = $("artist", xml);
				
				
				getArtistInfo();
			},
			error: function(artist_list) {
				showErrorMsg(
						"<p>User does not exist.</p>" +
						"<p>Please <a href='/'>try another username</a>.</p>"
				);
				
				$.cookie('active', null);
			}
		});
		
		
		
		// get data for each artist and outputs the artist name, total playcount, image
		// and call function for calculating diversity coefficient and preparing the ribbon
		function getArtistInfo() {
			// exit on final artist + 1
			if (artist_index == $(artist_list).length) {
				storeData()
				return false;
			}
			
			// prepare all the necessary variables
			var artist = $(artist_list[artist_index]);
			var artist_name = artist.find("name").text();
			var artist_name_POST = prepareArtistNamePOST(artist_name);
			var artist_link = "http://www.lastfm.com/music/" + artist_name;
			var artist_playcount = artist.find("playcount").text();
			var artist_image = artist.find("image").text();
			var artist_div_id = "id_" + artist_index + "_" + artist_playcount;
			
			
			// use last.fm logo image if there is no available image for an artist
			if (!artist_image)
				artist_image = "images/lastfm.jpg";
			
			
			// prepare artist box divs
			var box = "" +
				"<div class='artist_box' id='box_" + artist_div_id + "'>" +
				"	<img src='" + artist_image + "' class='artist_image'>" +
				"	<div class='artist_info' id='info_" + artist_div_id + "'>" +
				"		<h2 class='artist_name'>" +
				"			" + (artist_index + 1) + ". " +
				"			<a href='" + artist_link + "' class='artist_link' target='_blank'>" + artist_name + "</a>" +
				"		</h2>" +
				"		<div class='artist_playcount'><b>Total playcount:</b> " + artist_playcount + " plays</div>" +
				"	</div>" +
				"	<div class='artist_coeff_holder' id='coeff_holder_" + artist_div_id + "'></div>" +
				"	<div class='artist_ribbon' id='ribbon_" + artist_div_id + "'>" +
				"			<img src='images/loading.gif' class='loading_image'>" +
				"			<span class='loading_text'>Fetching and processing data, please wait.</span>" +
				"	</div>" +
				"</div>";
			
			
			// fade in artist boxes
			$("#results_holder").append(box);
			$("#box_" + artist_div_id).hide().fadeIn(2000);
			
			
			// fetch data for every artist and output songs bar and coefficient
			getTracksInfo(artist_name, artist_name_POST, artist_playcount, artist_div_id, getArtistInfo);
			artist_index++;
		};
		
		
		
		// get and output info for all the tracks of an artist (ribbon with tooltips)
		// generate and output diversity coefficient, number of different tracks per artist in the library
		function getTracksInfo(artist_name, artist_name_POST, artist_playcount, artist_div_id, callback) {
			$.ajax({  
				type: "POST",  
				url:  "handler.php",
				data: "method=GetTracks&artist=" + artist_name_POST + "&playcount=" + artist_playcount,
				dataType: "xml",
				success: function(xml) {
					var ribbon = generateRibbon(artist_playcount, xml);
					
					
					// calculates diversity coefficient and outputs (fades-in) the data
					$("tracks",xml).each(function() {
						// calculates diversity coefficient (entropy)					
						var artist_coefficient = (artist_entropy_sum * 10).toPrecision(5);
						var artist_total_tracks = $(this).find("totaltracks").text();		
						
						
						// prepare total_tracks_div and coefficient divs
						var total_tracks_div = "" + 
							"<div class='artist_diff_tracks' id='diff_tracks_" + artist_div_id + "'>" + 
							"	<b>Different tracks in your library:</b> " + artist_total_tracks + " tracks (hover over ribbon for details)" +
							"</div>";
						
						var coefficient_box = "" +
							"<div class='coefficient_box' id='coefficient_" + artist_div_id + "'>" +
							"	<div class='coefficient_text'>Diversity coefficient</div>" +
							"	<div class='coefficient_value'>" + artist_coefficient + "</div>" +
							"	<div class='coefficient_explanation' " + 
							"		onMouseOver='toolTip(\"" + coeff_explanation + "\", 310, \"left\")' " +
							"		onMouseOut='toolTip()' " + 
							"		onClick='return false;'>What is this?" +
							"	</div>" +
							"</div>";
						
						
						// calculate and output avg. coefficient
						coefficient_index++;
						coefficient_sum += artist_entropy_sum;
						avg_score = ((coefficient_sum / coefficient_index) * 10).toPrecision(5);
							
						var score_box = "" +
							"<div id=\"score_text\">" + username + "'s " + "avg(" + avg_counter++ + "/" + amount + "): </div>" +
							"<div id=\"avg_score\">" + avg_score + "</div>";
							
						$("#score_box").empty();
						$("#score_box").append(score_box);
						
						
						//remove loading information
						$("#ribbon_" + artist_div_id).empty();
						
						// fade in total_tracks_div, coefficient_box and ribbon
						$("#info_" + artist_div_id).append(total_tracks_div);
						$("#diff_tracks_" + artist_div_id).hide().fadeIn(2000);
						
						$("#coeff_holder_" + artist_div_id).append(coefficient_box);
						$("#coefficient_" + artist_div_id).hide().fadeIn(2000);
						
						$("#ribbon_" + artist_div_id).append(ribbon);
						$("#ribbon_" + artist_div_id).hide().fadeIn(2000);						
    				});						
				},
				complete: function() {
					// re-set session timeout to disable multiple sessions
					setSessionTimeout();
					
					// pause for 1500ms between each call
					setTimeout(callback, 1500);
				}
			});  			
		}
		
		
		
		// generate and return the whole ribbon for an artist
		function generateRibbon(artist_playcount, xml) {			
			var ribbon = "<div class='vertical_line'></div>"; // ribbon beginning
			var temp_width = 0;
			artist_entropy_sum = 0
			
			
			// get data for each track and calculate its percentage and div width and prepare the tooltip
			$("track",xml).each(function() {
				var track_name = $(this).find("name").text();
				var track_playcount = $(this).find("playcount").text();
				var track_percentage = parseFloat(track_playcount) / parseFloat(artist_playcount);
				var track_div_width = track_percentage * ribbon_width * (artist_playcount / top_artist_playcount);
				var track_tooltip = "<b>" + track_name + "</b> - " + track_playcount + " plays (" + (track_percentage * 100).toPrecision(3) + "% of " + artist_playcount + ")";
				var rest = track_div_width % 1;
				
				
				// if percentage got parsed right (is not NaN), add it to artist entropy sum
				if (track_percentage != 0)
					// entropy formula
					artist_entropy_sum += (track_percentage * (Math.log(1 / track_percentage) / Math.log(2)));
				
				
				// fix ribbon width (converts song pixel width to integer)
				// for tracks listened < 1% of the time
				if (track_div_width < 1) {
					if ((track_div_width + temp_width) > 1) {
						temp_width = track_div_width + temp_width - 1;
						track_div_width = 1;
						
						ribbon += generateRibbonSongDiv(track_div_width, track_tooltip);
					}
					else
						temp_width += track_div_width;
				}
				// for tracks listened >= 1% of the time
				else {
					if ((rest + temp_width) > 1) {
						track_div_width = Math.floor(track_div_width + temp_width);
						temp_width = rest + temp_width - 1;
					}
					else {
						track_div_width = Math.floor(track_div_width);
						temp_width += rest;
					}
					ribbon += generateRibbonSongDiv(track_div_width, track_tooltip);
				}
			});
			
			
			ribbon += "<div class='vertical_line'></div>"; // ribbon end
			
			
			return ribbon;
		}
		
		
		
		// generate colored div with a tooltip for each track
		function generateRibbonSongDiv(track_div_width, track_tooltip) {
			var song_div = "" +
				"<div class='colored_div' " +
				"	style='width:" + track_div_width + "px;' " +
				"	onMouseOver='toolTip(\"" + track_tooltip + "\", 150)' " +
				"	onMouseOut='toolTip()'>" +
				"</div>";
			
			return song_div;
		};
		
		
		
		// store calculated avg. diff. coeff. to the database
		function storeData() {
			$.ajax({
				type: "POST",
				url:  "handler.php",
				data: "method=StoreData&avg_score=" + avg_score,
				dataType: "text",
				success: function(data) {					
					// on inserting data into the database, refresh 
					// HoF tables and show users rank in HoF
					getUserHofRank();
					$("#hof_table_holder").empty();
					generateHofTables("GetTop");
					generateHofTables("GetLowest");
					
					// release current session
					$.cookie('active', null);
				}
			});
		}
		
		
		
		// set session timeout to disable multiple sessions
		function setSessionTimeout() {
			// set cookie to expire in 0.15 minutes (cca 10 seconds)
			var date = new Date();
			date.setTime(date.getTime() + (0.15 * 60 * 1000));
			
			// quickly delete the session cookie before setting it
			if ($.cookie('active') != null)
				$.cookie('active', null);
			
			$.cookie('active', 'true', { expires: date });
		}
		
		
		
		// initialize PHP session variables
		function initializeSessionVariables() {
			$.ajax({
				type: "POST",
				url:  "handler.php",
				data: "method=SetSessionVars&username=" + username + "&amount=" + amount,
				async: false
			});
		}
		
		
		
		// get users HoF rank and output it in HoF above tables
		function getUserHofRank() {
			$.ajax({
				type: "POST",
				url:  "handler.php",
				data: "method=GetUserHofRank",
				dataType: "text",
				success: function(data) {
					$("#info_box").empty();
					
					if (data != "error")
						var hof_score_msg = data;
					else
						var hof_score_msg = "An error occured.</br>Please <a href='/'>try again</a>.";
					
					// notify user above the menu about final score being available
					$("#info_box").css("color", "#F5B800");
					$("#info_box").append("calculating done, check your ranking in \"HoF\"");
					$("#info_box").hide().fadeIn(2000);
					
					$("#hof_user_score").append(hof_score_msg);
				}
			});
		}
		
		
		
		// check if user has enough artists in his library
		function checkSafeAmount() {
			// sometimes users don't have the exact same amount of artists in their 
			// library while there is only 4 choices to chose from (5, 10, 25, 50)
			var real_amount = 0;
			
			$.ajax({
				type: "POST",
				url:  "handler.php",
				data: "method=CheckSafeAmount",
				async: false,
				dataType: "text",
				success: function(data) {
					real_amount = data;
					
					if (real_amount < amount)
						amount = real_amount;
					
					if (real_amount < 5) {
						showErrorMsg(
							"<p>Not enough artists in " + username + "'s library (minimum 5).</p>" +
							"<p>Please <a href='/'>try again</a>.</p>"
						);
						
						$.cookie('active', null);
					}
					else {
						// fade in the info box
						$("#info_box").append("while waiting, feel free to visit Hall of Fame (HoF)");
						$("#info_box").hide().fadeIn(2000);
					}
				}
			});
			return real_amount;
		}		
		
		
		
		// gets total user playcount
		function getTotalUserPlaycount() {
			$.ajax({
				type: "POST",
				url:  "handler.php",
				data: "method=GetTotalUserPlaycount",
				dataType: "text",
				success: function(data) {
					total_user_playcount = data;
				}
			});
		}		
		
		
		
		// checks and temporary fixes artist name of problematic characters for PHP POST request
		function prepareArtistNamePOST(temp_artist_name) {
			temp_artist_name = temp_artist_name.replace(/\+/g, "cmikfmplusreplacer");
			temp_artist_name = temp_artist_name.replace(/\'/g, "cmikfmapostrophereplacer");
			return encodeURIComponent(temp_artist_name);
		}
		
		
		
		return false;		
	});


	
	// check if session already exists
	function checkIfSessionExists() {
		if ($.cookie('active') == "true") {
			showErrorMsg(
				"<p>Session already in use.</p>" +
				"<p>Please wait until it's finished, and then <a href='/'>try again</a>.</br>" +
				"(retry allowed 10 seconds after the previous one has finished)</p>"
			);
		}
	}

	
	
	// on error, navigate to #error_holder
	function showErrorMsg(message) {
		error_message = message;
		$("#error_text").append(error_message);
		
		state = "#error_holder";
		navigate("#error_holder");
		$("#home_link").empty();
		$("#home_link").append("Error");
	}	
});
