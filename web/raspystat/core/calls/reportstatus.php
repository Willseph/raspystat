<?php
	require_once(sprintf('%s/../config.php', __DIR__));

	$secret = Utils::v('secret');
	$fan = Utils::v('fan');
	$heat = Utils::v('heat');
	$cool = Utils::v('cool');
	$resting = Utils::v('resting');
	$error = Utils::v('error');
	$ver = Utils::v('ver');

	if(!$secret) {
	    RestUtils::sendBadResponse('Missing controller secret.');
		exit;
	}
	
	if(!($fan === '0' || $fan === '1')) {
	    RestUtils::sendBadResponse('"Fan" value must be 0 or 1.');
		exit;
	}
	
	if(!($heat === '0' || $heat === '1')) {
	    RestUtils::sendBadResponse('"Heat" value must be 0 or 1.');
		exit;
	}
	
	if(!($cool === '0' || $cool === '1')) {
	    RestUtils::sendBadResponse('"Cool" value must be 0 or 1.');
		exit;
	}
	
	if(!($resting === '0' || $resting === '1')) {
	    RestUtils::sendBadResponse('"Resting" value must be 0 or 1.');
		exit;
	}

	$controller = Utils::getControllerDetailsFromSecret($secret);

	if(!$controller) {
	    RestUtils::sendBadResponse('Invalid controller secret.');
		exit;
	}

	$id = $controller['id'];
	
	$status = 'off';
	if ($cool === '1') $status = 'cool';
	else if ($heat === '1') $status = 'heat';
	else if ($fan === '1') $status = 'fan';

	$updateResult = MySQL::executeNonQuery("UPDATE `controllers` SET `status`='{0}', `resting`={1}, `updated`={2}, `error`='{3}', `ver`='{4}' WHERE `id`={5};", array($status, $resting, time(), $error, $ver, $id));
	if($updateResult->successful() !== true) {
		RaspystatError::setError($updateResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
	}

	RestUtils::sendGoodResponse('Controller status updated.');
?>
