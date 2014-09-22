<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * VWM Polls
 *
 * Fieldtype
 *
 * @package		VWM Polls
 * @author		Victor Michnowicz
 * @copyright	Copyright (c) 2011 Victor Michnowicz
 * @license		http://www.apache.org/licenses/LICENSE-2.0.html
 * @link		http://github.com/vmichnowicz/vwm_polls
 */

// -----------------------------------------------------------------------------

class Vwm_polls_ft extends EE_Fieldtype {

	public $info = array(
		'name'						=> 'VWM Polls',
		'version'					=> '0.10.1'
	);

	public $valid_options = array(
		'option_type'				=> array('defined', 'other'),
		'options_order'				=> array('asc', 'desc', 'alphabetical', 'reverse_alphabetical', 'random', 'custom'),
		'results_chart_type'		=> array('bar', 'pie')
	);

	public $default_settings = array(
		'member_groups_can_vote'	=> 'ALL', // All groups can vote by default
		'multiple_votes'			=> FALSE,
		'multiple_options'			=> FALSE,
		'multiple_options_min'		=> 0,
		'multiple_options_max'		=> 0,
		'options_order'				=> 'custom',
		'results_chart_type'		=> 'pie',
		'results_chart_width'		=> 330,
		'results_chart_height'		=> 330,
		'results_chart_labels' 		=> 1
	);

	private $member_groups_can_vote;

	private static $member_groups = array();
	private static $css_and_javascript_loaded = FALSE;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		// Make damn sure module path is defined
		ee()->load->add_package_path(PATH_THIRD . 'vwm_polls/');

		// Load helper & model
		ee()->lang->loadfile('vwm_polls');
		ee()->load->model('vwm_polls_m');
		ee()->load->helper('vwm_polls');

