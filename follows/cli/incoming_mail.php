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
				elseif (stripos(@$user['error'], 'already on your list') !== false or User::Create((isset($user['screen_name']) ? $user : array('screen_name' => $info['headers']['x-twittersenderscreenname']))))
				{
					Twitter::Tweet('d '.$info['headers']['x-twittersenderscreenname'].' Welcome to Follows from TwitApps. Send your email address by direct message to @'.$_twitter['username'].' to activate this service.');
				}
				else
				{
					echo 'Failed to create user "'.$info['headers']['x-twittersenderscreenname'].'": '.mysql_error(GetDB())."\n";
				}
				break;
				
			case 'direct_message':
				// Direct message should contain a command or an email address
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
							$dm = false;
							$bits = preg_split('/\s/', trim(substr(strtolower($body), strpos(strtolower($body), 'set')+3)));
							switch ($bits[0])
							{
								case 'posturl':
									$posturl = '';
									if (!empty($bits[1]))
									{
										$posturl = trim($bits[1]);
									}
									
									$lposturl = strtolower($posturl);
									if (strpos($lposturl, 'http') !== 0 or
										strpos($lposturl, '://') === false or
										strpos($lposturl, 'localhost') !== false or
										strpos($lposturl, '127.0.0.1') !== false)
									{
										$dm = 'Invalid URL. Please check it and try again. For support please see http://twitapps.com/follows.';
									}

									if ($dm === false)
									{
										if (User::Update($info['headers']['x-twittersenderscreenname'], array('post_url' => $posturl)))
										{
											if (strlen($posturl) == 0)
											{
												$dm = 'Your post URL has been removed. You will now receive your change summary by email.';
											}
											else
											{
												$dm = 'Your follower changes will now be posted to the URL you have supplied. You will no longer get the emails.';
											}
										}
										else
										{
											$dm = 'Failed to store your posturl. Please try again or request support on http://twitapps.com/.';
										}
									}
									break;

								case 'frequency':
									$updatedata = array('frequency' => @$bits[1], 'hour' => @$bits[2], 'when' => @$bits[3]);
									if (!in_array($updatedata['frequency'], array('daily', 'weekly', 'monthly')))
									{
										$dm = 'Invalid value "'.$updatedata['frequency'].'". Please see http://twitapps.com/follows for valid values.';
									}
									else
									{
										$updatedata['hour'] = intval($updatedata['hour']);
										if (!is_numeric($updatedata['hour']) or $updatedata['hour'] < 0 or $updatedata['hour'] > 23)
										{
											$dm = 'Invalid value "'.$updatedata['hour'].'". Please see http://twitapps.com/follows for valid values.';
										}
										else
										{
											switch ($updatedata['frequency'])
											{
												case 'weekly':
													if (!in_array($updatedata['when'], array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')))
													{
														$dm = 'Invalid value "'.$updatedata['when'].'". Please see http://twitapps.com/follows for valid values.';
													}
													break;
													
												case 'monthly':
													$updatedata['when'] = intval($updatedata['when']);
													if ($updatedata['when'] < 0 or $updatedata['when'] > 28)
													{
														$dm = 'Invalid value "'.$updatedata['when'].'". Please see http://twitapps.com/follows for valid values.';
													}
													break;
											}
											
											if ($dm === false)
											{
												if (User::Update($info['headers']['x-twittersenderscreenname'], $updatedata))
												{
													$hour = 'at or shortly after '.($updatedata['hour'] > 12 ? ($updatedata['hour']-12).'pm' : ($updatedata['hour'] == 0 ? 'midnight' : $updatedata['hour'].($updatedata['hour'] == 12 ? 'pm' : 'am'))).' UTC';
													switch ($updatedata['frequency'])
													{
														case 'daily':
															$dm = 'Success! I\'ll now send you a daily update by email '.$hour.'.';
															break;

														case 'weekly':
															$dm = 'Success! I\'ll now send you a weekly update by email every '.ucfirst($updatedata['when']).' '.$hour.'.';
															break;

														case 'monthly':
															$dm = 'Success! I\'ll now send you a monthly update by email on the '.date('jS', strtotime('2010-01-'.(strlen($updatedata['when']) == 1 ? '0' : ''))).' '.$hour;
															break;
													}
												}
												else
												{
													$dm = 'An unhandled error occurred when setting the frequency preference. Try again or contact @twitapps.';
												}
											}
										}
									}
									break;

								default:
									$dm = 'Unknown option "'.$bits[0].'". Please see http://twitapps.com/follows for valid options.';
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
