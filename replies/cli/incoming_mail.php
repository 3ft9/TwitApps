<?php
	if (empty($info['headers']['x-twitteremailtype']) or
		empty($info['headers']['x-twittersendername']) or
		empty($info['headers']['x-twittersenderscreenname']))
	{
		mail('contact@twitapps.com', 'TwitApps incoming mail: Missing header(s)', $body."\n\n========================================\n\n".$data, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');
	}
	else
	{
		require dirname(__FILE__).'/../fx.php';

		switch ($info['headers']['x-twitteremailtype'])
		{
			case 'is_following':
				// Note that we handle the case where we're already following a user and don't try to re-create them
				$user = Twitter::Follow($info['headers']['x-twittersenderscreenname']);
				if (isset($user['error']) and stripos($user['error'], 'already on your list') === false)
				{
					echo 'Follow failed for "'.$info['headers']['x-twittersenderscreenname'].'": '.$user['error']."\n";
				}
				elseif (stripos(@$user['error'], 'already on your list') !== false or User::Create($user))
				{
					Twitter::Tweet('d '.$info['headers']['x-twittersenderscreenname'].' Welcome to Replies from TwitApps. Send your email address by direct message to @'.$_twitter['username'].' to activate this service.');
				}
				else
				{
					echo 'Failed to create user "'.$info['headers']['x-twittersenderscreenname'].'": '.mysql_error(GetDB())."\n";
				}
				break;
				
			case 'direct_message':
				$email = false;
				foreach (preg_split('/\s/', strtolower($body)) as $word)
				{
					switch ($word)
					{
						case 'start':
							//echo 'Start for "'.$info['headers']['x-twittersenderscreenname'].'"'."\n";
							$user = Twitter::GetUserDetails($info['headers']['x-twittersenderscreenname']);
							if ($user and User::SetStatus($info['headers']['x-twittersenderscreenname'], 'active', $user->status->id))
							{
								$dm = 'Sorted! I\'ll start sending you emails again shortly.';
							}
							else
							{
								$dm = 'Grrr, something went wrong restarting your emails. I\'ve notified the team and they\'ll look into it ASAP.';
								echo '  Failed to start emails for "'.$info['headers']['x-twittersenderscreenname'].'"'."\n\n";
							}
							Twitter::Tweet('d '.$info['headers']['x-twittersenderscreenname'].' '.$dm);
							break;
							
						case 'stop':
							//echo 'Stop for "'.$info['headers']['x-twittersenderscreenname'].'"'."\n";
							if (User::SetStatus($info['headers']['x-twittersenderscreenname'], 'inactive'))
							{
								$dm = 'Ok, I\'ll stop sending you emails for now. Send the word "start" to restart them again.';
							}
							else
							{
								$dm = 'Grrr, something went wrong stopping your emails. I\'ve notified the team and they\'ll look into it ASAP.';
								echo '  Failed to stop emails for "'.$info['headers']['x-twittersenderscreenname'].'"'."\n\n";
							}
							Twitter::Tweet('d '.$info['headers']['x-twittersenderscreenname'].' '.$dm);
							break;
							
						case 'set':
							$bits = preg_split('/\s/', trim(substr(strtolower($body), strpos(strtolower($body), 'set')+3)));
							switch ($bits[0])
							{
								case 'ignore_self':
									if ($bits[1] == 'on' or $bits[1] == 1)
									{
										if (User::Update($info['headers']['x-twittersenderscreenname'], array($bits[0] => 1)))
										{
											$dm = 'Success! I\'m now ignoring tweets from you that contain @'.$info['headers']['x-twittersenderscreenname'].'. To change this send "set ignore_self off".';
										}
										else
										{
											$dm = 'An unhandled error occurred when setting the ignore_self preference. Try again or contact @twitapps.';
										}
									}
									elseif ($bits[1] == 'off' or $bits[1] == 0)
									{
										if (User::Update($info['headers']['x-twittersenderscreenname'], array($bits[0] => 0)))
										{
											$dm = 'Success! I\'m now including tweets from you that contain @'.$info['headers']['x-twittersenderscreenname'].'. To change this send "set ignore_self on".';
										}
										else
										{
											$dm = 'An unhandled error occurred when setting the ignore_self preference. Try again or contact @twitapps.';
										}
									}
									else
									{
										$dm = 'Invalid value "'.$bits[1].'" for ignore_self option. Please use "on" or "off".';
									}
									break;

								case 'replies_only':
									if ($bits[1] == 'on' or $bits[1] == 1)
									{
										if (User::Update($info['headers']['x-twittersenderscreenname'], array($bits[0] => 1)))
										{
											$dm = 'Success! I\'m now only watching for tweets that start with @'.$info['headers']['x-twittersenderscreenname'].'. To change this send "set replies_only off".';
										}
										else
										{
											$dm = 'An unhandled error occurred when setting the replies_only preference. Try again or contact @twitapps.';
										}
									}
									elseif ($bits[1] == 'off' or $bits[1] == 0)
									{
										if (User::Update($info['headers']['x-twittersenderscreenname'], array($bits[0] => 0)))
										{
											$dm = 'Success! I\'m now including all tweets containing @'.$info['headers']['x-twittersenderscreenname'].'. To change this send "set replies_only on".';
										}
										else
										{
											$dm = 'An unhandled error occurred when setting the replies_only preference. Try again or contact @twitapps.';
										}
									}
									else
									{
										$dm = 'Invalid value "'.$bits[1].'" for replies_only option. Please use "on" or "off".';
									}
									break;
									
								case 'frequency':
									$updatedata = array('min_interval' => $bits[1], 'max_queued' => $bits[2]);
									if (!is_numeric($updatedata['min_interval']))
									{
										$dm = 'Invalid value "'.$updatedata['min_interval'].'". Please see http://twitapps.com/replies for valid values.';
									}
									else
									{
										$updatedata['min_interval'] = intval($updatedata['min_interval']) * 60;
										if (!is_numeric($updatedata['max_queued']))
										{
											$dm = 'Invalid value "'.$updatedata['max_queued'].'". Please see http://twitapps.com/replies for valid values.';
										}
										else
										{
											$updatedata['max_queued'] = intval($updatedata['max_queued']);
											if (User::Update($info['headers']['x-twittersenderscreenname'], $updatedata))
											{
												if ($updatedata['min_interval'] == 0 or $updatedata['max_queued'] <= 1)
												{
													$dm = 'Success! I\'ll now send you an email as soon as I see that you\'ve had a reply. Please see http://twitapps.com/replies for why this may not always be instant.';
												}
												else
												{
													$dm = 'Success! I\'ll now send you an email every '.($updatedata['min_interval'] / 60).' minute'.($updatedata['min_interval'] == 60 ? '' : 's').' or when you\'ve had '.$updatedata['max_queued'].' replies, whichever occurs sooner.';
												}
											}
											else
											{
												$dm = 'An unhandled error occurred when setting the frequency preference. Try again or contact @twitapps.';
											}
										}
									}
									break;
									
								default:
									$dm = 'Unknown option "'.$bits[0].'". Please see http://twitapps.com/replies for valid options.';
									break;
							}
							Twitter::Tweet('d '.$info['headers']['x-twittersenderscreenname'].' '.$dm);
							unset($dm);
							break;
							
						default:
							$pos = strpos($word, '@');
							if ($pos !== false and $pos !== 0)
							{
								$email = $word;
								break 2;
							}
					}
				}
				
				if ($email !== false)
				{
					//echo 'Activate for "'.$info['headers']['x-twittersenderscreenname'].'"'."\n";
					if (!User::Activate($info['headers']['x-twittersenderscreenname'], $email))
					{
						echo '  Failed to activate user "'.$info['headers']['x-twittersenderscreenname'].'": '.mysql_error(GetDB())."\n";
					}
				}
				break;

			default:
				mail('contact@twitapps.com', 'TwitApps incoming mail: Unhandled type "'.$info['headers']['x-twitteremailtype'].'"', $body."\n\n========================================\n\n".$data, 'From: TwitApps <contact@twitapps.com>', '-fcontact@twitapps.com');
				break;
		}
	}
