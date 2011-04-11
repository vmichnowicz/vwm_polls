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
	
	function  __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	function index()
	{
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('vwm_polls_module_name'));
		return 'Some stuff may go here one day.';
	}

}
// END CLASS

/* End of file mcp.vwm_polls.php */
/* Location: ./system/expressionengine/third_party/vwm_polls/mcp.vwm_polls.php */