$(document).ready(function() {

/**
 * Toggle allowed members multiselect
 *
 * The code below toggles the multiselect inputs for allowed and denied members
 * and groups based on the value ofa  corresponding select input. The options
 * inside this select can take on one of three values, "A", "NULL", or "SELECT".
 * If the value of the radio button is "SELECT" then we want to show the
 * corresponding multiselect input.
 */
(function() {

	var toggle_select_groups = function toggle_select_groups() {
		var selected = $('select[name^="member_groups_can_vote"]');

		$(selected).each(function() {
			var select = $(this).closest('tbody').find('select[name^="select_member_groups_can_vote"]');
			var tr = $(select).closest('tr');

			$(this).val() === 'SELECT' ? $(tr).show() : $(tr).hide();
		});

		return toggle_select_groups;
	}();

	$('select[name^="member_groups_can_vote"]').live('change', function() {
		toggle_select_groups();
	});

})();

});