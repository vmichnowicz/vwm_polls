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

	/**
	 * Model construct
	 */
	public function __construct()
	{
		// Let's get it started in here
	}

	/**
	 * Set entry ID
	 *
	 * @access public
	 * @param int			Entry ID
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
	 * @param int			Field ID
	 * @return object
	 */
	public function field_id($field_id)
	{
		$this->field_id = abs((int)$field_id);

		// Generate field name while we are at it
		$this->field_name = 'field_id_' . $this->field_id;

		return $this;
	}

	/**
	 * Get all member groups
	 *
	 * @access public
	 * @return array
	 */
	public function member_groups()
	{
		$query = $this->db->get('member_groups');

		foreach ($query->result() as $row)
		{
			$data[$row->group_id] = $row->group_title;
		}

		return $data;
	}

	/**
	 * Get poll options for a given poll
	 *
	 * @access public
	 * @param int			Entry ID
	 * @param int			Field ID
	 * @param string		Option order ("custom", "alphabetical", "reverse_alphabetical", "asc", "desc", or "random")
	 * @retrun array		Returns the poll_options array
	 */
	public function poll_options($order = 'custom')
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

		return $this->poll_options;
	}

	/**
	 * Get other poll options and add to poll_options array
	 *
	 * @access public
	 * @return object
	 */
	public function poll_other_options()
	{
		$query = $this->db
			->where_in('poll_option_id', array_keys($this->poll_options))
			->get('vwm_polls_other_votes');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$this->poll_options[$row->poll_option_id]['other_votes'][] = $row->text;
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
	private function previous_votes()
	{
		$votes = array();

		$id = $this->entry_id . '-' . $this->field_id;

		if (isset($_COOKIE[$id]))
		{
			$cookie = $_COOKIE[$id];
			$votes = json_decode($cookie, TRUE);
		}

		return $votes;
	}

	/**
	 * Get all other votes for this poll and add to poll options array
	 *
	 * @access public
	 * @return void
	 */
	public function other_votes()
	{
		$other_votes = array();

		// Get all other votes that are in our valid_poll_option_ids array
		$query = $this->EE->db
			->where_in('poll_option_id', $valid_poll_option_ids)
			->get('vwm_polls_other_votes');

		if ($query->num_rows() > 0)
		{
			foreach ($query->result() as $row)
			{
				$other_votes[$row->poll_option_id][] = $row->text;
			}
		}
		
		// If this poll has no recorder other votes we can skip the following
		if ($other_votes)
		{
			// Loop through all of our poll options
			foreach ($this->poll_options as &$option)
			{
				// If this poll option has some associated other poll votes
				if (array_key_exists($option['id'], $other_votes) )
				{
					$option['other_votes'] = $other_votes[ $option['id'] ];
				}
			}
		}
	}

	/**
	 * Update (or delete) a poll option
	 *
	 * @access public
	 * @param int			Option ID
	 * @param string		Option type ("defined" or "other")
	 * @param string		Option hex color
	 * @param string		Option text
	 * @return void
	 */
	public function update_option($id, $type, $color, $text)
	{
		// Option type (make sure it is either "defined" or "other")
		$type = in_array($type, array('defined', 'other')) ? $type : 'defined';

		// Option color
		$color = hex_color($color);

		// Option text (trimmed)
		$text = trim($text);

		// If the text is has a value (update option)
		if ($text)
		{
			$data = array(
				'type' => $type,
				'text' => $text,
				'color' => $color
			);

			$this->db
				->where('id', $id)
				->where('entry_id', $this->entry_id)
				->where('field_id', $this->field_id)
				->update('vwm_polls_options', $data);
		}
		// If there is no text (delete option)
		else
		{
			$this->db
				->where('id', $id)
				->where('entry_id', $this->entry_id)
				->where('field_id', $this->field_id)
				->delete('vwm_polls_options');
		}
	}

	/**
	 * Insert a new option
	 *
	 * @access pbli
	 * @param string		Option type ("defined" or "other")
	 * @param string		Option hex color
	 * @param string		Option text
	 * @param int			Option order
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

		// If the text is has a value (update option)
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
			$settings = json_decode($data[$this->field_name], TRUE);
		}

		return $settings;
	}

	/**
	 * Cast a vote
	 *
	 * @access public
	 * @param int			Option ID
	 * @return void
	 */
	public function cast_vote($option_id)
	{
		$data = array(
			'entry_id' => $this->entry_id,
			'field_id' => $this->field_id,
			'poll_option_id' => $option_id,
			'member_id' => $this->session->userdata('member_id'),
			'ip_address' => $this->session->userdata('ip_address'),
			'timestamp' => time()
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
	 * @param int			Option ID
	 * @param string		Other option text
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