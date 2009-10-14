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
			$replies = array();
			$replies['status'] = (trim($_POST['status']) == 'active' ? 'active' : 'inactive');
			$replies['email'] = trim($_POST['email']);
			$replies['min_interval'] = intval($_POST['min_interval']) * 60;
			$replies['max_queued'] = intval($_POST['max_queued']);
			$replies['replies_only'] = (empty($_POST['replies_only']) ? 0 : 1);
			$replies['ignore_self'] = (empty($_POST['ignore_self']) ? 0 : 1);
			
			$result = User::UpdateService('replies', $data['user']['id'], $replies);
			
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

	$data['replies'] = User::GetServices($data['user']['id'], 'replies');

	Layout('Replies', 'account');

	if ($data['replies'] === false)
	{
		TPL('www/account/replies/install', $data);
	}
	else
	{
		$data['replies']['min_interval'] = $data['replies']['min_interval'] / 60;

		TPL('www/account/replies/index', $data);
	}

//	echo '<pre>';
//	var_dump($data['replies']);
//	echo '</pre>';
