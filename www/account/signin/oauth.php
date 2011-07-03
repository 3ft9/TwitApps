<?php
	require dirname(__FILE__).'/../../../fx.php';

	ob_start();
	
	if (!empty($_REQUEST['oauth_token']))
	{
		// Create TwitterOAuth object with app key/secret and token key/secret from default phase
		$to = new TwitterOAuth(__('oauth', 'consumer_key'), __('oauth', 'consumer_secret'), User::cGet('ort'), User::cGet('orts'));

		// Request access tokens from twitter
		$tok = $to->getAccessToken();

		if (!isset($tok['oauth_token']))
		{
			ob_end_clean();
			die('An error occurred while signing you in. Please <a href="/account/signin/oauth">click here to try again</a>.');
		}

		// Save the access tokens
		User::cSet('oat', $tok['oauth_token']);
		User::cSet('oats', $tok['oauth_token_secret']);
		
		// Create TwitterOAuth with app key/secret and user access key/secret
		$to = new TwitterOAuth(__('oauth', 'consumer_key'), __('oauth', 'consumer_secret'), User::cGet('oat'), User::cGet('oats'));

		// Run request on twitter API as user to get their user details
		$user_json = $to->OAuthRequest('https://twitter.com/account/verify_credentials.json', array(), 'GET');
		if ($to->lastStatusCode() != 200)
			die('An error occurred while getting your account details. Please wait a few minutes and try again.');

		// Decode the account details
		$user = json_decode($user_json, true);

		// Store the user ID in the cookie
		User::cSet('id', $user['id']);

		// Add the oauth token and secret to the user object
		$user['oauth_token'] = User::cGet('oat');
		$user['oauth_token_secret'] = User::cGet('oats');
		
		// Now save the user in the DB
		User::Update($user, true);

		// Tell me about it
		//@mail('stuart@twitapps.com', 'TwitApps signin: '.$user['screen_name'], print_r($user, true));

		// Get rid of any output
		ob_end_clean();

		// Take them to the account or migration page
		Redirect('/account/');
	}
	else
	{
		$to = new TwitterOAuth(__('oauth', 'consumer_key'), __('oauth', 'consumer_secret'));
		$tok = $to->getRequestToken();
		
		if (!$tok)
		{
			Redirect('/account/signin/');
		}
		
		User::cSet('ort', $tok['oauth_token']);
		User::cSet('orts', $tok['oauth_token_secret']);
		
		Redirect($to->getAuthorizeURL($tok['oauth_token']));
	}
