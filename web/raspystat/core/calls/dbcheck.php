<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	if(!Config::$DbConfig) {
		RestUtils::sendBadResponse('DB not set up', 'nodb');
		exit;
	}
	
	$usersQueryResult = MySQL::executeScalar("SELECT COUNT(*) FROM `users`;", array());
	if($usersQueryResult->successful() !== true) {
		RestUtils::sendBadResponse('DB not set up', 'nodb');
		exit;
    }
	
	RestUtils::sendGoodResponse('DB is set up.');
?>