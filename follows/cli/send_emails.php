<?php
	require dirname(__FILE__).'/../fx.php';
	
	register_shutdown_function('ReleaseUser');
	function ReleaseUser(){User::Release();}
	
	@$GLOBALS['apihits']['follows_emails'] = 0;
	
	$endtime = time() + (60 * 15);
	
	while (time() < $endtime)
	{
		$sleep = true;

		$user = User::GetNextToEmail();
		if ($user !== false)
		{
			if (strlen(trim($user['email'])) == 0)
			{
				mail('stuart@twitapps.com', '[TwitApps Follows] No email address for user', print_r($user, true));
				User::Update($user['username'], array('last_email_at' => time()));
				continue;
			}

			$counter = 0;
			$delta = 0;

			$updatedata = array('last_email_at' => time());
			
			switch ($user['frequency'])
			{
				case 'monthly':
					$updatedata['next_email_at'] = strtotime(date('Y-m-'.$user['when'].' '.$user['hour'].':i:s', strtotime('next month')));
					break;

				case 'weekly':
					$updatedata['next_email_at'] = strtotime(date('Y-m-d '.$user['hour'].':i:s', strtotime('next '.$user['when'])));
					break;
					
				case 'daily':
				default:
					$updatedata['next_email_at'] = strtotime(date('Y-m-d '.$user['hour'].':i:s', time()+86400));
					break;
			}
			
			$query = Followers::GetDiffs($user['username'], $user['last_email_at']);
			
			$changes = array('new' => array(), 'old' => array());
			
			if (empty($user['post_url']))
			{
				$body_text = TPL('emails/body_text_header', array('username' => $user['username'], 'last_email_at' => $user['last_email_at']), true);
				$body_html = TPL('emails/body_html_header', array('username' => $user['username'], 'last_email_at' => $user['last_email_at']), true);
			}
			
			$currentlynew = 0;
			
			$new_text = '';
			$new_html = '';
			
			$ex_text = '';
			$ex_html = '';

			while ($row = mysql_fetch_assoc($query))
			{
				// Update the user
				$twitteruser = Twitter::GetUserDetails($row['follower_id'], 'user_id');
				if (!$twitteruser or !$twitteruser->screen_name) continue; // User not found, skip it
				$userdata = array(
					'username' => (string)$twitteruser->screen_name,
					'description' => (string)$twitteruser->description,
					'url' => (string)$twitteruser->url,
					'name' => (string)$twitteruser->name,
					'protected' => ((int)$twitteruser->protected ? 1 : 0),
					'followers_count' => (string)$twitteruser->followers_count,
					'following_count' => (string)$twitteruser->friends_count,
					'profile_image_url' => (string)$twitteruser->profile_image_url,
					'location' => (string)$twitteruser->location,
				);
				TwitterUsers::Set($twitteruser->id, $twitteruser->screen_name, $userdata);
				$row['username'] = $twitteruser->screen_name;
				foreach ($userdata as $key => $value)
				{
					$row[$key] = $value;
				}
				
				$counter++;
				
				$row['last_email_at'] = $user['last_email_at'];

				$new = ($row['unfollowed_at'] == 0 or $row['followed_at'] > $user['last_email_at']);
				if ($new)
				{
					if (empty($user['post_url']))
					{
						$new_text.= TPL('emails/body_text_item', $row, true);
						$new_html.= TPL('emails/body_html_item', $row, true);
					}
					else
					{
						$changes['new'][] = $userdata;
					}

					$delta++;
				}
				else
				{
					if (empty($user['post_url']))
					{
						$ex_text.= TPL('emails/body_text_item', $row, true);
						$ex_html.= TPL('emails/body_html_item', $row, true);
					}
					else
					{
						$changes['old'][] = $userdata;
					}

					$delta--;
				}
			}
			
			mysql_free_result($query);
			
			if ($counter > 0)
			{
				if (!empty($user['post_url']))
				{
					$changes['delta'] = $delta;

					$curl_handle = curl_init();
					curl_setopt($curl_handle, CURLOPT_URL, $user['post_url']);
					curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 60);
					curl_setopt($curl_handle, CURLOPT_POST, 1);
					curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=UTF-8'));
					curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($changes));
					curl_exec($curl_handle);
					curl_close($curl_handle);
					
					// Posted so update the user data
					User::Update($user['username'], $updatedata);
					@$GLOBALS['apihits']['follows_posts']++;
				}
				else
				{
					if (strlen($new_text) > 0)
					{
						$body_text.= TPL('emails/body_text_type_open', array('title' => 'New followers'), true);
						$body_html.= TPL('emails/body_html_type_open', array('title' => 'New followers'), true);
						
						$body_text.= $new_text;
						$body_html.= $new_html;
	
						$body_text.= TPL('emails/body_text_type_close', array('title' => 'New followers'), true);
						$body_html.= TPL('emails/body_html_type_close', array('title' => 'New followers'), true);
					}
	
					if (strlen($ex_text) > 0)
					{
						$body_text.= TPL('emails/body_text_type_open', array('title' => 'Ex followers'), true);
						$body_html.= TPL('emails/body_html_type_open', array('title' => 'Ex followers'), true);
						
						$body_text.= $ex_text;
						$body_html.= $ex_html;
	
						$body_text.= TPL('emails/body_text_type_close', array('title' => 'Ex followers'), true);
						$body_html.= TPL('emails/body_html_type_close', array('title' => 'Ex followers'), true);
					}
	
					$body_text.= TPL('emails/body_text_footer', array('username' => $user['username']), true);
					$body_html.= TPL('emails/body_html_footer', array('username' => $user['username']), true);
		
					$mail = new PHPMailer();
					
					$mail->FromName = 'TwitApps';
					$mail->From = 'noreply@twitapps.com';
					$mail->Sender = 'noreply@twitapps.com';
					$mail->AddReplyTo('noreply@twitapps.com', 'TwitApps');
					
					$mail->Subject = 'Follower changes on Twitter for '.$user['username'].' ('.($delta > 0 ? '+' : '').$delta.')';
					
					$mail->Body = $body_html;
					$mail->AltBody = $body_text;
					$mail->IsHTML(true);
					
					$mail->WordWrap = 79;
					
					$mail->AddAddress($user['email']);
					//$mail->AddBCC('twitapps@stut.net');
	
					//file_put_contents('/tmp/twitapps_'.getmypid().'.txt', 'Sending email to "'.$user['username'].'"'."\nBody...\n\n".$body_text."\n\n-------------------------------------------------------------------------------\n\n");
					
					if ($mail->Send())
					{
						// Sent the email so update the user data
						User::Update($user['username'], $updatedata);
						@$GLOBALS['apihits']['follows_emails']++;
					}
				}
			}
			else
			{
				// Didn't send the email, but update the user anyway
				User::Update($user['username'], $updatedata);
			}

			User::Release();
		}

		if ($sleep)
		{
			sleep(60);
		}
	}
