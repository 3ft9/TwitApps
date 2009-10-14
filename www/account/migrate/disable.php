<?php
	require dirname(__FILE__).'/../../../fx.php';
	header('Content-Type: text/plain');
	$user = array('screen_name' => $_GET['u']);
	$result = User::UpdateService('follows', $user, array('status' => 'inactive'));
	if ($result === true)
	{
		$result = User::UpdateService('replies', $user, array('status' => 'inactive'));
		if ($result === true)
		{
			echo '1';
			exit;
		}
	}

	echo '0';
