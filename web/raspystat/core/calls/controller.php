<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	
	$controllerQueryResult = MySQL::executeQuery("SELECT `status`,`resting`,`updated`,`error`,`ver`,`needsupdate` FROM `controllers` ORDER BY `id` ASC LIMIT 1;", array());
	if($controllerQueryResult->successful() !== true) {
    	RaspystatError::setError($controllerQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    
    if ($controllerQueryResult->getRowCount () < 1) {
		RestUtils::sendBadResponse('No controllers set up in database.');
		exit;
    }
    
    $controller = $controllerQueryResult->getRow(0);
    $controller['resting'] = $controller['resting'] === '1';
    $controller['warning'] = $controller['error'] || (time() > $controller['updated']+Config::SENSOR_DELAY_LIMIT);
    $controller['needsupdate'] = $controller['needsupdate'] === '1';
    
    RestUtils::sendGoodResponse("Success", array('controller'=>$controller));
?>