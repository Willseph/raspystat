<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	
	$min = intval(Utils::v('min'));
	$max = intval(Utils::v('max'));
	$fan = Utils::v('fan');
	$heat = Utils::v('heat');
	$cool = Utils::v('cool');
	$format = Utils::v('format');
	$theme = Utils::v('theme');
	
	if($min < 1 || $min>150000) {
	    RestUtils::sendBadResponse('"Min" value must be between 1 and 150000.');
		exit;
	}
	
	if($max < 1 || $max>150000) {
	    RestUtils::sendBadResponse('"Max" value must be between 1 and 150000.');
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
	
	if(!($format === 'C' || $format === 'F')) {
	    RestUtils::sendBadResponse('"Format" value must be C or F.');
		exit;
	}
	
	if(!($theme === 'dark' || $theme === 'light')) {
	    RestUtils::sendBadResponse('"Theme" value must be "light" or "dark".');
		exit;
	}
	
	$currentSettingsQueryResult = MySQL::executeQuery("SELECT * FROM `settings` LIMIT 1;", array());
	if($currentSettingsQueryResult->successful() !== true) {
    	RaspystatError::setError($currentSettingsQueryResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    
    if($currentSettingsQueryResult->getRowCount() < 1) {
	    RestUtils::sendBadResponse('Settings not found. Check DB integrity.', 'nosettings');
		exit;
    }
    
    $currentSettings = $currentSettingsQueryResult->getRow(0);
    
    $tempThreshold = 1000;
    
    // Keeping min/max from overlapping
    if ($max <= $min+$tempThreshold) {
	    // What is changing right now, max or min? Whichever it is, the other one should be changing.
	    
	    // Max is changing, so bump down min
	    if ($currentSettings['max'] !== $max) {
		    $min = $max - $tempThreshold;
	    } 
	    
	    // Min is changing, so bump up max
	    else if ($currentSettings['min'] !== $min) {
			$max = $min + $tempThreshold;
	    }
	    // Nothing is changing and it's still overlapped? Just lower the min. 
	    else {
		    $min = $max - $tempThreshold;
	    }
    }
    
    // Changing set temps if changing temp format
    if ($currentSettings['format'] === 'F' && $format === 'C') {
	    // F to C
	    
	    $min = intval(1000 * (($min/1000.0) - 32.0) * (5.0/9.0)); // Conversion
	    $max = intval(1000 * (($max/1000.0) - 32.0) * (5.0/9.0));
	    
	    $min = intval(1000 * (floor(($min * 2.0)/1000.0) / 2.0)); // Rounding to 500
	    $max = intval(1000 * (ceil(($max * 2.0)/1000.0) / 2.0));
	    
	    // Ensuring 1 degree difference
	    while ($max - $min < $tempThreshold)
		    $min -= 500;
	    
    } else if ($currentSettings['format'] === 'C' && $format === 'F') {
	    // C to F
	    
	    $min = intval(1000 * (($min/1000.0) * (9.0/5.0) + 32.0)); // Conversion
	    $max = intval(1000 * (($max/1000.0) * (9.0/5.0) + 32.0));
	    
	    $min = intval(1000 * (floor(($min * 2.0)/1000.0) / 2.0)); // Rounding to 500
	    $max = intval(1000 * (ceil(($max * 2.0)/1000.0) / 2.0));
	    
	    // Ensuring 1 degree difference
	    while ($max - $min < $tempThreshold)
		    $min -= 500;
    }
	
	$settingsUpdateResult = MySQL::executeNonQuery("UPDATE `settings` SET `min`={0}, `max`={1}, `fan`={2}, `heat`={3}, `cool`={4}, `format`='{5}', `theme`='{6}';", array($min, $max, $fan, $heat, $cool, $format, $theme));
	if($settingsUpdateResult->successful() !== true) {
    	RaspystatError::setError($settingsUpdateResult->getError());
		RestUtils::sendBadResponse(RaspystatError::getError());
		exit;
    }
    
    include_once(sprintf('%s/settings.php', __DIR__));
?>