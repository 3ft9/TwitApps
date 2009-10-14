<?php
	class User
	{
		// COOKIE STUFF

		const DEFAULT_SALT = 'banana republic';
		const COOKIE_NAME = 'u';

		private static $_cookiedata = false;

		public static function cReset()
		{
			// Expire the cookie
			setcookie(self::COOKIE_NAME, '', time() - 86400, '/');
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

		public static function LoggedIn()
		{
			return (strlen(self::cGet('id')) > 0 and strlen(self::cGet('token')) > 0);
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
			$db = GetDB('shared');
			$query = mysql_query('select * from users where id = "'.mysql_real_escape_string($id, $db).'"', $db);
			if (mysql_num_rows($query) > 0)
			{
				$retval = mysql_fetch_assoc($query);
				$retval['status'] = unserialize($retval['status']);
			}
			@mysql_free_result($query);
			return $retval;
		}
		
		public static function Update($data, $registering = false)
		{
			$db = GetDB('shared');

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
			
			$retval = mysql_query($sql, $db);
			if (!$retval) die(mysql_error($db));
			return $retval;
		}
		
		public static function GetServices($user, $service = false)
		{
			$retval = array('replies' => false, 'follows' => false);
			
			// Is this user registered for replies?
			if ($service === false or $service == 'replies')
			{
				$repliesdb = GetDB('replies');
				$sql = 'select * from users where username = "'.mysql_real_escape_string($user['screen_name'], $repliesdb).'"';
				$query = mysql_query($sql, $repliesdb);
				if (mysql_num_rows($query) > 0)
				{
					$retval['replies'] = mysql_fetch_assoc($query);
				}
				@mysql_free_result($query);
			}

			// Is this user registered for follows?
			if ($service === false or $service == 'follows')
			{
				$followsdb = GetDB('follows');
				$sql = 'select * from users where username = "'.mysql_real_escape_string($user['screen_name'], $followsdb).'"';
				$query = mysql_query($sql, $followsdb);
				if (mysql_num_rows($query) > 0)
				{
					$retval['follows'] = mysql_fetch_assoc($query);
				}
				@mysql_free_result($query);
			}
			
			if (false and $user['screen_name'] == 'stut' and ($service === false or $service == 'feeds'))
			{
				$feedsdb = GetDB('feeds');
				$sql = 'select * from users where username = "'.mysql_real_escape_string($user['screen_name'], $feedsdb).'"';
				$query = mysql_query($sql, $feedsdb);
				if (mysql_num_rows($query) > 0)
				{
					$retval['feeds'] = mysql_fetch_assoc($query);
				}
				@mysql_free_result($query);
			}

			return ($service === false ? $retval : $retval[$service]);
		}
		
		public static function InstallService($service, $user, $data)
		{
			$db = GetDB($service);
			
			$sql = 'insert into users set ';
			foreach ($data as $var => $val)
			{
				$sql .= '`'.$var.'` = "'.mysql_real_escape_string($val, $db).'", ';
			}
			$sql.= '`username` = "'.mysql_real_escape_string($user['screen_name'], $db).'", ';
			switch ($service)
			{
				case 'replies':
					$sql.= '`last_run_at` = 0, ';
					$sql.= '`last_email_at` = 0, ';
					$sql.= '`last_id` = 0, ';
					$sql.= '`registered_at` = '.time().', ';
					$sql.= '`processor_pid` = 0';
					break;

				case 'follows':
					$sql.= '`last_run_at` = 0, ';
					$sql.= '`last_email_at` = 0, ';
					$sql.= '`next_email_at` = 0, ';
					$sql.= '`registered_at` = '.time().', ';
					$sql.= '`processor_pid` = 0';
					break;

				case 'feeds':
					$sql.= '`last_run_at` = 0, ';
					$sql.= '`registered_at` = '.time().', ';
					$sql.= '`processor_pid` = 0';
					break;
			}

			$retval = mysql_query($sql, $db);
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
		
		public static function UpdateService($service, $user, $data)
		{
			$db = GetDB($service);
			
			$sql = 'update users set ';
			foreach ($data as $var => $val)
			{
				$sql .= '`'.$var.'` = "'.mysql_real_escape_string($val, $db).'", ';
			}
			$sql = substr($sql, 0, -2).' where ';
			switch ($service)
			{
				case 'replies':
				case 'follows':
					$sql .= '`username` = "'.mysql_real_escape_string($user['screen_name'], $db).'"';
					break;
			}
			
			$retval = mysql_query($sql, $db);
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
	}
