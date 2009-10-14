<?php
	require dirname(__FILE__).'/../fx.php';
	
	$endtime = strtotime(date('Y-m-d H:'.(date('i') >= 45 ? '58' : (date('i') >= 30 ? '43' : (date('i') >= 15 ? '28' : '13'))).':00'));

	while (time() < $endtime)
	{
		ob_start();

		$mbox = @imap_open($_email['server'], $_email['username'], $_email['password']);
		if (!$mbox) { ob_end_clean(); sleep(10); continue; }
	
		$processed = 0;

		$msgs = imap_search($mbox, 'UNSEEN');
		
		if ($msgs)
		{
			foreach ($msgs as $msgid)
			{
				// Crap out here if we've run out of time
				if (time() >= $endtime) break;

				$imap_headers = @imap_fetchheader($mbox, $msgid);
				if (!$imap_headers) break;
				
				$headers = array();
				
				foreach (explode("\n", trim($imap_headers)) as $header)
				{
					if (strlen(trim($header)) == 0 or $header[0] == ' ' or $header[0] == "\t") continue;
	
					list($key, $val) = explode(':', trim($header));
					
					$key = strtolower($key);
					$val = trim($val);
					
					switch ($key)
					{
						case 'x-twitterrecipientscreenname':
							if ($val != $_twitter['username'])
							{
								// Skip to the next message - this one is not for us!
								break 2;
							}
							break;
	
						case 'x-twitteremailtype':
						case 'x-twittersendername':
						case 'x-twittersenderscreenname':
							$headers[$key] = $val;
							break;
					}
				}
	
				if (!empty($headers['x-twitteremailtype']) and
					!empty($headers['x-twittersendername']) and
					!empty($headers['x-twittersenderscreenname']))
				{
					// Mark it as seen now to minimise the possibility of clashes
					$processed++;

					switch ($headers['x-twitteremailtype'])
					{
						case 'is_following':
							// Note that we handle the case where we're already following a user and don't try to re-create them
							$user = Twitter::Follow($headers['x-twittersenderscreenname']);
							if (isset($user['error']) and stripos($user['error'], 'already on your list') === false)
							{
								echo 'Follow failed for "'.$headers['x-twittersenderscreenname'].'": '.$user['error']."\n";
							}
							elseif (stripos(@$user['error'], 'already on your list') !== false or User::Create((isset($user['screen_name']) ? $user : array('screen_name' => $headers['x-twittersenderscreenname']))))
							{
								Twitter::Tweet('d '.$headers['x-twittersenderscreenname'].' Welcome to Follows from TwitApps. Send your email address by direct message to @'.$_twitter['username'].' to activate this service.');
							}
							else
							{
								echo 'Failed to create user "'.$headers['x-twittersenderscreenname'].'": '.mysql_error(GetDB())."\n";
							}
							break;
							
						case 'direct_message':
							// Direct message should contain a command or an email address
							$body = imap_body($mbox, $msgid, FT_PEEK);
							$email = false;
							foreach (preg_split('/\s/', strtolower($body)) as $word)
							{
								switch ($word)
								{
									case 'start':
										//echo 'Start for "'.$headers['x-twittersenderscreenname'].'"'."\n";
										$user = Twitter::GetUserDetails($headers['x-twittersenderscreenname']);
										if ($user and User::SetStatus($headers['x-twittersenderscreenname'], 'active', $user->status->id))
										{
											$dm = 'Sorted! I\'ll start sending you emails again shortly.';
										}
										else
										{
											$dm = 'Grrr, something went wrong restarting your emails. I\'ve notified the team and they\'ll look into it ASAP.';
											echo '  Failed to start emails for "'.$headers['x-twittersenderscreenname'].'"'."\n\n";
										}
										Twitter::Tweet('d '.$headers['x-twittersenderscreenname'].' '.$dm);
										break;
										
									case 'stop':
										//echo 'Stop for "'.$headers['x-twittersenderscreenname'].'"'."\n";
										if (User::SetStatus($headers['x-twittersenderscreenname'], 'inactive'))
										{
											$dm = 'Ok, I\'ll stop sending you emails for now. Send the word "start" to restart them again.';
										}
										else
										{
											$dm = 'Grrr, something went wrong stopping your emails. I\'ve notified the team and they\'ll look into it ASAP.';
											echo '  Failed to stop emails for "'.$headers['x-twittersenderscreenname'].'"'."\n\n";
										}
										Twitter::Tweet('d '.$headers['x-twittersenderscreenname'].' '.$dm);
										break;
										
									case 'set':
										$dm = false;
										$bits = preg_split('/\s/', trim(substr(strtolower($body), strpos(strtolower($body), 'set')+3)));
										switch ($bits[0])
										{
											case 'frequency':
												if (count($bits) < 4)
												{
													$dm = 'Incomplete command. Please see http://twitapps.com/follows for help.';
												}
												else
												{
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
																if (User::Update($headers['x-twittersenderscreenname'], $updatedata))
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
												}
												break;

											default:
												$dm = 'Unknown option "'.$bits[0].'". Please see http://twitapps.com/follows for valid options.';
												break;
										}
										Twitter::Tweet('d '.$headers['x-twittersenderscreenname'].' '.$dm);
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
								//echo 'Activate for "'.$headers['x-twittersenderscreenname'].'"'."\n";
								if (!User::Activate($headers['x-twittersenderscreenname'], $email))
								{
									echo '  Failed to activate user "'.$headers['x-twittersenderscreenname'].'": '.mysql_error(GetDB())."\n";
								}
							}
							break;
	
						default:
							echo 'Unknown type: "'.$headers['x-twitteremailtype'].'"'."\n";
							var_dump($headers);
							break;
					}
		
					// Move to [Google Mail]/All Mail after processing
					imap_mail_move($mbox, $msgid, '[Google Mail]/All Mail');
				}
			}
		}
		
		imap_close($mbox);

		$content = trim(ob_get_clean());
		if (strlen($content) > 0)
		{
			@mail('stuart@stut.net', 'TwitApps Follows Errors', $content);
		}

		// Didn't process anything, rest for a while
		if ($processed == 0)
		{
			//echo "Sleeping...\n";
			sleep(60);
		}
	}
