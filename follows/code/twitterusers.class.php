<?php
	class TwitterUsers
	{
		public static function Get($id_or_user, $fields = true)
		{
			$retval = false;
			$isid = is_numeric($id_or_user);
			$db = GetDB();
			$sql = 'select ';
			if (is_array($fields))
			{
				$sql .= implode(',', $fields);
			}
			elseif ($fields === true)
			{
				$sql .= '*';
			}
			else
			{
				$sql .= $fields;
			}
			$sql.= ' from twitterusers where '.($isid ? 'id' : 'username').' = "'.mysql_real_escape_string($id_or_user).'" limit 1';
			$query = mysql_query($sql, $db);
			if (!$query) die('Failed to get user: "'.$id_or_user.'"'."\n");
			if (mysql_num_rows($query) > 0)
			{
				$retval = mysql_fetch_assoc($query);
				if (is_string($fields) and isset($retval[$fields])) $retval = $retval[$fields];
			}
			return $retval;
		}
		
		public static function Set($id, $username, $data = array())
		{
			$retval = false;
			$db = GetDB();

			$sets = array();
			if (!isset($data['username'])) $sets['username'] = 'username = "'.mysql_real_escape_string($username).'"';
			foreach ($data as $key => $value)
			{
				$sets[] = $key.' = "'.mysql_real_escape_string($value).'"';
			}

			$sql = 'insert into twitterusers set ';
			$sql.= 'id = "'.mysql_real_escape_string($id, $db).'", ';
			$sql.= implode(', ', $sets);
			$sql.= 'on duplicate key update ';
			$sql.= implode(', ', $sets);
			$retval = mysql_query($sql, $db);
			if (!$retval) echo 'TwitterUsers::Set("'.$username.'", "'.$username.'", '.print_r($data, true).'): '.mysql_error($db)."\n\n";
			return $retval;
		}
		
		public static function Add($id)
		{
			$retval = false;
			$db = GetDB();
			$sql = 'insert into twitterusers set id = "'.mysql_real_escape_string($id, $db).'" on duplicate key update id = "'.mysql_real_escape_string($id, $db).'"';
			$retval = mysql_query($sql, $db);
			if (!$retval) echo 'TwitterUsers::Add('.$id.'): '.mysql_error($db)."\n\n";
			return $retval;
		}
	}
