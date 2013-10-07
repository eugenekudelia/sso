<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SSO :: Model_SSO_Auth extends Model_Common
 * 
 * Query Builder port of SSO ORM
 * by Ivan Brotkin <https://github.com/biakaveron/sso>
 *
 * @package    Kohana/SSO
 * @category   Models
 * @author     Eugene Kudelia <https://github.com/eugenekudelia/sso>
 * @copyright  (c) 2013 Eugene Kudelia
 * @license    MIT License
 */
class Kohana_Model_SSO_Auth extends Model_Common {

	/**
	 * Database table names
	 *
	 * @var array
	 */
	protected $_tables	= array(
		'users'				=> 'sso_users',
		'tokens'			=> 'sso_tokens'
	);

	/**
	 * Token object
	 */
	protected $_token = NULL;


	public function users()
	{
		// Database Query Builder object: $this->_query
		$this->_query($this->_tables['users']);

		// Database Query Builder limitations and ordering
		$this->_query_format();

		return $this;
	}

	public function user($id = NULL)
	{
		//if no id was passed use the current users id
		if ($id === NULL)
		{
			$session_user = Session::instance()->get(Kohana::$config->load('sso.user_key'));
			$id = $session_user ? $session_user->id : 0;
		}

		$this->where($this->_tables['users'].'.id', '=', $id)
			->limit(1)
			->users();

		return $this;
	}

	public function _save_user(array $data)
	{
		$data = $this->_filter_data($this->_tables['users'], $data);

		$user = new stdClass;
		foreach ($data as $key => $val)
		{
			$user->{$key} = $val;
		}

		$columns = array_keys($data);
		$values = array_values($data);
		$columns[] = 'is_active';
		$values[] = $is_active = (bool) Kohana::$config->load('sso.active_user');

		list($id, $rows) = DB::insert($this->_tables['users'])
								->columns($columns)
								->values($values)
								->execute();

		if ($rows == 1)
		{
			$user->id = $id;
			$user->is_active = $is_active;
			return $user;
		}
		return FALSE;
	}

	/**
	 * Get avatar URL
	 *
	 * @param  int $size  used for gravatar images only
	 *
	 * @return mixed|null|string
	 */
	public function get_avatar($id = NULL, $size = NULL)
	{
		$user = $this->select(arrray('email', 'avatar'))->user($id)->row();
		$avatar = $user->avatar;
		if (empty($avatar) AND ! empty($user->email) )
		{
			// use email as Gravatar ID
			$avatar = md5($user->email);
		}

		if (empty($avatar))
		{
			return NULL;
		}

		if (strpos($avatar, '://') == FALSE)
		{
			// its a Gravatar ID
			$avatar = 'http://gravatar.com/avatar/' . $avatar;
			$params = array();
			if (empty($avatar))
			{
				// use default Gravatar
				$params['f'] = 'y';
			}

			if ($size)
			{
				$params['s'] = intval($size);
			}

			if ( ! empty($params) )
			{
				$avatar .= http_build_query($params);
			}
		}

		return $avatar;
	}

	// Token

	public function token($token)
	{
		if ($this->_token)
		{
			return $this->_token;
		}

		$token = DB::select()
					->from($this->_tables['tokens'])
					->where('token', '=', $token)
		            ->limit(1)
					->as_object()
					->execute();

		if ($token->count() !== 1)
		{
			return FALSE;
		}
		$this->_token = $token->current();

		$user = DB::select()
					->from($this->_tables['users'])
					->where('id', '=', $this->_token->user_id)
		            ->limit(1)
					->as_object()
					->execute();

		if ($user->count() !== 1)
		{
			return FALSE;
		}
		$this->_token->user = $user->current();

		return $this->_token;
	}

	public function is_valid($token)
	{
		return $token AND $token->expires > time() AND $token->user_agent == sha1(Request::$user_agent);
	}

	protected function _generate_token_value()
	{
		do
		{
			$token = sha1(uniqid(Text::random('alnum', 32), TRUE));
		}
		while(count(
			DB::select()
				->from($this->_tables['tokens'])
				->where('token', '=', $token)
				->execute()
			) > 0
		);

		return $token;
	}

	public function generate($lifetime)
	{
		is_object($this->_token) OR $this->_token = new stdClass;
		$this->_token->expires = time() + $lifetime;
		$this->_token->token = $this->_generate_token_value();

		if (isset($this->_token->id))
		{
			// save new token value & timestamp
			$data['expires'] = $this->_token->expires;
			$data['token'] = $this->_token->token;

			$result = DB::update($this->_tables['tokens'])
						->set($data)
						->where('id', '=', $this->_token->id)
						->execute();

			return $result === 1 ? $this->_token : FALSE;
		}
		else
		{
			// this is a new token, so we dont need to save it (yet)
			$this->_token->user_agent = sha1(Request::$user_agent);
		}

		return $this->_token;
	}

	public function token_save($token)
	{
		if (isset($token->id))
		{
			$_token = (array) $token;
			unset($_token['id']);
			unset($_token['user']);
			$result = DB::update($this->_tables['tokens'])
						->set($_token)
						->where('id', '=', $token->id)
						->execute();

			return $result === 1;
		}
		else
		{
			$columns = array_keys((array) $token);
			$values = array_values((array) $token);
			list($id, $rows) = DB::insert($this->_tables['tokens'])
								->columns($columns)
								->values($values)
								->execute();

			return $rows == 1 ? $id : FALSE;
		}
	}

	public function token_delete($id)
	{
		$result = DB::delete($this->_tables['tokens'])
					->where('id', '=', $id)
					->execute();

		$this->_token = NULL;
		return $result > 0;
	}

}
