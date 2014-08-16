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
 * Model used for database interactions with polling module and fieldtype
 */
class Vwm_polls_m extends CI_Model {

	private $entry_id, $field_id, $field_name;
	public $poll_options = array();
	public $valid_poll_option_ids = array();
	public $total_votes = 0;
	private $hash;

	/**
	 * Model construct
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		// Load helper functions
		$this->load->helper('vwm_polls');
	}

	/**
	 * Set entry ID
	 *
	 * @access public
	 * @param $entry_id int Entry ID
	 * @return object
	 */
	public function entry_id($entry_id)
	{
		$this->entry_id = abs((int)$entry_id);
		return $this;
	}

	/**
	 * Set field ID and field name
	 *
	 * @access public
	 * @param $field_id int Field ID
	 * @return object
	 */
	public function field_id($field_id)
	{
		$this->field_id = abs((int)$field_id);

		// Generate field name while we are at it
		$this->field_name = 'field_id_' . $this->field_id;

		return $this;
	}

	public function set_hash($hash)
	{
		$this->hash = $hash;
		return $this;
	}

	/**
	 * Get poll options for a given poll
	 *
	 * @access public
	 * @param $order string Option order ("custom", "alphabetical", "reverse_alphabetical", "asc", "desc", or "random")
	 * @param $other_votes bool
	 * @return array
	 */
	public function poll_options($order = 'custom', $other_votes = FALSE)
	{
		$this->db
			->where('entry_id', $this->entry_id)
			->where('field_id', $this->field_id);

		switch ($order)
		{
			case 'alphabetical':
				$this->db->order_by('text', 'ASC');
				break;
			case 'reverse_alphabetical':
				$this->db->order_by('text', 'DESC');
				break;
			case 'asc':
				$this->db->order_by('votes', 'ASC');
				break;
			case 'desc':
				$this->db->order_by('votes', 'DESC');
				break;
			case 'random':
				$this->db->order_by('id', 'RANDOM');
				break;
			default: // Defalut to "custom"
				$this->db->order_by('custom_order', 'ASC');
				break;
		}

		// Get poll options
		$query = $this->db->get('vwm_polls_options');

		// Reset poll options, total votes, & valid poll option IDs
		$this->poll_options = array();
		$this->total_votes = 0;
		$this->valid_poll_option_ids = array();

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				// Add this option ID to the array of valid poll option IDs
				$this->valid_poll_option_ids[] = $row->id;

				// Update votes total
				$this->total_votes += $row->votes;
				
				$this->poll_options[$row->id] = array(
					'id' => $row->id,
					'order' => $row->custom_order,
					'type' => $row->type,
					'color' => $row->color,
					'text' => htmlspecialchars($row->text, ENT_QUOTES, 'UTF-8'),
					'votes' => $row->votes
				);
			}
		}

		// If we need to grab all the "other" votes
		if ($other_votes)
		{
			$this->poll_other_options();
		}

		return $this->poll_options;
	}

	/**
	 * Get all other votes for this poll and add to poll_options array
	 *
	 * @access public
	 * @return object
	 */
	public function poll_other_options()
	{
		$where_in = $this->poll_options ? array_keys($this->poll_options) : '';

		$query = $this->db
			->where_in('poll_option_id', $where_in)
			->get('vwm_polls_other_votes');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$this->poll_options[$row->poll_option_id]['other_votes'][] = htmlspecialchars($row->text, ENT_QUOTES, 'UTF-8');
			}
		}

		return $this;
	}

	/**
	 * Prep options for template
	 *
	 * Add in option_name and user_vote
	 *
	 * @access public
	 * @return void
	 */
	public function poll_options_template_prep()
	{
		// Search cookies for previous votes
		$previous_votes = $this->previous_votes();

		foreach ($this->poll_options as &$option)
		{
			$option['other_name'] = $option['type'] == 'other' ? 'vwm_polls_other_options[' . $option['id'] . ']' : NULL; // Used in EE templates so user can submit a text input
			$option['user_vote'] = in_array($option['id'], $previous_votes) ? TRUE : FALSE; // Check cookies to see if user has voted for this option
		}
	}

	/**
	 * Get user previous votes from cookies
	 *
	 * @access private
	 * @return array
	 */
	public function previous_votes()
	{
		$votes = array();
		$cookie = ee()->input->cookie($this->entry_id . '-' . $this->field_id);

		if ( !empty($cookie) )
		{
			$votes = json_decode($cookie, TRUE);
		}

		return $votes;
	}

	/**
	 * Update (or delete) a poll option
	 *
	 * @access public
	 * @param $id int Option ID
	 * @param $type string Option type ("defined" or "other")
	 * @param $color string Option hex color
	 * @param $text string Option text
	 * @param $order bool|string "defined" or "other"
	 * @return void
	 */
	public function update_option($id, $type, $color, $text, $order = false)
	{
		// Option type (make sure it is either "defined" or "other")
		$type = in_array($type, array('defined', 'other')) ? $type : 'defined';

		// Option color
		$color = hex_color($color);

		// Option text (trimmed)
		$text = trim($text);

		// If the text has a value (update option)
		if ($text)
		{
			$data = array(
				'type' => $type,
				'text' => $text,
				'color' => $color
			);
			
			if ($order !== false) { // strict type check since this could be 0
				$data['custom_order'] = $order;
			}

			$this->db
				->where('id', $id)
				->where('entry_id', $this->entry_id)
				->where('field_id', $this->field_id)
				->update('vwm_polls_options', $data);
		}
		// If there is no text (delete option)
		else
		{
			$this->remove_option($id);
		}
	}

	/**
	 * Delete a poll option
	 *
	 * @access public
	 * @param $id int Option ID
	 * @return bool
	 */
	public function remove_option($id)
	{
		// Remove all "other" votes
		$this->db
			->where('poll_option_id', $id)
			->delete('vwm_polls_other_votes');

		// Remove poll option
		$this->db
			->where('id', $id)
			->where('entry_id', $this->entry_id)
			->where('field_id', $this->field_id)
			->limit(1)
			->delete('vwm_polls_options');

		return $this->db->affected_rows() > 0 ? TRUE : FALSE;
	}

	/**
	 * Insert a new option
	 *
	 * @access public
	 * @param $type string Option type ("defined" or "other")
	 * @param $color string Option hex color
	 * @param $text string Option text
	 * @param $order int Option order
	 * @return bool
	 */
	public function insert_option($type, $color, $text, $order = 0)
	{
		// Order
		$order = abs((int)$order);
		
		// Option type (make sure it is either "defined" or "other")
		$type = in_array($type, array('defined', 'other')) ? $type : 'defined';

		// Option color
		$color = hex_color($color);

		// Option text (trimmed)
		$text = trim($text);

		// If the text is has a value (insert option)
		if ($text)
		{
			$data = array(
				'custom_order' => $order,
				'type' => $type,
				'text' => $text,
				'color' => $color,
				'entry_id' => $this->entry_id,
				'field_id' => $this->field_id
			);

			$this->db->insert('vwm_polls_options', $data);

			return $this->db->affected_rows() > 0 ? TRUE : FALSE;
		}

		return FALSE;
	}

	/**
	 * Get poll settings
	 *
	 * @access public
	 * @return array
	 */
	public function poll_settings()
	{
		$settings = array();

		// Get poll data
		$query = $this->db
			->where('entry_id', $this->entry_id)
			->limit(1)
			->get('channel_data');

		if ($query->num_rows() > 0)
		{
			$data = $query->row_array();

			// Make sure this field has poll settings
			if ( isset($data[$this->field_name]) )
			{
				$settings = json_decode($data[$this->field_name], TRUE);
			}
		}

		return $settings;
	}

	/**
	 * Cast a vote
	 *
	 * @access public
	 * @param $option_id int Option ID
	 * @return void
	 */
	public function cast_vote($option_id)
	{
		$member_id = $this->session->userdata('member_id');

		$data = array(
			'entry_id' => $this->entry_id,
			'field_id' => $this->field_id,
			'poll_option_id' => $option_id,
			'member_id' => empty($member_id) ? NULL : (int)$member_id,
			'ip_address' => $this->session->userdata('ip_address'),
			'hash' => $this->hash,
			'timestamp' => ee()->localize->now
		);

		// Update poll votes table
		$this->db->insert('vwm_polls_votes', $data);

		// Update poll options table (+1 to the votes tally)
		$this->db
			->where('id', $option_id)
			->set('votes', 'votes + 1', FALSE)
			->update('vwm_polls_options');
	}

	/**
	 * Record an "other" vote
	 *
	 * @access public
	 * @param $option_id int Option ID
	 * @param $text string Other option text
	 * @return void
	 */
	public function record_other_vote($option_id, $text)
	{
		$data = array(
			'poll_option_id' => $option_id,
			'text' => $text
		);

		$this->db->insert('vwm_polls_other_votes', $data);
	}

}

// EOF