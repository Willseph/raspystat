<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	
	$time = intval(Utils::v('time'));
	if ($time < 1) {
		RestUtils::sendBadResponse('"Time" value must be at least 1.');
		exit;
	}
	
	$sensorsQueryResult = MySQL::executeQuery("SELECT * FROM `sensors`;", array());
	if($sensorsQueryResult->successful() !== true) {
		RaspystatError::setError($sensorsQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
	}
	
	$sensorRows = $sensorsQueryResult->getAllRows();
	$sensorNames = array();
	foreach ($sensorRows as $sensorRow) {
		$sensorNames[$sensorRow['id']] = $sensorRow['name'];
	}
	
	$historyQueryResult = MySQL::executeQuery("SELECT * FROM `history` WHERE `date` >= {0} ORDER BY `date` ASC;", array(time() - $time));
	if($historyQueryResult->successful() !== true) {
		RaspystatError::setError($historyQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
	}
	
	$history = array('sensors'=>array(), 'avg'=>array());
	
	// TODO populate $sensors
	$historyRows = $historyQueryResult->getAllRows();
	foreach($historyRows as $historyRow) {
		$date = $historyRow['date']*1000;
		$avg = $historyRow['average'];
		$status = $historyRow['status'];
		$rowSensors = json_decode($historyRow['sensors'], true);
		
		if ($avg > 1) {
			array_push($history['avg'], array ('x'=>$date, 'y'=>$avg, 's'=>$status));
		}
		
		foreach ($rowSensors as $rowSensor) {
			if ($rowSensor['temp'] <= 1) {
				continue;
			}
			
			if(isset($sensorNames[$rowSensor['id']]) !== true) {
				continue;
			}
			
			$rowSensorName = $sensorNames[$rowSensor['id']];
			if(isset($history['sensors'][$rowSensorName]) !== true) {
				$history['sensors'][$rowSensorName] = array();
			}
			
			$sensorEntry = array('x'=>$date, 'y'=>$rowSensor['temp']);
			array_push($history['sensors'][$rowSensorName], $sensorEntry);
		}
	}
	
	RestUtils::sendGoodResponse("Success", array('history'=>$history));
?>