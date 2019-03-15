<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	header('Content-type: text/plain');
	
	if(Config::isCron() !== true) {
		die('Unauthorized');
	}
	
	$HISTORY_LIMIT = 60*60*24;
	
	// Sensors
	$sensorsQueryResult = MySQL::executeQuery("SELECT `id`,`name`,`temp`,`adjustment`,`updated`,`error`,`observed` FROM `sensors` ORDER BY `name` ASC;", array());
	if($sensorsQueryResult->successful() !== true) {
    	die ($sensorsQueryResult->getError());
    }
    
    $sensorRows = $sensorsQueryResult->getAllRows();
    
    $sensors = array();
    $observedSensors = array();
    
    for($i=0; $i<count($sensorRows); $i++) {
    	$sensor = $sensorRows[$i];
		$warn = ($sensor['error']) || (time() > $sensor['updated']+Config::SENSOR_DELAY_LIMIT);
		$observed = $sensor['observed'] === '1';
		$adjustedTemp = $sensor['temp'] + $sensor['adjustment'];
                $sensor['rawTemp'] = $sensor['temp'];
                $sensor['temp'] = $adjustedTemp;

		$a = array('id'=>$sensor['id'], 'temp'=>$sensor['temp']);
		
		if ($warn === false) {
			array_push($sensors, $a);
			if ($observed) {
				array_push($observedSensors, $a);
			}
		}
    }
    
    $avgTemp = -1;
    if (count($observedSensors) > 0) {
    	$avgTemp = 0.0;
	    foreach($observedSensors as $sensor) {
		    $avgTemp += $sensor['temp'];
	    }
		$avgTemp = intval(round($avgTemp / count($observedSensors)));
    }
    
    $status = 'off';
    
    // Controller
    $controllerQueryResult = MySQL::executeQuery("SELECT `status`,`updated`,`error` FROM `controllers` ORDER BY `id` ASC LIMIT 1;", array());
	if($controllerQueryResult->successful() !== true) {
    	die ($controllerQueryResult->getError());
    }
    
    if ($controllerQueryResult->getRowCount () > 0) {
	    $controller = $controllerQueryResult->getRow(0);
	    $warning = $controller['error'] || (time() > $controller['updated']+Config::SENSOR_DELAY_LIMIT);
	    
	    if ($warning === false) {
		    $status = $controller['status'];
	    }
    }
    
	$insertResult = MySQL::executeNonQuery("INSERT INTO `history` (`date`,`average`,`sensors`,`status`) VALUES ({0},{1},'{2}','{3}');", array(time(), $avgTemp, json_encode($sensors), $status));
	if($insertResult->successful() !== true) {
    	die ($insertResult->getError());
    }
    
    $deleteOldEntriesResult = MySQL::executeNonQuery("DELETE FROM `history` WHERE `date` < {0};", array(time() - $HISTORY_LIMIT));
	if($deleteOldEntriesResult->successful() !== true) {
    	die ($deleteOldEntriesResult->getError());
    }
    
    die ('Success');
?>
