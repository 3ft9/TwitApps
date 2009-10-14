<?php
	define('ROOTDIR', dirname(__FILE__).'/');
	define('CODEDIR', ROOTDIR.'code/');
	define('TPLDIR', ROOTDIR.'tpl/');
	define('PID', getmypid());
	if (!defined('DEBUG')) define('DEBUG', 1);
	
	date_default_timezone_set('Europe/London');

	ShowErrors(true);
	
	require ROOTDIR.'config.php';
	
	function __autoload($class)
	{
		require CODEDIR.'classes/'.strtolower($class).'.class.php';
	}
	
	function GetDB()
	{
		static $_db = false;
		if ($_db === false)
		{
			$_db = mysql_connect(__('db', 'host'), __('db', 'username'), __('db', 'password')) or die('DB gone? <!-- '.mysql_error().' -->');
			mysql_select_db(__('db', 'database'), $_db) or die('DB dead? <!-- '.mysql_error().' -->');
			mysql_query('SET NAMES utf8', $_db);
		}
		return $_db;
	}
	
	/**
	 * Output the layout header and set up the footer
	 * @param string $title
	 * @param string $section
	 */
	function Layout($title, $section = '', $data = array(), $type = 'www')
	{
		// Expire the page immediately
		@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		// always modified
		@header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		@header("Cache-Control: post-check=0, pre-check=0", false);
		@header("Pragma: no-cache");                          // HTTP/1.0

		// Now output the page
		$GLOBALS['layoutdata'] = $data;
		$GLOBALS['layoutdata']['title'] = $title;
		$GLOBALS['layoutdata']['section'] = $section;
		$GLOBALS['layouttype'] = $type;
		register_shutdown_function('Footer');
		TPL($GLOBALS['layouttype'].'/layout/header', $GLOBALS['layoutdata']);
	}

	/**
	 * Output the layout footer
	 */
	function Footer()
	{
		TPL($GLOBALS['layouttype'].'/layout/footer', $GLOBALS['layoutdata']);
	}

	/**
	 * Turn error display on or off
	 *
	 * @param bool $show
	 */
	function ShowErrors($show)
	{
		if ($show)
		{
			error_reporting(E_ALL);
			ini_set('display_errors', 1);
		}
		else
		{
			error_reporting(0);
			ini_set('display_errors', 0);
		}
	}

	/**
	 * Turn off output buffering
	 */
	function NoBuffers()
	{
		while (@ob_end_clean());
	}

	/**
	 * "Execute" a template
	 *
	 * @param string $filename
	 * @param array $data
	 * @return string
	 */
	function TPL($____filename, $____data = array(), $____return = false)
	{
		$____filename = TPLDIR.$____filename.'.tpl.php';
		if ($____return) ob_start();
		extract($____data);
		include $____filename;
		if ($____return)
		{
			$retval = ob_get_contents();
			ob_end_clean();
			return $retval;
		}
	}

	/**
	 * Simple function to split the URL by /, trim it and return the array.
	 * Example: /blog/2007/01/14/example-post
	 *          ParseURL()  => array('blog', '2007', '01', '14', 'example-post')
	 *          ParseURL(1) => array('2007', '01', '14', 'example-post')
	 *          ParseURL(4) => array('example-post')
	 *
	 * @param string $url The URL to parse
	 * @param integer $skip The number of items to drop from the start
	 * @return array
	 */
	function ParseURL($skip = 0, $url = false)
	{
		if ($url === false) $url = array_shift(explode('?', $_SERVER['REQUEST_URI'], 2));
		$bits = explode('?', $url);
		$bits = explode('/', array_shift($bits));
		while (count($bits) > 0 and strlen($bits[0]) == 0)
			array_shift($bits);
		while (count($bits) > 0 and strlen($bits[count($bits)-1]) == 0)
			array_pop($bits);
		while ($skip-- > 0)
			array_shift($bits);
		return $bits;
	}

	function Authenticate($realm = '', $users = false)
	{
		if (empty($realm)) $realm = 'Restricted area';

		// user => password
		if ($users === false) $users = array('admin' => 'password');

		if (!isset($_SERVER['PHP_AUTH_USER']))
		{
			header('WWW-Authenticate: Basic realm="'.$realm.'"');
			header('HTTP/1.0 401 Unauthorized');
			die('<h1>Unauthorised</h1><p>You are not authorised to view this page</p>');
		}
		else
		{
			if (!isset($users[$_SERVER['PHP_AUTH_USER']]) or $users[$_SERVER['PHP_AUTH_USER']] != $_SERVER['PHP_AUTH_PW'])
				die('<h1>Unauthorised</h1><p>You are not authorised to view this page: Invalid credentials</p>');
		}
	}

	function V(&$array, $element, $default = '')
	{
		return (isset($array[$element]) ? $array[$element] : $default);
	}

	function Redirect($url, $exit = true, $permanent = false)
	{
		if (substr($url, 0, 7) != 'http://' and $url[0] == '/')
		{
			$url = 'http://'.$_SERVER['HTTP_HOST'].$url;
		}
		
		if (headers_sent())
		{
?>
<script type="text/javascript"><!--
	location.href = '<?php echo $url; ?>';
--></script>
<?php
		}
		else
		{
			// Output the redirect header
			header('Location: '.$url, true, ($permanent ? 301 : 302));
		}

		// If told to exit, output the moved message and do so
		if ($exit)
		{
			// Empty and clean up any pending output buffers
			while (@ob_end_clean());

			print '<h1>Document Moved</h1>';
			print '<p>The requested document has moved <a href="'.$url.'">here</a>.</p>';
			print '<script type="text/javascript"> location.href = "'.$url.'"; </script>';
			exit;
		}
	}

	// CACHE STUFF

	define('KEY_SEARCH_LIMITED', 'search_limited');
	define('KEY_API_LIMITED', 'api_limited');

	function cache($op, $key, $val = '', $expiry = 604800)
	{
		static $memcache = false;
		if ($memcache === false)
		{
			$memcache = new Memcache();
			$memcache->connect('localhost', 11211) or die('Fatal error - could not connect to Memcache');
		}
		
		$retval = true;
		
		// Prefix the key to avoid collisions with other apps
		$key = 'twitapps_'.$key;
		
		switch ($op)
		{
			case 'set':
				$memcache->set($key, $val, false, $expiry) or die('Fatal error - could not store '.htmlentities($key).' in Memcache');
				break;
				
			case 'get':
				$retval = $memcache->get($key);
				break;
				
			case 'inc':
				$retval = $memcache->increment($key);
				break;
				
			case 'add':
				$retval = $memcache->add($key, $val, false, $expiry);
				break;
		}
		
		return $retval;
	}

	function HTMLifyTweet($in, $twitapps_search_for_users = false, $newwin_links = false, $newwin_users = false)
	{
		$newwin_links = ($newwin_links ? ' target="_blank"' : '');
		$newwin_users = ($newwin_users ? ' target="_blank"' : '');
		$out = nl2br($in);
		$out = preg_replace('|(https?://[^\s\(\)\[\]\<\>]+)|i', '<a href="\\1"'.$newwin_links.'>\\1</a>', $out);
		$out = preg_replace('|@([a-z0-9_]+)|i', ($twitapps_search_for_users ? '@<a href="http://search.twitapps.com/~\\1"'.$newwin_users.'>\\1</a>' : '@<a href="http://twitter.com/\\1"'.$newwin_users.'>\\1</a>'), $out);
		return $out;
	}

	function InDays($stamp)
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
	
	// DEBUGGING STUFF
	
	function Debug($level, $txt)
	{
		static $log = false;
		if (DEBUG >= $level)
		{
			if ($log === false) $log = Log::_();
			$log->Write($txt);
		}
	}
	
	function Debug_StartBlock($level, $prefix, $message = false)
	{
		$log = Log::_();
		if (DEBUG >= $level and $message !== false)
			$log->Write($message);
		$log->Indent();
		$log->SetPrefix($prefix);
	}

	function Debug_EndBlock($level, $message = false)
	{
		$log = Log::_();
		$log->Unindent();
		$log->SetPrefix(false);
		if (DEBUG >= $level and $message !== false)
			$log->Write($message);
	}
