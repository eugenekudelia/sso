<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Driver OAuth2 Github
 * 
 * @package    Kohana/SSO
 * @category   Drivers
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 */
class Kohana_SSO_Driver_OAuth2_Github extends SSO_Driver_OAuth2 {

	protected $_provider = 'Github';

	/**
	 * @param   string  $user object (response from provider)
	 * @return  Array
	 */
	protected function _get_user_data($user)
	{
		$user = json_decode($user, TRUE);
		$avatar = Arr::get($user, 'gravatar_id', Arr::get($user, 'avatar_url'));

		return array(
			'service_id'    => $user['id'],
			'service_name'  => $user['login'],
			'realname'      => Arr::get($user, 'name'),
			'service_type'  => 'OAuth2.Github',
			'email'         => Arr::get($user, 'email'),
			// Github uses Gravatar for profile images
			'avatar'        => $avatar,
		);
	}

	protected function _url_verify_credentials(OAuth2_Token_Access $token)
	{
		return 'https://api.github.com/user';
	}

	protected function _credential_params(OAuth2_Client $client, OAuth2_Token_Access $token)
	{
		return array(
			'access_token' => $token->token,
		);
	}



}