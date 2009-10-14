<?php TPL('www/account/loggedin', array('user' => $user)); ?>
<h1><a href="/account/">Your Account</a> &raquo; Install Follows</h1>
<p id="message" style="margin-top: 1em; font-style: italic;"><?php echo $message; ?></p>
<p style="text-align: center;">
	You have not yet installed the Follows service on your account.
</p>
<div style="text-align: center; margin-top: 2em; margin-bottom: 2em;">
	<form method="post" action="/account/follows/install" style="font-size: 2em;">
		Email: <input type="text" id="email_field" name="email" style="width: 10em;" /><br />
		<br />
		<input type="submit" value="Install Follows..." />
	</form>
</div>
<script type="text/javascript"><!--
function byeByeMessage(){document.getElementById('message').innerHTML='&nbsp;';}setTimeout('byeByeMessage();',5000);
document.getElementById('email_field').focus();
--></script>
