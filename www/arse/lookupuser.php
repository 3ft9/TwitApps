<?php
	require dirname(__FILE__).'/common.php';

	if (empty($_REQUEST['username']))
	{
		Redirect('/arse/');
	}
	
	$u = $_REQUEST['username'];
	
	Layout('Admin', 'account');
?>
<h1>
	<div style="float:right;"><a href="/arse/">&laquo; Back</a></div>
	User: <span style="font-weight:normal;"><?php echo htmlentities($u); ?></span>
</h1>
<?php
	$user = false;
	$db_shared = GetDB('shared');
	$sql = 'select * from users where screen_name = "'.mysql_real_escape_string($u, $db_shared).'"';
	$query = mysql_query($sql, $db_shared);
	
	if (mysql_num_rows($query) == 1)
	{
		$user = mysql_fetch_assoc($query);
	}
	mysql_free_result($query);
?>
<h2>Main account</h2>
<?php
	if ($user === false)
	{
?>
<p>User not found.</p>
<?php
	}
	else
	{
?>
<table>
<?php
		$fields = array(
			'id',
			'registered_at',
			'updated_at',
			'name',
			'description',
			'location',
			'url',
			'statuses_count',
			'followers_count',
			'friends_count',
			'favourites_count',
			'created_at',
			'protected',
		);
		foreach ($fields as $key)
		{
?>
	<tr>
		<th valign="top" style="text-align:left;font-weight:bold;padding:2px 6px;text-align:right;"><?php echo htmlentities($key); ?></th>
		<td valign="top" style="padding:2px 6px;"><?php
			if (substr($key, -3) == '_at' and is_numeric($user[$key]))
			{
				if ($user[$key] == 0)
				{
					echo '<em>Never</em>';
				}
				else
				{
					echo date('F jS, Y \a\t H:i:s', $user[$key]);
				}
			}
			elseif (substr($key, -4) == '_url')
			{
				echo '<div style="max-width: 400px; max-height: 100px; overflow: auto;">'.htmlentities($user[$key]).'<br /><img src="'.htmlentities($user[$key]).'" /></div>';
			}
			elseif (is_numeric($user[$key]))
			{
				echo number_format($user[$key]);
			}
			else
			{
				echo htmlentities($user[$key]);
			}
		?></td>
	</tr>
<?php
		}
?>
</table>
<?php
	}
	
	$user = false;
	
	$db_replies = GetDB('replies');
	$sql = 'select * from users where username = "'.mysql_real_escape_string($u, $db_replies).'"';
	$query = mysql_query($sql, $db_replies);
	
	if (mysql_num_rows($query) == 1)
	{
		$user = mysql_fetch_assoc($query);
	}
	mysql_free_result($query);
?>
<h2>Replies</h2>
<?php
	if ($user === false)
	{
?>
<p>User not found.</p>
<?php
	}
	else
	{
?>
<table>
<?php
		foreach (array_keys($user) as $key)
		{
?>
	<tr>
		<th valign="top" style="text-align:left;font-weight:bold;padding:2px 6px;text-align:right;"><?php echo htmlentities($key); ?></th>
		<td valign="top" style="padding:2px 6px;"><?php
			if (substr($key, -3) == '_at' and is_numeric($user[$key]))
			{
				if ($user[$key] == 0)
				{
					echo '<em>Never</em>';
				}
				else
				{
					echo date('F jS, Y \a\t H:i:s', $user[$key]);
				}
			}
			elseif (substr($key, -4) == '_url')
			{
				echo '<div style="max-width: 400px; max-height: 100px; overflow: auto;">'.htmlentities($user[$key]).'<br /><img src="'.htmlentities($user[$key]).'" /></div>';
			}
			elseif (is_numeric($user[$key]))
			{
				echo number_format($user[$key]);
			}
			else
			{
				echo htmlentities($user[$key]);
			}
		?></td>
	</tr>
<?php
		}
?>
</table>
<?php
	}
	
	$user = false;
	
	$db_follows = GetDB('follows');
	$sql = 'select * from users where username = "'.mysql_real_escape_string($u, $db_follows).'"';
	$query = mysql_query($sql, $db_follows);
	
	if (mysql_num_rows($query) == 1)
	{
		$user = mysql_fetch_assoc($query);
	}
	mysql_free_result($query);
?>
<h2>Follows</h2>
<?php
	if ($user === false)
	{
?>
<p>User not found.</p>
<?php
	}
	else
	{
?>
<table>
<?php
		foreach (array_keys($user) as $key)
		{
?>
	<tr>
		<th valign="top" style="text-align:left;font-weight:bold;padding:2px 6px;text-align:right;"><?php echo htmlentities($key); ?></th>
		<td valign="top" style="padding:2px 6px;"><?php
			if (substr($key, -3) == '_at' and is_numeric($user[$key]))
			{
				if ($user[$key] == 0)
				{
					echo '<em>Never</em>';
				}
				else
				{
					echo date('F jS, Y \a\t H:i:s', $user[$key]);
				}
			}
			elseif (substr($key, -10) == '_image_url')
			{
				echo '<div style="max-width: 400px; max-height: 100px; overflow: auto;">'.htmlentities($user[$key]).'<br /><img src="'.htmlentities($user[$key]).'" /></div>';
			}
			elseif (is_numeric($user[$key]))
			{
				echo number_format($user[$key]);
			}
			else
			{
				echo htmlentities($user[$key]);
			}
		?></td>
	</tr>
<?php
		}
?>
</table>
<?php
	}
	
