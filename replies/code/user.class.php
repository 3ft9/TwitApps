<?php
	class User
	{
		public static function Create($user)
		{
			$db = GetDB();
			$sql = 'insert into users set ';
			$sql.= 'username = "'.mysql_real_escape_string($user['screen_name'], $db).'", ';
			$sql.= 'email = "", ';
			$sql.= 'status = "inactive", ';
			$sql.= 'last_run_at = 0, ';
			$sql.= 'last_email_at = 0, ';
			$sql.= 'last_id = "'.(isset($user['status']['id']) ? mysql_real_escape_string($user['status']['id'], $db) : 0).'", ';
			$sql.= 'registered_at = "'.mysql_real_escape_string(time(), $db).'", ';
			$sql.= 'processor_pid = 0 ';
			$sql.= 'on duplicate key update ';
			$sql.= 'username = "'.mysql_real_escape_string($user['screen_name'], $db).'"';
			return @mysql_query($sql, $db);
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
		
		public static function SetStatus($username, $newstatus, $lastid = false)
		{
			$db = GetDB();
			$sql = 'update users set ';
			$sql.= 'status = "'.mysql_real_escape_string($newstatus, $db).'"';
			if ($lastid !== false)
			{
				$sql.= ', last_id = "'.mysql_real_escape_string($lastid, $db).'"';
			}
			$sql.= ' where username = "'.mysql_real_escape_string($username, $db).'"';
			$retval = @mysql_query($sql, $db);
			
			if ($retval)
			{
				switch ($newstatus)
				{
					case 'inactive':
						Queue::Clear($username);
						break;
				}
			}
			
			return $retval;
		}
		
		public static function SendWelcomeEmail($username, $email)
		{
			$retval = false;
			
			$body = TPL('emails/welcome', array('username' => $username), true);
			
			$retval = mail($email, 'Welcome to Replies from TwitApps', $body, 'From: TwitApps <noreply@twitapps.com>', '-fnoreply@twitapps.com');
			
			return $retval;
		}
		
		public static function GetNext($pid = false)
		{
			if ($pid === false) $pid = getmypid();

			$retval = false;

			$db = GetDB();
			// Get any users not run within the last 15 minutes ordered by last run date then last emailed date
			$sql = 'update users set processor_pid = "'.mysql_real_escape_string($pid, $db).'" where last_run_at < '.(time() - (60*5)).' and status = "active" and username != "" and email != "" order by last_run_at asc, last_email_at asc limit 1';
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
				$sql.= $key.' = "'.mysql_real_escape_string($val, $db).'", ';
			}
			$sql = trim($sql, ', ');
			$sql.= ' where username = "'.mysql_real_escape_string($username, $db).'"';
			//echo $sql."\n\n";
			return mysql_query($sql, $db);
		}
	}
