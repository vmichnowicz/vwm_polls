<?php if ( ! defined('EXT')) { exit('Invalid file request'); }

/**
 * VWM Polls
 *
 * Main polling class
 *
 * @package		VWM Polls
 * @author		Victor Michnowicz
 * @copyright	Copyright (c) 2011 Victor Michnowicz
 * @license		http://www.apache.org/licenses/LICENSE-2.0.html
 * @link		http://github.com/vmichnowicz/vwm_polls
 */

// -----------------------------------------------------------------------------

class Vwm_polls {

	private $entry_id;
	private $field_id;
	private $poll_settings = array();
	private $poll_options = array();
	private $errors = array();
	private $already_voted = FALSE;
	private $prefix;

	private $javascript_attribute_hash; // 32 character hash
	private $javascript_attributes = array(); // Array of all JavaScript attributes used in hash

	// User details
	private $member_id, $group_id, $ip_address, $timestamp;

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	public function __construct()
	{
		// Make damn sure module path is defined
		ee()->load->add_package_path(PATH_THIRD . 'vwm_polls/');

		// Load lang, helper, config, and model
		ee()->lang->loadfile('vwm_polls');
		ee()->load->helper('vwm_polls');
		ee()->load->model('vwm_polls_m');
		ee()->config->load('vwm_polls');

		$this->prefix = ee()->config->item('vwm_polls_template_prefix');
	}

	/**
	 * Get user details and save the properties
	 *
	 * @access private
	 * @return void
	 */
	private function user_details()
	{
		$this->member_id = ee()->session->userdata('member_id') ? ee()->session->userdata('member_id') : NULL;
		$this->group_id = ee()->session->userdata('group_id') ? ee()->session->userdata('group_id') : NULL;
		$this->ip_address = ee()->session->userdata('ip_address');
		$this->timestamp = ee()->localize->now;
	}

