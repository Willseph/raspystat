<?php	
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	
	$observe = Utils::v('observe');
	$sensor = Utils::v('sensor');
	
	if($observe === null) {
	    RestUtils::sendBadResponse('Missing "observe" value.');
		exit;
	}
	
	if(!($observe === '0' || $observe === '1')) {
	    RestUtils::sendBadResponse('"Observe" value must be 0 or 1.');
		exit;
	}
	
	if(!$sensor) {
	    RestUtils::sendBadResponse('Missing "sensor" value.');
		exit;
	}
	
	// Checking if sensor ID is legit
	$verifySensorQueryResult = MySQL::executeScalar("SELECT COUNT(*) as `c` FROM `sensors` WHERE `id`={0};", array($sensor));
	if($verifySensorQueryResult->successful() !== true) {
    	RaspystatError::setError($verifySensorQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    $count = $verifySensorQueryResult->getScalar();
    $count = $count['c'];
    
    if($count < 1) {
	    RestUtils::sendBadResponse(sprintf('No sensor found with id "%s"', $sensor));
		exit;
    }
    
    // Updating DB
    $updateResult = MySQL::executeNonQuery("UPDATE `sensors` SET `observed`={0} WHERE `id`={1};", array($observe, $sensor));
    if($updateResult->successful() !== true) {
    	RaspystatError::setError($updateResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    
    include_once(sprintf('%s/sensors.php', __DIR__));
?>