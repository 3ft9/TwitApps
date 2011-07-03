<?php
	$_domains = array(
		'www' => 'twitapps.com',
		'static' => 'static.twitapps.com',
	);

	$_service = array(
		'list' => array(
			'replies',
			'follows',
		),
	);

	$_db = array(
		'username' => 'twitapps_user',
		'password' => 'password_one',
		'database' => 'twitapps',
		'host' => 'localhost',
	);

	$_twitter = array(
		'twitapps' => array(
			'username' => 'twitapps',
			'password' => 'password_one',
			'source' => 'twitapps',
		),
		'replies' => array(
			'username' => 'ta_replies',
			'password' => 'password_two',
			'source' => 'twitapps',
		),
		'follows' => array(
			'username' => 'ta_follows',
			'password' => 'password_three',
			'source' => 'twitapps',
		),
	);

	$_oauth = array(
		'consumer_key' => '', // Get this from Twitter
		'consumer_secret' => '', // Get this from Twitter
		'url_request_token' => 'http://twitter.com/oauth/request_token',
		'url_access_token' => 'http://twitter.com/oauth/access_token',
		'url_authorize' => 'http://twitter.com/oauth/authorize',
	);

	$_smtp = array(
		'email' => 'email@example.com',
		'pass' => 'my_smtp_password',
		'host' => 'ssl://smtp.gmail.com',
		'port' => 465,
		'auth' => true,
	);

	function __($type, $k1 = 'twitapps', $k2 = false)
	{
		if ($k2 !== false and isset($GLOBALS['_'.$type][$k1][$k2]))
			return $GLOBALS['_'.$type][$k1][$k2];
		elseif ($k2 === false and isset($GLOBALS['_'.$type][$k1]))
			return $GLOBALS['_'.$type][$k1];
		else
			return null;
	}
