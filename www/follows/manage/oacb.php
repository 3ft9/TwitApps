<?php
	require dirname(__FILE__).'/fx.php';

	try
	{
		OAuthRequester::requestAccessToken($server['consumer_key'], $_GET['oauth_token'], $user_id);
		
		// We need to verify that they're who they said they were

		// Obtain a request object for the request we want to make
		$req = new OAuthRequester($server['server_uri'].'/account/verify_credentials.json', 'GET', array());
	
		// Sign the request, perform a curl request and return the results, throws OAuthException exception on an error
		try
		{
			$result = $req->doRequest($user_id);
		}
		catch (OAuthException $e)
		{
			echo 'Something went wrong: '.$e->getMessage();
			//header('Location: /follows/manage/register');
			exit;
		}
		
		$info = json_decode($result['body']);
		if ($info->screen_name == $user_id)
		{
			header('Location: /follows/manage/');
			exit;
		}
		
		$store->deleteServer($server['consumer_key'], $user_id);
		setcookie('username', '', strtotime('2000-01-01 00:00:00'), '/follows', 'twitapps.com');

		echo 'Username mismatch!';
	}
	catch (OAuthException $e)
	{
		echo 'Something went wrong: '.$e->getMessage();
	}
