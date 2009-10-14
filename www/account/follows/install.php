<?php
	require dirname(__FILE__).'/../../../fx.php';

	// Are we logged in?
	if (strlen(User::cGet('id')) == 0 or strlen(User::cGet('oat')) == 0 or strlen(User::cGet('oats')) == 0)
	{
		Redirect('/account/signin');
	}
	
	$data = array('user' => User::Get());
	
	$follows = array(
		'status' => 'active',
		'email' => !empty($_POST['email']) ? trim($_POST['email']) : '',
		'frequency' => 'daily',
		'hour' => date('H'),
		'when' => '',
		'post_url' => '',
		'post_format' => '',
	);
	
	$result = User::InstallService('follows', $data['user'], $follows);

	if ($result === true)
	{
		Redirect('/account/follows/');
	}

	$data['message'] = 'Installation failed; '.$result;
	
	Layout('Follows', 'account');
	TPL('account/follows/install', $data);
