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
	// If no color is provided, return the default color
	else
	{
		return 'aabbcc';
	}
}

/**
 * Generate a Google chart
 *
 * @access public
 * @param array			Poll data
 * @param array			Poll options
 * @param array			Poll votes
 * @return string
 */
function google_chart($poll_settings, $poll_options)
{
	// Google charts URL
	$data = 'http://chart.apis.google.com/chart?';

	// Chart size
	$data .= 'chs=' . $poll_settings['results_chart_width'] . 'x' . $poll_settings['results_chart_height'];

	// Chart type
	switch($poll_settings['results_chart_type'])
	{
		case 'pie':
			$data .= AMP . 'cht=p';
			break;
		case 'bar':
			$data .= AMP . 'chbh=a';
			$data .= AMP . 'cht=bhs';
			break;
	}

	// Chart data
	$chd = array(); // Chart data
	$chdl = array(); // Chart labels
	$chco = array(); // Chart colors

	foreach ($poll_options as $option)
	{
		$chdl[ $option['id'] ] = rawurlencode($option['text']);
		$chd[ $option['id'] ] = $option['votes'];
		$chco[ $option['id'] ] = $option['color'];
	}

	$chd = implode(',', $chd);
	$chdl = implode('|', $chdl);
	$chco = implode('|', $chco);

	$data .= AMP . 'chd=t:' . $chd;
	$data .= AMP . 'chdl=' . $chdl;
	$data .= AMP . 'chf=bg,s,00000000';
	$data .= AMP . 'chco=' . $chco;

	return $data;
}