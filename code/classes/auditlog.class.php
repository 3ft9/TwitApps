<?php
	class AuditLog
	{
		const TABLE = 'audit_log';

		static public function Write($service, $user_id, $type, $message)
		{
			$db = GetDB();
			$sql = 'insert into `'.self::TABLE.'` set ';
			$sql.= '`service` = "'.mysql_real_escape_string($service, $db).'", ';
			$sql.= '`user_id` = "'.mysql_real_escape_string($user_id, $db).'", ';
			$sql.= '`stamp` = unix_timestamp(), ';
			$sql.= '`type` = "'.mysql_real_escape_string($type, $db).'", ';
			$sql.= '`message` = "'.mysql_real_escape_string($message, $db).'"';
			@mysql_query($sql, $db);
		}
	}
