<?php defined('SYSPATH') or die('No direct script access.');

abstract class Kohana_SSO {

	/**
	 * @var  SSO
	 */
	protected static $_instance;

	/**
	 * @static
	 * @return  SSO
	 */
	public static function instance()
	{
		if ( empty(SSO::$_instance))
		{
			$config = Kohana::$config->load('sso');
			SSO::$_instance = new SSO($config);
		}

		return SSO::$_instance;
	}

	/**
	 * @var  Config
	 */
	protected $_config;
	/**
	 * @var  Session
	 */
	protected $_session;

	//protected $_user_key      = 'sso_user';
	//protected $_driver_key    = 'sso_driver';
	//protected $_autologin_key = 'sso_auto_login';
	//protected $_forced_key    = 'sso_forced';

	/**
	 * @var  SSO_Driver[]  SSO driver collection
	 */
	protected $_drivers = array();
	/**
	 * @var SSO_Auth
	 */
	protected $_sso_auth;

	protected function __construct($config = NULL)
	{
		$this->_config = $config;
		$session = Arr::get($config, 'session');
		$this->_session = Session::instance($session);
	}

	/**
	 * @param bool $refresh reload user data from DB
	 * @return  FALSE | Model_Auth_Data
	 */
	public function get_user($refresh = FALSE)
	{
		$driver = $this->_session->get($this->_config['driver_key']);
		if ( ! $driver AND $this->_session->get($this->_config['forced_key']) !== TRUE )
		{
			if ( ! $this->auto_login())
				return FALSE;
		}

		if ($user = $this->_session->get($this->_config['user_key']))
		{
			if ($refresh)
			{
				$user = $this->sso_auth()->get_user($user);
				$this->_session->set($this->_config['user_key'], $user);
			}
			return $user;
		}

		return $this->driver($driver)->get_user();
	}

	/**
	 * This method can use different param types and count depends on driver.
	 *
	 *      // try to log in via OAuth v2 as Github user (access token required)
	 *      SSO::instance()->login('oauth2.github', $token);
	 *
	 *
	 * @throws SSO_Exception
	 * @return  boolean
	 */
	public function login()
	{
		if (func_num_args() < 2)
		{
			throw new SSO_Exception('Minimum two params required to log in');
		}

		// automatically logout
		$this->logout();

		$params = func_get_args();
		$driver_name = array_shift($params);
		$driver = $this->driver($driver_name);
		if ($user = call_user_func_array(array($driver, 'login'), $params))
		{
			$this->_complete_login($user, $driver_name);
			// check for autologin option
			$remember = $this->_config['lifetime'] > 0;
			if ($remember)
			{
				$token = $this->sso_auth()->generate_token($user, $driver_name, $this->_config['lifetime']);
				Cookie::set($this->_config['autologin_key'], $token->token);
			}
			return TRUE;
		}

		return FALSE;
	}

	protected function _complete_login($user, $driver = NULL)
	{
		$this->_session->set($this->_config['driver_key'], $driver);
		$this->_session->set($this->_config['user_key'], $user);
	}

	/**
	 *
	 *
	 * @param  mixed   $user
	 * @param  boolean $mark_as_forced
	 * @return boolean
	 */
	public function force_login($user, $mark_as_forced = TRUE)
	{
		$user = $this->sso_auth()->get_user($user);
		if ( ! $user )
		{
			return FALSE;
		}

		$this->_complete_login($user, NULL);

		if ($mark_as_forced)
		{
			$this->_session->set($this->_config['forced_key'], TRUE);
		}

		return TRUE;
	}

	public function auto_login()
	{
		if ( ! $token = Cookie::get($this->_config['autologin_key']))
		{
			return FALSE;
		}

		if ($token = $this->sso_auth()->get_token($token))
		{
			// its a valid token
			$this->_complete_login($token->user, $token->driver);
			$token = $this->sso_auth()->generate($this->_config['lifetime']);
			Cookie::set($this->_config['autologin_key'], $token->token);
			return $token->user;
		}
		else
		{
			// delete cookie
			Cookie::delete($this->_config['autologin_key']);
		}

		return FALSE;
	}

	public function logout()
	{
		if ( ! $driver = $this->_session->get($this->_config['driver_key']))
		{
			return TRUE;
		}

		$this->driver($driver)->logout();
		if ($token = Cookie::get($this->_config['autologin_key']))
		{
			$this->sso_auth()->delete_token($token);
			Cookie::delete($this->_config['autologin_key']);
		}

		$this->_session
			->delete($this->_config['user_key'])
			->delete($this->_config['driver_key'])
			->delete($this->_config['forced_key']);
	}

	/**
	 * @param  string  $name  Driver type
	 *
	 * @throws SSO_Exception
	 * @return SSO_Driver
	 */
	public function driver($name = NULL)
	{
		if ($name === NULL AND ! $name = $this->_session->get($this->_config['driver_key']))
		{
			throw new SSO_Exception('SSO driver name required');
		}
		// OAuth.Google will be a OAuth_Google driver
		$name = str_replace('.', '_', $name);
		if ( ! isset($this->_drivers[$name]))
		{
			$class = 'SSO_Driver_'.$name;
			$driver = new $class($this);
			$driver->init();
			$this->_drivers[$name] = $driver;
		}

		return $this->_drivers[$name];
	}

	/**
	 * @return SSO_Auth
	 */
	public function sso_auth()
	{
		if ( ! $this->_sso_auth)
		{
			$this->_sso_auth = new SSO_Auth;
		}

		return $this->_sso_auth;
	}
}