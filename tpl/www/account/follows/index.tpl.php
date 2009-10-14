<?php TPL('www/account/loggedin', array('user' => $user)); ?>
<h1><a href="/account/">Your Account</a> &raquo; Follows</h1>
<p id="message" style="margin-top: 1em; font-style: italic;"><?php echo $message; ?></p>
<form method="post" action="/account/follows/">
	<table class="configform">
		<tr>
			<th valign="top"><label for="status_field">Status</label></th>
			<td valign="top"><select id="status_field" name="status" size="1">
				<option value="active"<?php if (@$follows['status'] == 'active') { echo ' selected'; } ?>>Active</option>
				<option value="inactive"<?php if (@$follows['status'] == 'inactive') { echo ' selected'; } ?>>Inactive</option>
			</select></td>
		</tr>
		<tr>
			<th valign="top"><label for="email_field">Email</label></th>
			<td valign="top"><input id="email_field" name="email" value="<?php echo htmlentities(@$follows['email']); ?>" style="width: 15em;" /></td>
		</tr>
		<tr>
			<th valign="top"><label for="frequency_field">Frequency</label></th>
			<td valign="top">
				<select id="frequency_field" name="frequency" size="1" onchange="document.getElementById('when_weekly').style.display=this.value=='weekly'?'inline':'none';document.getElementById('when_monthly').style.display=this.value=='monthly'?'inline':'none';">
					<option value="daily"<?php if (@$follows['frequency'] == 'daily') { echo ' selected'; } ?>>Daily</option>
					<option value="weekly"<?php if (@$follows['frequency'] == 'weekly') { echo ' selected'; } ?>>Weekly</option>
					<option value="monthly"<?php if (@$follows['frequency'] == 'monthly') { echo ' selected'; } ?>>Monthly</option>
				</select>
				at
				<select id="hour_field" name="hour" size="1">
<?php for ($hour = 0; $hour < 24; $hour++) { ?>
					<option value="<?php echo $hour; ?>"<?php if (@$follows['hour'] == $hour) { echo ' selected'; } ?>><?php echo ($hour == 0 ? 'Midnight' : ($hour < 13 ? $hour.'am' : ($hour-12).'pm')); ?></option>
<?php } ?>
				</select> UTC
				<span id="when_weekly"<?php if (@$follows['frequency'] != 'weekly') { echo ' style="display: none;"'; } ?>>
					on
					<select name="when_weekly">
						<option value="monday"<?php if (@$follows['when'] == 'monday') { echo ' selected'; } ?>>Mondays</option>
						<option value="tuesday"<?php if (@$follows['when'] == 'tuesday') { echo ' selected'; } ?>>Tuesdays</option>
						<option value="wednesday"<?php if (@$follows['when'] == 'wednesday') { echo ' selected'; } ?>>Wednesdays</option>
						<option value="thursday"<?php if (@$follows['when'] == 'thursday') { echo ' selected'; } ?>>Thursdays</option>
						<option value="friday"<?php if (@$follows['when'] == 'friday') { echo ' selected'; } ?>>Fridays</option>
						<option value="saturday"<?php if (@$follows['when'] == 'saturday') { echo ' selected'; } ?>>Saturdays</option>
						<option value="sunday"<?php if (@$follows['when'] == 'sunday') { echo ' selected'; } ?>>Sundays</option>
					</select>
				</span>
				<span id="when_monthly"<?php if (@$follows['frequency'] != 'monthly') { echo ' style="display: none;"'; } ?>>
					on the
					<select name="when_monthly">
<?php for ($day = 1; $day < 29; $day++) { ?>
						<option value="<?php echo $day; ?>"<?php if (@$follows['when'] == $day) { echo ' selected'; } ?>><?php echo date('jS', strtotime('2010-01-'.($day < 10 ? '0' : '').$day)); ?></option>
<?php } ?>
					</select>
				</span>
			</td>
		</tr>
		<tr>
			<th valign="top"><label for="notification_type_field">Notification&nbsp;type</label></th>
			<td valign="top">
				<select id="notification_type_field" name="notification_type" size="1" onchange="document.getElementById('postinfo').style.display=this.value=='email'?'none':'block';">
					<option value="email"<?php if (strlen(@$follows['post_url']) == 0) { echo ' selected'; } ?>>Email</option>
					<option value="post"<?php if (strlen(@$follows['post_url']) > 0) { echo ' selected'; } ?>>HTTP Post</option>
				</select>
				<div id="postinfo"<?php if (strlen(@$follows['post_url']) == 0) { echo ' style="display:none;"'; } ?>>
					<table class="configform" style="margin-left: 0; margin-bottom: 0;">
						<tr>
							<td valign="top" style="padding-left: 0; border: 0;"><label for="post_format_field">Format</label></td>
							<td valign="top" style="padding-left: 0; border: 0;"><select id="post_format_field" name="post_format" size="1">
								<option value="json"<?php if (@$follows['post_format'] == 'json') { echo ' selected'; } ?>>JSON</option>
							</select></td>
						</tr>
						<tr>
							<td valign="top" style="padding-left: 0; border: 0;"><label for="post_url_field">URL</label></td>
							<td valign="top" style="padding-left: 0; border: 0;"><input id="post_url_field" name="post_url" value="<?php echo htmlentities(@$follows['post_url']); ?>" style="width: 15em;" /></td>
						</tr>
					</table>
				</div>
			</td>
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
		<strong>Frequency</strong>: Set when you want to get your notification of changes.
	</li>
	<li>
		<strong>Notification type</strong>: By default your notifications will be delivered by email.
		If you are a developer and would like the changes POSTed to a URL in JSON you can configure that here.
	</li>
</ul>
<script type="text/javascript"><!--
function byeByeMessage(){document.getElementById('message').innerHTML='&nbsp;';}setTimeout('byeByeMessage();',5000);
--></script>
