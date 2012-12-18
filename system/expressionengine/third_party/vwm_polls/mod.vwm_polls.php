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
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		// Make damn sure module path is defined
		$this->EE->load->add_package_path(PATH_THIRD . 'vwm_polls/');

		// Load lang, helper, and model
		$this->EE->lang->loadfile('vwm_polls');
		$this->EE->load->helper('vwm_polls');
		$this->EE->load->model('vwm_polls_m');
	}

	/**
	 * Get user details and save the properties
	 *
	 * @access private
	 * @return void
	 */
	private function user_details()
	{
		$this->member_id = $this->EE->session->userdata('member_id') ? $this->EE->session->userdata('member_id') : NULL;
		$this->group_id = $this->EE->session->userdata('group_id') ? $this->EE->session->userdata('group_id') : NULL;
		$this->ip_address = $this->EE->session->userdata('ip_address');
		$this->timestamp = time();
	}

	/**
	 * Build the poll
	 *
	 * @access public
	 * @return string
	 */
	public function poll()
	{
		$redirect = $this->EE->TMPL->fetch_param('redirect');
		$this->entry_id = $this->EE->TMPL->fetch_param('entry_id');
		$this->field_id = $this->EE->TMPL->fetch_param('field_id');

		// The entry ID is required
		if ( ! $this->entry_id ) { return FALSE; }

		// Set entry ID & field ID
		$this->poll_settings = $this->EE->vwm_polls_m
				->entry_id($this->entry_id)
				->field_id($this->field_id);

		// Get poll settings
		$this->poll_settings = $this->EE->vwm_polls_m->poll_settings();

		// If there are no settings for this poll
		if ( ! $this->poll_settings) { return; }

		// Get poll options
		$this->EE->vwm_polls_m->poll_options($this->poll_settings['options_order']);
		$this->EE->vwm_polls_m->poll_options_template_prep();
		$this->poll_options = $this->EE->vwm_polls_m->poll_options;

		// Get all the info inside the template tags
		$tagdata = $this->EE->TMPL->tagdata;

		// Template variable data
		$variables[] = array(
			'input_type' => $this->poll_settings['multiple_options'] ? 'checkbox' : 'radio',
			'input_name' => 'vwm_polls_options[]',
			'max_options' => $this->poll_settings['multiple_options_max'],
			'can_vote' => $this->can_vote(),
			'already_voted' => $this->already_voted,
			'chart' => google_chart($this->poll_settings, $this->poll_options),
			'total_votes' => $this->EE->vwm_polls_m->total_votes,
			'options' => array_values($this->poll_options), // I guess our array indexes need to start at 0...
			'options_results' => calculate_results($this->poll_options, $this->EE->vwm_polls_m->total_votes)
		);

		// Get hidden fields, class, and ID for our form
		$form_data = array(
			'id' => 'vwm_polls_poll_' . $this->entry_id,
			'class' => 'vwm_polls_poll',
			'hidden_fields' => array(
				'ACT' => $this->EE->functions->fetch_action_id('Vwm_polls', 'vote'),
				'RET' => ( ! $this->EE->TMPL->fetch_param('return'))  ? '' : $this->EE->TMPL->fetch_param('return'),
				'URI' => ($this->EE->uri->uri_string == '') ? 'index' : $this->EE->uri->uri_string,
				'entry_id' => $this->entry_id,
				'field_id' => $this->field_id,
				'redirect' => $redirect
			)
		);

		// Make the magic happen
		return $this->EE->functions->form_declaration($form_data) . $this->EE->TMPL->parse_variables($this->EE->TMPL->tagdata, $variables) . '</form>';
	}

	/**
	 * Handle a vote
	 *
	 * @access public
	 * @return void
	 */
	public function vote()
	{
		//EE 2.1.1 and earler do not have the secure_forms_check method
		if ( method_exists($this->EE->security, 'secure_forms_check') )
		{
			// Check XID
			if ( ! $this->EE->security->secure_forms_check($this->EE->input->post('XID')))
			{
				$this->errors[] = $this->EE->lang->line('invalid_xid');
				die( $this->show_errors() );
			}
		}
		
		$this->EE->load->helper('html');

		$redirect = $this->EE->input->post('redirect');
		$this->entry_id = $this->EE->input->post('entry_id');
		$this->field_id = $this->EE->input->post('field_id');

		$this->poll_settings = $this->EE->vwm_polls_m
			->entry_id($this->entry_id)
			->field_id($this->field_id)
			->poll_settings();

		// Get poll options
		$this->EE->vwm_polls_m->poll_options($this->poll_settings['options_order']);
		$this->EE->vwm_polls_m->poll_options_template_prep();
		$this->poll_options = $this->EE->vwm_polls_m->poll_options;
		$valid_poll_option_ids = $this->EE->vwm_polls_m->valid_poll_option_ids;

		$selected_poll_options = $this->EE->input->post('vwm_polls_options') ? $this->EE->input->post('vwm_polls_options') : array();
		$other_options = $this->EE->input->post('vwm_polls_other_options');

		// Make sure this poll exists
		if ( ! $this->poll_settings)
		{
			$this->errors[] = $this->EE->lang->line('poll_not_exist');
			die( $this->show_errors() );
		}
		
		// Results only?  (Useful to fetch the most updated results for clicking "view results" via AJAX for sites that use caching)
		$results_only = false;
		if (AJAX_REQUEST && $this->EE->input->post('results_only')) {
			$results_only = true; // this will let us skip a bunch of error checks
		}

		if (!$results_only) {
			// Make sure the user submitted a poll option
			if ( ! $selected_poll_options)
			{
				$this->errors[] = $this->EE->lang->line('no_options_submitted');
				die( $this->show_errors() );
			}
			
			// If this poll only accecpts one poll option and the user submitted more than one
			if (($this->poll_settings['multiple_options'] == FALSE AND count($selected_poll_options) > 1))
			{
				$this->errors[] = sprintf($this->EE->lang->line('no_options_submitted'), count($selected_poll_options));
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
						$this->errors[] = sprintf($this->EE->lang->line('too_few_options_submitted'), $this->poll_settings['multiple_options_min'], count($selected_poll_options));
					}
				}
			
				// If multiple options limit is set (a limit of "0" means there is no limit to the amount of options a user can select)
				if ($this->poll_settings['multiple_options_max'] > 0)
				{
					// If the user selects more than the allowed number of optoins
					if (count($selected_poll_options) > $this->poll_settings['multiple_options_max'])
					{
						$this->errors[] = sprintf($this->EE->lang->line('too_many_options_submitted'), $this->poll_settings['multiple_options_max'], count($selected_poll_options));
					}
				}
			}
			
			// Make sure the user submitted a valid poll option
			foreach ($selected_poll_options as $option)
			{
				if ( ! in_array($option, $valid_poll_option_ids))
				{
					$this->errors[] = $this->EE->lang->line('invalid_poll_option');
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
				$this->EE->input->set_cookie($this->entry_id . '-' . $this->field_id, json_encode($selected_poll_options), 31536000); // Cookie expires in ~1 year
	
				// Cast a vote for each poll option
				foreach ($selected_poll_options as $option_id)
				{
					// Record this vote
					$this->EE->vwm_polls_m->cast_vote($option_id);
	
					// If this option is of type "other"
					if ( $this->poll_options[$option_id]['type'] == 'other')
					{
						// Record this "other" vote
						$this->EE->vwm_polls_m->record_other_vote($option_id, $other_options[$option]);
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
				// Get updated poll options
				$this->EE->vwm_polls_m->poll_options($this->poll_settings['options_order']);

				$updated_total_votes = $this->EE->vwm_polls_m->total_votes;
				$updated_options = calculate_results($this->EE->vwm_polls_m->poll_options, $updated_total_votes);
				$updated_chart = str_replace('&amp;', '&', google_chart($this->poll_settings, $updated_options)); // The ampersands in "&amp;" end up getting encoded again...

				$data = array(
					'options' => $updated_options,
					'total_votes' => $updated_total_votes,
					'chart' => $updated_chart
				);

				// Send updated poll options
				$this->EE->output->send_ajax_response($data);
			}
			else
			{
				$this->EE->functions->redirect($redirect);
			}
		}
		// No vote for you!
		else
		{
			die( $this->show_errors() );
		}
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
		$query = $this->EE->db
			->where('entry_id', $this->entry_id)
			->limit(1)
			->get('channel_titles');

		$row = $query->row();

		// Is this entry open?
		if ($row->status != 'open')
		{
			$this->errors[] = $this->EE->lang->line('poll_not_open');
			return FALSE;
		}

		// If we have an expiration date
		if ( ! empty($row->expiration_date) )
		{
			// Has this entry expired?
			if (time() > $row->expiration_date)
			{
				$this->errors[] = sprintf($this->EE->lang->line('poll_expired'), date('Y-m-d', $row->expiration_date));
				return FALSE;
			}
		}

		// If this poll is not open to any member groups
		if ( $this->poll_settings['member_groups_can_vote'] === 'NONE' )
		{
			$this->errors[] = $this->EE->lang->line('member_group_cannot_vote');
			return FALSE;
		}
		// Else, is this poll is only open to select member groups
		elseif ( is_array($this->poll_settings['member_groups_can_vote']) )
		{
			if ( ! in_array($this->group_id, $this->poll_settings['member_groups_can_vote']))
			{
				$this->errors[] = $this->EE->lang->line('member_group_cannot_vote');
				return FALSE;
			}
		}

		// Check cookies to see if user has already voted
		if ( isset($_COOKIE[$this->entry_id . '-' . $this->field_id]) )
		{
			$this->already_voted = TRUE;
		}

		// If there are no cookies that say the user has already voted - check the database
		else
		{
			// If this member or IP address has voted in this poll
			$this->EE->db->where('(entry_id = ' . $this->entry_id . ' AND field_id = ' . $this->field_id . ')', NULL, FALSE);

			// If this is a guest member
			if ( empty($this->member_id) )
			{
				$this->EE->db->where('ip_address', $this->ip_address);
			}
			// If this is a registered member
			else
			{
				$this->EE->db->where('(member_id = ' . (int)$this->member_id . ' OR ip_address = ' . (int)$this->ip_address . ')', NULL, FALSE);
			}

			$query = $this->EE->db->get('vwm_polls_votes');

			if ($query->num_rows() > 0)
			{
				$this->already_voted = TRUE;
			}
		}

		// If this poll does not allow multiple votes, make sure user has not already voted
		if ( ! $this->poll_settings['multiple_votes'] AND $this->already_voted)
		{
			$this->errors[] = $this->EE->lang->line('can_only_vote_once');
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
			$this->EE->output->send_ajax_response(array('errors' => $this->errors, 'xid' => $this->refresh_xid()), TRUE); // Send JSON with a 500 status code
		}
		// No AJAX
		else
		{
			$this->EE->output->show_user_error('submission', $this->errors);
		}
	}
	
	/**
	 * Refresh the XID
	 * 
	 * After a user submits a poll that has errors the XID is destroyed. We must
	 * create a new one so the user can successfully submit the poll again.
	 * 
	 * @return string
	 */
	private function refresh_xid()
	{
		// If secure forms are enabled
		if ($this->EE->config->item('secure_forms') == 'y')
		{
			$hash = $this->EE->functions->random('encrypt');
			$this->EE->db->query("
				INSERT INTO exp_security_hashes (date, ip_address, hash)
				VALUES 
				(UNIX_TIMESTAMP(), '" . $this->EE->input->ip_address() . "', '" . $hash."')
			");
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
