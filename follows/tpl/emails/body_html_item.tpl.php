<div style="margin-bottom: 1em;">
	<div>
		<a href="http://twitter.com/<?php echo rawurlencode($username); ?>"><img src="<?php echo $profile_image_url; ?>" width="48" height="48" align="left" border="0" style="margin-right: 0.25em;" /></a>
		<strong><a href="http://twitter.com/<?php echo rawurlencode($username); ?>" style="text-decoration:none;"><?php echo htmlentities($username); ?></a></strong> <?php
	if ($followed_at > $last_email_at)
	{
		echo 'started following you on '.date('F jS', $followed_at);
		if ($unfollowed_at > $last_email_at)
		{
			echo ' and ';
		}
	}
	if ($unfollowed_at > $last_email_at)
	{
		echo 'stopped following you on '.date('F jS', $unfollowed_at);
	}
	echo '.<br /><span style="font-size: 0.9em;">';
	echo 'Location: '.$location.'<br />';
	if ($protected == 1) echo 'Protected account with ';
	echo number_format($followers_count).' follower'.($followers_count == 1 ? '' : 's').', following '.number_format($following_count);
	echo '</span>';
?>
	</div><div style="clear:both;"></div>
</div>
