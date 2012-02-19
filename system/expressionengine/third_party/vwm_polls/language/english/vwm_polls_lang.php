<?php if ( ! defined('EXT')) { exit('Invalid file request'); }

/**
 * VWM Polls
 *
 * @package		VWM Polls
 * @author		Victor Michnowicz
 * @copyright	Copyright (c) 2011 Victor Michnowicz
 * @license		http://www.apache.org/licenses/LICENSE-2.0.html
 * @link		http://github.com/vmichnowicz/vwm_polls
 */

// -----------------------------------------------------------------------------

$lang = array(
	// Module info
	'vwm_polls_module_name'			=> 'VWM Polls',
	'vwm_polls_module_description'	=> 'Polling module &amp; fieldtype',

	// Error messages
	'invalid_xid'					=> 'Invalid XID. Please enable cookies.',
	'poll_not_open'					=> 'Poll is not open at this time.',
	'poll_not_exist'				=> 'Poll does not exist.',
	'no_options_submitted'			=> 'No poll options submitted.',
	'one_option_allowed'			=> 'Poll only allows your to select one option. You selected %s.',
	'too_few_options_submitted'		=> 'Poll requires you select at least %u options. You selected %u.',
	'too_many_options_submitted'	=> 'Poll only allows your to select a maximum of %u options. You selected %u.',
	'invalid_poll_option'			=> 'Invalid poll option.',
	'poll_expired'					=> 'Poll expired on %s.',
	'member_group_cannot_vote'		=> 'Your member croup cannot vote in this poll.',
	'can_only_vote_once'			=> 'This poll only allows you to vote once.',

	// Display settings
	'member_groups_can_vote'		=> 'Member groups that can vote',
	'multiple_votes'				=> 'Allow an individual to vote multiple times for the same poll',
	'multiple_options'				=> 'Allow an individual to select multiple poll options',
	'multiple_options_min'			=> 'Minimum amount of poll options a user can select',
	'multiple_options_max'			=> 'Maximum amount of poll options a user can select',
	'options_order'					=> 'Poll options order',
	'results_chart_type'			=> 'Chart type',
	'results_chart_width'			=> 'Chart width (<acronym title="Pixels">px</acronym>)',
	'results_chart_height'			=> 'Chart height (<acronym title="Pixels">px</acronym>)',

	// Fieldtype titles
	'poll_options'					=> 'Poll Options',
	'poll_settings'					=> 'Poll Settings',
	'poll_results'					=> 'Poll Results',

	// Fieldtype table headers
	'option_color'					=> 'Color',
	'option_type'					=> 'Type',
	'option_text'					=> 'Text',
	'setting'						=> 'Setting',
	'value'							=> 'Value',
	'option'						=> 'Option',
	'votes'							=> 'Votes',

	// Chart types
	'chart_bar'						=> 'Bar',
	'chart_pie'						=> 'Pie',

	// Poll option orders
	'order_asc'						=> 'Ascending',
	'order_desc'					=> 'Descending',
	'order_alphabetical'			=> 'Alphabetical',
	'order_reverse_alphabetical'	=> 'Reverse Alphabetical',
	'order_random'					=> 'Random',
	'order_custom'					=> 'Custom',

	// Other fieldtype text
	'type_defined'					=> 'Defined',
	'type_other'					=> 'Other',
	'hex_color_placeholder'			=> 'Hex color',
	'option_text_placeholder'		=> 'Option text',
	'total_votes'					=> 'Total votes',
	'no_votes'						=> 'No votes for this option',
	'no'							=> 'No',
	'yes'							=> 'Yes'
);

// EOF