<?php
	class AuthUtils
	{	
		const SALTLENGTH = 32;
		
		public static function generateHash($password, $salt)
		{
			return hash('sha512', $salt.$password);
		}
		
		public static function newSalt()
		{
			return Utils::randString(static::SALTLENGTH);
		}
		
		public static function getAuthTokenDetails($token)
		{
			if (Utils::isNullOrWhitespace($token))
		    	return null;
		    
		    $tokenLookupResult = MySQL::executeQuery("SELECT * FROM `tokens` WHERE `tokens`.`token` = '{0}' AND `tokens`.`active` = 1 LIMIT 1;", array($token));
			if (!$tokenLookupResult->successful()) 
				return null;
			
			$tokenRows = $tokenLookupResult->getAllRows();
			if(count($tokenRows) < 1)
				return null;
			
			$tokenRow = $tokenRows[0];
			if(!$tokenRow)
				return null;
			
			return $tokenRow;
		}
		
		public static function userForToken($token)
		{
			$token = static::getAuthTokenDetails($token);
			if (!$token || $token['active'] !== '1') {
				return null;
			}
			
			$user = $token['user'];
			
			$userLookupResult = MySQL::executeQuery("SELECT * FROM `users` WHERE `user` = '{0}' LIMIT 1;", array($user));
			if (!$userLookupResult->successful()) 
				return null;
			
			if($userLookupResult->getRowCount() < 1) 
			    return null;
		    
		    return $userLookupResult->getRow(0);
		}
		
		public static function isAdminToken($token)
		{
			$user = static::userForToken($token);
			if (!$user) {
				return false;
			}
			
			return isset($user['admin']) && $user['admin'] === '1';
		}
		
		public static function insertNewToken($user)
		{
			$token = Utils::randString(64);
		    $result = MySQL::executeNonQuery("INSERT INTO `tokens` (`user`, `token`,`active`,`ip`,`date`) VALUES ('{0}','{1}', 1, '{2}', {3});", array($user, $token, Utils::ip(), time()));
		    if($result->successful() !== true) {
		    	RaspystatError::setError($result->getError());
			    return false;
		    }
			return $token;
		}
		
		public static function requireValidToken() 
		{
			if (Config::isLocal()) {
				return true;
			}
			
			$token = Utils::v('token');
			$sensor = Utils::v('sensor');
			$controller = Utils::v('controller');
			
			if (!$token && !$sensor && !$controller) {
				RestUtils::sendBadResponse('Missing token.', 'auth');
				exit;
			}
			
			$token = AuthUtils::getAuthTokenDetails($token);
			$sensor = Utils::getSensorDetailsFromSecret($sensor);
			$controller = Utils::getControllerDetailsFromSecret($controller);
			
			if (!$token && !$sensor && !$controller) {
				RestUtils::sendBadResponse('Invalid token.', 'auth');
				exit;
			}
		}
		
		public static function findUserByName($user)
		{
			$user = ''.strtolower(trim($user));
			if(!$user) return null;
			
			$userLookupResult = MySQL::executeQuery("SELECT * FROM `users` WHERE `user`='{0}' LIMIT 1;", array($user));
			if($userLookupResult->successful() !== true) {
		    	RaspystatError::setError($userLookupResult->getError());
			    return null;
		    }
		    
		    if($userLookupResult->getRowCount() < 1) {
			    return null;
		    }
		    
		    return $userLookupResult->getRow(0);
		}
		
		public static function passwordCorrectForUser($user, $password)
		{
			$user = static::findUserByName($user);
			if(!$user)
				return false;
			
			return static::generateHash($password, $user['salt']) === $user['hash'];
		}
	}
?>