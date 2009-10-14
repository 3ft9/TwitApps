<?php
	echo $username;
	if ($followed_at > $last_email_at)
	{
		echo ' started following you on '.date('F jS', $followed_at);
		if ($unfollowed_at > $last_email_at) echo ' and';
	}
	if ($unfollowed_at > $last_email_at)
	{
		echo ' stopped following you on '.date('F jS', $followed_at);
	}
?>.
Location: <?php echo $location; ?>

<?php
	if ($protected == 1) echo 'Protected account with ';
	echo number_format($followers_count).' follower'.($followers_count == 1 ? '' : 's').', following '.number_format($following_count);
?>

http://twitter.com/<?php echo rawurlencode($username); ?>




