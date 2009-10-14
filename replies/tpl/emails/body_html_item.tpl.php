<?php $url = 'http://twitter.com/'.rawurlencode($from_user).'/statuses/'.rawurlencode($id); ?>
<div style="margin-bottom: 1em;">
	<div>
		<a href="<?php echo $url; ?>"><img src="<?php echo $profile_image_url; ?>" width="48" height="48" align="left" border="0" style="margin-right: 0.25em;" /></a>
		<strong><a href="http://twitter.com/<?php echo rawurlencode($from_user); ?>" style="text-decoration:none;"><?php echo htmlentities($from_user); ?></a></strong> <?php echo Twitter::HTMLifyTweet($text); ?><br />
		<div style="font-size: small; font-style: italic;"><a href="<?php echo $url; ?>" style="text-decoration:none;"><?php echo htmlentities(ucfirst(InDays(strtotime($created_at)))); ?></a></div>
	</div><div style="clear:both;"></div>
</div>
