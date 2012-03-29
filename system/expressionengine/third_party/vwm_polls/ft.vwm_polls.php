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
		'version'					=> '0.5'
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
		'results_chart_height'		=> 330
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

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Make damn sure module path is defined
		$this->EE->load->add_package_path(PATH_THIRD . 'vwm_polls/');

		// Load helper & model
		$this->EE->lang->loadfile('vwm_polls');
		$this->EE->load->model('vwm_polls_m');

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
			$this->EE->load->model('member_model');
			$member_groups = $this->EE->member_model->get_member_groups();

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
	 * Take member_groups_can_vote ("ALL", "NONE", or "SELECT") and
	 * $select_member_groups_can_vote (array of member group IDs) and determine
	 * the member_groups_can_vote property.
	 *
	 * @access private
	 * @param string
	 * @param array
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
	 * We need this to protect against CSS & JS getting included multiple times.
	 * This could happen if a user puts more than one poll in an entry
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
			$this->EE->cp->add_js_script( array('ui' => array('sortable', 'tabs')) );

			$this->EE->cp->add_to_head('<link rel="stylesheet" type="text/css" href="' . $this->EE->config->item('theme_folder_url') . 'third_party/vwm_polls/css/vwm_polls.css" />');
			$this->EE->cp->add_to_head('<script type="text/javascript">EE.CP_URL = "' . $this->EE->config->item('cp_url') . '";</script>');
			$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/vwm_polls/js/vwm_polls.js"></script>');
			$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/vwm_polls/js/display_field.js"></script>');
			$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/vwm_polls/js/jquery.crayonpicker.js"></script>');

			// CSS and JavaScript have been loaded!
			self::$css_and_javascript_loaded = TRUE;
		}
	}

	/**
	 * Display Field on Publish
	 *
	 * @access public
	 * @param string		Existing data
	 * @return string
	 */
	public function display_field($data)
	{
		// Load our JavaScipt (but only if we need to)
		$this->load_css_and_javascript();

		// If this is an existing entry that has poll settings
		if ($data)
		{
			// Get the settings for this particular poll
			$poll_settings = json_decode(htmlspecialchars_decode($data, ENT_QUOTES), TRUE);

			// Get all poll options
			$poll_options = $this->EE->vwm_polls_m
				->entry_id( $this->EE->input->get('entry_id') ) // Set entry ID
				->field_id($this->field_id) // Set field ID
				->poll_options('custom', TRUE); // Make sure we add in all "other" votes

			/**
			 * Google chart time
			 * 
			 * Let's reference the google cart method which is a duplicate of
			 * what is found in the helper file.
			 * 
			 * @todo Figure out why the hell the EE load helper does not work
			 * consistently. Recommendation: beat head against brick wall for
			 * ~30 minutes.
			 */
			$chart = $this->google_chart($poll_settings, $poll_options);
		}
		// If we dont have any poll settings (either a new entry OR an existing entry with no poll settings)
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
				'results_chart_height'		=> (int)$this->settings['results_chart_height']
			);

			// If this is an existing entry but does not have any poll settings
			if ($this->EE->input->get('entry_id'))
			{
				// Get all poll options
				$this->EE->vwm_polls_m
					->entry_id( $this->EE->input->get('entry_id') ) // Set entry ID
					->field_id($this->field_id) // Set field ID
					->poll_options();

				$poll_options = $this->EE->vwm_polls_m->poll_other_options()->poll_options; // Make sure we add in all "other" votes
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
			'total_votes' => $this->EE->vwm_polls_m->total_votes,
			'chart' => $chart,
			'member_groups' => self::$member_groups,
			'field_name' => $this->field_name,
			'field_id' => $this->field_id
		);

		return $this->EE->load->view('display_field', $data, TRUE);
	}

	/**
	 * Save poll data from entry form
	 *
	 * @access public
	 * @param string		New poll data
	 * @return string
	 */
	public function save($data)
	{
		$member_groups_can_vote = $this->EE->input->post('member_groups_can_vote'); // Allowed member groups
		$select_member_groups_can_vote = $this->EE->input->post('select_member_groups_can_vote'); // Select allowed member groups

		$member_groups_can_vote = isset($member_groups_can_vote[$this->field_id]) ? $member_groups_can_vote[$this->field_id] : 'NONE'; // Default to "NONE"
		$select_member_groups_can_vote = ( isset($select_member_groups_can_vote[$this->field_id]) AND is_array($select_member_groups_can_vote[$this->field_id]) AND count($select_member_groups_can_vote[$this->field_id]) > 0 ) ? $select_member_groups_can_vote[$this->field_id] : array();

		// Using two pieces of POST data, determine the member groups that can vote
		$this->set_member_groups_can_vote($member_groups_can_vote, $select_member_groups_can_vote);

		// Multiple votes
		$multiple_votes = $this->EE->input->post('multiple_votes');
		$multiple_votes = (bool)$multiple_votes[$this->field_id];

		// Multiple options
		$multiple_options = $this->EE->input->post('multiple_options');
		$multiple_options = (bool)$multiple_options[$this->field_id];
		
		// Multiple options min
		$multiple_options_min = $this->EE->input->post('multiple_options_min');
		$multiple_options_min = (int)$multiple_options_min[$this->field_id];

		// Multiple options max
		$multiple_options_max = $this->EE->input->post('multiple_options_max');
		$multiple_options_max = (int)$multiple_options_max[$this->field_id];

		// Options order
		$options_order = $this->EE->input->post('options_order');
		$options_order = in_array($options_order[$this->field_id], $this->valid_options['options_order']) ? $options_order[$this->field_id] : $this->default_settings['options_order'];

		// Results chart type
		$results_chart_type = $this->EE->input->post('results_chart_type');
		$results_chart_type = in_array($results_chart_type[$this->field_id], $this->valid_options['results_chart_type']) ? $results_chart_type[$this->field_id] : $this->default_settings['results_chart_type'];

		// Results chart width
		$results_chart_width = $this->EE->input->post('results_chart_width');
		$results_chart_width = (int)$results_chart_width[$this->field_id];

		// Results chart height
		$results_chart_height = $this->EE->input->post('results_chart_height');
		$results_chart_height = (int)$results_chart_height[$this->field_id];

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
			'results_chart_height' => $results_chart_height
		);

		return json_encode($data);
	}

	/**
	 * Update or add new poll options (Now that we have an entry ID)
	 *
	 * @access public
	 * @param string
	 * @return void
	 */
	public function post_save($data)
	{	
		// Set entry ID & field ID
		$this->EE->vwm_polls_m
			->entry_id($this->settings['entry_id'])
			->field_id($this->field_id);

		// Get all POSTed poll options
		$options = $this->EE->input->post('vwm_polls_options');

		// Narrow it down to all poll options for this field ID
		$options = isset($options[$this->field_id]) ? $options[$this->field_id] : array();

		// Loop through all poll options
		foreach ($options as $id => $option)
		{
			// Update (or remove) option
			$this->EE->vwm_polls_m->update_option($id, $option['type'], $option['color'], $option['text']);
		}

		/*
		 * Get all new options
		 * This will only ever have data after we have saved a *new* channel entry
		 * It will never have data after saving a previously created channel entry
		 */
		
		// Make sure we have some new options to add
		if ($this->EE->input->post('vwm_polls_new_options'))
		{
			// Get new option text for this field ID
			$new_options = $this->EE->input->post('vwm_polls_new_options');
			$new_options = isset($new_options[$this->field_id]) ? $new_options[$this->field_id] : array();

			// Loop through all our new options
			foreach($new_options as $option)
			{
				// Insert new option
				$this->EE->vwm_polls_m->insert_option($option['type'], $option['color'], $option['text']);
			}
		}
	}

	/**
	 * Replace field_id tag
	 *
	 * Used in EE templates to pass the field ID to our polls module
	 *
	 * @access public
	 * @param string		Existing data
	 * @return string		Replacement text
	 */
	public function replace_field_id($data)
	{
		return $this->field_id;
	}

	/**
	 * Display Global Settings
	 *
	 * @access public
	 * @return string		Form contents
	 */
	public function display_global_settings()
	{
		return '<h3>Default Values</h3>';
	}

	/**
	 * Save Global Settings
	 *
	 * @access public
	 * @return string		Global settings
	 */
	public function save_global_settings()
	{
		return array_merge($this->settings, $_POST);
	}

	/**
	 * Display Settings Screen
	 *
	 * @access public
	 * @param string		Existing data
	 * @return void
	 */
	public function display_settings($data)
	{
		// Load our JavaScipt (but only if we need to)
		$this->load_css_and_javascript();

		// Member groups
		$member_groups_can_vote = isset($data['member_groups_can_vote']) ? $data['member_groups_can_vote'] : $this->settings['member_groups_can_vote'];
		$select_member_groups_can_vote = array();

		// Select
		if ( is_array($member_groups_can_vote) )
		{
			$select_member_groups_can_vote = $member_groups_can_vote;
			$member_groups_can_vote = 'SELECT';
		}

		$multiple_votes = isset($data['multiple_votes']) ? (bool)$data['multiple_votes'] : $this->settings['multiple_votes'];
		$multiple_options = isset($data['multiple_options']) ? (bool)$data['multiple_options'] : $this->settings['multiple_options'];
		$multiple_options_min = isset($data['multiple_options_min']) ? (int)$data['multiple_options_min'] : $this->settings['multiple_options_min'];
		$multiple_options_max = isset($data['multiple_options_max']) ? (int)$data['multiple_options_max'] : $this->settings['multiple_options_max'];
		$options_order = isset($data['options_order']) ? $data['options_order'] : $this->settings['options_order'];
		$results_chart_type = isset($data['results_chart_type']) ? $data['results_chart_type'] : $this->settings['results_chart_type'];
		$results_chart_width = isset($data['results_chart_width']) ? (int)$data['results_chart_width'] : $this->settings['results_chart_width'];
		$results_chart_height = isset($data['results_chart_height']) ? (int)$data['results_chart_height'] : $this->settings['results_chart_height'];

		// Member groups
		$this->EE->table->add_row(
			lang('member_groups_can_vote', 'member_groups_can_vote'),
			form_dropdown('member_groups_can_vote', array('ALL' => lang('all'), 'NONE' => lang('none'), 'SELECT' => lang('select')), $member_groups_can_vote, 'id="member_groups_can_vote"')
		);

		$this->EE->table->add_row(
			lang('select_member_groups_can_vote', 'select_member_groups_can_vote'),
			form_multiselect('select_member_groups_can_vote[]', self::$member_groups, $select_member_groups_can_vote, 'id="select_member_groups_can_vote"')
		);

		// Multiple votes
		$this->EE->table->add_row(
			lang('multiple_votes', 'multiple_votes'),
			form_dropdown('multiple_votes', array(lang('no'), lang('yes')), $multiple_votes, 'id="multiple_votes"')
		);

		// Multiple options
		$this->EE->table->add_row(
			lang('multiple_options', 'multiple_options'),
			form_dropdown('multiple_options', array(lang('no'), lang('yes')), $multiple_options, 'id="multiple_options"')
		);
		
		// Multiple options min
		$this->EE->table->add_row(
			lang('multiple_options_min', 'multiple_options_min'),
			form_input(array('name' => 'multiple_options_min', 'id' => 'multiple_options_min', 'value' => $multiple_options_min))
		);

		// Multiple options max
		$this->EE->table->add_row(
			lang('multiple_options_max', 'multiple_options_max'),
			form_input(array('name' => 'multiple_options_max', 'id' => 'multiple_options_max', 'value' => $multiple_options_max))
		);

		// Options order
		$this->EE->table->add_row(
			lang('options_order', 'options_order'),
			form_dropdown('options_order', array('asc' => lang('order_asc'), 'desc' => lang('order_desc'), 'alphabetical' => lang('order_alphabetical'), 'reverse_alphabetical' => lang('order_reverse_alphabetical'), 'random' => lang('order_random'), 'custom' => lang('order_custom')), $options_order, 'id="options_order"')
		);

		// Results chart type
		$this->EE->table->add_row(
			lang('results_chart_type', 'results_chart_type'),
			form_dropdown('results_chart_type', array('bar' => lang('chart_bar'), 'pie' => lang('chart_pie')), $results_chart_type, 'id="results_chart_type"')
		);

		// Chart width
		$this->EE->table->add_row(
			lang('results_chart_width', 'results_chart_width'),
			form_input(array('name' => 'results_chart_width', 'id' => 'results_chart_width', 'value' => $results_chart_width))
		);

		// Chart height
		$this->EE->table->add_row(
			lang('results_chart_height', 'results_chart_height'),
			form_input(array('name' => 'results_chart_height', 'id' => 'results_chart_height', 'value' => $results_chart_height))
		);
	}

	/**
	 * Save Settings
	 *
	 * @access public
	 * @param string		Form data
	 * @return array
	 */
	public function save_settings($data)
	{
		$member_groups_can_vote = $this->EE->input->post('member_groups_can_vote'); // "ALL", "NONE", or "SELECT"
		$select_member_groups_can_vote = is_array($this->EE->input->post('select_member_groups_can_vote')) ? $this->EE->input->post('select_member_groups_can_vote') : array(); // Array of member group IDs

		$this->set_member_groups_can_vote($member_groups_can_vote, $select_member_groups_can_vote);

		return array(
			'member_groups_can_vote' => $this->member_groups_can_vote,
			'multiple_votes' => $this->EE->input->post('multiple_votes'),
			'multiple_options' => $this->EE->input->post('multiple_options'),
			'multiple_options_min' => $this->EE->input->post('multiple_options_min'),
			'multiple_options_max' => $this->EE->input->post('multiple_options_max'),
			'options_order' => $this->EE->input->post('options_order'),
			'results_chart_type' => $this->EE->input->post('results_chart_type'),
			'results_chart_width' => $this->EE->input->post('results_chart_width'),
			'results_chart_height' => $this->EE->input->post('results_chart_height')
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
		return array(
			'member_groups_can_vote' => $this->default_settings['member_groups_can_vote'],
			'multiple_votes' => $this->default_settings['multiple_votes'],
			'multiple_options' => $this->default_settings['multiple_options'],
			'multiple_options_min' =>  $this->default_settings['multiple_options_min'],
			'multiple_options_max' =>  $this->default_settings['multiple_options_max'],
			'options_order' => $this->default_settings['options_order'],
			'results_chart_type' => $this->default_settings['results_chart_type'],
			'results_chart_width' => $this->default_settings['results_chart_width'],
			'results_chart_height' => $this->default_settings['results_chart_height']
		);
	}

	/**
	 * Generate a Google chart (duplicate of function found in helper file)
	 *
	 * Code duplicated in vwm_polls_helper.php
	 *
	 * @access public
	 * @param array			Poll settings
	 * @param array			Poll options
	 * @return string
	 */
	public function google_chart($poll_settings, $poll_options)
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
		$data .= AMP . 'chdl=' . $chdl;
		$data .= AMP . 'chf=bg,s,00000000';
		$data .= AMP . 'chco=' . $chco;
		$data .= $chds ? AMP . $chds . $most_votes : NULL;

		return $data;
	}
}

// EOF