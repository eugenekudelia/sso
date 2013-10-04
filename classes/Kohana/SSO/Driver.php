<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Driver
 * 
 * @package    Kohana/SSO
 * @category   Base
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 * @fork       Eugene Kudelia <https://github.com/eugenekudelia/sso>
 */
abstract class Kohana_SSO_Driver {

	protected $_provider = FALSE;
	/**
	 * @var Auth
	 */
	protected $_auth;

	abstract public function login();
	abstract public function logout();
	abstract public function get_user();

	public function __construct(SSO $auth)
	{
		$this->_auth = $auth;
	}

	public function provider($provider)
	{
		if (func_num_args() == 0)
		{
			return $this->_provider;
		}
		$this->_provider = $provider;
		return $this;
	}

	public function init()
	{
		// to be implemented in drivers
	}

	public function complete_login()
	{
		// to be implemented in drivers
	}


}