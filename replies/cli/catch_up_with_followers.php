<?php
	require dirname(__FILE__).'/../fx.php';
	
	while (@ob_end_clean());
	
	$skipping = true;

	$page = 1;
	while ($page !== false)
	{
		$followers = Twitter::GetFollowers('ta_replies', $page);
		foreach ($followers as $f)
		{
			$exists = User::Exists($f['screen_name']);
			if ($exists or $skipping) continue;
			
			$skipping = false;

			$user = Twitter::Follow($headers['x-twittersenderscreenname']);
			
			if (User::Create($f))
			{
				echo $f['screen_name']." created\n";
				Twitter::Tweet('d '.$f['screen_name'].' Welcome to Replies from TwitApps. Send your email address by direct message to @ta_replies to activate this service.');
			}
			else
			{
				echo 'Failed to create user for '.$f['screen_name'].': '.mysql_error()."\n";
			}
		}
		
		if (count($followers) == 100)
		{
			$page++;
		}
		else
		{
			$page = false;
		}
	}
