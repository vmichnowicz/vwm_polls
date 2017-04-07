$(document).ready(function() {

/**
 * Toggle allowed members multiselect
 *
 * The code below toggles the multiselect inputs for allowed and denied members
 * and groups based on the value of a corresponding select input. The options
 * inside this select can take on one of three values, "ALL", "NONE", or
 * "SELECT". If the value of the select input is "SELECT" then we want to show
 * the corresponding multiselect input.
 */
(function() {

	// Select input allowing for selection of "ALL", "NONE", or "SELECT"
	var member_groups_can_vote = $('select[name^="member_groups_can_vote"]');

	// Run toggle function
	var toggle_select_groups = function toggle_select_groups() {
		var selected = $(member_groups_can_vote);

		// Loop through each member group can vote select input
		$(selected).each(function() {
			var select = $(this).closest('tbody').find('select[name^="select_member_groups_can_vote"]'); // Grab corresponding multiselect input
			var tr = $(select).closest('tr'); // Grab parent table row

			$(this).val() === 'SELECT' ? $(tr).show() : $(tr).hide();
		});

		return toggle_select_groups;
	}();

	$(member_groups_can_vote).on('change', function() {
		toggle_select_groups();
	});

})();

});
