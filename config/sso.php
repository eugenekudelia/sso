<?php defined('SYSPATH') or die('No direct script access.');

return array(
	'lifetime'    => 1209600, // 2 weeks
	'active_user' => TRUE,    // user doesnt require activation

	'user_key'      => 'sso_user',
	'driver_key'    => 'sso_driver',
	'autologin_key' => 'sso_autologin',
	'forced_key'    => 'sso_forced'
);
