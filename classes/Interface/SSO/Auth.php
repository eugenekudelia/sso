<?php

interface Interface_SSO_Auth {

	function get_user($data);
	function get_token($token);
	function generate_token($user, $driver, $lifetime = NULL);
	function delete_token($token);
}