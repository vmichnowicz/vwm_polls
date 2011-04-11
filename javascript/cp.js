// Add new option input focus set to "false" by defaul
var option_focus = false;

// When new option text input (our color and text fields) gets and loses focus
$('.vwm_polls_new_option input[type="text"]').live('focusin focusout', function(e) {
	if (e.type == 'focusin') {
		option_focus = true;
	}
	else {
		option_focus = false;
	}
});

// When our form is subitted
$('#publishForm').submit(function(e) {
	// If our a option text input has focus
	if (option_focus) {
		e.preventDefault();
	}
});

// On keyup
$('.vwm_polls_new_option input[type="text"]').live('keyup', function(e) {
	// If the use pressed the "enter"
	if (e.which == 13) {
		add_option( $(this).closest('tfoot') );
	}
});

// When the "add new poll option" button is clicked
$('input[type="button"].vwm_polls_new_option').live('click', function() {
	add_option( $(this).closest('tfoot') );
});

// Make something sortable!
function make_sortable(sort_me) {
	$(sort_me).sortable({
		axis: 'y',
		handle: 'td.drag',
		containment: 'parent',
		update: function() {
			var ajax_update_order_action_id = parseInt( $(this).siblings('thead').find('input[name="vwm_polls_ajax_update_order_action_id"]').val() );
			var options = $(this).find('input[id^="vwm_polls_option"]');
			var obj = new Object();

			$(options).each(function(i, option) {
				var id = $(option).attr('id');
				id = parseInt( id.replace('vwm_polls_option_', '') );
				obj[id] = i;
			});

			$.post(BASE_URL, {
				ACT: ajax_update_order_action_id, // Action ID
				options: obj
			});
		}
	});
}

// Make poll options sortable
make_sortable('table[id^="vwm_polls_options"] tbody');

// Add a poll option
function add_option(new_option) {

	// Options table info
	var options_table = $(new_option).closest('table');
	var options_table_id = $(options_table).attr('id');

	// IDs
	var entry_id = $('input[name="entry_id"]').val();
	var field_id = $(new_option).find('input[name="vwm_polls_field_id"]').val();

	// Option data
	var text = $(new_option).find('input[name="vwm_polls_new_option_text"]').val().replace(/^\s+|\s+$/g,''); // Trim of whitespace
	var type = $(new_option).find('select[name="vwm_polls_new_option_type"]').val();
	var color = $(new_option).find('input[name="vwm_polls_new_option_color"]').val();

	// If the user just entered whitespace
	if (text == '') {
		return;
	}

	// If this is an existing entry
	if (entry_id > 0) {
		$.post(BASE_URL, {
				ACT: $(new_option).find('input[name="vwm_polls_ajax_add_option_action_id"]').val(), // Action ID
				text: text, // Option text
				type: type, // Option type
				color: color, // Option color
				order: $(options_table).find('tbody tr').length, // Our order is index 0 so we don't need to +1
				entry_id: entry_id, // Entry ID
				field_id: field_id // Field ID
			}, function(data, status) {
				// Success
				if (status == 'success') {
					// AJAX load some new options up in here!
					$(options_table).load(window.location.href + ' #' + options_table_id + ' > *', function() {
						// Let's make this sortable again, shall we?
						make_sortable($(this).find('tbody'));
					});

					// Clear text input
					$(new_option).find('input[name="vwm_polls_new_option_text"]').val('');
				}
				// Oops
				else {
					console.log(status + ' : ' + data);
				}
		}, 'json');
	}
	// If this is a new entry
	else {
		// Get last table row
		var last_tr = $(options_table).find('tbody tr:last');

		// The index of our next poll option (if empty, it will be 0)
		var next_num = 0;

		// If we already have some poll options
		if (last_tr.length) {
			next_num = parseInt( $(last_tr).attr('class').replace('option_', '') ) + 1;
		}

		// Generate input names
		var color_name = 'vwm_polls_new_options[' + field_id + '][' + next_num + '][color]';
		var type_name = 'vwm_polls_new_options[' + field_id + '][' + next_num + '][type]';
		var text_name = 'vwm_polls_new_options[' + field_id + '][' + next_num + '][text]';

		// Clone last table row
		var clone = $(new_option).find('tr').clone();
		$(clone).find('td:first').empty();
		$(clone).attr('class', 'option_' + next_num);
		$(clone).find('input[name*="color"]').attr('name', color_name);
		$(clone).find('select[name*="type"]').attr('name', type_name);
		$(clone).find('input[name*="text"]').attr('name', text_name);

		// Insert new table row
		$(options_table).find('tbody').append(clone);

		// Updade data
		var new_row = $(options_table).find('tbody tr:last');
		$(new_row).find('input[name*="text"]').val(text);
		$(new_row).find('input[name*="color"]').val(color);
		$(new_row).find('select[name*="type"]').val(type);

		// Clear text input
		$(new_option).find('input[name="vwm_polls_new_option_text"]').val('');
	}
}

// Tabs on publish page
$('div[id^="vwm_polls_tabs"]').tabs();

// Poll "other" votes
$('table.vwm_polls_results ul').hide();
$('table.vwm_polls_results a').click(function() {
	$(this).siblings('ul').slideToggle('slow');
});