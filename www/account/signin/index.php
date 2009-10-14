<?php
	require dirname(__FILE__).'/../../../fx.php';
	
	User::cReset();

	Layout('Sign In', 'account');
	TPL('www/account/signin');