	/**
	 * Build the poll
	 *
	 * @access public
	 * @return string
	 */
	public function poll()
	{
		ee()->load->library('javascript');

		$redirect = ee()->TMPL->fetch_param('redirect');
		$this->entry_id = ee()->TMPL->fetch_param('entry_id');
		$this->field_id = ee()->TMPL->fetch_param('field_id');

		$action = (! ee()->TMPL->fetch_param('action'))
			? ee()->config->item('base_url')
			: ee()->TMPL->fetch_param('action');

		// The entry ID is required
		if ( ! $this->entry_id ) { return FALSE; }

		// Set entry ID & field ID
		$this->poll_settings = ee()->vwm_polls_m
			->entry_id($this->entry_id)
			->field_id($this->field_id);

		// Get poll settings
		$this->poll_settings = ee()->vwm_polls_m->poll_settings();

		// If there are no settings for this poll
		if ( ! $this->poll_settings) { return; }

		// Get poll options
		ee()->vwm_polls_m->poll_options($this->poll_settings['options_order']);
		ee()->vwm_polls_m->poll_options_template_prep();
		$this->poll_options = ee()->vwm_polls_m->poll_options;

		// Get all the info inside the template tags
		$tagdata = ee()->TMPL->tagdata;

		// Gets appended after <form> element
		$javascript = '<script type="text/javascript">
			var form = document.getElementById("vwm_polls_poll_' . $this->entry_id . '");

			var date = new Date();

			form["user_agent"].value = navigator.userAgent;
			form["window_navigator"].value = navigator.appVersion;
			form["screen_width"].value = screen.width;
			form["screen_height"].value = screen.height;
			form["timezone_offset"].value = date.getTimezoneOffset();
			form["cookies"].value = navigator.cookieEnabled === true ? "1" : "0";
			form["platform"].value = navigator.platform;
			form["colors"].value = window.screen.colorDepth;
			form["java"].value = navigator.javaEnabled() ? "1" : "0";
		</script>';

		// Template variable data
		$variables[] = $this->prefix(array(
			'input_type' => $this->poll_settings['multiple_options'] ? 'checkbox' : 'radio',
			'input_name' => 'vwm_polls_options[]',
			'max_options' => $this->poll_settings['multiple_options_max'],
			'can_vote' => $this->can_vote(),
			'already_voted' => $this->already_voted(),
			'chart' => google_chart($this->poll_settings, $this->poll_options),
			'total_votes' => ee()->vwm_polls_m->total_votes,
			'options' => array_values($this->poll_options), // I guess our array indexes need to start at 0...
			'options_results' => calculate_results($this->poll_options, ee()->vwm_polls_m->total_votes),
		));

		// Get hidden fields, class, and ID for our form
		$form_data = array(
			'id' => 'vwm_polls_poll_' . $this->entry_id,
			'class' => 'vwm_polls_poll',
			'action' => $action,
			'hidden_fields' => array(
				'ACT' => ee()->functions->fetch_action_id('Vwm_polls', 'vote'),
				'RET' => ( ! ee()->TMPL->fetch_param('return'))  ? '' : ee()->TMPL->fetch_param('return'),
				'URI' => (ee()->uri->uri_string == '') ? 'index' : ee()->uri->uri_string,
				'entry_id' => $this->entry_id,
				'field_id' => $this->field_id,
				'redirect' => $redirect,
				'user_agent' => NULL,
				'window_navigator' => NULL,
				'screen_width' => NULL,
				'screen_height' => NULL,
				'timezone_offset' => NULL,
				'cookies' => NULL,
				'platform' => NULL,
				'colors' => NULL,
				'java' => NULL,
			)
		);

		// Make the magic happen
		return ee()->functions->form_declaration($form_data) . ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $variables) . '</form>' . $javascript;
	}

	/**
	 * Prefix template variables (only prefix non-numeric array keys)
	 *
	 * @access public
	 * @param array $array
	 * @param array $output
	 * @return array
	 */
	public function prefix(array $array, array $output = array())
	{
		foreach ($array as $key => $value)
		{
			$prefixed_key = is_int($key) ? $key : $this->prefix . $key; // Only prefix non-numeric array keys
			$output[ $prefixed_key ] = is_array($value) ? $this->prefix($value) : $value;
		}

		return $output;
	}

	/**
	 * Get JavaScript hash
	 *
	 * @access public
	 * @return string
	 */
	public function get_javascript_attribute_hash()
	{
		if ( empty($this->javascript_attribute_hash) && ! empty($this->javascript_attributes) )
		{
			$this->javascript_attribute_hash = md5( implode('', $this->javascript_attributes) );
		}
		return $this->javascript_attribute_hash;
	}

	/**
	 * Handle a vote
	 *
	 * @access public
	 * @return void
	 */
	public function vote()
	{
		// Check XID
		if ( ! ee()->security->secure_forms_check(ee()->input->post('XID')) )
		{
			$this->errors[] = ee()->lang->line('invalid_xid');
			die( $this->show_errors() );
		}

		ee()->load->helper('html');

		$redirect = ee()->input->post('redirect');
		$this->entry_id = ee()->input->post('entry_id');
		$this->field_id = ee()->input->post('field_id');

		// Get information passed by JavaScript
		$this->javascript_attributes = array(
			'user_agent' => ee()->input->post('user_agent'),
			'window_navigator' => ee()->input->post('window_navigator'),
			'screen_width' => ee()->input->post('screen_width'),
			'screen_height' => ee()->input->post('screen_height'),
			'timezone_offset' => ee()->input->post('timezone_offset'),
			'cookies' => ee()->input->post('cookies'),
			'platform' => ee()->input->post('platform'),
			'colors' => ee()->input->post('colors'),
			'java' => ee()->input->post('java'),
		);

		$this->poll_settings = ee()->vwm_polls_m
			->entry_id($this->entry_id)
			->field_id($this->field_id)
			->set_hash( $this->get_javascript_attribute_hash() )
			->poll_settings();

		// Get poll options
		ee()->vwm_polls_m->poll_options($this->poll_settings['options_order']);
		ee()->vwm_polls_m->poll_options_template_prep();
		$this->poll_options = ee()->vwm_polls_m->poll_options;
		$valid_poll_option_ids = ee()->vwm_polls_m->valid_poll_option_ids;

		$selected_poll_options = ee()->input->post('vwm_polls_options') ? ee()->input->post('vwm_polls_options') : array();
		$other_options = ee()->input->post('vwm_polls_other_options');

		// Make sure this poll exists
		if ( ! $this->poll_settings)
		{
			$this->errors[] = ee()->lang->line('poll_not_exist');
			die( $this->show_errors() );
		}

		// Results only?  (Useful to fetch the most updated results for clicking "view results" via AJAX for sites that use caching)
		$results_only = FALSE;
		if (AJAX_REQUEST && ee()->input->post('results_only')) {
			$results_only = TRUE; // this will let us skip a bunch of error checks
		}

		if (!$results_only) {
			// Make sure the user submitted a poll option
			if ( ! $selected_poll_options)
			{
				$this->errors[] = ee()->lang->line('no_options_submitted');
				die( $this->show_errors() );
			}

			// If this poll only accecpts one poll option and the user submitted more than one
			if (($this->poll_settings['multiple_options'] == FALSE AND count($selected_poll_options) > 1))
			{
				$this->errors[] = sprintf(ee()->lang->line('no_options_submitted'), count($selected_poll_options));
				die( $this->show_errors() );
			}

			// If this poll accecpts multiple options
			if ($this->poll_settings['multiple_options'] == TRUE)
			{
				// If multiple options minimum is set
				if ($this->poll_settings['multiple_options_min'] > 0)
				{
					// If the user selects less than the allowed number of options
					if (count($selected_poll_options) < $this->poll_settings['multiple_options_min'])
					{
						$this->errors[] = sprintf(ee()->lang->line('too_few_options_submitted'), $this->poll_settings['multiple_options_min'], count($selected_poll_options));
					}
				}

				// If multiple options limit is set (a limit of "0" means there is no limit to the amount of options a user can select)
				if ($this->poll_settings['multiple_options_max'] > 0)
				{
					// If the user selects more than the allowed number of optoins
					if (count($selected_poll_options) > $this->poll_settings['multiple_options_max'])
					{
						$this->errors[] = sprintf(ee()->lang->line('too_many_options_submitted'), $this->poll_settings['multiple_options_max'], count($selected_poll_options));
					}
				}
			}

			// Make sure the user submitted a valid poll option
			foreach ($selected_poll_options as $option)
			{
				if ( ! in_array($option, $valid_poll_option_ids))
				{
					$this->errors[] = ee()->lang->line('invalid_poll_option');
					die( $this->show_errors() );
				}
			}

			// Lets make sure this person can vote
			if ( ! $this->can_vote() ) {
				die( $this->show_errors() );
			}

			// Actually vote
			if (! $this->errors)
			{
				// We are gonna need some cookies up in here
				ee()->input->set_cookie($this->entry_id . '-' . $this->field_id, json_encode($selected_poll_options), 31536000); // Cookie expires in ~1 year

				// Cast a vote for each poll option
				foreach ($selected_poll_options as $option_id)
				{
					// Record this vote
					ee()->vwm_polls_m->cast_vote($option_id);

					// If this option is of type "other"
					if ( $this->poll_options[$option_id]['type'] == 'other')
					{
						// Record this "other" vote
						ee()->vwm_polls_m->record_other_vote($option_id, $other_options[$option]);
					}
				}
			}
			// No vote for you!
			else
			{
				die( $this->show_errors() );
			}
		}
		// Check for errors
		if (! $this->errors)
		{
			// Great success!
			if (AJAX_REQUEST)
			{
				$previous_votes = ee()->vwm_polls_m->previous_votes();

				// Get updated poll options
				ee()->vwm_polls_m->poll_options($this->poll_settings['options_order']);

				$updated_total_votes = ee()->vwm_polls_m->total_votes;
				$updated_options = calculate_results(ee()->vwm_polls_m->poll_options, $updated_total_votes);
				$updated_chart = str_replace('&amp;', '&', google_chart($this->poll_settings, $updated_options)); // The ampersands in "&amp;" end up getting encoded again...

				if ( is_array($updated_options) && is_array($previous_votes) )
				{
					foreach($updated_options as $key => $option)
					{
						$updated_options[$key]['user_vote'] = in_array($option['id'], $previous_votes);
					}
				}

				$data = array(
					'options' => $updated_options,
					'total_votes' => $updated_total_votes,
					'chart' => $updated_chart,
					'user_votes' => $previous_votes,
				);

				// Send updated poll options
				ee()->output->send_ajax_response($data);
			}
			else
			{
				ee()->functions->redirect($redirect);
			}
		}
		// No vote for you!
		else
		{
			die( $this->show_errors() );
		}
	}

	/**
	 * Determine if a user has already voted
	 *
	 * @access public
	 * @return bool
	 */
	public function already_voted()
	{
		// Check cookies to see if user has already voted
		if ( isset($_COOKIE[$this->entry_id . '-' . $this->field_id]) )
		{
			$this->already_voted = TRUE;
		}

		// If there are no cookies that say the user has already voted, and config is TRUE, check database for matching IP address
		if (ee()->config->item('vwm_polls_check_ip_address') === TRUE)
		{
			// If this member or IP address has voted in this poll
			ee()->db->where('(entry_id = ' . $this->entry_id . ' AND field_id = ' . $this->field_id . ')', NULL, FALSE);

			// If this is a guest member
			if ( empty($this->member_id) )
			{
				ee()->db->where('ip_address', $this->ip_address);
			}
			// If this is a registered member
			else
			{
				ee()->db->where('(member_id = ' . (int)$this->member_id . ' OR ip_address = ' . (int)$this->ip_address . ')', NULL, FALSE);
			}

			$query = ee()->db->get('vwm_polls_votes');

			if ($query->num_rows() > 0)
			{
				$this->already_voted = TRUE;
			}
		}

		// If we want to check "unique" JavaScript attribute and we have a javascript attribute hash defined
		if (ee()->config->item('vwm_polls_check_javascript_attributes') === TRUE && $this->get_javascript_attribute_hash() )
		{
			ee()->db->where("(hash = '" . $this->get_javascript_attribute_hash() . "' AND field_id = " . $this->field_id . ')', NULL, FALSE);
			$query = ee()->db->get('vwm_polls_votes');

			if ($query->num_rows() > 0)
			{
				$this->already_voted = TRUE;
			}
		}

		return $this->already_voted;
	}

	/**
	 * See if the current user can vote
	 *
	 * @access private
	 * @return bool
	 */
	private function can_vote()
	{
		// Get details for current user
		$this->user_details();

		// Use the entry data to see if this poll is open
		$query = ee()->db
			->where('entry_id', $this->entry_id)
			->limit(1)
			->get('channel_titles');

		$row = $query->row();

		// Is this entry open?
		if ($row->status != 'open')
		{
			$this->errors[] = ee()->lang->line('poll_not_open');
			return FALSE;
		}

		// If we have an expiration date
		if ( ! empty($row->expiration_date) )
		{
			// Has this entry expired?
			if (ee()->localize->now > $row->expiration_date)
			{
				$this->errors[] = sprintf(ee()->lang->line('poll_expired'), date('Y-m-d', $row->expiration_date));
				return FALSE;
			}
		}

		// If this poll is not open to any member groups
		if ( $this->poll_settings['member_groups_can_vote'] === 'NONE' )
		{
			$this->errors[] = ee()->lang->line('member_group_cannot_vote');
			return FALSE;
		}
		// Else, is this poll is only open to select member groups
		elseif ( is_array($this->poll_settings['member_groups_can_vote']) )
		{
			if ( ! in_array($this->group_id, $this->poll_settings['member_groups_can_vote']))
			{
				$this->errors[] = ee()->lang->line('member_group_cannot_vote');
				return FALSE;
			}
		}

		// If this poll does not allow multiple votes, make sure user has not already voted
		if ( ! $this->poll_settings['multiple_votes'] AND $this->already_voted() === TRUE)
		{
			$this->errors[] = ee()->lang->line('can_only_vote_once');
			return FALSE;
		}

		// So we are all good, this user can vote!
		return TRUE;
	}

	/**
	 * Show error messages
	 *
	 * @access private
	 * @return void
	 */
	private function show_errors()
	{
		// If this is an AJAX request
		if (AJAX_REQUEST)
		{
			ee()->output->send_ajax_response(array('errors' => $this->errors, 'xid' => $this->refresh_xid()), TRUE); // Send JSON with a 500 status code
		}
		// No AJAX
		else
		{
			ee()->output->show_user_error('submission', $this->errors);
		}
	}

	/**
	 * Refresh the XID
	 *
	 * After a user submits a poll that has errors the XID is destroyed. We must
	 * create a new one so the user can successfully submit the poll again.
	 *
	 * EE 2.5.4 changed the schema for the security_hashes table
	 *
	 * @return string
	 */
	private function refresh_xid()
	{
		// If secure forms are enabled
		if (ee()->config->item('secure_forms') == 'y')
		{
			$hash = ee()->functions->random('encrypt');

			$data = array(
				'date' => ee()->localize->now,
				'session_id' => ee()->session->userdata('session_id'),
				'hash' => $hash
			);

			ee()->db->insert('security_hashes', $data);
		}
		// If secure forms are not enabled
		else
		{
			$hash = NULL;
		}

		return $hash;
	}
}

// EOF
