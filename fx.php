<?php
	define('ROOTDIR', dirname(__FILE__).'/');
	define('CODEDIR', ROOTDIR.'code/');
	define('TPLDIR', ROOTDIR.'tpl/');
	
	require ROOTDIR.'common/shared.php';
	require ROOTDIR.'common/twitter.class.php';
	require ROOTDIR.'common/phpmailer/class.phpmailer.php';
	require ROOTDIR.'common/twitteroauth/twitterOAuth.php';

	require CODEDIR.'user.class.php';

	date_default_timezone_set('Europe/London');

	ShowErrors(true);
	
	require ROOTDIR.'config.php';
	if (file_exists(ROOTDIR.'config_dev.php')) require ROOTDIR.'config_dev.php';
	
	Twitter::Config($_twitter);

	function & GetDB($db = 'shared')
	{
		static $_db = array();
		if (!isset($_db[$db]))
		{
			$_db[$db] = mysql_connect(config('db', $db, 'host'), config('db', $db, 'username'), config('db', $db, 'password')) or die('DB gone? <!-- '.mysql_error().' -->');
			mysql_select_db(config('db', $db, 'database'), $_db[$db]) or die('DB dead? <!-- '.mysql_error().' -->');
			mysql_query('SET NAMES utf8', $_db[$db]);
		}
		return $_db[$db];
	}

	/**
	 * Output the layout header and set up the footer
	 * @param string $title
	 * @param string $section
	 */
	function Layout($title, $section = '', $data = array())
	{
		// Expire the page immediately
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		// always modified
		header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");                          // HTTP/1.0

		// Now output the page
		$GLOBALS['layoutdata'] = $data;
		$GLOBALS['layoutdata']['title'] = $title;
		$GLOBALS['layoutdata']['section'] = $section;
		register_shutdown_function('Footer');
		TPL('header', $GLOBALS['layoutdata']);
	}

	/**
	 * Output the layout footer
	 */
	function Footer()
	{
		TPL('footer', $GLOBALS['layoutdata']);
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
		elseif (DEV)
		{
			$url = str_replace('.freeads.net', '.'.$GLOBALS['_SITE'].'.net', $url);
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
