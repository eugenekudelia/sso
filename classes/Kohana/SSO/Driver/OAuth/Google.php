<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Driver OAuth Google
 * 
 * @package    Kohana/SSO
 * @category   Drivers
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 */
abstract class Kohana_SSO_Driver_OAuth_Google extends SSO_Driver_OAuth {

	protected $_provider = 'Google';

	protected function _url_verify_credentials(OAuth_Token_Access $token)
	{
		return 'http://www-opensocial.googleusercontent.com/api/people/@me/@self';
	}

	/**
	 * @param   string  $user object (response from OAuth provider)
	 * @return  Array
	 */
	protected function _get_user_data($user)
	{
		$user = json_decode($user);
		$user = $user->entry;
		return array(
			'service_id'    => $user->id,
			'service_name'  => $user->displayName,
			'realname'      => $user->displayName,
			'service_type'  => 'OAuth.Google',
			'email'         => isset($user->email) ? $user->email : NULL, // may be empty
			'avatar'        => $user->thumbnailUrl ? $user->thumbnailUrl : '',
		);

	}

}