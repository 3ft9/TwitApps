<?php
	ini_set('precision', 20);

	define('ROOTDIR', dirname(__FILE__).'/');
	define('CODEDIR', ROOTDIR.'code/');
	define('TPLDIR', ROOTDIR.'tpl/');
	
	require ROOTDIR.'../common/shared.php';
	require ROOTDIR.'../common/twitter.class.php';
	require ROOTDIR.'../common/phpmailer/class.phpmailer.php';

	require CODEDIR.'queue.class.php';
	require CODEDIR.'user.class.php';

	date_default_timezone_set('Europe/London');

	ShowErrors(true);
	
	require ROOTDIR.'config.php';
	if (file_exists(ROOTDIR.'config_dev.php')) require ROOTDIR.'config_dev.php';
	
	Twitter::Config($_twitter);

	function & GetDB()
	{
		static $_db = false;
		if ($_db === false)
		{
			$_db = mysql_connect('localhost', 'ta_replies', 'sdfoihsdukbsdkvhsldivhgsdlig') or die('DB gone? <!-- '.mysql_error().' -->');
			mysql_select_db('twitapps_replies', $_db) or die('DB dead? <!-- '.mysql_error().' -->');
			mysql_query('SET NAMES utf8', $_db);
		}
		return $_db;
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
		error_reporting(E_ALL);
		ini_set('display_errors', ($show ? 1 : 0));
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
	function ParseURL($url = false, $skip = 0)
	{
		if ($url === false) $url = $_SERVER['REQUEST_URI'];
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
