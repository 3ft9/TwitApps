<?php
	require dirname(__FILE__).'/fx.php';

	if ($user_id === false)
	{
?>
<form method="post" action="/follows/manage/">
	<input type="text" style="width:15em;" name="username" />
	<input type="submit" value="Login" />
</form>
<?php
		return;
	}
	
	// Obtain a request object for the request we want to make
	$req = new OAuthRequester($server['server_uri'].'/direct_messages.json', 'GET', array());

	// Sign the request, perform a curl request and return the results, throws OAuthException exception on an error
	try
	{
		$result = $req->doRequest($user_id);
	}
	catch (OAuthException $e)
	{
		header('Location: /follows/manage/register');
		exit;
	}

	$page = 'follows';
	$title = 'Follows';
	require dirname(__FILE__).'/../../header.tpl.php';
	
	echo '<h1>Direct Messages</h1>';
	echo '<p>Since this is currently just an OAuth test this just shows the results of a call to get your direct messages. This is an authenticated request so it serves to prove the point but has nothing to do with this application.</p>';

	$result = json_decode($result['body']);

	if (!$result) die('Invalid response');
	
	foreach ($result as $msg)
	{
		echo '<div style="clear:both; margin-bottom: 0.5em;">';
		echo '<img style="float:left;margin-right:0.5em;margin-bottom:0.25em;" src="'.htmlentities($msg->sender->profile_image_url).'" />';
		echo Twitter::HTMLifyTweet($msg->text).'<br />';
		echo '<small>';
		echo ucfirst(Twitter::FormatStamp(strtotime($msg->created_at)));
		echo '</small>';
		echo '<div style="clear:both;"></div></div>';
	}

	require dirname(__FILE__).'/../../footer.tpl.php';
