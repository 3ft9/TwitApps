<html>
	<head>
		<meta content="text/html; charset => utf-8" http-equiv="Content-Type" />
		<title><?php echo htmlentities($subject); ?></title>
	</head>
	<body>
		<p>Hiya <?php echo htmlentities($u['screen_name']); ?>,</p>
		<p>The following changes have occurred in your follower list<?php if (intval($user['last_email_at']) != 0) { echo ' since '.date('g:ia \o\n F jS', $user['last_email_at']); } ?>...</p>
<?php
	if (count($follows['new']) > 0)
	{
?>
		<div style="margin: 0.75em 0 0.5em 0; font-size: 1.25em; font-weight: bold;">New followers</div>
		<div style="margin-left: 1em;">
<?php
		foreach ($follows['new'] as $follower)
		{
			$fu = ((isset($users[$follower['follower_id']]) and $users[$follower['follower_id']]) ? $users[$follower['follower_id']] : false);
?>
			<div style="margin-bottom: 1em;">
				<div>
<?php
			if ($fu === false)
			{
?>
					User ID <?php echo htmlentities($follower['follower_id']); ?> started following you but when we tried to get their details the request failed.
<?php
			}
			else
			{
?>
					<a href="http://twitter.com/<?php echo rawurlencode($fu['screen_name']); ?>"><img src="<?php echo $fu['profile_image_url']; ?>" width="48" height="48" align="left" border="0" style="margin-right: 0.25em;" /></a>
					<strong><a href="http://twitter.com/<?php echo rawurlencode($fu['screen_name']); ?>" style="text-decoration:none;"><?php echo htmlentities($fu['screen_name']); ?></a></strong>
					started following you on <?php echo date('F jS', $follower['started_at']); ?>.<br />
					<span style="font-size: 0.9em;">
						Location: <?php echo htmlentities($fu['location']); ?><br />
						<?php if ($fu['protected']) { ?>Protected account with <?php } echo number_format($fu['followers_count']); ?> follower<?php echo ($fu['followers_count'] == 1 ? '' : 's'); ?>, following <?php echo number_format($u['friends_count']); ?>.
					</span>
<?php
			}
?>
				</div><div style="clear:both;"></div>
			</div>
<?php
		}
?>
		</div>
<?php
	}

	if (count($follows['old']) > 0)
	{
?>
		<div style="margin: 0.75em 0 0.5em 0; font-size: 1.25em; font-weight: bold;">Ex-followers</div>
		<div style="margin-left: 1em;">
<?php
		foreach ($follows['old'] as $follower)
		{
			$fu = ((isset($users[$follower['follower_id']]) and $users[$follower['follower_id']]) ? $users[$follower['follower_id']] : false);
?>
			<div style="margin-bottom: 1em;">
				<div>
<?php
			if ($fu === false)
			{
?>
					User ID <?php echo htmlentities($follower['follower_id']); ?> stopped following you but when we tried to get their details the request failed.
<?php
			}
			else
			{
?>
					<a href="http://twitter.com/<?php echo rawurlencode($fu['screen_name']); ?>"><img src="<?php echo $fu['profile_image_url']; ?>" width="48" height="48" align="left" border="0" style="margin-right: 0.25em;" /></a>
					<strong><a href="http://twitter.com/<?php echo rawurlencode($fu['screen_name']); ?>" style="text-decoration:none;"><?php echo htmlentities($fu['screen_name']); ?></a></strong>
					stopped following you on <?php echo date('F jS', $follower['stopped_at']); ?>.<br />
					<span style="font-size: 0.9em;">
						Location: <?php echo htmlentities($fu['location']); ?><br />
						<?php if ($fu['protected']) { ?>Protected account with <?php } echo number_format($fu['followers_count']); ?> follower<?php echo ($fu['followers_count'] == 1 ? '' : 's'); ?>, following <?php echo number_format($fu['friends_count']); ?>.
					</span>
<?php
			}
?>
				</div><div style="clear:both;"></div>
			</div>
<?php
		}
?>
		</div>
<?php
	}
?>
		<p style="font-size: 90%;">To stop these emails log in at http://twitapps.com/account/ and change the status of the Follows service.</p>
		<p style="margin-top:1em;">
			--&nbsp;<br />
			The TwitApps Team<br />
			<a href="http://twitapps.com/">http://twitapps.com/</a>
		</p>
	</body>
</html>