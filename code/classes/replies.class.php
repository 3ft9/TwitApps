<?php
	register_shutdown_function('Replies_ReleaseUser');
	function Replies_ReleaseUser() { Replies::ReleaseUser(); }

	class Replies
	{
		/**
		 * Constants for the table names
		 */
		const TABLE_USERS = 'replies_users';
		const TABLE_QUEUE = 'replies_queue';

		// EXECUTION FUNCTIONS

		/**
		 * Run the next user.
		 * @return bool False if there are no users due, otherwise true.
		 */
		static public function Run()
		{
			Debug_StartBlock(9, 'Replies::Run', 'Running Replies::Run...');

			$user = self::GetNextUser();
			
			$retval = false;
			
			if ($user === false)
			{
				Debug(9, 'No user to process');
			}
			else
			{
				Debug(5, 'NextUser == '.$user['id']);
				$u = User::Get($user['id']);
				
				if ($u === false)
				{
					AuditLog::Write('replies', $user['id'], 'error', 'Failed to load user details');
					Debug(5, 'Failed to get full user info');
				}
				else
				{
					Debug(5, 'Checking user '.$user['id'].'...');

					// Create TwitterOAuth with app key/secret and user access key/secret
					$twitter = new TwitterOAuth(__('oauth', 'consumer_key'), __('oauth', 'consumer_secret'), $u['oauth_token'], $u['oauth_token_secret']);

					if ($user['last_id'] == 0)
					{
						// First run for this user, get the last_id
						Debug(5, 'First run for this user, getting initial last_id...');
						$replies_json = $twitter->OAuthRequest('https://twitter.com/statuses/mentions.json', array('count' => 1), 'GET');
						if ($twitter->lastStatusCode() != 200)
						{
							switch ($twitter->lastStatusCode())
							{
								case 0:		// Some sort of network error
								case 502:	// Bad gateway
									// We do nothing here because these are transient errors
									break;

								default:
									Debug(1, 'Error getting replies for '.$user['id'].': '.$twitter->lastStatusCode());
									AuditLog::Write('replies', $user['id'], 'error', 'Call to statuses/mentions.json failed with error code "'.$twitter->lastStatusCode().'"');
									break;
							}
						}
						else
						{
							$replies = @json_decode($replies_json);
							if (is_array($replies) and isset($replies[0]))
							{
								$user['last_id'] = $replies[0]->id;
								Debug(5, 'Got '.$user['last_id']);
								AuditLog::Write('replies', $user['id'], 'notice', 'First run, set last_id to "'.$user['last_id'].'"');
							}
							else
							{
								Debug(5, 'Failed to get initial last_id');
								Debug(9, 'Replies[JSON]: '.$replies_json);
								Debug(9, 'Replies: '.print_r($replies, true));
								AuditLog::Write('replies', $user['id'], 'error', 'First run, Failed to get last_id: '.$replies_json);
							}
						}
					}
					else
					{
						// Run request on twitter API as user to get their user details
						Debug(5, 'Getting replies since ID '.$user['last_id']);
						$response = $twitter->OAuthRequest('https://twitter.com/statuses/mentions.json', array('count' => 200, 'since_id' => $user['last_id']), 'GET');
						if ($twitter->lastStatusCode() != 200)
						{
							switch ($twitter->lastStatusCode())
							{
								case 0:		// Some sort of network error
								case 502:	// Bad gateway
									// We do nothing here because these are transient errors
									break;

								default:
									Debug(1, 'Error getting replies for '.$user['id'].': '.$twitter->lastStatusCode());
									AuditLog::Write('replies', $user['id'], 'error', 'Call to statuses/mentions.json failed with error code "'.$twitter->lastStatusCode().'"');
									break;
							}
						}
						else
						{
							$replies = json_decode($response, true);
							if (is_array($replies))
							{
								Debug(5, 'Got '.count($replies).' replies');
								if (count($replies) == 0)
								{
									//AuditLog::Write('replies', $user['id'], 'notice', 'Checked but got nothing new');
								}
								else
								{
									AuditLog::Write('replies', $user['id'], 'notice', 'Got '.count($replies).' replies');
									foreach (array_reverse($replies) as $reply)
									{
										// Skip this if we've already seen it - should this be logged?
										if ($reply['id'] <= $user['last_id']) continue;
										
										// Store this ID if it's higher than the last ID seen
										if ($reply['id'] > $user['last_id']) $user['last_id'] = $reply['id'];
		
										// If we're ignoring self make sure this tweet is not from us
										if (!$user['ignore_self'] or $reply['user']['id'] != $user['id'])
										{
											// If we only want replies make sure this is a reply directly to us
											if (!$user['replies_only'] or strtolower(substr($reply['text'], 0, strlen($user['screen_name']) + 1)) == '@'.strtolower($user['screen_name']))
											{
												self::Store($user['id'], $reply);
											}
										}
									}
								}
							}
							else
							{
								Debug(5, 'Failed to get replies');
								Debug(9, 'Replies[JSON]: '.$response);
								Debug(9, 'Replies: '.print_r($replies, true));
								AuditLog::Write('replies', $user['id'], 'error', 'Invalid data from statuses/mentions.json: '.$response);
							}
						}
					}
					$retval = true;
				}

				Debug(5, 'Updating and releasing user');
				$user['last_run_at'] = time();
				self::UpdateUser($user['id'], $user);
				self::ReleaseUser();
				
				$retval = true;
			}
			
			Debug_EndBlock(9, 'Replies::Run completed '.($retval ? 'successfully' : 'unsuccessfully'));
			
			return $retval;
		}
		
		/**
		 * Send an email to the next user.
		 * @return bool False if there are no users due, otherwise true.
		 */
		static public function SendEmail()
		{
			Debug_StartBlock(9, 'Replies::SendEmail', 'Running Replies::SendEmail...');

			$user = self::GetNextEmailUser();
			
			$retval = false;
			
			if ($user === false)
			{
				Debug(5, 'No user to process');
			}
			else
			{
				Debug(5, 'NextUser == '.$user['id']);
				$u = User::Get($user['id']);
				
				if ($u === false)
				{
					AuditLog::Write('replies', $user['id'], 'error', 'Failed to load user details');
					Debug(5, 'Failed to get full user info');
				}
				else
				{
					$replies = self::GetPending($user['id']);
					
					Debug(9, 'Got '.count($replies).' replies');
					
					if (count($replies) == 0)
					{
						//AuditLog::Write('replies', $user['id'], 'notice', 'Nothing waiting to be emailed');
					}
					else
					{
						$ids = array();
						
						foreach ($replies as $reply)
						{
							$ids[] = $reply['id'];
						}
						
						$data = array('user' => $u, 'replies' => $replies);
						
						$mail = new PHPMailer();
						$mail->SetLanguage('en', CODEDIR.'phpmailer/language/');
						
						$mail->IsSMTP();          // set mailer to use SMTP
						$mail->Host = 'ssl://smtp.gmail.com'; // specify main and backup server
						$mail->SMTPAuth = true;
						$mail->Port = 465;        //  Used instead of 587 when only POP mail is selected
						
						$mail->Username = 'help@twitapps.com';  // SMTP username, you could use your google apps address too.
						$mail->Password = 'my_gmail_password'; // SMTP password

						$mail->FromName = 'TwitApps';
						$mail->From = 'noreply@twitapps.com';
						$mail->Sender = 'noreply@twitapps.com';
						$mail->AddReplyTo('noreply@twitapps.com', 'TwitApps');
						
						$mail->Subject = 'New Twitter repl'.(count($replies) == 1 ? 'y' : 'ies').' for '.$u['screen_name'];
						
						$mail->Body = TPL('email/replies.html', $data, true);
						$mail->AltBody = TPL('email/replies.txt', $data, true);
						$mail->IsHTML(true);
						
						$mail->WordWrap = 79;
						
						$mail->AddAddress($user['email'], $u['screen_name']);
						//$mail->AddBCC('twitapps@stut.net');
						
						if ($mail->Send())
						{
							AuditLog::Write('replies', $user['id'], 'notice', 'Sent '.count($replies).' by email to "'.$user['email'].'"');
							self::MarkAsEmailed($user['id'], $ids);
							$user['last_email_at'] = time();
						}
						else
						{
							AuditLog::Write('replies', $user['id'], 'error', 'Failed to send '.count($replies).' by email to "'.$user['email'].'": '.$mail->ErrorInfo);
							Debug(1, 'Failed to send Replies email: '.$mail->ErrorInfo);
						}
					}

					// Set up the next email
					$user['next_email_at'] = time() + ($user['min_interval'] == 0 ? 60 : $user['min_interval']);
				}

				Debug(5, 'Updating and releasing user');
				self::UpdateUser($user['id'], $user);
				self::ReleaseUser();
				
				$retval = true;
			}
			
			Debug_EndBlock(9, 'Replies::Run completed '.($retval ? 'successfully' : 'unsuccessfully'));
			
			return $retval;
		}
		
		// USER FUNCTIONS
		
		static private function GetNextUser()
		{
			$retval = false;

			$db = GetDB();
			
			$pid = mysql_real_escape_string((defined('PID') ? PID : getmypid()), $db);

			// Get any users not run within the last 5 minutes ordered by last run date then last emailed date
			$sql = 'update `'.self::TABLE_USERS.'` set `processor_pid` = "'.$pid.'" where `processor_pid` in (0,"'.mysql_real_escape_string(PID, $db).'") and `status` = "active" and `email` != "" and `last_run_at` < (unix_timestamp() - 60) order by processor_pid desc, last_run_at asc, last_email_at asc limit 1';
			mysql_query($sql, $db);

			$sql = 'select * from `'.self::TABLE_USERS.'` where `processor_pid` = "'.$pid.'" order by last_run_at asc, last_email_at asc limit 1';
			$query = mysql_query($sql, $db);
			if ($query)
			{
				if (mysql_num_rows($query) > 0)
				{
					$retval = mysql_fetch_assoc($query);
				}
				mysql_free_result($query);
			}
			
			return $retval;
		}
		
		static private function GetNextEmailUser()
		{
			$retval = false;

			$db = GetDB();
			
			$pid = mysql_real_escape_string((defined('PID') ? PID : getmypid()), $db);

			// Get any users not run within the last 5 minutes ordered by last run date then last emailed date
			$sql = 'update `'.self::TABLE_USERS.'` set `processor_pid` = "'.$pid.'" where `processor_pid` in (0,"'.mysql_real_escape_string(PID, $db).'") and `status` = "active" and `email` != "" and `next_email_at` < unix_timestamp() order by processor_pid desc, next_email_at asc limit 1';
			mysql_query($sql, $db);

			$sql = 'select * from `'.self::TABLE_USERS.'` where `processor_pid` = "'.$pid.'" order by next_email_at asc limit 1';
			$query = mysql_query($sql, $db);
			if ($query)
			{
				if (mysql_num_rows($query) > 0)
				{
					$retval = mysql_fetch_assoc($query);
				}
				mysql_free_result($query);
			}
			
			return $retval;
		}
		
		static private function UpdateUser($id, $data)
		{
			$db = GetDB();

			$fields = array('`updated_at` = '.time());
			foreach ($data as $var => $val)
			{
				if (is_array($val))
				{
					$fields[] = '`'.$var.'` = "'.mysql_real_escape_string(serialize($val), $db).'"';
				}
				else
				{
					$fields[] = '`'.$var.'` = "'.mysql_real_escape_string($val, $db).'"';
				}
			}
			
			$sql = 'update `'.self::TABLE_USERS.'` set '.implode(', ', $fields).' where `id` = "'.mysql_real_escape_string($id, $db).'"';
			
			$retval = @mysql_query($sql, $db);
			if (!$retval) die('Something bad happened. Sorry. <!-- '.mysql_error($db).' -->');
			
			return $retval;
		}
		
		static public function ReleaseUser()
		{
			$db = GetDB();
			$pid = mysql_real_escape_string((defined('PID') ? PID : getmypid()), $db);
			$sql = 'update `'.self::TABLE_USERS.'` set `processor_pid` = 0 where `processor_pid` = "'.$pid.'"';
			mysql_query($sql, $db);
		}
		
		// QUEUE FUNCTIONS
		
		static private function Store($user_id, $tweet)
		{
			$db = GetDB();
			
			// Store the tweet in the queue for this user
			debug(9, 'StoreReply: Storing tweet '.$tweet['id'].'...');
			$fields = '`sender_id` = "'.mysql_real_escape_string($tweet['user']['id'], $db).'"';
			$fields.= ', `received_at` = '.strtotime($tweet['created_at']);
			$fields.= ', `data` = "'.mysql_real_escape_string(serialize($tweet), $db).'"';

			$sql = 'insert into `'.self::TABLE_QUEUE.'` set ';
			$sql.= '`recipient_id` = "'.mysql_real_escape_string($user_id, $db).'", ';
			$sql.= '`tweet_id` = "'.mysql_real_escape_string($tweet['id'], $db).'", ';
			$sql.= '`emailed_at` = 0, ';
			$sql.= $fields;
			$sql.= ' on duplicate key update ';
			$sql.= $fields;
			$retval = mysql_query($sql, $db);
			if (!$retval)
			{
				debug(1, 'StoreReply: '.mysql_error($db));
				debug(1, 'StoreReply: '.$sql);
			}
			
			// Also store the user details we've been given so our user info is up to date
			debug(9, 'StoreReply: Updating tweet sender ('.$tweet['user']['id'].')...');
			User::Update($tweet['user']);
			
			return $retval;
		}
		
		static private function GetPending($user_id)
		{
			$retval = false;
			$db = GetDB();
			$sql = 'select * from `'.self::TABLE_QUEUE.'` where `recipient_id` = "'.mysql_real_escape_string($user_id, $db).'" and `emailed_at` = 0 order by received_at asc';
			$query = mysql_query($sql, $db);
			if ($query !== false)
			{
				$retval = array();
				while ($row = mysql_fetch_assoc($query))
				{
					$row['data'] = unserialize($row['data']);
					$retval[] = $row;
				}
				@mysql_free_result($query);
			}
			return $retval;
		}
		
		static private function MarkAsEmailed($user_id, $ids)
		{
			$db = GetDB();
			$sql = 'update `'.self::TABLE_QUEUE.'` set `emailed_at` = unix_timestamp() where `recipient_id` = "'.mysql_real_escape_string($user_id, $db).'" and `emailed_at` = 0 and `id` in ('.implode(',', $ids).')';
			return mysql_query($sql, $db);
		}
	}
