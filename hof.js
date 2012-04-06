function generateHofTables(method_name) {
	if (method_name == "GetTop") {
		var title = "Top 50 scores";
		var float = "left";
	}
	if (method_name == "GetLowest") {
		var title = "Lowest 50 scores";
		var float = "right";
	}
	

	// generate table header information and append users with scores below (data)
	$.ajax({
		type: "POST",
		url:  "handler.php",
		data: "method=" + method_name,
		dataType: "text",
		success: function(data) {
			var users = "" +
				"<table class='hof_table' style='float:" + float + ";'>" +
					"<tr><th colspan='3'>" + title + "</th></tr>" +
					"<tr class='column_names'>" +
						"<td class='column1' id='coumn_name_user'>username</td>" +
						"<td class='column2'>score</td>" +
						"<td class='column3'>amount</td>" +
					"</tr>" + data +
				"</table>";
			$("#hof_table_holder").append(users);
		}
	});

	
	return false; 
}

