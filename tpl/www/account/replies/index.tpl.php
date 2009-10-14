<?php TPL('www/account/loggedin', array('user' => $user)); ?>
<h1><a href="/account/">Your Account</a> &raquo; Replies</h1>
<p id="message" style="margin-top: 1em; font-style: italic;"><?php echo $message; ?></p>
<form method="post" action="/account/replies/">
	<table class="configform">
		<tr>
			<th valign="top"><label for="status_field">Status</label></th>
			<td valign="top"><select id="status_field" name="status" size="1">
				<option value="active"<?php if (@$replies['status'] == 'active') { echo ' selected'; } ?>>Active</option>
				<option value="inactive"<?php if (@$replies['status'] == 'inactive') { echo ' selected'; } ?>>Inactive</option>
			</select></td>
		</tr>
		<tr>
			<th valign="top"><label for="email_field">Email</label></th>
			<td valign="top"><input id="email_field" name="email" value="<?php echo htmlentities(@$replies['email']); ?>" style="width: 15em;" /></td>
		</tr>
		<tr>
			<th valign="top"><label for="min_interval_field">Frequency</label></th>
			<td valign="top"><select id="min_interval_field" name="min_interval" size="1">
<?php if (!in_array($replies['min_interval'], array(0, 5, 10, 15, 30, 60, 120, 180, 360, 720, 1440))) { ?>
				<option value="<?php echo htmlentities($replies['min_interval']); ?>" selected><?php echo htmlentities(@$replies['min_interval']); ?></option>
<?php } ?>
				<option value="0"<?php if (@$replies['min_interval'] == 0) { echo ' selected'; } ?>>Immediate</option>
				<option value="5"<?php if (@$replies['min_interval'] == 5) { echo ' selected'; } ?>>5 minutes</option>
				<option value="10"<?php if (@$replies['min_interval'] == 10) { echo ' selected'; } ?>>10 minutes</option>
				<option value="15"<?php if (@$replies['min_interval'] == 15) { echo ' selected'; } ?>>15 minutes</option>
				<option value="30"<?php if (@$replies['min_interval'] == 30) { echo ' selected'; } ?>>30 minutes</option>
				<option value="60"<?php if (@$replies['min_interval'] == 60) { echo ' selected'; } ?>>1 hour</option>
				<option value="120"<?php if (@$replies['min_interval'] == 120) { echo ' selected'; } ?>>2 hours</option>
				<option value="180"<?php if (@$replies['min_interval'] == 180) { echo ' selected'; } ?>>3 hours</option>
				<option value="360"<?php if (@$replies['min_interval'] == 360) { echo ' selected'; } ?>>6 hours</option>
				<option value="720"<?php if (@$replies['min_interval'] == 720) { echo ' selected'; } ?>>12 hours</option>
				<option value="1440"<?php if (@$replies['min_interval'] == 1440) { echo ' selected'; } ?>>24 hours</option>
			</select></td>
		</tr>
		<tr>
			<th valign="top"><label for="max_queued_field">Max Queued</label></th>
			<td valign="top"><select id="max_queued_field" name="max_queued" size="1">
<?php if (!in_array(@$replies['max_queued'], array(1, 2, 5, 10, 15, 20, 25, 50, 100))) { ?>
				<option value="<?php echo htmlentities(@$replies['max_queued'] == 0 ? 1 : @$replies['max_queued']); ?>" selected><?php echo htmlentities(@$replies['max_queued']); ?></option>
<?php } ?>
				<option value="1"<?php if (@$replies['max_queued'] == 1) { echo ' selected'; } ?>>1</option>
				<option value="2"<?php if (@$replies['max_queued'] == 5) { echo ' selected'; } ?>>2</option>
				<option value="5"<?php if (@$replies['max_queued'] == 10) { echo ' selected'; } ?>>5</option>
				<option value="10"<?php if (@$replies['max_queued'] == 15) { echo ' selected'; } ?>>10</option>
				<option value="15"<?php if (@$replies['max_queued'] == 30) { echo ' selected'; } ?>>15</option>
				<option value="20"<?php if (@$replies['max_queued'] == 60) { echo ' selected'; } ?>>20</option>
				<option value="25"<?php if (@$replies['max_queued'] == 120) { echo ' selected'; } ?>>25</option>
				<option value="50"<?php if (@$replies['max_queued'] == 180) { echo ' selected'; } ?>>50</option>
				<option value="100"<?php if (@$replies['max_queued'] == 360) { echo ' selected'; } ?>>100</option>
			</select></td>
		</tr>
		<tr>
			<th valign="top">Scope</th>
			<td valign="top">
				<input type="checkbox" id="replies_only_field" name="replies_only"<?php if (isset($replies['replies_only']) and $replies['replies_only'] == 1) { echo ' checked'; } ?> />
				<label for="replies_only_field">Replies only</label><br />
				
				<input type="checkbox" id="ignore_self_field" name="ignore_self"<?php if (isset($replies['ignore_self']) and $replies['ignore_self'] == 1) { echo ' checked'; } ?> />
				<label for="ignore_self_field">Ignore tweets from @<?php echo htmlentities($user['screen_name']); ?></label><br />
			</select></td>
		</tr>
		<tr>
			<th colspan="2" valign="top" style="text-align:center;"><input type="submit" name="save" value="Save changes" /></th>
		</tr>
	</table>
</form>

<h2>Notes</h2>
<ul class="spaced">
	<li>
		<strong>Email</strong>: Only minimal validation is performed so make sure this is correct.
	</li>
	<li>
		<strong>Frequency</strong>: Set how often do you want notifications. Note that immediate notification actually means <em>"as quickly as we can"</em> due to restrictions placed on the Twitter API.
	</li>
	<li>
		<strong>Max Queued</strong>: If you get this many replies before your scheduled email is due (as set by <em>frequency</em>) we'll send you an email anyway since you're clearly popular at the moment!
	</li>
	<li>
		<strong>Replies only</strong>: Check this box to only include tweets that start with @<?php echo htmlentities($user['screen_name']); ?>.
	</li>
	<li>
		<strong>Ignore tweets from @<?php echo htmlentities($user['screen_name']); ?></strong>: Check this box to ignore tweets from yourself.
	</li>
</ul>
<script type="text/javascript"><!--
function byeByeMessage(){document.getElementById('message').innerHTML='&nbsp;';}setTimeout('byeByeMessage();',5000);
--></script>
