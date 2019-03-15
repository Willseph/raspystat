<?php
class Utils
{
    //Formats a string similar to the .NET string.Format method.
    public static function formatString($format, $callback)
    {
        return preg_replace_callback('/{((0)|([1-9][0-9]*))}/', $callback, $format);
    }
    
    //Returns true if a provided string is null or composed only of whitespace.
    public static function isNullOrWhitespace($string)
    {
        if ($string === null)
            return true;
        if (!$string)
            return true;
        if (trim($string) == '')
            return true;
        return false;
    }
    
    //Returns true if a provided value is a non-zero, positive integer.
    public static function isPositiveNumber($id)
    {
        if (!isset($id) || !$id || $id == null)
            return false;
        return preg_match('/^((0)|([1-9][0-9]*))$/', $id);
    }
    
    //Generates a cryptographically-secure random hexadecimal string of the provided length.
    public static function randomHexString($length)
    {
        return substr(bin2hex(file_get_contents('/dev/random', 0, null, -1, $length / 2 + 1)), 0, $length);
    }

    public static function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
    {
        $str = '';
        $count = strlen($charset);
        while ($length--) 
            $str .= $charset[mt_rand(0, $count-1)];
        return $str;
    }
    public static function currentDir($https = false)
    {
        $url = $_SERVER['REQUEST_URI']; //returns the current URL
        $parts = explode('/',$url);
        $dir = $_SERVER['SERVER_NAME'];
        for ($i = 0; $i < count($parts) - 1; $i++) {
         $dir .= $parts[$i] . "/";
        }
        return ($https?"https://":"http://").$dir;
    }
    public static function v ($key) {
	    if(isset($_REQUEST[$key]) !== true) return null;
	    return $_REQUEST[$key];
    }
    
    public static function ip() {
	    $ipaddress = '';
	    if ($_SERVER['HTTP_CLIENT_IP'])
	        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
	        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	    else if($_SERVER['HTTP_X_FORWARDED'])
	        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	    else if($_SERVER['HTTP_FORWARDED_FOR'])
	        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	    else if($_SERVER['HTTP_FORWARDED'])
	        $ipaddress = $_SERVER['HTTP_FORWARDED'];
	    else if($_SERVER['REMOTE_ADDR'])
	        $ipaddress = $_SERVER['REMOTE_ADDR'];
	    else
	        $ipaddress = 'UNKNOWN';
	    return $ipaddress;
	}
	
	public static function getControllerDetailsFromSecret($secret) {
		return static::getDetailsFromSecret ($secret, 'controllers');
	}
	
	public static function getSensorDetailsFromSecret($secret) {
		return static::getDetailsFromSecret ($secret, 'sensors');
	}
	
	private static function getDetailsFromSecret($secret, $table) {
		$getQueryResult = MySQL::executeQuery("SELECT * FROM `{0}` WHERE `secret`='{1}' LIMIT 1;", array($table, $secret));
		if($getQueryResult->successful() !== true) {
			RaspystatError::setError($getQueryResult->getError());
			RestUtils::sendBadResponse(RaspystatError::getError());
			exit;
		}
		
		if($getQueryResult->getRowCount() < 1) {
			return null;
	    }
	    
	    return $getQueryResult->getRow(0);
	}
	
	public static function formatTemp($rawTemp, $format) {
		$tempC = $rawTemp/1000.0;
		if (strtolower($format) === 'f') {
			return $tempC * (9.0/5.0) + 32.0;
			
		} else if (strtolower($format) === 'c') {
			return $tempC;
		}
		
		return 0;
	}
}
?>