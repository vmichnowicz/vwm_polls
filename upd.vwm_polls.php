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
 * Lets install, uninstall, or update this bad boy
 */
class Vwm_polls_upd {

	public $version = '1.0';
	
	function __construct()
	{
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
	}

	/**
	 * Module Installer
	 *
	 * @access public
	 * @return bool
	 */	
	public function install()
	{
		// VWM Polls module information
		$data = array(
			'module_name' => 'Vwm_polls' ,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);

		$this->EE->db->insert('modules', $data);

		// For exp_actions
		$action_vote = array('class' => 'Vwm_polls', 'method' => 'vote');
		$action_ajax_add_option = array('class' => 'Vwm_polls', 'method' => 'ajax_add_option');
		$action_ajax_update_order = array('class' => 'Vwm_polls', 'method' => 'ajax_update_order');

		$this->EE->db->insert('actions', $action_vote);
		$this->EE->db->insert('actions', $action_ajax_add_option);
		$this->EE->db->insert('actions', $action_ajax_update_order);

		// Get database prefix
		$prefix = $this->EE->db->dbprefix;

		// Table to record poll votes
		$this->EE->db->query("
			CREATE TABLE IF NOT EXISTS `{$prefix}vwm_polls_votes` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`entry_id` int(10) unsigned NOT NULL,
				`field_id` int(10) unsigned NOT NULL,
				`poll_option_id` int(10) unsigned NOT NULL,
				`member_id` int(10) unsigned DEFAULT NULL,
				`ip_address` varchar(16) NOT NULL,
				`session_id` varchar(40) DEFAULT NULL,
				`timestamp` int(10) unsigned NOT NULL,
				PRIMARY KEY (`id`),
				KEY `entry_id` (`entry_id`,`field_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		");

		// Table to store poll options
		$this->EE->db->query("
			CREATE TABLE IF NOT EXISTS `{$prefix}vwm_polls_options` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`entry_id` int(10) unsigned NOT NULL,
				`field_id` int(10) unsigned NOT NULL,
				`custom_order` tinyint(3) unsigned NOT NULL,
				`type` enum('defined','other') NOT NULL DEFAULT 'defined',
				`color` varchar(6) NOT NULL,
				`text` varchar(128) NOT NULL,
				`votes` mediumint(6) unsigned NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
		");

		// Table to store "other" poll votes
		$this->EE->db->query("
		CREATE TABLE IF NOT EXISTS `{$prefix}vwm_polls_other_votes` (
			`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			`poll_option_id` int(10) unsigned NOT NULL,
			`text` tinytext NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM DEFAULT CHARSET=utf8;
	");

		return TRUE;
	}

	/**
	 * Module Uninstaller
	 *
	 * @access public
	 * @return bool
	 */	
	public function uninstall()
	{
		// Get database prefix
		$prefix = $this->EE->db->dbprefix;
		
		// Get module ID
		$query = $this->EE->db
			->select('module_id')
			->where('module_name', 'Vwm_polls')
			->limit(1)
			->get('modules');

		// Delete from module_member_groups
		$this->EE->db
			->where('module_id', $query->row('module_id'))
			->delete('module_member_groups');

		// Delete from modules
		$this->EE->db
			->where('module_id', $query->row('module_id'))
			->delete('modules');

		// Delete from actions
		$this->EE->db
			->where('class', 'Vwm_polls')
			->delete('actions');

		// Delete all extra tables
		$this->EE->db->query("DROP TABLE {$prefix}vwm_polls_options");
		$this->EE->db->query("DROP TABLE {$prefix}vwm_polls_other_votes");
		$this->EE->db->query("DROP TABLE {$prefix}vwm_polls_votes");

		return TRUE;
	}

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function update($current='')
	{
		return FALSE;
	}
	
}
// END CLASS

/* End of file upd.vwm_polls.php */
/* Location: ./system/expressionengine/third_party/vwm_polls/upd.vwm_polls.php */