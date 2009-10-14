<?php
	require dirname(__FILE__).'/../fx.php';
	
	register_shutdown_function('ReleaseUser');
	function ReleaseUser(){User::Release();}
	
	@$GLOBALS['apihits']['replies_emails'] = 0;
	
	$endtime = time() + (60 * 15);
	
	while (time() < $endtime)
	{
		$sleep = true;
		
		// Don't even try if we're rate limited
		$limited = (cache('get', KEY_SEARCH_LIMITED) !== false);

		$user = ($limited ? false : User::GetNext());

		if ($user !== false)
		{
			$sleep = false;
			
			$user['last_run_at'] = time();
			
			$searchterm = ($user['replies_only'] == 1 ? 'to:' : '@').$user['username'];
			if ($user['ignore_self'] == 1) $searchterm .= ' -from:'.$user['username'];

			$searchresults = Twitter::Search($searchterm, $user['last_id']);
			
			if ($searchresults === false)
			{
				// Search failed, do nothing
			}
			else
			{
				list($user['last_id'], $replies) = $searchresults;
				

				if (count($replies) > 0)
				{
					//echo "Got ".count($replies)." results for ".$user['username']."\n";

					foreach ($replies as $reply)
					{
						Queue::Add($user['username'], $reply['id'], strtotime($reply['created_at']), $reply);
					}
				}

				$num = Queue::Num($user['username']);
				if ($num > 0 and ($num >= $user['max_queued'] or ($user['last_email_at'] < (time() - $user['min_interval']))))
				{
					if (!Queue::SendEmail($user['username'], $user['email']))
					{
						// Message NOT sent
						echo 'Failed to send the email to "'.$user['username'].'"'."\n";
					}
					else
					{
						$user['last_email_at'] = time();
						@$GLOBALS['apihits']['replies_emails']++;
					}
				}
			}

			User::Update($user['username'], $user);
			User::Release();
			
			// Sleep for half a second
			usleep(500000);
		}

		if ($sleep)
		{
			sleep(10);
		}
	}
