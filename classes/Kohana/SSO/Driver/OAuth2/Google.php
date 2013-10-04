<?php
/**
 * SSO Driver OAuth2 Google
 * 
 * @package    Kohana/SSO
 * @category   Drivers
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 */
abstract class Kohana_SSO_Driver_Oauth2_Google extends SSO_Driver_OAuth2 {

	protected $_provider = 'Google';

	/**
	 * @param   string  $user object (response from provider)
	 * @return  Array
	 */
	protected function _get_user_data($user)
	{
		$user = json_decode($user);
		$name = empty($user->name) ? trim($user->given_name . ' ' . $user->family_name) : $user->name;
		return array(
			'service_id'    => $user->id,
			'service_name'  => $name,
			'realname'      => $name,
			'service_type'  => 'OAuth2.Google',
			'email'         => isset($user->email) ? $user->email : NULL, // may be empty
			'avatar'        => $user->picture ? $user->picture : '',
		);
	}

	protected function _url_verify_credentials(OAuth2_Token_Access $token)
	{
		return 'https://www.googleapis.com/oauth2/v1/userinfo';
	}
}