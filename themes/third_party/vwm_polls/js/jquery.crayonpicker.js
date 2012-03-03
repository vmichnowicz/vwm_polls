/**
 * jQuery Crayon Picker Plugin 0.2
 *
 * Source: https://github.com/vmichnowicz/jquery.crayonpicker
 * Example: http://www.vmichnowicz.com/examples/crayonpicker/index.html
 *
 * Copyright (c) 2012, Victor Michnowicz (http://www.vmichnowicz.com/)
 */
(function($) {

	$.fn.crayonpicker = function(options) {

		// Merge options into settings object
		var settings = $.extend({
			/**
			 * On crayon picker open
			 *
			 * @access public
			 * @param object
			 * @param object
			 * @param string
			 * @return void
			 */
			onOpen: function(target, trigger, color) {},
			/**
			 * crayon picker close
			 *
			 * @access public
			 * @param object
			 * @param object
			 * @param string
			 * @return void
			 */
			onClose: function(target, trigger, color) {},
			/**
			 * On crayon picker color choice
			 *
			 * A "selection" is not the same as "change". If the user
			 * selects the same color twice the first time will register
			 * as both a selection and a change. However, once the first
			 * choice has been made the second time the user selects
			 * that same color only a "selection" will be registered.
			 *
			 * @access public
			 * @param object
			 * @param object
			 * @param string
			 * @return void
			 */
			onSelection: function(target, trigger, color) {},
			/**
			 * On color change
			 *
			 * @access public
			 * @param object
			 * @param object
			 * @param string
			 * @return void
			 */
			onChange: function(target, trigger, color) {},
			/**
			 * Position picker
			 *
			 * @access protected
			 * @param object
			 * @param object
			 * @return void
			 */
			_position: function(target, table) {
				// Add inline CSS to picker table
				$(table).css({
					top: $(target).position().top + $(target).outerHeight(true, true),
					left: $(target).position().left
				});
			}
		}, options);

		return this.each(function() {

			// Target properties
			var target = $(this).attr('href') ? $( $(this).attr('href') ) : $(this);

			// Additional trigger to launch picker
			var trigger = $(this).attr('href') ? $(this) : null;

			$(target).addClass('crayonpicker-target');

			var colors = ['#800000','#808000','#008000','#008080','#000080','#800080','#7f7f7f','#808080','#804000','#408000','#008040','#004080','#400080','#800040','#666666','#999999','#ff0000','#ffff00','#ffff00','#00ffff','#0000ff','#ff00ff','#4c4c4c','#b3b3b3','#ff8000','#80ff00','#00ff80','#0080ff','#8000ff','#ff0080','#333333','#cccccc','#ff6666','#ffff66','#66ff66','#66ffff','#6666ff','#ff66ff','#191919','#e6e6e6','#ffcc66','#ccff66','#66ffcc','#66ccff','#cc66ff','#ff6fcf','#000000','#ffffff'];
			var columns = 8;
			var rows = 6;

			var table;

			// If we already have a crayon picker table
			if ( $(target).siblings('table.crayonpicker-table').length > 0 ) {
				table = $(target).siblings('table.crayonpicker-table');
			}
			// If we do not already have a crayon picker table
			else {
				table = $('<table />')
					.attr({
						'class': 'crayonpicker-table',
						cellpadding: 0,
						cellspacing: 0,
						border: 0
					})
					.css({
						display: 'none',
						position: 'absolute',
						'z-index': 99
					});

				// For each row
				for (var r = 1; r <= rows; r++) {

					// Row
					var tr = $('<tr />');

					// For each column inside current row
					for (var c = 1; c <= columns; c++) {

						// Index of color in colors array
						var index = (r * columns) - columns + c - 1;

						// Get color code
						var color = colors[index];

						// Cell
						var td = $('<td />');

						// Anchor link
						var a = $('<a />').attr('href', color).css('background-color', color).html(color);

						// Append link in cell
						$(td).append(a);

						// Append cell in row
						$(tr).append(td);
					}

					// Append row in table
					$(table).append(tr);
				}

				// Place table after target element
				$(this).after(table);
			}

			// Set color on click
			$(table).delegate('a', 'click', function(e) {
				e.preventDefault();

				// Selected color
				var val = $(this).attr('href');

				// Run onSelection() function
				settings.onSelection(target, trigger, val);

				// If selected color is different than the current color
				if (val !== $(target).val() ) {
					$(target).val( $(this).attr('href') );

					// Run onSelection() function
					settings.onChange(target, trigger, val);
				}
			});

			// Show picker on focus
			$(target).bind('focusin', function() {

				// Position picker
				settings._position(target, table);

				// Run onOpen() function
				settings.onOpen(target, trigger, $(target).val());

				// Open picker
				$(table).show();
			});

			// Show picker on trigger click
			$(trigger).bind('click', function(e) {
				e.preventDefault();

				// Position picker
				settings._position(target, table);

				// Run onOpen() function
				settings.onOpen(target, trigger, $(target).val());

				// Open picker
				$(table).show();
			});

			// Hide picker
			$(document).bind('click', function(e) {
				// If table is open AND clicked element was our not our target element AND not our trigger element
				if ( $(table).is(':visible') && $(e.target).get(0) != $(target).get(0) && $(e.target).get(0) != $(trigger).get(0) ) {
					// Run onClose() function
					settings.onClose(target, trigger, $(target).val());

					// Hide picker
					$(table).hide();
				}
			});
		});
	}

})(jQuery);