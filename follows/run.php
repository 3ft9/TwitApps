<?php
	define('DEBUG', 5);

	require dirname(__FILE__).'/../fx.php';
	
	$endtime = time() + 3600;
	
	while (time() < $endtime)
	{
		if (!Follows::Run())
		{
			sleep(10);
		}
	}
