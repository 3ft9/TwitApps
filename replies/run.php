<?php
	define('DEBUG', 1);

	require dirname(__FILE__).'/../fx.php';
	
	$endtime = time() + 3600;
	
	while (time() < $endtime)
	{
		if (!Replies::Run())
		{
			sleep(10);
		}
	}
