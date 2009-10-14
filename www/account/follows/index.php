<?php
	require dirname(__FILE__).'/../../../fx.php';

	// Are we logged in?
	if (strlen(User::cGet('id')) == 0 or strlen(User::cGet('oat')) == 0 or strlen(User::cGet('oats')) == 0)
	{
		Redirect('/account/signin');
	}
	
	$data = array('user' => User::Get(), 'message' => '&nbsp;');
	
	if (!empty($_POST['save']))
	{
		$pattern = '/^([a-z0-9\+])(([-a-z0-9\+._])*([a-z0-9\+]))*\@([a-z0-9])(([a-z0-9-])*([a-z0-9]))+(\.([a-z0-9])([-a-z0-9_-])?([a-z0-9])+)+$/i';
		if (!preg_match($pattern, $_POST['email']))
		{
			$data['message'] = '<span style="color:red;">Save failed: Invalid email address; please try again.</span>';
		}
		else
		{
			$follows = array();
			$follows['status'] = (trim($_POST['status']) == 'active' ? 'active' : 'inactive');
			$follows['email'] = trim($_POST['email']);
			$follows['frequency'] = in_array($_POST['frequency'], array('daily', 'weekly', 'monthly')) ? strtolower($_POST['frequency']) : 'daily';
			$follows['hour'] = intval($_POST['hour']);

			if ($follows['hour'] < 0)
				$follows['hour'] = 0;
			elseif ($follows['hour'] > 23)
				$follows['hour'] = 23;

			switch ($follows['frequency'])
			{
				default:
				case 'daily':
					$follows['when'] = '';
					break;
				
				case 'weekly':
					$_POST['when_weekly'] = strtolower($_POST['when_weekly']);
					if (!in_array(strtolower($_POST['when_weekly']), array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')))
						$_POST['when_weekly'] = 'monday';
					$follows['when'] = $_POST['when_weekly'];
					break;
				
				case 'monthly':
					$_POST['when_monthly'] = intval($_POST['when_monthly']);
					if ($_POST['when_monthly'] < 1)
						$_POST['when_monthly'] = 1;
					elseif ($_POST['when_monthly'] > 28)
						$_POST['when_monthly'] = 28;
					$follows['when'] = $_POST['when_monthly'];
					break;
			}
			switch ($_POST['notification_type'])
			{
				case 'email':
					$follows['post_url'] = '';
					$follows['post_format'] = '';
					break;

				case 'post':
					$follows['post_url'] = $_POST['post_url'];
					$follows['post_format'] = in_array($_POST['post_format'], array('json')) ? $_POST['post_format'] : 'json';
					break;
			}
			
			$result = User::UpdateService('follows', $data['user'], $follows);
			
			if ($result === true)
			{
				$data['message'] = 'Changes saved <strong>successfully</strong>';
			}
			else
			{
				$data['message'] = '<span style="color:red;">Save failed: '.$result.'</span>';
			}
		}
	}

	$data['follows'] = User::GetServices($data['user'], 'follows');

	Layout('Follows', 'account');
	
	if ($data['follows'] === false)
	{
		TPL('account/follows/install', $data);
	}
	else
	{
		TPL('account/follows/index', $data);
	}

//	echo '<pre>';
//	var_dump($data['follows']);
//	echo '</pre>';
