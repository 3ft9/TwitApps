<?php
	class User
	{
		// COOKIE STUFF

		const DEFAULT_SALT = 'arse bandits';
		const COOKIE_NAME = 'u';

		private static $_cookiedata = false;

		public static function cReset()
		{
			// Expire the cookie by clearing it and setting the expiry to 2 days ago
			setcookie(self::COOKIE_NAME, '', time() - 172800, '/');
		}

		public static function cGet($var)
		{
			if (self::$_cookiedata === false) self::cLoad();
			if (isset(self::$_cookiedata[$var])) return self::$_cookiedata[$var];
			return null;
		}
		
		public static function cSet($var, $val)
		{
			self::$_cookiedata[$var] = $val;
			self::cSave();
		}
		
		public static function cLoad()
		{
			if (!empty($_COOKIE[self::COOKIE_NAME]))
			{
				$cookieval = self::Decrypt(base64_decode($_COOKIE[self::COOKIE_NAME]));
				if ($cookieval)
				{
					self::$_cookiedata = unserialize($cookieval);
				}
				else
				{
					self::$_cookiedata = array();
				}
			}
		}
		
		public static function cSave()
		{
			setcookie(self::COOKIE_NAME, base64_encode(self::Encrypt(serialize(self::$_cookiedata))), strtotime('+1 month'), '/');
		}

		public static function IsLoggedIn()
		{
			return (strlen(User::cGet('id')) > 0 and strlen(User::cGet('oat')) > 0 and strlen(User::cGet('oats')) > 0);
		}
		
		private static function Encrypt($value, $salt = false)
		{
			if ($salt === false) $salt = self::DEFAULT_SALT;

			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
			return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $salt, $value, MCRYPT_MODE_ECB, $iv);
		}
		
		private static function Decrypt($value, $salt = false)
		{
			if ($salt === false) $salt = self::DEFAULT_SALT;

			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB);
			$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
			return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $salt, $value, MCRYPT_MODE_ECB, $iv);
		}
		
		// USER OBJECT
		
		public static function Get($id = false)
		{
			if ($id === false) $id = self::cGet('id');
			$retval = false;
			$db = GetDB();
			$sql = 'select * from users where id = "'.mysql_real_escape_string($id, $db).'"';
			$query = @mysql_query($sql, $db);
			if ($query !== false)
			{
				if (mysql_num_rows($query) > 0)
				{
					$retval = mysql_fetch_assoc($query);
					$retval['status'] = unserialize($retval['status']);
				}
				mysql_free_result($query);
			}
			return $retval;
		}
		
		public static function GetByScreenName($screen_name)
		{
			$retval = false;
			$db = GetDB();
			$sql = 'select * from users where screen_name = "'.mysql_real_escape_string($screen_name, $db).'"';
			$query = @mysql_query($sql, $db);
			if ($query !== false)
			{
				if (mysql_num_rows($query) > 0)
				{
					$retval = mysql_fetch_assoc($query);
					$retval['status'] = unserialize($retval['status']);
				}
				mysql_free_result($query);
			}
			return $retval;
		}
		
		public static function Update($data, $registering = false)
		{
			$db = GetDB();

			$fields = array();
			foreach ($data as $var => $val)
			{
				switch ($var)
				{
					case 'id':
					case 'screen_name':
					case 'name':
					case 'description':
					case 'location':
					case 'url':
					case 'utc_offset':
					case 'time_zone':
					case 'statuses_count':
					case 'followers_count':
					case 'friends_count':
					case 'favourites_count':
					case 'created_at':
					case 'protected':
					case 'profile_sidebar_fill_color':
					case 'profile_sidebar_border_color':
					case 'profile_background_tile':
					case 'profile_background_color':
					case 'profile_text_color':
					case 'profile_image_url':
					case 'profile_background_image_url':
					case 'profile_link_color':
					case 'oauth_token':
					case 'oauth_token_secret':
						$fields[] = '`'.$var.'` = "'.mysql_real_escape_string($val, $db).'"';
						break;

					case 'status':
						$fields[] = '`'.$var.'` = "'.mysql_real_escape_string(serialize($val), $db).'"';
						break;
					
					default:
						// Do nothing - there are some fields that are ignored
						break;
				}
			}
			
			$fields[] = '`updated_at` = '.time();

			$sql = 'insert into `users` set '.implode(', ', $fields).' ';
			$sql.= ',`registered_at` = '.($registering ? time() : 0);
			$sql.= ' on duplicate key update '.implode(', ', $fields);
			if ($registering) $sql.= ',`registered_at` = if(registered_at = 0, '.time().', registered_at)';
			
			$retval = @mysql_query($sql, $db);
			if (!$retval) die('Something bad happened. Sorry. <!-- '.mysql_error($db).' -->');
			return $retval;
		}
		
		public static function GetServices($userid, $service = false)
		{
			$db = GetDB();

			$retval = array();
			
			$services = ($service === false ? __('service', 'list') : (is_array($service) ? $service : array($service)));
			
			$sql_userid = mysql_real_escape_string($userid, $db);

			foreach ($services as $serv)
			{
				$retval[$serv] = false;
				$sql = 'select * from `'.$serv.'_users` where id = "'.$sql_userid.'"';
				$query = @mysql_query($sql, $db);
				if ($query !== false and mysql_num_rows($query) > 0)
				{
					$retval[$serv] = mysql_fetch_assoc($query);
				}
				@mysql_free_result($query);
			}
			
			//echo '<pre>'; var_dump($retval); echo '</pre>';

			return ($service === false ? $retval : $retval[$service]);
		}
		
		public static function InstallService($service, $userid, $data)
		{
			$db = GetDB();
			
			$sql = 'insert into `'.$service.'_users` set ';
			foreach ($data as $var => $val)
			{
				$sql .= '`'.$var.'` = "'.mysql_real_escape_string($val, $db).'", ';
			}
			$sql.= '`id` = "'.mysql_real_escape_string($userid, $db).'", ';
			switch ($service)
			{
				case 'replies':
					if (!isset($data['last_run_at'])) $sql.= '`last_run_at` = 0, ';
					if (!isset($data['last_email_at'])) $sql.= '`last_email_at` = 0, ';
					if (!isset($data['last_id'])) $sql.= '`last_id` = 0, ';
					if (!isset($data['registered_at'])) $sql.= '`registered_at` = '.time().', ';
					$sql.= '`processor_pid` = 0';
					break;

				case 'follows':
					if (!isset($data['last_run_at'])) $sql.= '`last_run_at` = 0, ';
					if (!isset($data['last_email_at'])) $sql.= '`last_email_at` = 0, ';
					if (!isset($data['next_email_at'])) $sql.= '`next_email_at` = 0, ';
					if (!isset($data['registered_at'])) $sql.= '`registered_at` = '.time().', ';
					$sql.= '`processor_pid` = 0';
					break;

				case 'feeds':
					if (!isset($data['last_run_at'])) $sql.= '`last_run_at` = 0, ';
					if (!isset($data['registered_at'])) $sql.= '`registered_at` = '.time().', ';
					$sql.= '`processor_pid` = 0';
					break;
			}

			$retval = @mysql_query($sql, $db);
			if ($retval)
			{
				$retval = true;
			}
			else
			{
				$retval = mysql_error($db);
			}
			return $retval;
		}
		
		public static function UpdateService($service, $userid, $data)
		{
			$db = GetDB();
			
			$sql = 'update `'.$service.'_users` set ';
			foreach ($data as $var => $val)
			{
				$sql .= '`'.$var.'` = "'.mysql_real_escape_string($val, $db).'", ';
			}
			$sql = substr($sql, 0, -2).' where `id` = "'.mysql_real_escape_string($userid, $db).'"';
			
			$retval = @mysql_query($sql, $db);
			if ($retval)
			{
				$retval = true;
			}
			else
			{
				$retval = mysql_error($db);
			}
			return $retval;
		}
		
		public static function GetUserDetails(&$twitter, $userid)
		{
			$retval = false;
			$count = -1;
			while ($count != 0)
			{
				$user_json = $twitter->OAuthRequest('https://twitter.com/users/show.json', array('user_id' => $userid), 'GET');
				if ($twitter->lastStatusCode() != 200)
				{
					switch ($twitter->lastStatusCode())
					{
						case 0:		// Some sort of network error
						case 502:	// Bad gateway
							if ($count == -5)
							{
								// Tried 5 times to get this page and still getting an error, give up
								$count = 0;
							}
							else
							{
								$count = ($count < 0 ? $count-1 : -1);
							}
							break;

						default:
							Debug(1, 'Error getting user infor for '.$userid.': '.$twitter->lastStatusCode());
							AuditLog::Write('user', $userid, 'error', 'Call to users/show.json failed with error code "'.$twitter->lastStatusCode().'"');
							$count = 0;
							break;
					}
				}
				else
				{
					$retval = @json_decode($user_json, true);
					if (!is_array($retval))
					{
						Debug(5, 'Failed to get user info for '.$userid);
						Debug(9, 'User[JSON]: '.$user_json);
						Debug(9, 'User: '.print_r($retval, true));
						AuditLog::Write('user', $userid, 'error', 'Failed to get user info for '.$userid.': '.$user_json);
					}
					$count = 0;
				}
			}
			
			self::Update($retval);
			
			return $retval;
		}
	}
