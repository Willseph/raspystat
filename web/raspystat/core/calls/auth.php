<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	if (Config::isLocal()) {
		RestUtils::sendGoodResponse('Running locally, no need for password.');
		exit;
	}
	
	$user = strtolower(trim(Utils::v('user')));
	if (!$user) {
		RestUtils::sendBadResponse('Invalid or missing username.');
		exit;
	}
	
	$pass = Utils::v('pass');
	if (!trim($pass)) {
		RestUtils::sendBadResponse('Invalid or missing password.');
		exit;
	}
	
	$userRow = AuthUtils::findUserByName($user);
	if(!$userRow) {
		RestUtils::sendBadResponse('Incorrect user/password.');
		exit;
	}
	
	if(AuthUtils::passwordCorrectForUser($user, $pass) !== true) {
		RestUtils::sendBadResponse('Incorrect user/password.');
		exit;
	}
	
	$newToken = AuthUtils::insertNewToken($user);
	if(!$newToken) {
		RestUtils::sendBadResponse(RaspystatError::getError());
	}
	
	RestUtils::sendGoodResponse('Authentication successful', array('token'=>$newToken, 'user'=>$user));
?>