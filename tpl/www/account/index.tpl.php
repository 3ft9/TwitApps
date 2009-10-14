<?php TPL('www/account/loggedin', array('user' => $user)); ?>
<h1>Your Account</h1>
<?php if (!empty($_GET['migrated']) and $_GET['migrated'] == 'yes') { ?>
<p style="margin-top: 0.75em; font-weight: bold; color: green;">Your account has been successfully migrated from v1. Please note that this may cause us to miss some replies and/or follower changes. We apologise for this but unfortunately it cannot be helped.</p>
<?php } ?>
<table class="services">
<?php
	foreach ($services as $service => $config)
	{
?>
	<tr>
		<td><a href="/<?php echo htmlentities($service); ?>/" title="<?php echo htmlentities(ucfirst($service)); ?> description"><?php echo htmlentities(ucfirst($service)); ?></a></td>
		<td style="font-style: italic;"><?php echo ($config === false ? '<span style="color:#bebebe;">-</span>' : ($config['status'] == 'inactive' ? 'Inactive' : 'Active')); ?></td>
		<td><a href="/account/<?php echo htmlentities($service); ?>/" title="<?php echo $config === false ? 'Install' : 'Manage'; ?> your <?php echo htmlentities(ucfirst($service)); ?> service"><?php echo $config === false ? 'Install' : 'Manage'; ?>...</a></td>
	</tr>
	<tr>
		<td class="info" colspan="3">
<?php
		if ($config === false)
		{
			echo 'Click <em>Install...</em> to install '.htmlentities(ucfirst($service)).' in your account.';
		}
		else
		{
			switch ($service)
			{
				case 'replies':
					echo 'Emailed to <em>'.htmlentities($config['email']).'</em> ';
					if ($config['min_interval'] == 0)
					{
						echo 'as soon as possible.';
					}
					else
					{
						echo 'every ',($config['min_interval'] / 60).' minutes ';
						echo 'or when '.$config['max_queued'].' repl'.($config['max_queued'] == 1 ? 'y' : 'ies').' have been received.';
					}
					break;
					
				case 'follows':
					echo ucfirst($config['frequency']).' email to <em>'.htmlentities($config['email']).'</em> ';
					$hour = 'at or shortly after '.($config['hour'] > 12 ? ($config['hour']-12).'pm' : ($config['hour'] == 0 ? 'midnight' : $config['hour'].($config['hour'] == 12 ? 'pm' : 'am'))).' UTC';
					switch ($config['frequency'])
					{
						case 'daily':
							echo $hour.'.';
							break;

						case 'weekly':
							echo 'on '.ucfirst($config['when']).' '.$hour.'.';
							break;

						case 'monthly':
							echo 'on the '.date('jS', strtotime('2010-01-'.(strlen($config['when']) == 1 ? '0' : ''))).' '.$hour;
							break;
					}
					break;
			}
		}
?>
		</td>
	</tr>
<?php
	}
?>
</table>
