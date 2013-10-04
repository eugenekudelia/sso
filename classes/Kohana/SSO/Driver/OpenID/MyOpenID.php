<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO Driver OpenID MyOpenID
 * 
 * @package    Kohana/SSO
 * @category   Drivers
 * @author     Ivan Brotkin <https://github.com/biakaveron/sso>
 */
abstract class Kohana_SSO_Driver_OpenID_MyOpenID extends SSO_Driver_OpenID {

	public $name = 'OpenID.MyOpenID';

	protected function _get_user_data($user)
	{
		$result = parent::_get_user_data($user);
		// Myopenid's OpenID identity is an unique URL, so we need to change it to original ID
		$result['service_name'] = $this->_openid->public_id();
		return $result;
	}

}