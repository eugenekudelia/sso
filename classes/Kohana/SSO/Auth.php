<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Auth
 * 
 * @package    Kohana/SSO
 * @category   Base
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 * @fork       Eugene Kudelia <https://github.com/eugenekudelia/sso>
 */
abstract class Kohana_SSO_Auth implements Interface_SSO_Auth {

	/**
	 * SSO_Auth model instance
	 */
	protected $sso_auth_model;

	/**
	 * __construct
	 *
	 * @return SSO_Auth_Model
	 * @author Eugene Kudelia
	 */
	public function __construct()
	{
		$this->sso_auth_model = Model::factory('SSO_Auth');
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
	 * @param  mixed  $data  user data (Array) or user ID (int) or SSO_Auth object
	 * @return user data object
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
				->limit(1)
				->users()
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
			$return = $this->token_delete($token->id);
			unset($token);
			return $return;
		}

		return FALSE;
	}

}
