<?php
    
    class MailUpException extends Exception {
        
        var $statusCode;
        
        function __construct($inStatusCode, $inMessage) {
            parent::__construct($inMessage);
            $this->statusCode = $inStatusCode;
        }
        
        function getStatusCode() {
            return $this->statusCode;
        }
        
        function setStatusCode($inStatusCode) {
            $this->statusCode = $inStatusCode;
        }
    }

    class MailUpClient {
        
        var $logonEndpoint;
        var $authorizationEndpoint;
        var $tokenEndpoint;
        var $consoleEndpoint;
        var $mailstatisticsEndpoint;
        
        var $clientId;
        var $clientSecret;
        var $callbackUri;
        var $accessToken;
        var $refreshToken;
        
        function getLogonEndpoint() {
            return $this->logonEndpoint;
        }
        
        function setLogonEndpoint($inLogonEndpoint) {
            $this->logonEndpoint = $inLogonEndpoint;
        }
        
        function getAuthorizationEndpoint() {
            return $this->authorizationEndpoint;
        }
        
        function setAuthorizationEndpoint($inAuthorizationEndpoint) {
            $this->authorizationEndpoint = $inAuthorizationEndpoint;
        }
        
        function getTokenEndpoint() {
            return $this->tokenEndpoint;
        }
        
        function setTokenEndpoint($inTokenEndpoint) {
            $this->tokenEndpoint = $inTokenEndpoint;
        }
        
        function getConsoleEndpoint() {
            return $this->consoleEndpoint;
        }
        
        function setConsoleEndpoint($inConsoleEndpoint) {
            $this->consoleEndpoint = $inConsoleEndpoint;
        }
        
        function getMailstatisticsEndpoint() {
            return $this->mailstatisticsEndpoint;
        }
        
        function setMailstatisticsEndpoint($inMailstatisticsEndpoint) {
            $this->mailstatisticsEndpoint = $inMailstatisticsEndpoint;
        }
        
        function getClientId() {
            return $this->clientId;
        }
        
        function setClientId($inClientId) {
            $this->clientId = $inClientId;
        }
        
        function getClientSecret() {
            return $this->clientSecret;
        }
        
        function setClientSecret($inClientSecret) {
            $this->clientSecret = $inClientSecret;
        }
        
        function getCallbackUri() {
            return $this->callbackUri;
        }
        
        function setCallbackUri($inCallbackUri) {
            $this->callbackUri = $inCallbackUri;
        }
        
        function getAccessToken() {
            return $this->accessToken;
        }
        
        function setAccessToken($inAccessToken) {
            $this->accessToken = $inAccessToken;
        }
        
        function getRefreshToken() {
            return $this->refreshToken;
        }
        
        function setRefreshToken($inRefreshToken) {
            $this->refreshToken = $inRefreshToken;
        }
        
        function __construct($inClientId, $inClientSecret, $inCallbackUri) {
            $this->logonEndpoint = "https://services.mailup.com/Authorization/OAuth/LogOn";
            $this->authorizationEndpoint = "https://services.mailup.com/Authorization/OAuth/Authorization";
            $this->tokenEndpoint = "https://services.mailup.com/Authorization/OAuth/Token";
            $this->consoleEndpoint = "https://services.mailup.com/API/v1.1/Rest/ConsoleService.svc";
            $this->mailstatisticsEndpoint = "https://services.mailup.com/API/v1.1/Rest/MailStatisticsService.svc";
            
            $this->clientId = $inClientId;
            $this->clientSecret = $inClientSecret;
            $this->callbackUri = $inCallbackUri;
            $this->loadToken();
        }
        
        function getLogOnUri() {
            $url = $this->getLogonEndpoint() . "?client_id=" . $this->getClientId() . "&client_secret=" . $this->getClientSecret() . "&response_type=code&redirect_uri=" . $this->getCallbackUri();
            return $url;
        }
        
        function logOn() {
            $url = $this->getLogOnUri();
            header("Location: " . $url);
        }
        function logOnWithPassword($username, $password) {
        	return $this->retreiveAccessToken($username, $password);
		}
        function retreiveAccessTokenWithCode($code) {
            $url = $this->getTokenEndpoint() . "?code=" . $code . "&grant_type=authorization_code";
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            $result = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($code != 200 && $code != 302) throw new MailUpException($code, "Authorization error");
            
            $result = json_decode($result);
            
            $this->accessToken = $result->access_token;
            $this->refreshToken = $result->refresh_token;
            
            $this->saveToken();
            
            return $this->accessToken;
        }
        
        function retreiveAccessToken($login, $password) {
            $url = $this->getTokenEndpoint();
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, 1);

			$body = "grant_type=password&username=".$login."&password=".$password."&client_id=".$this->clientId."&client_secret=".$this->clientSecret;
		
			$headers = array();
			$headers["Content-length"] = strlen($body);
			$headers["Accept"] = "application/json";
			$headers["Authorization"] = "Basic ".base64_encode($this->clientId.":".$this->clientSecret);
			
			curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
			
			$result = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($code != 200 && $code != 302) throw new MailUpException($code, "Authorization error");
            
            $result = json_decode($result);
            
            $this->accessToken = $result->access_token;
            $this->refreshToken = $result->refresh_token;
            
            $this->saveToken();
            
            return $this->accessToken;
        }
        
        function refreshAccessToken() {
            $url = $this->getTokenEndpoint();
            $body = "client_id=" . $this->clientId . "&client_secret=" . $this->clientSecret . "&refresh_token=" . $this->refreshToken . "&grant_type=refresh_token";
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/x-www-form-urlencoded", "Content-length: " . strlen($body)));
            $result = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($code != 200 && $code != 302) throw new MailUpException($code, "Authorization error");
            
            $result = json_decode($result);
            
            $this->accessToken = $result->access_token;
            $this->refreshToken = $result->refresh_token;
            
            $this->saveToken();
            
            return $this->accessToken;
        }
        
        function callMethod($url, $verb, $body = "", $contentType = "JSON", $refresh = true) {
            $temp = null;
            $cType = ($contentType == "XML" ? "application/xml" : "application/json");
            
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            if ($verb == "POST") {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: " . $cType, "Content-length: " . strlen($body), "Accept: " . $cType, "Authorization: Bearer " . $this->accessToken));
            } else if ($verb == "PUT") {
                curl_setopt($curl, CURLOPT_PUT, 1);
                $temp = tmpfile();
                fwrite($temp, $body);
                fseek($temp, 0);
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: " . $cType, "Content-length: " . strlen($body), "Accept: " . $cType, "Authorization: Bearer " . $this->accessToken));
                curl_setopt($curl, CURLOPT_INFILE, $temp);
                curl_setopt($curl, CURLOPT_INFILESIZE, strlen($body));
            } else if ($verb == "DELETE") {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: " . $cType, "Content-length: 0", "Accept: " . $cType, "Authorization: Bearer " . $this->accessToken));
            } else {
                curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: " . $cType, "Content-length: 0", "Accept: " . $cType, "Authorization: Bearer " . $this->accessToken));
            }
            
            $result = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            
            if ($temp != null) fclose($temp);
            curl_close($curl);
            
            if ($code == 401 && $refresh == true) {
                $this->refreshAccessToken();
                return $this->callMethod($url, $verb, $body, $contentType, false);
            } else if ($code == 401 && $refresh == false) throw new MailUpException($code, "Authorization error");
             else if ($code != 200 && $code != 302) throw new MailUpException($code, "Unknown error");
            
            return $result;
        }
        
        function loadToken() {
            if (isset($_COOKIE["access_token"])) $this->accessToken = $_COOKIE["access_token"];
            if (isset($_COOKIE["refresh_token"])) $this->refreshToken = $_COOKIE["refresh_token"];
        }
        
        function saveToken() {
            setcookie("access_token", $this->accessToken, time()+60*60*24*30);
            setcookie("refresh_token", $this->refreshToken, time()+60*60*24*30);
        }
    }
    
?>
