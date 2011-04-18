<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
 * VWM Polls fieldtype
 */
class Vwm_polls_ft extends EE_Fieldtype {

	public $info = array(
		'name'						=> 'VWM Polls',
		'version'					=> '0.3'
	);

	public $valid_options = array(
		'option_type'				=> array('defined', 'other'),
		'options_order'				=> array('asc', 'desc', 'alphabetical', 'reverse_alphabetical', 'random', 'custom'),
		'results_chart_type'		=> array('bar', 'pie')
	);

	public $default_settings = array(
		'multiple_votes'			=> FALSE,
		'multiple_options'			=> FALSE,
		'multiple_options_limit'	=> 0,
		'options_order'				=> 'custom',
		'results_chart_type'		=> 'pie',
		'results_chart_width'		=> 330,
		'results_chart_height'		=> 330
	);

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

		// Load helper & model
		$this->EE->lang->loadfile('vwm_polls');
		$this->EE->load->helper('vwm_polls');
		$this->EE->load->model('vwm_polls_m');

		// Get member groups
		$this->member_groups();
	}

	/**
	 * Get all member groups
	 *
	 * @access private
	 * @return void
	 */
	private function member_groups()
	{
		if ( ! self::$member_groups)
		{
			$this->EE->load->model('member_model');
			$member_groups = $this->EE->member_model->get_member_groups();

			// Set member group ID as array key
			foreach ($member_groups->result_array() as $group)
			{
				self::$member_groups[$group['group_id']] = $group['group_title'];
			}
		}
	}

	/**
	 * Load CSS and JavaScript
	 *
	 * We need this to protect against CSS & JS getting included multiple times.
	 * This could happen if a user puts more than one poll in an entry
	 *
	 * @access private
	 * @return void
	 */
	private function load_css_and_javascript()
	{
		// If CSS and JavaScript has not been loaded - load it!
		if ( ! self::$css_and_javascript_loaded)
		{
			// jQuery UI tabs
			$this->EE->cp->add_js_script( array("ui" => array('tabs')) );

			$this->EE->cp->add_to_head('<script type="text/javascript">var BASE_URL = "' . base_url() . '"; </script>');
			$this->EE->cp->load_package_js('cp');
			$this->EE->cp->load_package_css('cp');

			// CSS and JavaScript have been loaded!
			self::$css_and_javascript_loaded = TRUE;
		}
	}

	/**
	 * Display Field on Publish
	 *
	 * @access public
	 * @param string		existing data
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

			// Google chart time
			$chart = google_chart($poll_settings, $poll_options);
		}
		// If we dont have any poll settings (either a new entry OR an existing entry with no poll settings)
		else
		{
			// Load default settings
			$poll_settings = array(
				'member_groups_can_vote'	=> $this->settings['member_groups_can_vote'],
				'multiple_votes'			=> (bool)$this->settings['multiple_votes'],
				'multiple_options'			=> (bool)$this->settings['multiple_options'],
				'multiple_options_limit'	=> (int)$this->settings['multiple_options_limit'],
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

		$data = array(
			'data' => $poll_settings,
			'options' => $poll_options,
			'total_votes' => $this->EE->vwm_polls_m->total_votes,
			'chart' => $chart,
			'member_groups' => self::$member_groups,
			'vwm_polls_ajax_add_option_action_id' => $this->EE->cp->fetch_action_id('vwm_polls', 'ajax_add_option'),
			'vwm_polls_ajax_update_order_action_id' => $this->EE->cp->fetch_action_id('vwm_polls', 'ajax_update_order'),
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
		// Member groups
		$member_groups_can_vote = $this->EE->input->post('member_groups_can_vote');
		$member_groups_can_vote = isset($member_groups_can_vote[$this->field_id]) ? $member_groups_can_vote[$this->field_id] : array();

		// Let's make sure these are all integers
		foreach ($member_groups_can_vote as &$group)
		{
			$group = abs( (int)$group );
		}

		// Multiple votes
		$multiple_votes = $this->EE->input->post('multiple_votes');
		$multiple_votes = (bool)$multiple_votes[$this->field_id];

		// Multiple options
		$multiple_options = $this->EE->input->post('multiple_options');
		$multiple_options = (bool)$multiple_options[$this->field_id];

		// Multiple options limit
		$multiple_options_limit = $this->EE->input->post('multiple_options_limit');
		$multiple_options_limit = (int)$multiple_options_limit[$this->field_id];

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

		$data = array(
			'member_groups_can_vote' => $member_groups_can_vote,
			'multiple_votes' => $multiple_votes,
			'multiple_options' => $multiple_options,
			'multiple_options_limit' => $multiple_options_limit,
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
	 * @return void
	 */
	public function post_save()
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
		$new_options = $this->EE->input->post('vwm_polls_new_options');

		// Make sure we have some new options to add
		if ($new_options)
		{
			// Get new option text for this field ID
			$new_options = $new_options[$this->field_id];

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
	 * @return form contents
	 */
	public function display_global_settings()
	{
		return '<h3>Default Values</h3>';
	}

	/**
	 * Save Global Settings
	 *
	 * @access public
	 * @return global settings
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
		$member_groups_can_vote = isset($data['member_groups_can_vote']) ? $data['member_groups_can_vote'] : $this->settings['member_groups_can_vote'];
		$multiple_votes = isset($data['multiple_votes']) ? (bool)$data['multiple_votes'] : $this->settings['multiple_votes'];
		$multiple_options = isset($data['multiple_options']) ? (bool)$data['multiple_options'] : $this->settings['multiple_options'];
		$multiple_options_limit = isset($data['multiple_options_limit']) ? (int)$data['multiple_options_limit'] : $this->settings['multiple_options_limit'];
		$options_order = isset($data['options_order']) ? $data['options_order'] : $this->settings['options_order'];
		$results_chart_type = isset($data['results_chart_type']) ? $data['results_chart_type'] : $this->settings['results_chart_type'];
		$results_chart_width = isset($data['results_chart_width']) ? (int)$data['results_chart_width'] : $this->settings['results_chart_width'];
		$results_chart_height = isset($data['results_chart_height']) ? (int)$data['results_chart_height'] : $this->settings['results_chart_height'];

		// Member groups
		$this->EE->table->add_row(
			lang('member_groups_can_vote', 'member_groups_can_vote'),
			form_multiselect('member_groups_can_vote[]', self::$member_groups, $member_groups_can_vote, 'id="member_groups_can_vote"')
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

		// Multiple options limit
		$this->EE->table->add_row(
			lang('multiple_options_limit', 'multiple_options_limit'),
			form_input(array('name' => 'multiple_options_limit', 'id' => 'multiple_options_limit', 'value' => $multiple_options_limit))
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
	 * @param string		form data
	 * @return void
	 */
	public function save_settings($data)
	{
		return array(
			'member_groups_can_vote' => $this->EE->input->post('member_groups_can_vote'),
			'multiple_votes' => $this->EE->input->post('multiple_votes'),
			'multiple_options' => $this->EE->input->post('multiple_options'),
			'multiple_options_limit' => $this->EE->input->post('multiple_options_limit'),
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
	 * @return void
	 */
	public function install()
	{
		// Return an array of default settings
		return array(
			'member_groups_can_vote' => array(),
			'multiple_votes' => $this->default_settings['multiple_votes'],
			'multiple_options' => $this->default_settings['multiple_options'],
			'multiple_options_limit' =>  $this->default_settings['multiple_options_limit'],
			'options_order' => $this->default_settings['options_order'],
			'results_chart_type' => $this->default_settings['results_chart_type'],
			'results_chart_width' => $this->default_settings['results_chart_width'],
			'results_chart_height' => $this->default_settings['results_chart_height']
		);
	}
}

/* End of file ft.vwm_polls.php */
/* Location: ./system/expressionengine/third_party/vwm_polls/ft.vwm_polls.php */