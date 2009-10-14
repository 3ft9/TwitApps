<?php
	class User
	{
		public static function Create($user)
		{
			$db = GetDB();
			$sql = 'insert into users set ';
			$sql.= '`username` = "'.mysql_real_escape_string($user['screen_name'], $db).'", ';
			$sql.= '`email` = "", ';
			$sql.= '`status` = "inactive", ';
			$sql.= '`last_run_at` = 0, ';
			$sql.= '`last_email_at` = 0, ';
			$sql.= '`next_email_at` = '.(time() + 86400).', ';
			$sql.= '`registered_at` = "'.mysql_real_escape_string(time(), $db).'", ';
			$sql.= '`frequency` = "daily", ';
			$sql.= '`hour` = "'.date('G').'", ';
			$sql.= '`when` = "", ';
			$sql.= '`follower_count` = 0, ';
			$sql.= '`processor_pid` = 0 ';
			$sql.= 'on duplicate key update ';
			$sql.= '`username` = "'.mysql_real_escape_string($user['screen_name'], $db).'"';
			return mysql_query($sql, $db);
		}
		
		public static function Exists($username)
		{
			$retval = false;
			$db = GetDB();
			$sql = 'select count(1) from users where username = "'.mysql_real_escape_string($username, $db).'"';
			$query = mysql_query($sql, $db);
			if ($query and mysql_num_rows($query) > 0)
			{
				$retval = (mysql_result($query, 0, 0) > 0);
			}
			return $retval;
		}
		
		public static function Activate($username, $email)
		{
			$db = GetDB();
			$sql = 'update users set ';
			$sql.= 'email = "'.mysql_real_escape_string($email, $db).'", ';
			$sql.= 'status = "active" ';
			$sql.= 'where username = "'.mysql_real_escape_string($username, $db).'"';
			$retval = @mysql_query($sql, $db);
			if ($retval)
			{
				$retval = self::SendWelcomeEmail($username, $email);
			}
			return $retval;
		}
		
		public static function SetStatus($username, $newstatus)
		{
			$db = GetDB();
			$sql = 'update users set ';
			$sql.= 'status = "'.mysql_real_escape_string($newstatus, $db).'" ';
			$sql.= 'where username = "'.mysql_real_escape_string($username, $db).'"';
			$retval = @mysql_query($sql, $db);
			
			if ($retval)
			{
				switch ($newstatus)
				{
					case 'inactive':
						break;
						
					case 'active':
						break;
				}
			}
			
			return $retval;
		}
		
		public static function SendWelcomeEmail($username, $email)
		{
			$retval = false;
			
			$body = TPL('emails/welcome', array('username' => $username), true);
			
			$retval = mail($email, 'Welcome to Follows from TwitApps', $body, 'From: TwitApps <noreply@twitapps.com>', '-fnoreply@twitapps.com');
			
			return $retval;
		}
		
		public static function GetNext($pid = false, $username = false)
		{
			if ($pid === false) $pid = getmypid();

			$retval = false;

			$db = GetDB();
			// Get any users due to be run
			if ($username === false)
				$sql = 'update users set processor_pid = "'.mysql_real_escape_string($pid, $db).'" where last_run_at < '.(time() - 86400).' and status = "active" and username != "" and email != "" order by last_run_at asc, next_email_at asc limit 1';
			else
				$sql = 'update users set processor_pid = "'.mysql_real_escape_string($pid, $db).'" where username = "'.mysql_real_escape_string($username, $db).'" limit 1';
			mysql_query($sql, $db);
			$sql = 'select * from users where processor_pid = "'.mysql_real_escape_string($pid, $db).'"';
			$query = mysql_query($sql, $db);
			if ($query and mysql_num_rows($query) > 0)
			{
				$retval = mysql_fetch_assoc($query);
			}
			
			return $retval;
		}
		
		public static function GetNextToEmail($pid = false)
		{
			if ($pid === false) $pid = getmypid();

			$retval = false;

			$db = GetDB();
			// Get any users due to be emailed
			$sql = 'update users set processor_pid = "'.mysql_real_escape_string($pid, $db).'" where next_email_at < '.time().' and status = "active" and last_email_at != 0 and username != "" and email != "" order by last_run_at asc, next_email_at asc limit 1';
			mysql_query($sql, $db);
			$sql = 'select * from users where processor_pid = "'.mysql_real_escape_string($pid, $db).'"';
			$query = mysql_query($sql, $db);
			if ($query and mysql_num_rows($query) > 0)
			{
				$retval = mysql_fetch_assoc($query);
			}
			
			return $retval;
		}
		
		public static function Release($pid = false)
		{
			if ($pid === false) $pid = getmypid();

			$db = GetDB();
			$sql = 'update users set processor_pid = 0 where processor_pid = "'.mysql_real_escape_string($pid, $db).'"';
			return mysql_query($sql, $db);
		}
		
		public static function Update($username, $data)
		{
			$db = GetDB();
			$sql = 'update users set ';
			foreach ($data as $key => $val)
			{
				$sql.= '`'.$key.'` = "'.mysql_real_escape_string($val, $db).'", ';
			}
			$sql = trim($sql, ', ');
			$sql.= ' where username = "'.mysql_real_escape_string($username, $db).'"';
			return mysql_query($sql, $db);
		}
	}
