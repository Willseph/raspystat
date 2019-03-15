<?php
	require_once(sprintf('%s/../config.php', __DIR__));
	
	AuthUtils::requireValidToken();
	RestUtils::sendGoodResponse('Token valid', $token);
?>