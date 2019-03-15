<?php
	class RaspystatError
	{
		private static $error = '';
		
		public static function setError($e) {
			static::$error = $e;
		}
		
		public static function getError() {
			return static::$error;
		}
	}
?>