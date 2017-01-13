<?php if ( ! defined('BASEPATH')) { exit('Invalid file request'); }

/**
 * VWM Polls
 *
 * Module control panel
 *
 * @package		VWM Polls
 * @author		Victor Michnowicz
 * @copyright	Copyright (c) 2011 Victor Michnowicz
 * @license		http://www.apache.org/licenses/LICENSE-2.0.html
 * @link		http://github.com/vmichnowicz/vwm_polls
 */

// -----------------------------------------------------------------------------

class Vwm_polls_mcp {

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct()
	{
		// Make damn sure module path is defined
		ee()->load->add_package_path(PATH_THIRD . 'vwm_polls/');
	}

	/**
	 * Module CP page
	 *
	 * @access public
	 * @return string
	 */
	public function index()
	{
		ee()->view->cp_page_title = lang('vwm_polls_module_name');
		return 'Please reference the VWM Polls <a href="https://github.com/vmichnowicz/vwm_polls/wiki">GitHub wiki</a> for more information.';
	}

	/**
	 * Add a poll option
	 *
	 * This method is accessed from the fieldtype component
	 *
	 * @access public
	 * @return void
	 */
	public function ajax_add_option()
	{
		ee()->load->model('vwm_polls_m');
		ee()->load->helper('vwm_polls');

		// Set entry ID & field ID
		ee()->vwm_polls_m
			->entry_id(ee()->input->post('entry_id'))
			->field_id(ee()->input->post('field_id'));

		if ( ee()->vwm_polls_m->insert_option(ee()->input->post('type'), ee()->input->post('color'), ee()->input->post('text'), ee()->input->post('order')) )
		{
			ee()->output->send_ajax_response(array('result' => 'success'));
		}
		else
		{
			ee()->output->send_ajax_response(array('errors' => 'Error.'), TRUE);
		}
	}

	/**
	 * Update poll option order
	 *
	 * This method is accessed from the fieldtype component
	 *
	 * @access public
	 * @return string
	 */
	public function ajax_update_order()
	{
		$options = ee()->input->post('options') ? ee()->input->post('options') : array();

		foreach ($options as $option_id => $option_order)
		{
			$option_id = abs( (int)$option_id );
			$option_order = abs( (int)$option_order );

			ee()->db->where('id', $option_id)->update('vwm_polls_options', array('custom_order' => $option_order));
		}
		die; // Nothing to see here
	}

}

// EOF
