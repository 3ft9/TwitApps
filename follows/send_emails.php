<?php
	define('DEBUG', 9);

	require dirname(__FILE__).'/../fx.php';
	
	$endtime = time() + 3600;
	
	while (time() < $endtime)
	{
		if (!Follows::SendEmail())
		{
			sleep(10);
		}
	}
