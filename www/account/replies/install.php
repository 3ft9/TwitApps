<?php
	require dirname(__FILE__).'/../../../fx.php';

	// Are we logged in?
	if (strlen(User::cGet('id')) == 0 or strlen(User::cGet('oat')) == 0 or strlen(User::cGet('oats')) == 0)
	{
		Redirect('/account/signin');
	}
	
	$data = array('user' => User::Get());
	
	$replies = array(
		'status' => 'active',
		'email' => !empty($_POST['email']) ? trim($_POST['email']) : '',
		'min_interval' => 60,
		'max_queued' => 25,
		'replies_only' => 0,
		'ignore_self' => 0,
	);
	
	$result = User::InstallService('replies', $data['user'], $replies);

	if ($result === true)
	{
		Redirect('/account/replies/');
	}

	$data['message'] = 'Installation failed; '.$result;
	
	Layout('Replies', 'account');
	TPL('account/replies/install', $data);
