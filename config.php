<?php
	$_db = array(
		'shared' => array(
			'username' => 'twitapps_shared',
			'password' => 'password_one',
			'database' => 'twitapps_shared',
			'host' => 'localhost',
		),
		'replies' => array(
			'username' => 'ta_replies',
			'password' => 'password_two',
			'database' => 'twitapps_replies',
			'host' => 'localhost',
		),
		'follows' => array(
			'username' => 'ta_follows',
			'password' => 'password_three',
			'database' => 'twitapps_follows',
			'host' => 'localhost',
		),
	);

	$_twitter = array(
		'twitapps' => array(
			'username' => 'twitapps',
			'password' => 'password_one',
			'source' => 'twitapps',
		),
		'ta_replies' => array(
			'username' => 'ta_replies',
			'password' => 'password_two',
			'source' => 'twitapps',
		),
		'ta_follows' => array(
			'username' => 'ta_follows',
			'password' => 'password_three',
			'source' => 'twitapps',
		),
	);
	
	$_oauth = array(
		'consumer_key' => '', // Get this from Twitter
		'consumer_secret' => '', // Get this from Twitter too
		'url_request_token' => 'http://twitter.com/oauth/request_token',
		'url_access_token' => 'http://twitter.com/oauth/access_token',
		'url_authorize' => 'http://twitter.com/oauth/authorize',
	);
	
	function config($type, $account = 'twitapps', $key = false)
	{
		if ($key !== false and isset($GLOBALS['_'.$type][$account][$key]))
			return $GLOBALS['_'.$type][$account][$key];
		elseif ($key === false and isset($GLOBALS['_'.$type][$account]))
			return $GLOBALS['_'.$type][$account];
		else
			return null;
	}
