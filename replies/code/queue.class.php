<?php
	class Queue
	{
		public static function Add($username, $tweetid, $sent_at, $data)
		{
			$db = GetDB();
			$sql = 'insert into queue set ';
			$sql.= 'username = "'.mysql_real_escape_string($username, $db).'", ';
			$sql.= 'tweetid = "'.mysql_real_escape_string($tweetid, $db).'", ';
			$sql.= 'sent_at = "'.mysql_real_escape_string($sent_at, $db).'", ';
			$sql.= 'data = "'.mysql_real_escape_string(serialize($data), $db).'" ';
			$sql.= 'on duplicate key update ';
			$sql.= 'sent_at = "'.mysql_real_escape_string($sent_at, $db).'", ';
			$sql.= 'data = "'.mysql_real_escape_string(serialize($data), $db).'"';
			return mysql_query($sql, $db);
		}
		
		public static function Clear($username)
		{
			$db = GetDB();
			return mysql_query('delete from queue where username = "'.mysql_real_escape_string($username, $db).'"', $db);
		}
		
		public static function Num($username)
		{
			$retval = false;
			$db = GetDB();
			$query = mysql_query('select count(1) from queue where username = "'.mysql_real_escape_string($username, $db).'"', $db);
			if ($query)
			{
				$retval = mysql_fetch_array($query);
				$retval = $retval[0];
			}
			return $retval;
		}
		
		public static function & Get($username)
		{
			$retval = array();
			$db = GetDB();
			$sql = 'select data from queue where username = "'.mysql_real_escape_string($username, $db).'" order by sent_at asc';
			$query = mysql_query($sql, $db);
			while ($row = mysql_fetch_array($query))
			{
				$retval[] = unserialize($row[0]);
			}
			
			return $retval;
		}
		
		public static function SendEmail($username, $email)
		{
			$retval = false;
			
			$items = self::Get($username);

			$body_text = TPL('emails/body_text_header', array('username' => $username, 'num' => count($items)), true);
			$body_html = TPL('emails/body_html_header', array('username' => $username, 'num' => count($items)), true);

			foreach ($items as &$item)
			{
				$body_text.= TPL('emails/body_text_item', $item, true);
				$body_html.= TPL('emails/body_html_item', $item, true);
			}

			$body_text.= TPL('emails/body_text_footer', array('username' => $username), true);
			$body_html.= TPL('emails/body_html_footer', array('username' => $username), true);

			$mail = new PHPMailer();
			
			$mail->FromName = 'TwitApps';
			$mail->From = 'noreply@twitapps.com';
			$mail->Sender = 'noreply@twitapps.com';
			$mail->AddReplyTo('noreply@twitapps.com', 'TwitApps');
			
			$mail->Subject = 'New Twitter repl'.(count($items) == 1 ? 'y' : 'ies').' for '.$username;
			
			$mail->Body = $body_html;
			$mail->AltBody = $body_text;
			$mail->IsHTML(true);
			
			$mail->WordWrap = 79;
			
			$mail->AddAddress($email);
			if ($username == 'GWMan')
			{
				$mail->AddBCC('twitapps@stut.net');
			}
			
			if ($mail->Send())
			{
				self::Clear($username);
				$retval = true;
			}
			
			return $retval;
		}
	}
