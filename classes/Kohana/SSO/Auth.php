<?php defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_SSO_Auth implements Interface_SSO_Auth {

	/**
	 * SSO_Auth model instance
	 */
	protected $sso_auth_model;

	/**
	 * __construct
	 *
	 * @return void
	 * @author Ben
	 * @kohana Eugene Kudelia
	 */
	public function __construct()
	{
		// Create Ion Auth model instance
		$this->sso_auth_model = Model::factory('SSO_Auth');

		//auto-login the user if they are remembered
		//if ( ! $this->logged_in() AND Cookie::get('identity') AND Cookie::get('remember_code'))
		//{
		//	$this->ion_auth_model->login_remembered_user();
		//}
	}

	/**
	 * __call
	 */
	public function __call($method, $arguments)
	{
		if ( ! method_exists( $this->sso_auth_model, $method) )
		{
			throw new SSO_Exception('Undefined method SSO_Auth:::method() called',
				array(':method' => $method));
		}

		return call_user_func_array(array($this->sso_auth_model, $method), $arguments);
	}

	/**
	 * @param  mixed  $data  user data (Array) or user ID (int) or ORM object
	 * @return Model_Auth_Data
	 */
	public function get_user($data)
	{
		if ($data instanceof SSO_Auth)
		{
			// refresh user info
			$data = $data->user()->id;
		}

		if ( ! is_array($data) )
		{
			// find by unique key
			return $this->user($data)->row();
		}
		else
		{
			// get user by service identity
			$user = $this
				->where('service_id', '=', $data['service_id'])
				->where('service_type', '=', $data['service_type'])
				->user()
				->row();

			// if user not found, save user
			return $user ?: $this->_save_user($data);
		}
	}

	/**
	 * @param  $token
	 *
	 * @return  object token | bool
	 */
	public function get_token($token)
	{
		$token = $this->token($token);
		if ($this->is_valid($token))
		{
			return $token;
		}
		else
		{
			$this->delete_token($token);
			return FALSE;
		}
	}

	public function generate_token($user, $driver, $lifetime = NULL)
	{
		if ($token = $this->generate($lifetime))
		{
			$token->driver = $driver;
			$token->user_id = $user->id;
			$id = $this->token_save($token);
			if (is_numeric($id))
			{
				$token->id = $id;
				$token->user = $user;
			}

			return $id === FALSE ? FALSE : $token;
		}

		return FALSE;
	}

	public function delete_token($token)
	{
		if ( ! $token)
		{
			return FALSE;
		}

		is_object($token) OR $token = $this->token($token);

		if ($token AND isset($token->id))
		{
			if (isset($token->user))
			{
				unset($token->user);
			}
			return $this->token_delete($token->id);
		}

		return FALSE;
	}

}
