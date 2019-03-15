<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	$isAdmin = AuthUtils::isAdminToken(Utils::v('token'));
	
	if ($isAdmin !== true) {
	    RestUtils::sendBadResponse('Unauthorized.', 'auth');
		exit;
	}
	
	$name = Utils::v('name');
	if(!$name) {
	    RestUtils::sendBadResponse('Missing or invalid name. Must be between 1 and 64 characters.');
		exit;
	}
	$name = trim($name);
	if(strlen($name) < 1 || strlen($name) > 64) {
	    RestUtils::sendBadResponse('Missing or invalid name. Must be between 1 and 64 characters.');
		exit;
	}
	
	$secret = Utils::randString(10, 'abcdefghjkmnpqrstuvwxy3456789');
	
	$insertResult = MySQL::executeNonQuery("INSERT INTO `sensors` (`secret`,`name`,`observed`,`icon`) VALUES ('{0}', '{1}', 0, 'bed2');", array($secret, $name));
	if($insertResult->successful() !== true) {
		RaspystatError::setError($updateResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
	}
	
	RestUtils::sendGoodResponse('Sensor added.');
?>