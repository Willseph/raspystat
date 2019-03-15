<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	$key = Utils::v('key');
	if ($key !== Config::IFTTT_KEY) {
		AuthUtils::requireValidToken();
	}
	
	$isAdmin = AuthUtils::isAdminToken(Utils::v('token'));
	
	$secret = Utils::v('secret');

	$sensorsQueryResult = null;
	if ($secret) {
		$sensorsQueryResult = MySQL::executeQuery("SELECT `id`,`name`,`observed`,`temp`,`adjustment`,`updated`,`icon`,`error`,`ver`,`needsupdate`,`secret` FROM `sensors` WHERE `secret` = '{0}';", array($secret));
	} else {
		$sensorsQueryResult = MySQL::executeQuery("SELECT `id`,`name`,`observed`,`temp`,`adjustment`,`updated`,`icon`,`error`,`ver`,`needsupdate`,`secret` FROM `sensors` ORDER BY `name` ASC;", array());
	}
	if($sensorsQueryResult->successful() !== true) {
    	RaspystatError::setError($sensorsQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    
    $sensors = $sensorsQueryResult->getAllRows();
    for($i=0; $i<count($sensors); $i++) {
		$adjustedTemp = $sensors[$i]['temp'] + $sensors[$i]['adjustment'];
		$sensors[$i]['rawTemp'] = $sensors[$i]['temp'];
		$sensors[$i]['temp'] = $adjustedTemp;
		$sensors[$i]['warning'] = ($sensors[$i]['error']) || (time() > $sensors[$i]['updated']+Config::SENSOR_DELAY_LIMIT);
		$sensors[$i]['observed'] = intval($sensors[$i]['observed'])!=0;
		$sensors[$i]['needsupdate'] = $sensors[$i]['needsupdate'] === '1';

		if ($isAdmin !== true) {
			$sensors[$i]['secret'] = 'unauthorized';
			$sensors[$i]['ver'] = '0,unauthorized,0';
		}
    }
    
    RestUtils::sendGoodResponse("Success", array('sensors'=>$sensors));
?>
