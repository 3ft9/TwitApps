<?php
	ini_set('memory_limit', -1);
	require dirname(__FILE__).'/../fx.php';

	register_shutdown_function('ReleaseUser');
	function ReleaseUser(){User::Release();}
	
	$endtime = time() + (60 * 15);
	
	while (time() < $endtime)
	{
		$sleep = true;

		$user = User::GetNext();
		if ($user !== false)
		{
			$update_started_at = time();
			$num_followers = 0;

			$followers = Twitter::GetFollowers($user['username']);
			if ($followers === false)
			{
				User::Update($user['username'], array('last_run_at' => time()));
				User::Release();
				continue;
			}

			foreach ($followers as $f)
			{
				//TwitterUsers::Add($f);
				Followers::Add($user['username'], $f);
			}
			
			$num_followers += count($followers);
			
			Followers::Remove($user['username'], $update_started_at);
			
			$updatedata = array(
				'last_run_at' => time(),
				'follower_count' => $num_followers,
			);
			
			// If we haven't sent an email yet make sure we set the last time to the future
			// so we don't notify them of all their existing followers
			if ($user['last_email_at'] == 0) $updatedata['last_email_at'] = time()+3600;

			User::Update($user['username'], $updatedata);

			User::Release();
		}
		
		if ($sleep)
		{
			sleep(60);
		}
	}
