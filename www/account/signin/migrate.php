<?php
	require dirname(__FILE__).'/../../../fx.php';
	
	$urlextra = '';

	$user = User::Get();
	
	$str = @file_get_contents('http://migrate.twitapps.com/account/migrate/get?u='.urlencode($user['screen_name']));
	
	if (!$str)
	{
		die('Failed to get your account details from the v1 database. Please contact <a href="http://twitter.com/twitapps">@twitapps</a>.');
	}
	else
	{
		$data = json_decode($str, true);
		if (!$data) die('Failed to get your account details from the v1 system. Please contact <a href="http://twitter.com/twitapps">@twitapps</a>.');
		
		foreach ($data as $service => $info)
		{
			switch ($service)
			{
				case 'replies':
					if (!empty($info['status']))
					{
						$replies = array(
							'status' => $info['status'],
							'email' => $info['email'],
							'min_interval' => $info['min_interval'],
							'max_queued' => $info['max_queued'],
							'replies_only' => $info['replies_only'],
							'ignore_self' => $info['ignore_self'],
							'last_run_at' => $info['last_run_at'],
							'last_email_at' => $info['last_email_at'],
							'next_email_at' => $info['last_email_at'] + ($info['min_interval'] == 0 ? 60 : $info['min_interval']),
							'last_id' => $info['last_id'],
							'registered_at' => $info['registered_at'],
							'updated_at' => time(),
						);
						User::InstallService('replies', $user['id'], $replies);
					}
					break;

				case 'follows':
					if (!empty($info['status']))
					{
						$follows = array(
							'status' => $info['status'],
							'email' => $info['email'],
							'frequency' => $info['frequency'],
							'hour' => $info['hour'],
							'when' => $info['when'],
							'post_url' => $info['post_url'],
							'post_format' => $info['post_format'],
							'last_run_at' => 0,
							'last_email_at' => $info['last_email_at'],
							'next_email_at' => $info['next_email_at'],
							'registered_at' => $info['registered_at'],
							'updated_at' => time(),
						);
						User::InstallService('follows', $user['id'], $follows);
					}
					break;
			}
		}
		
		$urlextra = '?migrated=yes';
		
		// Disable the account in v1
		@file_get_contents('http://migrate.twitapps.com/account/migrate/disable?u='.urlencode($user['screen_name']));
	}
	
	Redirect('/account/'.$urlextra);
