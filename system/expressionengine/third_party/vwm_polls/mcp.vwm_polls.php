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

class Vwm_polls_mcp {

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	/**
	 * Module CP page
	 *
	 * @access public
	 * @return string
	 */
	public function index()
	{
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('vwm_polls_module_name'));
		return 'Some stuff may go here one day.';
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
		$this->EE->load->model('vwm_polls_m');
		$this->EE->load->helper('vwm_polls');

		// Set entry ID & field ID
		$this->EE->vwm_polls_m
			->entry_id($this->EE->input->post('entry_id'))
			->field_id($this->EE->input->post('field_id'));

		if ( $this->EE->vwm_polls_m->insert_option($this->EE->input->post('type'), $this->EE->input->post('color'), $this->EE->input->post('text'), $this->EE->input->post('order')) )
		{
			$this->EE->output->send_ajax_response(array('result' => 'success'));
		}
		else
		{
			$this->EE->output->send_ajax_response(array('errors' => 'Error.'), TRUE);
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
		$options = $this->EE->input->post('options') ? $this->EE->input->post('options') : array();

		foreach ($options as $option_id => $option_order)
		{
			$option_id = abs( (int)$option_id );
			$option_order = abs( (int)$option_order );

			$this->EE->db->where('id', $option_id)->update('vwm_polls_options', array('custom_order' => $option_order));
		}
		die; // Nothing to see here
	}

}

// EOF