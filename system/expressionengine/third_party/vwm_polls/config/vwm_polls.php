<?php

/**
 * When determining if a user can vote in a poll we check the IP address by
 * efault to make sure that the user has not already voted. This can cause some
 * issues when multiple users share the same IP address.
 */
$config['vwm_polls_check_ip_address'] = FALSE; //TRUE;
$config['vwm_polls_check_javascript'] = TRUE; // Check "unique" JavaScript attributes

// EOF