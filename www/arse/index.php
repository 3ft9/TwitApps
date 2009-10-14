<?php
	require dirname(__FILE__).'/common.php';

	Layout('Admin', 'account');
?>
<h1>Administration</h1>
<form method="get" action="/arse/lookupuser">
	<table style="margin: 1.5em;">
		<tr>
			<td style="padding: 5px;">Lookup user</td>
			<td style="padding: 5px;"><input type="text" name="username" value="" /></td>
			<td style="padding: 5px;"><input type="submit" value="Get user info" /></t>
		</tr>
	</table>
</form>
