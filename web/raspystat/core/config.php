<?php
	if(Config::DEBUG === true) {
		ini_set('display_errors', 1);
		error_reporting(~0);
	}

	require_once(sprintf('%s/lib/auth-utils.php',	__DIR__));
	require_once(sprintf('%s/lib/error.php',	__DIR__));
	require_once(sprintf('%s/lib/mysql.php',	__DIR__));
	require_once(sprintf('%s/lib/rest-utils.php',	__DIR__));
	require_once(sprintf('%s/lib/mysql.php',	__DIR__));
	require_once(sprintf('%s/lib/utils.php',	__DIR__));
	require_once(sprintf('%s/lib/uuid.php',		__DIR__));

	class Config
	{
		const DEBUG = false;
		const FPATH = 'raspystat';
		const CONFIG_JSON_NAME = 'raspystat-config.json';

		const CONTROLLER_DELAY_LIMIT = 60;
		const SENSOR_DELAY_LIMIT = 60;

		public static $ConfigOptions = null;

		public static function run () {
			date_default_timezone_set('UTC');
			header('Access-Control-Allow-Origin: *');

			static::getConfigSettings();

			if (static::isSecure() !== true) {
				RestUtils::sendBadResponse('Not running securely.');
			}
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
			return static::isCron() === true || static::isSelf() === true || (static::$ConfigOptions['allowLanGuests'] === true && static::isLan() === true);
		}

		public static function isSecure () {
			$x = static::isLocal() || $_SERVER["HTTPS"] === "on" || static::$ConfigOptions['tlsIgnored'] === true;
			return $x;
		}

		static function getConfigSettings() {
			$configLocation = sprintf('%s/../../../%s', __DIR__, static::CONFIG_JSON_NAME);

			if(file_exists($configLocation)) {
				$json = trim(file_get_contents($configLocation));
				if($json) {
					static::$ConfigOptions = json_decode($json, true);
				}
			}
		}
	}

	Config::run();
?>
