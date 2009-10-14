<?php
	require dirname(__FILE__).'/../fx.php';
	
	$endtime = strtotime(date('Y-m-d H:'.(date('i') >= 45 ? '58' : (date('i') >= 30 ? '43' : (date('i') >= 15 ? '28' : '13'))).':00'));

	while (time() < $endtime)
	{
		ob_start();

		$mbox = @imap_open($_email['server'], $_email['username'], $_email['password']);
		if (!$mbox) { ob_end_clean(); sleep(10); continue; }
	
		$processed = 0;

		$msgs = @imap_search($mbox, 'UNSEEN');
		
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
					!empty($header['x-twittersenderscreenname']))
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
							elseif (stripos(@$user['error'], 'already on your list') !== false or User::Create($user))
							{
								Twitter::Tweet('d '.$headers['x-twittersenderscreenname'].' Welcome to Replies from TwitApps. Send your email address by direct message to @'.$_twitter['username'].' to activate this service.');
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
										$bits = preg_split('/\s/', trim(substr(strtolower($body), strpos(strtolower($body), 'set')+3)));
										switch ($bits[0])
										{
											case 'ignore_self':
												if ($bits[1] == 'on' or $bits[1] == 1)
												{
													if (User::Update($headers['x-twittersenderscreenname'], array($bits[0] => 1)))
													{
														$dm = 'Success! I\'m now ignoring tweets from you that contain @'.$headers['x-twittersenderscreenname'].'. To change this send "set ignore_self off".';
													}
													else
													{
														$dm = 'An unhandled error occurred when setting the ignore_self preference. Try again or contact @twitapps.';
													}
												}
												elseif ($bits[1] == 'off' or $bits[1] == 0)
												{
													if (User::Update($headers['x-twittersenderscreenname'], array($bits[0] => 0)))
													{
														$dm = 'Success! I\'m now including tweets from you that contain @'.$headers['x-twittersenderscreenname'].'. To change this send "set ignore_self on".';
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
													if (User::Update($headers['x-twittersenderscreenname'], array($bits[0] => 1)))
													{
														$dm = 'Success! I\'m now only watching for tweets that start with @'.$headers['x-twittersenderscreenname'].'. To change this send "set replies_only off".';
													}
													else
													{
														$dm = 'An unhandled error occurred when setting the replies_only preference. Try again or contact @twitapps.';
													}
												}
												elseif ($bits[1] == 'off' or $bits[1] == 0)
												{
													if (User::Update($headers['x-twittersenderscreenname'], array($bits[0] => 0)))
													{
														$dm = 'Success! I\'m now including all tweets containing @'.$headers['x-twittersenderscreenname'].'. To change this send "set replies_only on".';
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
														if (User::Update($headers['x-twittersenderscreenname'], $updatedata))
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
					@imap_mail_move($mbox, $msgid, '[Google Mail]/All Mail');
				}
			}
		}
		
		@imap_close($mbox);
		
		$content = trim(ob_get_clean());
		if (strlen($content) > 0)
		{
			@mail('stuart@stut.net', 'TwitApps Replies Errors', $content);
		}

		// Didn't process anything, rest for a while
		if ($processed == 0)
		{
			//echo "Sleeping...\n";
			sleep(60);
		}
	}