		// Set member groups
		$this->set_member_groups();
	}

	/**
	 * Get all member groups and set member_groups property
	 *
	 * @access private
	 * @return object
	 */
	private function set_member_groups()
	{
		if ( ! self::$member_groups)
		{
			ee()->load->model('member_model');
			$member_groups = ee()->member_model->get_member_groups();

			foreach ($member_groups->result_array() as $group)
			{
				// Set member group ID as array key
				self::$member_groups[ $group['group_id'] ] = $group['group_title'];
			}
		}

		return $this;
	}

	/**
	 * Set the member groups can vote
	 *
	 * Take member_groups_can_vote ("ALL", "NONE", or "SELECT") and $select_member_groups_can_vote (array of member
	 * group IDs) and determine the member_groups_can_vote property.
	 *
	 * @access private
	 * @param $member_groups_can_vote string
	 * @param $select_member_groups_can_vote array
	 * @return object
	 */
	private function set_member_groups_can_vote($member_groups_can_vote, array $select_member_groups_can_vote)
	{
		// Switch between "ALL", "NONE", or "SELECT"
		switch ($member_groups_can_vote)
		{
			// All member groups can vote
			case 'ALL':
				$this->member_groups_can_vote = 'ALL';
				break;
			// Select member groups can vote
			case 'SELECT':
				// If user wants select member groups to vote
				if ( count($select_member_groups_can_vote) > 0 )
				{
					// Let's make sure these are all integers
					foreach ($select_member_groups_can_vote as &$group)
					{
						$group = abs( (int)$group );
					}

					// Make sure there are no duplicate group IDs
					array_unique($select_member_groups_can_vote, SORT_NUMERIC);

					// Sort array of group IDs from lowest to highest numericarly
					sort($select_member_groups_can_vote, SORT_NUMERIC);

					$this->member_groups_can_vote = $select_member_groups_can_vote;
				}
				// If user selected the "Select" member groups that can vote option but did not select any member groups, set to "NONE"
				else
				{
					$this->member_groups_can_vote = 'NONE';
				}
				break;
			// Default to no member groups that can vote, "NONE"
			default:
				$this->member_groups_can_vote = 'NONE';
				break;
		}

		return $this;
	}

	/**
	 * Load CSS and JavaScript
	 *
	 * We need this to protect against CSS & JS getting included multiple times. This could happen if a user puts more
	 * than one poll in an entry
	 *
	 * @access private
	 * @param string
	 * @return void
	 */
	private function load_css_and_javascript()
	{
		// If CSS and JavaScript have not been loaded - load em!
		if ( ! self::$css_and_javascript_loaded)
		{
			// jQuery UI tabs
			ee()->cp->add_js_script( array('ui' => array('sortable', 'tabs'), 'plugin' => array('jscolor')) );

			ee()->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . ee()->config->item('theme_folder_url') . 'third_party/vwm_polls/css/vwm_polls.css" />');
			ee()->cp->add_to_head('<script type="text/javascript">EE.CP_URL = "' . ee()->config->item('cp_url') . '";  EE.vwm_polls_option_text_removed = "'.ee()->lang->line('option_text_removed').'";</script>');
			ee()->cp->add_to_head('<script type="text/javascript" src="' . ee()->config->item('theme_folder_url') . 'third_party/vwm_polls/js/vwm_polls.js"></script>');
			ee()->cp->add_to_head('<script type="text/javascript" src="' . ee()->config->item('theme_folder_url') . 'third_party/vwm_polls/js/display_field.js"></script>');

			// CSS and JavaScript have been loaded!
			self::$css_and_javascript_loaded = TRUE;
		}
	}

	/**
	 * Display Field on Publish
	 *
	 * @access public
	 * @param $data string Existing data
	 * @return string
	 */
	public function display_field($data)
	{
		// Load our JavaScript (but only if we need to)
		$this->load_css_and_javascript();

		// If this is an existing entry that has poll settings
		if ($data)
		{
			// Get the settings for this particular poll
			$poll_settings = json_decode(htmlspecialchars_decode($data, ENT_QUOTES), TRUE);

			// Get all poll options
			$poll_options = ee()->vwm_polls_m
				->entry_id( ee()->input->get('entry_id') ) // Set entry ID
				->field_id($this->field_id) // Set field ID
				->poll_options('custom', TRUE); // Make sure we add in all "other" votes

			// EE load helper seems to work as of EE 2.6.0 (for some reason it did not work earlier)
			$chart = google_chart($poll_settings, $poll_options);
		}
		// If we don't have any poll settings (either a new entry OR an existing entry with no poll settings)
		else
		{
			// Load default settings
			$poll_settings = array(
				'member_groups_can_vote'	=> $this->settings['member_groups_can_vote'],
				'select_member_groups_can_vote'	=> array(),
				'multiple_votes'			=> (bool)$this->settings['multiple_votes'],
				'multiple_options'			=> (bool)$this->settings['multiple_options'],
				'multiple_options_min'		=> (int)$this->settings['multiple_options_min'],
				'multiple_options_max'		=> (int)$this->settings['multiple_options_max'],
				'options_order'				=> $this->settings['options_order'],
				'results_chart_type'		=> $this->settings['results_chart_type'],
				'results_chart_width'		=> (int)$this->settings['results_chart_width'],
				'results_chart_height'		=> (int)$this->settings['results_chart_height'],
				'results_chart_labels' 		=> (bool)$this->settings['results_chart_labels']
			);

			// If this is an existing entry but does not have any poll settings
			if (ee()->input->get('entry_id'))
			{
				// Get all poll options
				ee()->vwm_polls_m
					->entry_id( ee()->input->get('entry_id') ) // Set entry ID
					->field_id($this->field_id) // Set field ID
					->poll_options();

				$poll_options = ee()->vwm_polls_m->poll_other_options()->poll_options; // Make sure we add in all "other" votes
			}

			// If this is a new poll we will not have any poll options
			else
			{
				$poll_options = array();
			}

			// Since we have no poll data we will have no results
			$chart = NULL;
		}

		$poll_settings['json'] = htmlentities(json_encode($poll_settings), ENT_QUOTES, 'UTF-8');
		$poll_settings['select_member_groups_can_vote'] = array();

		// Select
		if ( is_array($poll_settings['member_groups_can_vote']) )
		{
			$poll_settings['select_member_groups_can_vote'] = $poll_settings['member_groups_can_vote'];
			$poll_settings['member_groups_can_vote'] = 'SELECT';
		}

		$data = array(
			'data' => $poll_settings,
			'options' => $poll_options,
			'total_votes' => ee()->vwm_polls_m->total_votes,
			'chart' => $chart,
			'member_groups' => self::$member_groups,
			'field_name' => $this->field_name,
			'field_id' => $this->field_id
		);

		return ee()->load->view('display_field', $data, TRUE);
	}

	/**
	 * Save poll data from entry form
	 *
	 * @access public
	 * @param $data string New poll data
	 * @return string
	 */
	public function save($data)
	{
		$member_groups_can_vote = ee()->input->post('member_groups_can_vote'); // Allowed member groups
		$select_member_groups_can_vote = ee()->input->post('select_member_groups_can_vote'); // Select allowed member groups

		$member_groups_can_vote = isset($member_groups_can_vote[$this->field_id]) ? $member_groups_can_vote[$this->field_id] : 'NONE'; // Default to "NONE"
		$select_member_groups_can_vote = ( isset($select_member_groups_can_vote[$this->field_id]) AND is_array($select_member_groups_can_vote[$this->field_id]) AND count($select_member_groups_can_vote[$this->field_id]) > 0 ) ? $select_member_groups_can_vote[$this->field_id] : array();

		// Using two pieces of POST data, determine the member groups that can vote
		$this->set_member_groups_can_vote($member_groups_can_vote, $select_member_groups_can_vote);

		// Multiple votes
		$multiple_votes = ee()->input->post('multiple_votes');
		$multiple_votes = (bool)$multiple_votes[$this->field_id];

		// Multiple options
		$multiple_options = ee()->input->post('multiple_options');
		$multiple_options = (bool)$multiple_options[$this->field_id];
		
		// Multiple options min
		$multiple_options_min = ee()->input->post('multiple_options_min');
		$multiple_options_min = (int)$multiple_options_min[$this->field_id];

		// Multiple options max
		$multiple_options_max = ee()->input->post('multiple_options_max');
		$multiple_options_max = (int)$multiple_options_max[$this->field_id];

		// Options order
		$options_order = ee()->input->post('options_order');
		$options_order = in_array($options_order[$this->field_id], $this->valid_options['options_order']) ? $options_order[$this->field_id] : $this->default_settings['options_order'];

		// Results chart type
		$results_chart_type = ee()->input->post('results_chart_type');
		$results_chart_type = in_array($results_chart_type[$this->field_id], $this->valid_options['results_chart_type']) ? $results_chart_type[$this->field_id] : $this->default_settings['results_chart_type'];

		// Results chart width
		$results_chart_width = ee()->input->post('results_chart_width');
		$results_chart_width = (int)$results_chart_width[$this->field_id];

		// Results chart height
		$results_chart_height = ee()->input->post('results_chart_height');
		$results_chart_height = (int)$results_chart_height[$this->field_id];

		$results_chart_labels = $this->EE->input->post('results_chart_labels');
		$results_chart_labels = (bool)$results_chart_labels[$this->field_id];

		// JSON all up in this piece
		$data = array(
			'member_groups_can_vote' => $this->member_groups_can_vote,
			'multiple_votes' => $multiple_votes,
			'multiple_options' => $multiple_options,
			'multiple_options_min' => $multiple_options_min,
			'multiple_options_max' => $multiple_options_max,
			'options_order' => $options_order,
			'results_chart_type' => $results_chart_type,
			'results_chart_width' => $results_chart_width,
			'results_chart_height' => $results_chart_height,
			'results_chart_labels' => $results_chart_labels,
		);

		return json_encode($data);
	}

	/**
	 * Update or add new poll options (Now that we have an entry ID)
	 *
	 * @access public
	 * @param $data string
	 * @return void
	 */
	public function post_save($data)
	{	
		// Set entry ID & field ID
		ee()->vwm_polls_m
			->entry_id($this->settings['entry_id'])
			->field_id($this->field_id);

		// Get all POSTed poll options
		$options = ee()->input->post('vwm_polls_options');

		// Narrow it down to all poll options for this field ID
		$options = isset($options[$this->field_id]) ? $options[$this->field_id] : array();

		// Loop through all poll options
		foreach ($options as $order => $option)
		{
			if ($option['id'] == "new") {
				// Insert new option, since we no longer use AJAX
				ee()->vwm_polls_m->insert_option($option['type'], $option['color'], $option['text'], $order);
			} else {
				// Update (or remove) option
				ee()->vwm_polls_m->update_option($option['id'], $option['type'], $option['color'], $option['text'], $order);
			}
		}
	}

	/**
	 * Replace field_id tag
	 *
	 * Used in EE templates to pass the field ID to our polls module
	 *
	 * @access public
	 * @param $data string Existing data
	 * @return string Replacement text
	 */
	public function replace_field_id($data)
	{
		return $this->field_id;
	}

	/**
	 * Display Global Settings
	 *
	 * @access public
	 * @return string
	 */
	public function display_global_settings()
	{
		return '<h3>Default Values</h3>';
	}

	/**
	 * Save Global Settings
	 *
	 * @access public
	 * @return string
	 */
	public function save_global_settings()
	{
		return array_merge($this->settings, $_POST);
	}

	/**
	 * Display Settings Screen
	 *
	 * @access public
	 * @param $data string Existing data
	 * @return void
	 */
	public function display_settings($data)
	{
		// Load our JavaScript (but only if we need to)
		$this->load_css_and_javascript();

		$options = $this->default_settings;

		foreach ($options as $option => $value)
		{
			if ( isset($data[$option]) )
			{
				$options[$option] = $data[$option];
			}
			elseif ( isset($this->settings[$option]) )
			{
				$options[$option] = $this->settings[$option];
			}
		}

		// Select member groups that can vote in this poll
		$select_member_groups_can_vote = array();

		if ( is_array($options['member_groups_can_vote']) )
		{
			$select_member_groups_can_vote = $options['member_groups_can_vote'];
			$options['member_groups_can_vote'] = 'SELECT';
		}

		// Member groups
		ee()->table->add_row(
			lang('member_groups_can_vote', 'member_groups_can_vote'),
			form_dropdown('member_groups_can_vote', array('ALL' => lang('all'), 'NONE' => lang('none'), 'SELECT' => lang('select')), $options['member_groups_can_vote'], 'id="member_groups_can_vote"')
		);

		ee()->table->add_row(
			lang('select_member_groups_can_vote', 'select_member_groups_can_vote'),
			form_multiselect('select_member_groups_can_vote[]', self::$member_groups, $select_member_groups_can_vote, 'id="select_member_groups_can_vote"')
		);

		// Multiple votes
		ee()->table->add_row(
			lang('multiple_votes', 'multiple_votes'),
			form_dropdown('multiple_votes', array(lang('no'), lang('yes')), $options['multiple_votes'], 'id="multiple_votes"')
		);

		// Multiple options
		ee()->table->add_row(
			lang('multiple_options', 'multiple_options'),
			form_dropdown('multiple_options', array(lang('no'), lang('yes')), $options['multiple_options'], 'id="multiple_options"')
		);
		
		// Multiple options min
		ee()->table->add_row(
			lang('multiple_options_min', 'multiple_options_min'),
			form_input(array('name' => 'multiple_options_min', 'id' => 'multiple_options_min', 'value' => $options['multiple_options_min']))
		);

		// Multiple options max
		ee()->table->add_row(
			lang('multiple_options_max', 'multiple_options_max'),
			form_input(array('name' => 'multiple_options_max', 'id' => 'multiple_options_max', 'value' => $options['multiple_options_max']))
		);

		// Options order
		ee()->table->add_row(
			lang('options_order', 'options_order'),
			form_dropdown('options_order', array('asc' => lang('order_asc'), 'desc' => lang('order_desc'), 'alphabetical' => lang('order_alphabetical'), 'reverse_alphabetical' => lang('order_reverse_alphabetical'), 'random' => lang('order_random'), 'custom' => lang('order_custom')), $options['options_order'], 'id="options_order"')
		);

		// Results chart type
		ee()->table->add_row(
			lang('results_chart_type', 'results_chart_type'),
			form_dropdown('results_chart_type', array('bar' => lang('chart_bar'), 'pie' => lang('chart_pie')), $options['results_chart_type'], 'id="results_chart_type"')
		);

		// Chart width
		ee()->table->add_row(
			lang('results_chart_width', 'results_chart_width'),
			form_input(array('name' => 'results_chart_width', 'id' => 'results_chart_width', 'value' => $options['results_chart_width']))
		);

		// Chart height
		ee()->table->add_row(
			lang('results_chart_height', 'results_chart_height'),
			form_input(array('name' => 'results_chart_height', 'id' => 'results_chart_height', 'value' => $options['results_chart_height']))
		);

		// Chart labels
		$this->EE->table->add_row(
			lang('results_chart_labels', 'results_chart_labels'),
			form_dropdown('results_chart_labels', array(lang('no'), lang('yes')), $options['results_chart_labels'], 'id="results_chart_labels"')
		);
	}

	/**
	 * Save Settings
	 *
	 * @access public
	 * @param $data string Form data
	 * @return array
	 */
	public function save_settings($data)
	{
		$member_groups_can_vote = ee()->input->post('member_groups_can_vote'); // "ALL", "NONE", or "SELECT"
		$select_member_groups_can_vote = is_array(ee()->input->post('select_member_groups_can_vote')) ? ee()->input->post('select_member_groups_can_vote') : array(); // Array of member group IDs

		$this->set_member_groups_can_vote($member_groups_can_vote, $select_member_groups_can_vote);

		return array(
			'member_groups_can_vote' => $this->member_groups_can_vote,
			'multiple_votes' => ee()->input->post('multiple_votes'),
			'multiple_options' => ee()->input->post('multiple_options'),
			'multiple_options_min' => ee()->input->post('multiple_options_min'),
			'multiple_options_max' => ee()->input->post('multiple_options_max'),
			'options_order' => ee()->input->post('options_order'),
			'results_chart_type' => ee()->input->post('results_chart_type'),
			'results_chart_width' => ee()->input->post('results_chart_width'),
			'results_chart_height' => ee()->input->post('results_chart_height'),
			'results_chart_labels' => ee()->input->post('results_chart_labels'),
		);
	}

	/**
	 * Install Fieldtype
	 *
	 * @access public
	 * @return array
	 */
	public function install()
	{
		// Return an array of default settings
		return $this->default_settings;
	}
}

// EOF