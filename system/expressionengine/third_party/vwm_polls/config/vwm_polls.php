<?php

/**
 * When determining if a user can vote in a poll we check the IP address by
 * efault to make sure that the user has not already voted. This can cause some
 * issues when multiple users share the same IP address.
 */
$config['vwm_polls_check_ip_address'] = TRUE;

// Below configs not yet working...
$config['vwm_polls_check_user_agent'] = FALSE;
$config['vwm_polls_check_http_accept_headers'] = FALSE;
$config['vwm_polls_check_window_navigator'] = FALSE; // window.navigator
$config['vwm_polls_check_screen_size'] = FALSE; // screen.height; screen.width;
$config['vwm_polls_check_window_navigator'] = FALSE; // var date = new Date(); var offset = date.getTimezoneOffset();

// EOF