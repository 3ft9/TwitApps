Hiya <?php echo $user['screen_name']; ?>,

You've had the following repl<?php echo (count($replies) == 1 ? 'y' : 'ies'); ?> on Twitter. Please *do not* reply to this email. To respond to <?php echo (count($replies) == 1 ? 'this tweet' : 'these tweets'); ?> please use the Twitter website or a desktop client.

<?php
	foreach ($replies as $reply)
	{
?>
@<?php echo $reply['data']['user']['screen_name']; ?>: <?php echo $reply['data']['text']; ?>

<?php echo ucfirst(InDays(strtotime($reply['data']['created_at']))); ?>

http://twitter.com/<?php echo rawurlencode($reply['data']['user']['screen_name']); ?>/statuses/<?php echo rawurlencode($reply['tweet_id']); ?>


<?php
	}
?>
To stop these emails log in at http://twitapps.com/account/ and change the status of the Follows service.

-- 
The TwitApps Team
http://twitapps.com/