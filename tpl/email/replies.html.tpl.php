<html>
	<head>
		<meta content="text/html; charset => utf-8" http-equiv="Content-Type" />
		<title>You have <?php echo (count($replies) == 1 ? 'a new reply' : 'new replies'); ?> on Twitter</title>
	</head>
	<body>
		<p>Hiya <?php echo htmlentities($user['screen_name']); ?>,</p>
		<p>You've had the following repl<?php echo (count($replies) == 1 ? 'y' : 'ies'); ?> on Twitter. <strong>Please do not reply to this email.</strong> To respond to <?php echo (count($replies) == 1 ? 'this tweet' : 'these tweets'); ?> please use the <a href="http://twitter.com/">Twitter website</a> or a <a href="http://www.tweetdeck.com/">desktop</a> <a href="http://www.twhirl.org/">client</a>.</p>
<?php
	foreach ($replies as $reply)
	{
		$url = 'http://twitter.com/'.rawurlencode($reply['data']['user']['screen_name']).'/statuses/'.rawurlencode($reply['tweet_id']);
?>
		<div style="margin-top:1em;">
			<div>
				<a href="<?php echo $url; ?>"><img src="<?php echo $reply['data']['user']['profile_image_url']; ?>" width="48" height="48" align="left" border="0" style="margin-right: 0.25em;" /></a>
				<strong><a href="http://twitter.com/<?php echo rawurlencode($reply['data']['user']['screen_name']); ?>" style="text-decoration:none;"><?php echo htmlentities($reply['data']['user']['screen_name']); ?></a></strong> <?php echo HTMLifyTweet($reply['data']['text']); ?><br />
				<div style="font-size: small; font-style: italic;"><a href="<?php echo $url; ?>" style="text-decoration:none;"><?php echo htmlentities(ucfirst(InDays(strtotime($reply['data']['created_at'])))); ?></a></div>
			</div><div style="clear:both;"></div>
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