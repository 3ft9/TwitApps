<?php
	require dirname(__FILE__).'/../../../follows/fx.php';
	require dirname(__FILE__).'/../../../common/oauth/library/OAuthStore.php';
	require dirname(__FILE__).'/../../../common/oauth/library/OAuthRequester.php';

	$server = array(
		'consumer_key' => $_oauth['consumer_key'],
		'consumer_secret' => $_oauth['consumer_secret'],
		'server_uri' => 'http://twitter.com',
		'signature_methods' => array('HMAC-SHA1'),
		'request_token_uri' => $_oauth['url_request_token'],
		'authorize_uri' => $_oauth['url_authorize'],
		'access_token_uri' => $_oauth['url_access_token'],
	);
	
	if (!empty($_COOKIE['username']))
	{
		$user_id = $_COOKIE['username'];
	}
	elseif (!empty($_POST['username']))
	{
		$user_id = $_POST['username'];
		setcookie('username', $user_id, strtotime('2038-01-01 00:00:00'), '/follows', 'twitapps.com');
	}
	else
	{
		$user_id = false;
	}
	
	try
	{
		$store = OAuthStore::instance('MySQL', array('server' => $_db['host'], 'username' => $_db['username'], 'password' => $_db['password'], 'database' => $_db['database']));
		if ($user_id !== false) $store->updateServer($server, $user_id);
	}
	catch (OAuthException $e) { }
