<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Driver OAuth Yahoo
 * 
 * @package    Kohana/SSO
 * @category   Drivers
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 */
abstract class Kohana_SSO_Driver_OAuth_Yahoo extends SSO_Driver_OAuth {

	protected $_provider = 'Yahoo';

	protected function _url_verify_credentials(OAuth_Token_Access $token)
	{
		return 'http://social.yahooapis.com/v1/user/' . $token->xoauth_yahoo_guid . '/profile?format=json';
	}

	/**
	 * @param   string  $user object (response from LinkedIn OAuth)
	 * @return  Array
	 */
	protected function _get_user_data($user)
	{
		$user = json_decode($user);
		$user = $user->profile;

		$realname = trim(
			(isset($user->givenName) ? $user->givenName : '')
			. ' ' .
			(isset($user->familyName) ? $user->familyName : '')
		);

		return array(
			'service_id'    => $user->guid,
			'service_name'  => $user->nickname,
			'realname'      => $realname ? $realname : $user->nickname,
			'service_type'  => 'OAuth.Yahoo',
			'email'         => NULL,
			'avatar'        => $user->image->imageUrl,
		);

	}

}
