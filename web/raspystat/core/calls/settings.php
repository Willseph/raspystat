<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	
	$settingsQueryResult = MySQL::executeQuery("SELECT `min`,`max`,`fan`,`heat`,`cool`,`format`,`theme`,`historyperiod` FROM `settings` LIMIT 1;", array());
	if($settingsQueryResult->successful() !== true) {
    	RaspystatError::setError($settingsQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    
    if($settingsQueryResult->getRowCount() < 1) {
	    RestUtils::sendBadResponse('Settings not found. Check DB integrity.', 'nosettings');
		exit;
    }
    
    $settings = $settingsQueryResult->getRow(0);
    $settings['fan'] = $settings['fan']==='1';
    $settings['heat'] = $settings['heat']==='1';
    $settings['cool'] = $settings['cool']==='1';
    
    RestUtils::sendGoodResponse("Success", array('settings'=>$settings));
?>