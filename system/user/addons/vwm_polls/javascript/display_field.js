$(document).ready(function() {

	// Add new option input focus set to "false" by default
	var option_focus = false;
	EE.vwm_polls_option_text_removed = "Option will be removed";
	/**
	 * When new option text input (our color and text fields) gets and loses focus
	 */
	$('.vwm_polls_new_option input[type="text"]').on('focus', function(e) {
		 option_focus = true;
	}).on('blur', function(e) {
		option_focus = false;
	});

	/**
	 * When the publish form is subitted
	 */
	$('.ajax-validate').submit(function(e) {
		// If our a option text or color input has focus
		if (option_focus) {
			// Stop the form submission (so we can add this poll option)
			e.preventDefault();
		}
	});

	/**
	 * On keyup inside a new poll option text input
	 */
	$('.vwm_polls_new_option input[type="text"]').on('keyup', function(e) {
		// If the use pressed the "enter" key
		if (e.which == 13) {
			add_option( $(this).closest('tfoot') );
		}
	});

	/**
	 * When the "add new poll option" button is clicked
	 */
	$('input[type="button"].vwm_polls_new_option').on('click', function() {
		add_option( $(this).closest('tfoot') );
	});

	/**
	 * Make poll options sortable!
	 */
	var make_sortable = function make_sortable() {
		// Select poll options table tbody
		$('body').find('table[id^="vwm_polls_options"] > tbody').sortable({
			axis: 'y',
			handle: 'td.drag',
			containment: 'parent',
			update: function() {

				// Grab entry ID
				var entry_id = $('#publishForm input[name="entry_id"]').val();

				var options = [];

				options = $(this).children('tr');
				var field_id = $(this).closest('table').find('input[name="vwm_polls_field_id"]').val();

				// Loop through all of our new poll options and update each one to reflect their new order
				$(options).each(function(i, option) {
					var color = $(option).find('input[name*="color"]');
					var type = $(option).find('select[name*="type"]');
					var text = $(option).find('input[name*="text"]');
					var id = $(option).find('input[name*="id"]');

					$(color).attr('name', 'vwm_polls_options[' + field_id + '][' + i + '][color]');
					$(type).attr('name', 'vwm_polls_options[' + field_id + '][' + i + '][type]');
					$(text).attr('name', 'vwm_polls_options[' + field_id + '][' + i + '][text]');
					$(id).attr('name', 'vwm_polls_options[' + field_id + '][' + i + '][id]');
				});
			}
		});

		return make_sortable;
	}();

	/**
	 * Add a poll option
	 *
	 * @param object		Table row of our new option
	 */
	function add_option(new_option) {

		// Options table info
		var options_table = $(new_option).closest('table');
		var options_tbody = $(options_table).children('tbody');
		var options_table_id = $(options_table).attr('id');

		// IDs
		var entry_id = $(new_option).find('input[name="entry_id"]').val();
		var field_id = $(new_option).find('input[name="vwm_polls_field_id"]').val();

		// Option data
		var text = $(new_option).find('input[name="vwm_polls_new_option_text"]').val().replace(/^\s+|\s+$/g,''); // Trim of whitespace
		var type = $(new_option).find('input[name="vwm_polls_new_option_type"]').val();
		var color = $(new_option).find('input[name="vwm_polls_new_option_color"]').val();

		// If the user just entered whitespace
		if (text == '') {
			return;
		}

		// Get the index of our new option
		var option_index = $(options_tbody).children('tr').length ? $(options_tbody).children('tr').length : 0;

		// Generate new input name attributes
		var color_name = 'vwm_polls_options[' + field_id + '][' + option_index + '][color]';
		var type_name = 'vwm_polls_options[' + field_id + '][' + option_index + '][type]';
		var text_name = 'vwm_polls_options[' + field_id + '][' + option_index + '][text]';
		var id_name = 'vwm_polls_options[' + field_id + '][' + option_index + '][id]';

		// Clone last table row
		var clone = $(new_option).children('tr').clone();
		$(clone).find('td:first').empty();
		$(clone).find(':input[name*="color"]').attr('name', color_name);
		$(clone).find(':input[name*="type"]').attr('name', type_name);
		$(clone).find(':input[name*="text"]').attr('name', text_name);
		$(clone).find(':input[name*="id"]').attr('name', id_name);

		// Insert new table row
		$(options_tbody).append(clone);

		// Update data
		var new_row = $(options_tbody).children('tr:last');
		$(new_row).find('input[name*="id"]').val("new"); // set id as new
		$(new_row).find('input[name*="text"]').val(text).attr('placeholder',EE.vwm_polls_option_text_removed);
		$(new_row).find('input[name*="color"]').val(color);
		$(new_row).find('select[name*="type"]').val(type);

		// Clear text input
		$(new_option).find('input[name="vwm_polls_new_option_text"]').val('');
		//option_focus = true;
		// Cleanup as if this was an Ajax request
		ajaxCleanup();
	}

	// Tabs on publish page
	$('[id^=vwm_polls_tabs]').tabs({active: 0});

	// Poll "other" votes
	$('table.vwm_polls_results ul').hide();
	$('table.vwm_polls_results a').click(function() {
		$(this).siblings('ul').slideToggle('slow');
	});

	// Toggle min & max poll options
	(function() {
		// Min & max inputs
		var min = $('#multiple_options_min');
		var max = $('#multiple_options_max');

		// Multiple option select input
		var multiple_options = $('#multiple_options');

		// Hide and reset min & max inputs
		function hide_min_max() {
			$(min).val(0).closest('tr').hide();
			$(max).val(0).closest('tr').hide();
		}

		// Show min and max inputs
		function show_min_max() {
			$(min).closest('tr').show();
			$(max).closest('tr').show();
		}

		// On page load, if multiple options are disabled, hide min and max inputs
		if ( $(multiple_options).val() == 0 ) {
			hide_min_max()
		}

		// When the multiple option select input is changed
		$(multiple_options).change(function() {
			if ( $(this).val() == 1 ) {
				show_min_max();
			}
			else {
				hide_min_max();
			}
		});

	})();


	/**
	 * jQuery Pill plugin
	 */
	$.fn.pill = function() {
		this.each(function() {
				var pill = $(this);
				var radios = $(pill).find('input[type="radio"]');

				// Add "checked" class on plugin load
				$(radios).filter(':checked').closest('div').addClass('checked');

				// Toggle on radio change
				$(radios).on('change', function() {
					var parent = $(this).closest('div');
					var siblings = $(parent).siblings('div');
					$(siblings).removeClass('checked');
					$(this).closest('div').addClass('checked');
				});
			});

		return this;
	}

	/**
	 * Get our pills runnin'
	 */
	var pill = function pill() {
		$('body').find('.pill').pill();
		return pill;
	}();

	/**
	 * Run cleanup function on Ajax complete!
	 */
	$('body').ajaxComplete(function() {
		ajaxCleanup();
	});

	/**
	 * Run Ajax cleanup functions!
	 */
	var ajaxCleanup = function ajaxCleanup() {
		// Run cleanup functions
		pill();
		jscolor.bind(); // rebind the jscolor picker
		make_sortable();
		$('.vwm_polls_remove_option').not('done').addClass('done').click(function() {
			$(this).closest('tr').find(':input[name*="text"]').val('');
		});
		return ajaxCleanup;
	}

});
function removeOption(el){
	e = el.parentElement.previousElementSibling.firstElementChild;
	e.value = "";
	//e.placeholder="Option will be removed";
}
