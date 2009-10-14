<html>
	<head>
		<meta content="text/html; charset => utf-8" http-equiv="Content-Type" />
		<title>Follower changes on Twitter</title>
	</head>
	<body>
		<p>Hiya <?php echo htmlentities($username); ?>,</p>
		<p><strong>PLEASE NOTE</strong>: TwitApps is shutting down at the end of September. See the blog post for more details: <a href="http://3ft9.com/10-twitapps-shutting-down">http://3ft9.com/10-twitapps-shutting-down</a></p>
		<p>The following changes have occurred in your follower list<?php if (intval($last_email_at) != 0) { echo ' since '.date('h:ia \o\n F jS', $last_email_at); } ?>...</p>
