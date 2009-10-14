<?php
	require dirname(__FILE__).'/../../../fx.php';

	// Are we logged in?
	if (strlen(User::cGet('id')) == 0 or strlen(User::cGet('oat')) == 0 or strlen(User::cGet('oats')) == 0)
	{
		Redirect('/account/signin');
	}
	
	$data = array('user' => User::Get());
	
	$result = Follows::Install($data['user']['id'], !empty($_POST['email']) ? trim($_POST['email']) : '');

	if ($result === true)
	{
		Redirect('/account/follows/');
	}

	$data['message'] = 'Installation failed; '.$result;
	
	Layout('Follows', 'account');
	TPL('www/account/follows/install', $data);
