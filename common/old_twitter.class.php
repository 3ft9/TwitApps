<?php
	/**
	 * The Twitter class wraps access to various Twitter API methods.
	 */
	class Twitter
	{
		private static $_config = array();
		
		public static function Config(&$config)
		{
			self::$_config = $config;
		}

		/**
		 * Verify that the username and password are correct.
		 * @param string $u
		 * @param string $p
		 * @return bool
		 */
		public static function VerifyCredentials($u = false, $p = false)
		{
			if ($u === false)
			{
				$u = self::$_config['username'];
				$p = self::$_config['password'];
			}
			
			$result = false;
			$authdata = $u.':'.$p;

			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/account/verify_credentials.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_USERPWD, $authdata);
			$result = curl_exec($curl_handle);
			if ($result !== false)
			{
				$info = curl_getinfo($curl_handle);
				if ($info['http_code'] == 200)
				{
					$result = true;
				}
				else
				{
					$result = false;
				}
			}
			curl_close($curl_handle);
			
			return $result;
		}

		/**
		 * Get the given users details from Twitter.
		 * @param string $username
		 * @return array
		 */
		public static function GetUserDetails($username)
		{
			$result = false;

			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/users/show/'.rawurlencode($username).'.xml');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);

			if ($result)
			{
				$result = simplexml_load_string($result);
			}
			
			return $result;
		}

		/**
		 * Send a tweet.
		 * @param string $username
		 * @param string $password
		 * @param string $message
		 * @return bool
		 */
		public static function Tweet($message, $u = false, $p = false)
		{
			if ($u === false)
			{
				$u = self::$_config['username'];
				$p = self::$_config['password'];
			}
			
			$result = false;
			$postdata = 'status='.urlencode($message);
			if (!empty(self::$_config['source'])) $postdata .= '&source='.rawurlencode(self::$_config['source']);
			$authdata = $u.':'.$p;

			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/statuses/update.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_POST, 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl_handle, CURLOPT_USERPWD, $authdata);
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);

			return $result;
		}
		
		public static function Follow($target, $u = false, $p = false)
		{
			if ($u === false)
			{
				$u = self::$_config['username'];
				$p = self::$_config['password'];
			}
			
			$result = false;
			$postdata = 'follow=true';
			$authdata = $u.':'.$p;

			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/friendships/create/'.rawurlencode($target).'.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_POST, 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl_handle, CURLOPT_USERPWD, $authdata);
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);

			return json_decode($result, true);
		}
		
		public static function & Search($for, $since_id)
		{
			$results = array();

			$qs = '?rpp=100&since_id='.urlencode($since_id).'&q='.urlencode($for);
			while ($qs != '')
			{
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, 'http://search.twitter.com/search.json'.$qs);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				$result = curl_exec($curl_handle);
				curl_close($curl_handle);
				
				$result = json_decode($result, true);

				$qs = (isset($result['next_page']) ? $result['next_page'] : '');
				
				$since_id = $result['max_id'];
				
				$results += $result['results'];
			}

			$retval = array($since_id, &$results);
			return $retval;
		}
		
		public static function HTMLifyTweet($in, $twitapps_search_for_users = false, $newwin_links = false, $newwin_users = false)
		{
			$newwin_links = ($newwin_links ? ' target="_blank"' : '');
			$newwin_users = ($newwin_users ? ' target="_blank"' : '');
			$out = nl2br($in);
			$out = preg_replace('|(https?://[^\s\(\)\[\]\<\>]+)|i', '<a href="\\1"'.$newwin_links.'>\\1</a>', $out);
			$out = preg_replace('|@([a-z0-9_]+)|i', ($twitapps_search_for_users ? '@<a href="http://search.twitapps.com/~\\1"'.$newwin_users.'>\\1</a>' : '@<a href="http://twitter.com/\\1"'.$newwin_users.'>\\1</a>'), $out);
			return $out;
		}
	}
