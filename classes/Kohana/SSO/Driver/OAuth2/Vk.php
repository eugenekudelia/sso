<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Driver OAuth2 Vk (VKontakte)
 * 
 * @package    Kohana/SSO
 * @category   Drivers
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 */
abstract class Kohana_SSO_Driver_OAuth2_Vk extends SSO_Driver_OAuth2 {

	protected $_provider = 'Vk';

	/**
	 * @param   string  $user object (response from provider)
	 * @return  Array
	 */
	protected function _get_user_data($user)
	{
		$user = json_decode($user);
		$user = current($user->response);

		$login = trim($user->first_name.' '.$user->last_name);
		$displayname = isset($user->nickname) && ! empty($user->nickname) ? $user->nickname : $login;
		return array(
			'service_id'    => $user->uid,
			'service_name'  => $displayname,
			'realname'      => $login,
			'service_type'  => 'OAuth2.Vk',
			'email'         => NULL,
			'photo'         => $user->photo,
		);
	}

	protected function _url_verify_credentials(OAuth2_Token_Access $token)
	{
		return 'https://api.vk.com/method/users.get';
	}

	protected function _credential_params(OAuth2_Client $client, OAuth2_Token_Access $token)
	{
		return array(
			'uids'          => $token->user_id,
			'access_token' => $token->token,
			'fields'       => 'uid,first_name,last_name,nickname,sex,bdate,city,country,photo,photo_medium,photo_big,photo_rec',
		);
	}

}
