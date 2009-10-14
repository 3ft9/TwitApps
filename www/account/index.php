<?php
	require dirname(__FILE__).'/../../fx.php';

	// Are we logged in?
	if (strlen(User::cGet('id')) == 0 or strlen(User::cGet('oat')) == 0 or strlen(User::cGet('oats')) == 0)
	{
		Redirect('/account/signin/');
	}

	$data = array();
	
	$data['user'] = User::Get();

	$data['services'] = User::GetServices($data['user']['id']);

	Layout('Your Account', 'account');
	TPL('www/account/index', $data);
