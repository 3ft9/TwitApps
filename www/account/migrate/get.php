<?php
	require dirname(__FILE__).'/../../../fx.php';
	header('Content-Type: text/plain');
	$user = array('screen_name' => $_GET['u']);
	$services = User::GetServices($user);
	echo json_encode($services);
	//var_dump($services);
