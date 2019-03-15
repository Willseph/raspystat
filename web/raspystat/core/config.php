<?php
	if(Config::DEBUG === true) {
		ini_set('display_errors', 1);
		error_reporting(~0);
	}
	
	require_once(sprintf('%s/lib/auth-utils.php',	__DIR__));
	require_once(sprintf('%s/lib/error.php',		__DIR__));
	require_once(sprintf('%s/lib/mysql.php',		__DIR__));
	require_once(sprintf('%s/lib/rest-utils.php',	__DIR__));
	require_once(sprintf('%s/lib/mysql.php',		__DIR__));
	require_once(sprintf('%s/lib/utils.php',		__DIR__));
	require_once(sprintf('%s/lib/uuid.php',			__DIR__));
	
	class Config
	{
		const DEBUG = false;
		const FPATH = 'raspystat';
		
		const CONTROLLER_DELAY_LIMIT = 60;
		const SENSOR_DELAY_LIMIT = 60;
		
		const IFTTT_KEY = ""; //TODO: Get from external file
		const TLS_IGNORED = false; //TODO: Get from external file
		const ALLOW_LAN_GUESTS = false;
				
		public static $DbLocation;
		public static $DbConfig;
		
		public static function run () {
			date_default_timezone_set('UTC');
			header('Access-Control-Allow-Origin: *');
			
			if (static::isSecure() !== true) {
				RestUtils::sendBadResponse('Not running securely.');
				return;
			}
			
			static::getDbConfig();
		}
		
		public static function isCron () {
			return isset($_SERVER['argv']) && count($_SERVER['argv']) >= 2 && $_SERVER['argv'][1] === 'CRON';
		}
		
		public static function isLan() {
			return strrpos($_SERVER['HTTP_HOST'], '192.168') === 0;
		}

		public static function isSelf() {
			return $_SERVER['HTTP_HOST'] === '127.0.0.1';
		}
		
		public static function isLocal () {
			return static::isCron() === true || static::isSelf() === true || (static::ALLOW_LAN_GUESTS && static::isLan() === true);
		}
		
		public static function isSecure () {
			return static::isLocal() || $_SERVER["HTTPS"] === "on" || static::TLS_IGNORED;
		}
		
		static function getDbConfig() {
			static::$DbLocation = sprintf('%s/../../../raspystat-db.json', __DIR__);
			static::$DbConfig = null;
			
			if(file_exists(static::$DbLocation)) {
				$json = trim(file_get_contents(static::$DbLocation));
				if($json) {
					$dbArray = json_decode($json, true);
					if(
						$dbArray 
						&& isset($dbArray['dbname']) && trim($dbArray['dbname']) 
						&& isset($dbArray['dbuser']) && trim($dbArray['dbuser']) 
						&& isset($dbArray['dbpass']) && trim($dbArray['dbpass']) 
						&& isset($dbArray['dbhost']) && trim($dbArray['dbhost'])
					) {
						static::$DbConfig = $dbArray;
					}
				}
			}
		}
	}
	
	Config::run();
?>