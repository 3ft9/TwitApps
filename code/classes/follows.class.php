<?php
	register_shutdown_function('Follows_ReleaseUser');
	function Follows_ReleaseUser() { Follows::ReleaseUser(); }

	class Follows
	{
		/**
		 * Constants for the table names
		 */
		const TABLE_USERS = 'follows_users';
		const TABLE_FOLLOWERS = 'follows_followers';

		// (UN)INSTALLATION FUNCTIONS
		
		static function Install($user_id, $email, $frequency = false, $hour = false, $when = false, $post_url = false, $post_format = false)
		{
			if ($frequency === false) $frequency = 'daily';
			if ($hour === false) $hour = date('H');
			if ($when === false) $when = '';
			if ($post_url === false) $post_url = '';
			if ($post_format === false) $post_format = '';

			$db = GetDB();
			
			$sql = 'insert into `'.self::TABLE_USERS.'` set ';
			$sql.= '`id` = "'.mysql_real_escape_string($user_id, $db).'", ';
			$sql.= '`status` = "active", ';
			$sql.= '`email` = "'.mysql_real_escape_string($email, $db).'", ';
			$sql.= '`frequency` = "'.mysql_real_escape_string($frequency, $db).'", ';
			$sql.= '`hour` = "'.mysql_real_escape_string($hour, $db).'", ';
			$sql.= '`when` = "'.mysql_real_escape_string($when, $db).'", ';
			$sql.= '`post_url` = "'.mysql_real_escape_string($post_url, $db).'", ';
			$sql.= '`post_format` = "'.mysql_real_escape_string($post_format, $db).'", ';
			$sql.= '`last_run_at` = 0, ';
			$sql.= '`next_run_at` = 0, ';
			$sql.= '`last_email_at` = 0, ';
			$sql.= '`next_email_at` = "'.mysql_real_escape_string(self::CalcNextEmailAt($frequency, $hour, $when), $db).'", ';
			$sql.= '`registered_at` = '.time().', ';
			$sql.= '`processor_pid` = 0';

			$retval = @mysql_query($sql, $db);
			if ($retval)
			{
				$retval = true;
			}
			else
			{
				$retval = mysql_error($db);
			}
			return $retval;
		}

		// EXECUTION FUNCTIONS

		/**
		 * Run the next user.
		 * @return bool False if there are no users due, otherwise true.
		 */
		static public function Run()
		{
			Debug_StartBlock(9, 'Follows::Run', 'Running Follows::Run...');

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
					AuditLog::Write('follows', $user['id'], 'error', 'Failed to load user details');
					Debug(5, 'Failed to get full user info');
				}
				else
				{
					Debug(5, 'Updating followers for user '.$user['id'].'...');

					self::UpdateFollowers($user, $u);

					if ($user['last_run_at'] == 0)
					{
						// First run for this user, get the last_id
						Debug(5, 'First run for this user, setting next_email_at...');
						$user['next_email_at'] = self::CalcNextEmailAt($user['frequency'], $user['hour'], $user['when']);
					}
					elseif ($user['next_email_at'] <= strtotime(date('Y-m-d 23:59:59')))
					{
						// Not the first run but we're due to send an email
						$retval = self::SendEmail($user, $u);
						if ($retval)
						{
							$user['next_email_at'] = self::CalcNextEmailAt($user['frequency'], $user['hour'], $user['when']);
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
			
			Debug_EndBlock(9, 'Follows::Run completed '.($retval ? 'successfully' : 'unsuccessfully'));
			
			return $retval;
		}
		
		/**
		 * Send an email to the next user.
		 * @return bool False if there are no users due, otherwise true.
		 */
		static public function SendEmail(&$user, &$u)
		{
			$retval = false;
			
			$current_count = self::GetCount($user['id']);
			$follows = self::GetChanges($user['id'], $user['last_email_at']);
			Debug(9, 'Got '.count($follows['new']).' new followers and lost '.count($follows['old']));
			
			if (count($follows['new']) == 0 and count($follows['old']) == 0)
			{
				$retval = true;
			}
			else
			{
				$delta = count($follows['new']) - count($follows['old']);
				
				if (count($follows) == 0)
				{
					Debug(9, 'Nothing waiting to be emailed for '.$user['id']);
				}
				else
				{
					$users = array();
					
					// Create TwitterOAuth with app key/secret and user access key/secret
					$twitter = new TwitterOAuth(__('oauth', 'consumer_key'), __('oauth', 'consumer_secret'), $u['oauth_token'], $u['oauth_token_secret']);
					
					foreach ($follows as $arr)
					{
						foreach (array_keys($arr) as $key)
						{
							$users[$arr[$key]['follower_id']] = User::GetUserDetails(&$twitter, $arr[$key]['follower_id']);
							if (!$users[$arr[$key]['follower_id']])
							{
								$users[$arr[$key]['follower_id']] = User::Get($arr[$key]['follower_id']);
							}
						}
					}
	
					$data = array(
						'u' => $u,
						'user' => $user,
						'follows' => $follows,
						'users' => $users,
						'subject' => 'Follower changes on Twitter for '.$u['screen_name'].' ('.($delta >= 0 ? '+' : '').$delta.($current_count ? ', now '.$current_count : '').')',
					);
					
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
	
					$mail->Subject = $data['subject'];

					$mail->Body = TPL('email/follows.html', $data, true);
					$mail->AltBody = TPL('email/follows.txt', $data, true);
					$mail->IsHTML(true);
					
					$mail->WordWrap = 79;
					
					$mail->AddAddress($user['email'], $u['screen_name']);
					//$mail->AddBCC('twitapps@stut.net');
					
					if ($mail->Send())
					{
						AuditLog::Write('follows', $user['id'], 'notice', 'Sent '.count($follows).' by email to "'.$user['email'].'"');
						$user['last_email_at'] = time();
						
						$retval = true;
					}
					else
					{
						AuditLog::Write('follows', $user['id'], 'error', 'Failed to send '.count($follows).' by email to "'.$user['email'].'": '.$mail->ErrorInfo);
						Debug(1, 'Failed to send Follows email: '.$mail->ErrorInfo);
					}
				}
			}

			return $retval;
		}
		
		// USER FUNCTIONS
		
		static private function GetNextUser()
		{
			$retval = false;

			$db = GetDB();
			
			$pid = mysql_real_escape_string((defined('PID') ? PID : getmypid()), $db);

			// Get any users not run within the last 5 minutes ordered by last run date then last emailed date
			$sql = 'update `'.self::TABLE_USERS.'` set `processor_pid` = "'.$pid.'" where `processor_pid` in (0,"'.mysql_real_escape_string(PID, $db).'") and `status` = "active" and `email` != "" and `last_run_at` < (unix_timestamp() - 86400) order by processor_pid desc, last_run_at asc, last_email_at asc limit 1';
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
		
		// FOLLOWER FUNCTIONS
		
		static private function UpdateFollowers($user, $u)
		{
			$now = time();

			// Create TwitterOAuth with app key/secret and user access key/secret
			$twitter = new TwitterOAuth(__('oauth', 'consumer_key'), __('oauth', 'consumer_secret'), $u['oauth_token'], $u['oauth_token_secret']);

			$count = -1;
			$page = 1;
			while ($count != 0)
			{
				$followers_json = $twitter->OAuthRequest('https://twitter.com/followers/ids.json', array('page' => $page), 'GET');
				if ($twitter->lastStatusCode() != 200)
				{
					switch ($twitter->lastStatusCode())
					{
						case 0:		// Some sort of network error
						case 502:	// Bad gateway
							if ($count == -5)
							{
								// Tried 5 times to get this page and still getting an error, give up
								$count = 0;
							}
							else
							{
								$count = ($count < 0 ? $count-1 : -1);
							}
							break;

						default:
							Debug(1, 'Error getting page '.$page.' of followers for '.$user['id'].': '.$twitter->lastStatusCode());
							AuditLog::Write('follows', $user['id'], 'error', 'Call to followers/ids.json failed with error code "'.$twitter->lastStatusCode().'"');
							$count = 0;
							break;
					}
				}
				else
				{
					$followers = @json_decode($followers_json);
					if (is_array($followers))
					{
						$count = count($followers);
						if ($count > 0)
						{
							Debug(5, 'Got '.$count.' followers');
							AuditLog::Write('follows', $user['id'], 'notice', 'First run, got '.$count.' followers');
							
							$numadded = self::AddFollowers($user['id'], $followers, $now);
							if ($numadded != $count)
							{
								Debug(5, 'Added '.$numadded.' followers when we got '.$count);
								AuditLog::Write('follows', $user['id'], 'warning', 'Added '.$numadded.' followers when we got '.$count);
							}
						}
					}
					else
					{
						Debug(5, 'Failed to get page '.$page);
						Debug(9, 'Follows[JSON]: '.$followers_json);
						Debug(9, 'Follows: '.print_r($followers, true));
						AuditLog::Write('follows', $user['id'], 'error', 'Failed to get followers page '.$page.': '.$followers_json);
						$count = 0;
					}
					
					// Increment the page number
					$page++;
				}
			}
			
			// Now mark any not updated as having stopped following
			self::MarkAsStoppedFollowing($user['id'], $now);
		}

		static private function AddFollowers($user_id, $follower_ids, $now = false)
		{
			$retval = true;

			$db = GetDB();
			
			if ($now === false) $now = time();
			
			// Store the tweet in the queue for this user
			debug(9, 'AddFollower: Adding/updating '.count($follower_ids).' users as followers of '.$user_id.'...');
			
			$user_id_escaped = mysql_real_escape_string($user_id, $db);

			$values = array();
			foreach ($follower_ids as $id)
			{
				$values[] = '("'.$user_id_escaped.'", "'.mysql_real_escape_string($id).'", "'.$now.'", 0, "'.$now.'")';
				
				if (count($values) >= 50)
				{
					// Got 50, run the query
					$retval = self::RunAddFollowersQuery($values, $now, $db);
					$values = array();
					if ($retval === false) break;
				}
			}
			
			if ($retval !== false and count($values) > 0)
			{
				$retval = self::RunAddFollowersQuery($values, $now, $db);
			}
			
			return $retval;
		}
		
		static private function RunAddFollowersQuery($values, $now, $db = false)
		{
			$retval = true;
			
			if ($db === false) $db = GetDB();

			$sql = 'insert into `'.self::TABLE_FOLLOWERS.'` (user_id, follower_id, started_at, stopped_at, last_seen_at) VALUES ';
			$sql.= implode(', ', $values);
			$sql.= ' on duplicate key update ';
			$sql.= 'last_seen_at = "'.$now.'"';
			$retval = mysql_query($sql, $db);
			if (!$retval)
			{
				debug(1, 'RunAddFollowersQuery: '.mysql_error($db));
				debug(1, 'RunAddFollowersQuery: '.$sql);
				$retval = false;
			}
			
			return $retval;
		}
		
		static private function MarkAsStoppedFollowing($user_id, $now)
		{
			$retval = true;
			$db = GetDB();
			$sql = 'update `'.self::TABLE_FOLLOWERS.'` set stopped_at = "'.mysql_real_escape_string($now, $db).'" where user_id = "'.mysql_real_escape_string($user_id, $db).'" and last_seen_at < "'.mysql_real_escape_string($now, $db).'"';
			$retval = mysql_query($sql, $db);
			if (!$retval)
			{
				debug(1, 'MarkAsStoppedFollowing: '.mysql_error($db));
				debug(1, 'MarkAsStoppedFollowing: '.$sql);
				$retval = false;
			}
			return $retval;
		}
		
		static private function CalcNextEmailAt($frequency, $hour, $when)
		{
			$retval = 0;
			switch ($frequency)
			{
				case 'monthly':
					$retval = strtotime(date('Y-m-'.$when.' '.$hour.':00:00', strtotime('next month') - 3600));
					break;

				case 'weekly':
					$retval = strtotime(date('Y-m-d '.$hour.':00:00', strtotime('next '.$when)));
					break;
					
				case 'daily':
				default:
					$retval = strtotime(date('Y-m-d '.$hour.':00:00', time()+86400));
					break;
			}
			return $retval;
		}
		
		static private function GetChanges($user_id, $since)
		{
			$retval = false;
			$db = GetDB();
			$sql = 'select * from `'.self::TABLE_FOLLOWERS.'` where `user_id` = "'.mysql_real_escape_string($user_id, $db).'" and (`started_at` > "'.mysql_real_escape_string($since, $db).'" or `stopped_at` > "'.mysql_real_escape_string($since, $db).'") order by `started_at` asc, `stopped_at` asc';
			$query = mysql_query($sql, $db);
			if ($query !== false)
			{
				$retval = array('new' => array(), 'old' => array());
				while ($row = mysql_fetch_assoc($query))
				{
					$key = 'old';
					if ($row['stopped_at'] == 0 or $row['started_at'] > $row['stopped_at'])
						$key = 'new';
					$retval[$key][] = $row;
				}
				@mysql_free_result($query);
			}
			return $retval;
		}
		
		static private function GetCount($user_id)
		{
			$retval = false;
			$db = GetDB();
			$sql = 'select count(1) as num from `'.self::TABLE_FOLLOWERS.'` where `user_id` = "'.mysql_real_escape_string($user_id, $db).'" and (`stopped_at` = 0 or `started_at` > `stopped_at`)';
			$query = mysql_query($sql, $db);
			if ($query !== false)
			{
				$row = mysql_fetch_assoc($query);
				$retval = $row['num'];
			}
			return $retval;
		}
		
		static private function MarkAsEmailed($user_id, $ids)
		{
			$db = GetDB();
			$sql = 'update `'.self::TABLE_FOLLOWERS.'` set `emailed_at` = unix_timestamp() where `recipient_id` = "'.mysql_real_escape_string($user_id, $db).'" and `emailed_at` = 0 and `id` in ('.implode(',', $ids).')';
			return mysql_query($sql, $db);
		}
	}
