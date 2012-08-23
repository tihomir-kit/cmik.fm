var state = "#home";


// on menu click, hide all "holders" and fade in the clicked one
function navigate(link) {
	$("#home").hide()
	$("#results_holder").hide();
	$("#about_holder").hide();
	$("#hof_holder").hide();
	$("#error_holder").hide();
	$(link).show();
	$(link).hide().fadeIn(1000);
}


$(document).ready(function() {
	$("#home_link").click(function() {
		// for home, results and error states (they use the same button)
		navigate(state);

		// only for home, set focus on username box
		if (state == "#home")
			document.getElementById("username").focus();

		return false;
	});


	$("#about_link").click(function() {
		navigate("#about_holder");
		return false;
	});


	$("#hof_link").click(function() {
		navigate("#hof_holder");
		return false;
	});
});
