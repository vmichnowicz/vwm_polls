<?php

/**
 * When determining if a user can vote in a poll we check the IP address by default to make sure that the user has not
 * already voted. This can cause some issues when multiple users share the same IP address.
 */
$config['vwm_polls_check_ip_address'] = FALSE; //TRUE;
$config['vwm_polls_check_javascript_attributes'] = FALSE; // Check "unique" JavaScript attributes
$config['vwm_polls_template_prefix'] = ''; // Something like "vwm_polls_"

// EOF