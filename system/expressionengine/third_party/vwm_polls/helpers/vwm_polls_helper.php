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

/**
 * Make sure we are dealing with a (at least somewhat) valid hex color
 * If no color is provided, just output a default color
 *
 * @access public
 * @param string		User submitted hex color
 * @return string
 */
function hex_color($color)
{
	if ($color)
	{
		// Remove all non alpha numeric characters
		$color = preg_replace('/[^a-zA-Z0-9]/', '', $color);

		// Color can be a max of six characters
		return substr($color, 0, 6);
	}
	// If no color is provided, return a random color
	else
	{
		return sprintf('%02X%02X%02X', mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255));
	}
}

/**
 * Calculate percentages for poll options
 *
 * @access public
 * @param array			Poll options
 * @param int			Total number of votes in this poll
 * @return array
 */
function calculate_results($options, $total_votes)
{
	$options = array_values($options);

	foreach($options as &$option)
	{
		if ($total_votes)
		{
			$percent_decimal = $option['votes'] / $total_votes;
			$option['percent_decimal'] = $percent_decimal;
			$option['percent'] = round($percent_decimal * 100, 2);
		}
		else
		{
			$option['percent_decimal'] = 0;
			$option['percent'] = 0;
		}
	}

	return $options;
}

/**
 * Generate a Google chart
 *
 * This function is duplicated in ft.vwm_polls.php
 *
 * @param array			Poll settings
 * @param array			Poll options
 * @return string
 */
function google_chart($poll_settings, $poll_options)
{
	// Google charts URL
	$data = 'http://chart.apis.google.com/chart?';

	// Chart size
	$data .= 'chs=' . $poll_settings['results_chart_width'] . 'x' . $poll_settings['results_chart_height'];

	// Display chart labels
	$labels = isset($poll_settings['results_chart_labels']) && $poll_settings['results_chart_labels'];

	// Chart type
	switch($poll_settings['results_chart_type'])
	{
		case 'pie':
			$data .= AMP . 'cht=p';
			$chds = NULL; // Don't need this for pie charts
			break;
		case 'bar':
			$data .= AMP . 'chbh=a';
			$data .= AMP . 'cht=bhs';
			$data .= AMP . 'chg=10,0,5,5';
			//$data .= AMP . 'chxr=0,100';
			$chds = AMP . 'chds=0,';
			break;
	}

	$most_votes = 0;

	// Chart data
	$chd = array(); // Chart data
	$chdl = array(); // Chart labels
	$chco = array(); // Chart colors

	foreach ($poll_options as $option)
	{
		$votes = $option['votes'];
		$most_votes = $votes > $most_votes ? $votes : $most_votes;

		$chdl[ $option['id'] ] = $option['text'];
		$chd[ $option['id'] ] = $votes;
		$chco[ $option['id'] ] = $option['color'];
	}

	$chd = implode(',', $chd);
	$chdl = implode('|', $chdl);
	$chco = implode('|', $chco);

	$data .= AMP . 'chd=t:' . $chd;
	if ($labels) { $data .= AMP . 'chdl=' . $chdl; }
	$data .= AMP . 'chf=bg,s,00000000';
	$data .= AMP . 'chco=' . $chco;
	$data .= $chds ? AMP . $chds . $most_votes : NULL;

	return $data;
}

// EOF