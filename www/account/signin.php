<?php
	require dirname(__FILE__).'/../../fx.php';
	
	$url = ParseURL(2);
	
	// Has oauth registration been requested?
	if (!empty($url[0]) and $url[0] == 'oauth')
	{
		if (!empty($_REQUEST['oauth_token']))
		{
			// Create TwitterOAuth object with app key/secret and token key/secret from default phase
			$to = new TwitterOAuth(config('oauth', 'consumer_key'), config('oauth', 'consumer_secret'), User::cGet('ort'), User::cGet('orts'));
			// Request access tokens from twitter
			$tok = $to->getAccessToken();
			// Save the access tokens
			User::cSet('oat', $tok['oauth_token']);
			User::cSet('oats', $tok['oauth_token_secret']);
			
			// Create TwitterOAuth with app key/secret and user access key/secret
			$to = new TwitterOAuth(config('oauth', 'consumer_key'), config('oauth', 'consumer_secret'), User::cGet('oat'), User::cGet('oats'));
			// Run request on twitter API as user to get their user details
			$user = json_decode($to->OAuthRequest('https://twitter.com/account/verify_credentials.json', array(), 'GET'), true);
			// Store the user ID in the cookie
			User::cSet('id', $user['id']);
			// Now save the user in the DB
			User::Update($user, true);
			// Tell me about it
			//@mail('stuart@twitapps.com', 'TwitApps signin: '.$user['screen_name'], print_r($user, true));
			// Take them to the account page
			Redirect('/account/');
		}
		else
		{
			$to = new TwitterOAuth(config('oauth', 'consumer_key'), config('oauth', 'consumer_secret'));
			$tok = $to->getRequestToken();
			
			User::cSet('ort', $tok['oauth_token']);
			User::cSet('orts', $tok['oauth_token_secret']);
			
			Redirect($to->getAuthorizeURL($tok['oauth_token']));
		}
	}

	User::cReset();

	Layout('Sign In', 'account');
	TPL('account/signin');
