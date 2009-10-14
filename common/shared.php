<?php
	function & GetSharedDB($closeit = false)
	{
		static $_db = false;
		if ($closeit)
		{
			
		}
		elseif ($_db === false)
		{
			$_db = mysql_connect('localhost', 'twitapps_shared', 'sdvjbsdvkugskf7g7fgekufbskvkfbsk7') or die('DB gone? <!-- '.mysql_error().' -->');
			mysql_select_db('twitapps_shared', $_db) or die('DB dead? <!-- '.mysql_error().' -->');
			mysql_query('SET NAMES utf8', $_db);
			register_shutdown_function('CloseSharedDB');
		}
		return $_db;
	}
	
	function CloseSharedDB()
	{
		GetSharedDB(true);
	}
	
	function LogAPIRequests($num, $type = 'api')
	{
		if ($num > 0)
		{
			$db = GetSharedDB();
			$sql = 'insert into apistats set ';
			$sql.= 'y = '.date('Y').', ';
			$sql.= 'm = '.date('n').', ';
			$sql.= 'd = '.date('d').', ';
			$sql.= 'h = '.date('G').', ';
			$sql.= 't = "'.mysql_real_escape_string($type, $db).'", ';
			$sql.= 'n = '.intval($num).' ';
			$sql.= 'on duplicate key update n = n + '.intval($num);
			mysql_query($sql, $db);
		}
	}
	
	register_shutdown_function('RecordAPIHits');
	$apihits = array('get' => 0, 'post' => 0, 'search' => 0);
	
	function RecordAPIHits()
	{
		global $apihits;
		foreach ($apihits as $type => $num)
		{
			LogAPIRequests($num, $type);
		}
	}
	
	function APILog($service, $text)
	{
		$db = GetSharedDB();
		$sql = 'insert into apilog set ';
		$sql.= 'stamp = Now(), ';
		$sql.= 'service = "'.mysql_real_escape_string($service, $db).'", ';
		$sql.= 'command = "'.mysql_real_escape_string($text, $db).'"';
		mysql_query($sql, $db);
		
		if (strpos($text, 'FAILED ') === 0)
		{
			@mail('stuart@twitapps.com', 'TwitApps Twitter API Failure', $text."\n\n".print_r(debug_backtrace(), true));
		}
	}
	
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
