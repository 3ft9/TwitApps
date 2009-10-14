<?php
	/**
	 * The Twitter class wraps access to various Twitter API methods.
	 */
	require_once dirname(__FIlE__).'/../code/services_json.class.php';

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

			curl_headers_reset();
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/account/verify_credentials.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, 'curl_headers_read');
			curl_setopt($curl_handle, CURLOPT_USERPWD, $authdata);
			$result = curl_exec($curl_handle);
			if ($result !== false)
			{
				$info = curl_getinfo($curl_handle);
				if ($info['http_code'] == 200)
				{
					$result = true;
					@$GLOBALS['apihits']['get']++;
					APILog(APPNAME, 'verify_credentials for "'.$u.'"');
				}
				else
				{
					$result = false;
					APILog(APPNAME, 'FAILED 2 verify_credentials for "'.$u.'"');
				}
			}
			else
			{
				APILog(APPNAME, 'FAILED 1 verify_credentials for "'.$u.'"');
			}
			curl_close($curl_handle);
			
			return $result;
		}

		/**
		 * Get the given users details from Twitter.
		 * @param string $username
		 * @return array
		 */
		public static function GetUserDetails($username, $field = false)
		{
			$result = false;

			$url = 'http://twitter.com/users/show/'.rawurlencode($username).'.xml';
			if ($field != false) $url = 'http://twitter.com/users/show.xml?'.$field.'='.urlencode($username);
			
			curl_headers_reset();
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, 'curl_headers_read');
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);

			if ($result and strpos($result, '<html') === false)
			{
				$result = @simplexml_load_string($result);
				
				if ($result)
				{
					@$GLOBALS['apihits']['get']++;
					APILog(APPNAME, 'users/show for "'.$field.'" = "'.$username.'"');
				}
			}
			else
			{
				APILog(APPNAME, 'FAILED users/show for "'.$field.'" = "'.$username.'"');
				$result = false;
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

			curl_headers_reset();
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/statuses/update.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, 'curl_headers_read');
			curl_setopt($curl_handle, CURLOPT_POST, 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl_handle, CURLOPT_USERPWD, $authdata);
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);

			@$GLOBALS['apihits']['post']++;
			APILog(APPNAME, 'tweet as "'.$u.'" = "'.$message.'"');

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

			curl_headers_reset();
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/friendships/create/'.rawurlencode($target).'.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, 'curl_headers_read');
			curl_setopt($curl_handle, CURLOPT_POST, 1);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $postdata);
			curl_setopt($curl_handle, CURLOPT_USERPWD, $authdata);
			$result = curl_exec($curl_handle);
			curl_close($curl_handle);

			@$GLOBALS['apihits']['post']++;
			APILog(APPNAME, 'follow for "'.$u.'" => "'.$target.'"');

			return json_decode($result, true);
		}
		
		public static function & Search($for, $since_id = false, $lang = false)
		{
			$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
			
			$results = array();

			$qs = '?rpp=100&q='.urlencode($for);
			if ($since_id !== false) $qs .= '&since_id='.urlencode($since_id);
			if ($lang !== false) $qs .= '&lang='.urlencode($lang);
			while ($qs != '')
			{
				curl_headers_reset();
				$curl_handle = curl_init();
				curl_setopt($curl_handle, CURLOPT_URL, 'http://search.twitter.com/search.json'.$qs);
				curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
				curl_setopt($curl_handle, CURLOPT_HEADER, 0);
				curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, 'curl_headers_read');
				$result = curl_exec($curl_handle);
				$info = curl_getinfo($curl_handle);
				curl_close($curl_handle);
				
				if ($info['http_code'] == 404)
				{
					return self::Search($for, false, $lang);
				}
				elseif ($info['http_code'] == 502)
				{
					// Twitter are having issues - wait a minute
					cache('set', KEY_SEARCH_LIMITED, date('Y-m-d H:i:s'), 60);
					$retval = false;
					// Let's not hear about these - they're handled nicely now! APILog(APPNAME, 'FAILED '.$info['http_code'].' search for "'.$for.'"'.($since_id === false ? '' : ' since '.$since_id.' ').($lang === false ? '' : ' in language "'.$lang.'"')."\n\n".'http://search.twitter.com/search.json'.$qs."\n\n".print_r($headers, true));
					return $retval;
				}
				elseif ($info['http_code'] == 503)
				{
					// We're being rate limited - make a note in Memcache which will stop all search activity
					$headers = curl_headers_get();
					$retryafter = intval(@$headers['Retry-After']);
					if ($retryafter == 0) $retryafter = 60;
					cache('set', KEY_SEARCH_LIMITED, date('Y-m-d H:i:s'), $retryafter);
					$retval = false;
					// Let's not hear about these - they're handled nicely now! APILog(APPNAME, 'FAILED '.$info['http_code'].' search for "'.$for.'"'.($since_id === false ? '' : ' since '.$since_id.' ').($lang === false ? '' : ' in language "'.$lang.'"')."\n\n".'http://search.twitter.com/search.json'.$qs."\n\n".print_r($headers, true));
					return $retval;
				}
				elseif ($result === false or $info['http_code'] != 200)
				{
					$retval = false;
					APILog(APPNAME, 'FAILED '.$info['http_code'].' search for "'.$for.'"'.($since_id === false ? '' : ' since '.$since_id.' ').($lang === false ? '' : ' in language "'.$lang.'"')."\n\n".'http://search.twitter.com/search.json'.$qs."\n\n".print_r(curl_headers_get(), true));
					return $retval;
				}

				@$GLOBALS['apihits']['search']++;
				APILog(APPNAME, 'search for "'.$for.'"'.($since_id === false ? '' : ' since '.$since_id.' ').($lang === false ? '' : ' in language "'.$lang.'"'));

				//$result = json_decode($result, true);
				$result = $json->decode($result);

				$qs = (isset($result['next_page']) ? $result['next_page'] : '');
				
				if (isset($result['results']) and is_array($result['results']))
				{
					$since_id = $result['max_id'];
					foreach ($result['results'] as $r)
					{
						$results[] = $r;
					}
				}
			}

			$retval = array($since_id, &$results);
			return $retval;
		}
		
		public static function & GetFollowers($username, $page = 1, $u = false, $p = false)
		{
			if ($u === false)
			{
				$u = self::$_config['username'];
				$p = self::$_config['password'];
			}

			curl_headers_reset();
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, 'http://twitter.com/followers/ids/'.rawurlencode($username).'.json');
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Expect:'));
			curl_setopt($curl_handle, CURLOPT_HEADER, 0);
			curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, 'curl_headers_read');
			curl_setopt($curl_handle, CURLOPT_USERPWD, $u.':'.$p);
			$result = curl_exec($curl_handle);
			$info = curl_getinfo($curl_handle);
			curl_close($curl_handle);
			
			if ($result === false or $info['http_code'] != 200)
			{
				$retval = false;
				return $retval;
			}

			@$GLOBALS['apihits']['get']++;
			APILog(APPNAME, 'get followers for "'.$username.'"');

			$retval = json_decode($result, true);
			
			if (!empty($retval['error']))
			{
				echo 'Error getting followers for "'.$username.'": '.$retval['error']."\n";
				$retval = false;
			}
			
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
		
		public static function FormatStamp($stamp)
		{
			$retval = date('F jS, Y @ h:ia', $stamp);
			$diff = time() - $stamp;
			
			if ($diff <= 50)
				$retval = 'less than a minute ago';
			elseif ($diff < 70)
				$retval = 'about a minute ago';
			elseif ($diff < 180)
				$retval = 'a few minutes ago';
			elseif ($diff < 290)
				$retval = 'less than 5 minutes ago';
			elseif ($diff < 3300)
				$retval = 'about '.floor($diff / 60).' minutes ago';
			elseif ($diff < 3900)
				$retval = 'about an hour ago';
			elseif ($diff < 82800)
				$retval = 'about '.ceil($diff / 3600).' hours ago';
			else
			{
				$days = floor($diff / 86400);
				if ($days < 2)
					$retval = 'yesterday';
				else
					$retval = $days.' days ago';
			}
			
			return $retval;
		}
	}
	
	$GLOBALS['_CURL_HEADERS'] = array();

	function curl_headers_reset()
	{
		$GLOBALS['_CURL_HEADERS'] = array();
	}
	function curl_headers_read($ch, $header)
	{
		static $last = false;
		if ($header[0] == ' ' or $header[0] == "\t")
		{
			$GLOBALS['_CURL_HEADERS'][$last] .= ' '.trim($val);
		}
		else
		{
			if ($last === false)
			{
				$GLOBALS['_CURL_HEADERS']['Response'] = $header;
				$last = true;
			}
			else
			{
				$bits = explode(':', $header, 2);
				if (count($bits) == 2)
				{
					$GLOBALS['_CURL_HEADERS'][$bits[0]] = trim($bits[1]);
					$last = $bits[0];
				}
				else
				{
					$GLOBALS['_CURL_HEADERS'][trim($header)] = trim($header);
				}
			}
		}
		return strlen($header);
	}
	function curl_headers_get()
	{
		return $GLOBALS['_CURL_HEADERS'];
	}
