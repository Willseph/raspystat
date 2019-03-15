<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	$secret = Utils::v('secret');
	$temp = Utils::v('temp');
	$error = Utils::v('error');
	$ver = Utils::v('ver');
	
	if(!$secret) {
	    RestUtils::sendBadResponse('Missing sensor secret.');
		exit;
	}
	
	if(!$temp) {
		$temp = 0;
	}
	
	if(intval($temp) < 1) {
		$temp = 0;
	}
	
	$sensor = Utils::getSensorDetailsFromSecret($secret);
	
	if(!$sensor) {
	    RestUtils::sendBadResponse('Invalid sensor secret.');
		exit;
	}
	
	if ($error === 'sensor-error') {
		$temp = 0;
	}
	
	$id = $sensor['id'];
	
	$updateResult = MySQL::executeNonQuery("UPDATE `sensors` SET `temp`={0}, `updated`={1}, `error`='{2}', `ver`='{3}' WHERE `id`={4};", array(intval($temp), time(), $error, $ver, $id));
	if($updateResult->successful() !== true) {
		RaspystatError::setError($updateResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
	}
	
	RestUtils::sendGoodResponse('Temperature updated.');
?>