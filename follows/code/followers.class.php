<?php
	class Followers
	{
		public static function Add($username, $follower_id)
		{
			$db = GetDB();
			$sql = 'insert into followers set ';
			$sql.= 'username = "'.mysql_real_escape_string($username, $db).'", ';
			$sql.= 'follower_id = "'.mysql_real_escape_string($follower_id, $db).'", ';
			$sql.= 'followed_at = '.time().', ';
			$sql.= 'unfollowed_at = 0, last_updated_at = '.time().' ';
			$sql.= 'on duplicate key update last_updated_at = '.time();
			return mysql_query($sql, $db);
		}
		
		public static function Remove($username, $timestamp)
		{
			$db = GetDB();
			$sql = 'update followers set unfollowed_at = '.time().' where username = "'.mysql_real_escape_string($username, $db).'" and last_updated_at < '.$timestamp.' and unfollowed_at = 0';
			return mysql_query($sql, $db);
		}
		
		public static function & GetDiffs($username, $since)
		{
			$db = GetDB();
			$sql = 'select followers.follower_id, followers.followed_at, followers.unfollowed_at, twitterusers.* from followers left join twitterusers on followers.follower_id = twitterusers.id where followers.username = "'.mysql_real_escape_string($username, $db).'" and  (followers.followed_at > '.$since.' or followers.unfollowed_at > '.$since.') order by followers.unfollowed_at asc, followers.followed_at asc';
			$retval = mysql_query($sql, $db);
			return $retval;
		}
	}
