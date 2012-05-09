<?php if ( ! defined('EXT')) { exit('Invalid file request'); }

/**
 * VWM Polls
 *
 * Module install, uninstall, update class
 *
 * @package		VWM Polls
 * @author		Victor Michnowicz
 * @copyright	Copyright (c) 2011 Victor Michnowicz
 * @license		http://www.apache.org/licenses/LICENSE-2.0.html
 * @link		http://github.com/vmichnowicz/vwm_polls
 */

// -----------------------------------------------------------------------------

class Vwm_polls_upd {

	public $version = '0.5.2';

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
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
		$this->EE->db->insert('actions', $action_vote);

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
				`custom_order` tinyint(3) unsigned NOT NULL DEFAULT '0',
				`type` enum('defined','other') NOT NULL DEFAULT 'defined',
				`color` varchar(6) NOT NULL DEFAULT '',
				`text` varchar(128) NOT NULL DEFAULT '',
				`votes` mediumint(6) unsigned NOT NULL DEFAULT '0',
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
	public function update($current = NULL)
	{
		// Get database prefix
		$prefix = $this->EE->db->dbprefix;

		if ($current < '0.5.1')
		{
			$this->EE->db->query("
				ALTER TABLE `{$prefix}vwm_polls_options` CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				CHANGE `entry_id` `entry_id` INT(10) UNSIGNED NOT NULL,
				CHANGE `field_id` `field_id` INT(10) UNSIGNED NOT NULL,
				CHANGE `custom_order` `custom_order` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
				CHANGE `type` `type` ENUM('defined', 'other') CHARACTER SET utf8 NOT NULL DEFAULT 'defined',
				CHANGE `color` `color` VARCHAR(6) CHARACTER SET utf8 NOT NULL DEFAULT '',
				CHANGE `text` `text` VARCHAR(128) CHARACTER SET utf8 NOT NULL DEFAULT '',
				CHANGE `votes` `votes` MEDIUMINT(6) UNSIGNED NOT NULL DEFAULT '0'
			");
		}

		return TRUE;
	}

}

// EOF