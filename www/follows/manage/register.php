<?php
	require dirname(__FILE__).'/fx.php';

	// Obtain a request token from the server
	$token = OAuthRequester::requestRequestToken($server['consumer_key'], $user_id);

	// Now redirect to the autorization uri and get us authorized
	if (!empty($token['authorize_uri']))
	{
	    // Redirect to the server, add a callback to our server
	    if (strpos($token['authorize_uri'], '?'))
	    {
	        $uri = $token['authorize_uri'] . '&'; 
	    }
	    else
	    {
	        $uri = $token['authorize_uri'] . '?'; 
	    }
	    $uri .= 'oauth_token='.rawurlencode($token['token']);
	}
	else
	{
	    // No authorization uri, assume we are authorized, exchange request token for access token
	   $uri = '/follows/manage/oacb?oauth_token='.rawurlencode($token['token']);
	}

	header('Location: '.$uri);
