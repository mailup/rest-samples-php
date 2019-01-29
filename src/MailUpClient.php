<?php

require_once 'MailUpException.php';
require_once 'DataFilter.php';

class MailUpClient
{
    private $clientId;
    private $secretKey;
    private $api = array();
    private $accessToken;
    private $refreshToken;
    private $tokenTime;
    private $errorUrl;
    
    function __construct($auth, $api)
    {
        $this->clientId = $auth['client_id'];
        $this->secretKey = $auth['secret_key'];
        $this->api = $api;

        $this->loadToken();
    }

    public function getAccessToken()
    {
        return $this->accessToken;
    }

    public function getConsoleUrl()
    {
        return $this->api['console'];
    }

    public function getMailStatsUrl()
    {
        return $this->api['mail_stats'];
    }

    public function getErrorUrl()
    {
        return $this->errorUrl;
    }

    public function getTokenTime()
    {
        $time = $this->tokenTime;

        if (null !== $this->tokenTime) {
            $time = $this->tokenTime - time();
        }

        return $time;
    }
    
    public function logonByKey($callback)
    {
        $url = $this->api['logon'] . "?client_id=" . $this->clientId . "&client_secret=" . $this->secretKey . "&response_type=code&redirect_uri=" . $callback;
        header("Location: " . $url);
    }

    public function retrieveTokenByCode($code)
    {
        $url = $this->api['token'] . "?code=" . $code . "&grant_type=authorization_code";
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        // Return result as string to script
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        // Not verify the host certificate
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        // Not check name in the certificate
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $result = curl_exec($curl);
        $response_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($response_code !== 200 & $response_code !== 302) {
			$this->clearToken();
            throw new MailUpException($code, "Authorization error");
        }
        
        $result = json_decode($result);
        
        $this->saveToken($result->access_token, $result->refresh_token, $result->expires_in);
    }

    public function retrieveTokenByPassword($username, $password)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->api['token']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);

        $username = DataFilter::convertToString($username);
        $password = DataFilter::convertToString($password);

        $body = 'grant_type=password&username=' . $username . '&password=' . $password . '&client_id=' . $this->clientId . '&client_secret=' . $this->secretKey;
        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Content-length: " . strlen($body),
            "Accept: application/json",
            "Authorization: Basic " . base64_encode($this->clientId  . ':' . $this->secretKey)
        );
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        
        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($code !== 200 & $code !== 302) {
			$this->clearToken();
            throw new MailUpException($code, "Authorization error");
        }
        
        $result = json_decode($result);
        
        $this->saveToken($result->access_token, $result->refresh_token, $result->expires_in);
    }
    
    public function refreshToken()
    {
        $body = "client_id=" . $this->clientId . "&client_secret=" . $this->secretKey . "&refresh_token=" . $this->refreshToken . "&grant_type=refresh_token";
        $headers = array(
            "Content-type: application/x-www-form-urlencoded",
            "Content-length: " . strlen($body),
            "Accept: application/json"
        );
        
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->api['token']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($code != 200 && $code != 302) {
			$this->clearToken();
            throw new MailUpException($code, "Authorization error");
        }
        
        $result = json_decode($result);
        
        $this->saveToken($result->access_token, $result->refresh_token, $result->expires_in);
    }
    
    public function makeRequest($method, $content_type = "JSON", $url, $body = "", $refresh = true)
    {
        $temp_file = null;
        $content_type = ($content_type === "XML" ? "application/xml" : "application/json");
        $headers = array(
            "Content-type: " . $content_type,
            "Content-length: " . strlen($body),
            "Accept: " . $content_type,
            "Authorization: Bearer " . $this->accessToken
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        switch($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, true);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                break;
            case "PUT":
                $temp_file = tmpfile();
                fwrite($temp_file, $body);
                fseek($temp_file, 0);
                curl_setopt($curl, CURLOPT_PUT, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($curl, CURLOPT_INFILE, $temp_file);
                curl_setopt($curl, CURLOPT_INFILESIZE, strlen($body));
                break;
            case "DELETE":
                $headers[1] = "Content-length: 0";
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
                break;
            default:
                $headers[1] = "Content-length: 0";
                curl_setopt($curl, CURLOPT_HTTPGET, true);
                curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        
        $result = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if ($temp_file !== null) {
            fclose($temp_file);
        }
        curl_close($curl);
        
        if ($code === 401 & $refresh === true) {
            $this->refreshToken();
            return $this->makeRequest($method, $content_type, $url, $body, false);
        }

        if ($code !== 200 & $code !== 302) {
            $result = json_decode($result);
            $error_desc = "";

            if (isset($result->ErrorDescription)) {
                $error_desc = $result->ErrorDescription;
            } else {
                $error_desc = "Unknown error";
            }

            throw new MailUpException($code, $error_desc);
        }
        
        return $result;
    }
    
    private function loadToken()
    {
        if (isset($_COOKIE['access_token'])) {
            $this->accessToken = $_COOKIE["access_token"];
        }

        if (isset($_COOKIE['refresh_token'])) {
            $this->refreshToken = $_COOKIE["refresh_token"];
        };

        if (isset($_COOKIE['token_time'])) {
            $this->tokenTime = $_COOKIE['token_time'];
        }
    }
    
    private function clearToken()
	{
		$this->accessToken = null;
        $this->refreshToken = null;
        $this->tokenTime = null;

        setcookie("access_token", $this->accessToken, $this->tokenTime);
        setcookie("refresh_token", $this->refreshToken, $this->tokenTime);
        setcookie("token_time", $this->tokenTime, $this->tokenTime);
	}	
	private function saveToken($token, $refresh, $time)
    {
        $this->accessToken = $token;
        $this->refreshToken = $refresh;
        $this->tokenTime = time() + $time;

        setcookie("access_token", $this->accessToken, $this->tokenTime);
        setcookie("refresh_token", $this->refreshToken, $this->tokenTime);
        setcookie("token_time", $this->tokenTime, $this->tokenTime);
    }

    public function runExample1($list_id = -1)
    {
        $_SESSION['examples']['example_1'] = array();

        if ($list_id === -1) {
            $result = $this->getResult(
                "POST",
                "JSON",
                json_encode(
                    array(
                        "Deletable" => true,
                        "Name" => "test import",
                        "Notes" => "test import"
                    )
                ),
                "Console",
                "/Console/List/" . 100 . "/Groups",
                "If the list does not contain a group named test \"import\", create it"
            );
        } else {
            $result = $this->getResult(
                "GET",
                "JSON",
                null,
                "Console",
                "/Console/List/" . $list_id . "/Groups",
                "Given a default list id (use idList = " . $list_id . "), request for user visible groups"
            );
        }

        $obj_result = json_decode($result['res_body']);
        $items = $obj_result->Items;
        for ($i = 0; $i < count($items); $i++) {
            $group = $items[$i];
            if ("test import" === $group->Name) $group_id = $group->idGroup;
        }
        $_SESSION['examples']['example_1'][] = $result;

        $_SESSION['group_id'] = $group_id;
        
        // Request for dynamic fields to map recipient name and surname
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/Recipient/DynamicFields",
            "Request for dynamic fields to map recipient name and surname"
        );
        $_SESSION['examples']['example_1'][] = $result;

        // Import recipients to group
        $result = $this->getResult(
            "POST",
            "JSON",
            json_encode(
                array(
                    array(
                        "Email" => "test@test.test",
                        "Fields" => array(
                            array(
                                "Description" => "String description",
                                "Id" => 1,
                                "Value" => "String value"
                            )
                        ),
                        "MobileNumber" => "",
                        "MobilePrefix" => "",
                        "Name" => "John Smith"
                    )
                )
            ),
            "Console",
            "/Console/Group/" . $group_id . "/Recipients",
            "Import recipients to group"
        );
        $_SESSION['examples']['example_1'][] = $result;

        $import_id = $result['res_body'];
        // Check the import result
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/Import/" . $import_id,
            "Check the import result"
        );
        $_SESSION['examples']['example_1'][] = $result;
    }

    public function runExample2($group_id)
    {
        $_SESSION['examples']['example_2'] = array();
        // Request for recipient in a group
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/Group/" . $group_id . "/Recipients",
            "Request for recipient in a group"
        );

        $_SESSION['examples']['example_2'][] = $result;
        $obj_result = json_decode($result['res_body']);
        $items = $obj_result->Items;
        
        if (count($items) > 0) {
            $recipient_id = $items[0]->idRecipient;
            // Pick up a recipient and unsubscribe it
            $result = $this->getResult(
                "DELETE",
                "JSON",
                null,
                "Console",
                "/Console/Group/" . $group_id . "/Unsubscribe/" . $recipient_id,
                "Pick up a recipient and unsubscribe it"
            );
            $_SESSION['examples']['example_2'][] = $result;
        }
    }

    public function runExample3($list_id)
    {
        $_SESSION['examples']['example_3'] = array();
        // Request for existing subscribed recipients
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Recipients/Subscribed",
            "Request for existing subscribed recipients"
        );
        $_SESSION['examples']['example_3'][] = $result;

        $obj_result = json_decode($result['res_body']);
        $items = $obj_result->Items;

        if (count($items) > 0) {
            $fields = '{
                "Description": "",
                "Id": 1, 
                "Value": "Updated value"
            }';
            $items[0]->Fields[0] = json_decode($fields);

            $result = $this->getResult(
                "PUT",
                "JSON",
                json_encode($items[0]),
                "Console",
                "/Console/Recipient/Detail",
                "Update the modified recipient"
            );

            $_SESSION['examples']['example_3'][] = $result;
        }
    }

    public function runExample4($list_id)
    {
        $_SESSION['examples']['example_4'] = array();
        // Get the available template list
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Templates",
            "Get the available template list"
        );
        $_SESSION['examples']['example_4'][] = $result;

        $template_id = 1;
        $obj_result = json_decode($result['res_body']);
        $items = $obj_result->Items;

        if (count($items) > 0) {
            $template_id = $items[1]->Id;
        }

        // Create the new message
        $result = $this->getResult(
            "POST",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Email/Template/" . $template_id,
            "Create the new message"
        );
        $_SESSION['examples']['example_4'][] = $result;

        $obj_result = json_decode($result['res_body']);

        if (count($obj_result) > 0) {
            $email_id = $obj_result->idMessage;
        }

        $_SESSION["email_id"] = $email_id;
        
        // Request for messages list
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "console",
            "/Console/List/" . $list_id . "/Emails",
            "Request for messages list"
        );
        $_SESSION['examples']['example_4'][] = $result;
    }

    public function runExample5($list_id)
    {
        $_SESSION['examples']['example_5'] = array();
        // Image bytes can be obtained from file, database or any other source
        $img = file_get_contents("https://www.mailup.it/risorse/logo/512x512.png");
        $img = base64_encode($img);
        $body = '{
            "Base64Data": "' . $img . '",
            "Name": "Avatar"
        }';
        // Upload an image
        $result = $this->getResult(
            "POST",
            "JSON",
            $body,
            "Console",
            "/Console/List/" . $list_id . "/Images",
            "Upload an image"
        );
        $_SESSION['examples']['example_5'][] = $result;

        // Get the images available
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/Images",
            "Get the images available"
        );
        $_SESSION['examples']['example_5'][] = $result;

        $img_src = "";
        $arr_result = json_decode($result['res_body']);

        if (count($arr_result) > 0) {
            $img_src = $arr_result[0];
        }
        
        $img_src = str_replace("\\", "\\\\", $img_src);
        $img_src = str_replace("/", "\\/", $img_src);
        // Not format this string, otherwise the server will return 400 error.
        $message = "<html><body><p>Hello<\\/p><img src=\\\"" . $img_src . "\\\" \\/><\\/body><\\/html>";
        $email = '{
            "Subject": "Test Message Objective-C",
            "idList": "' . $list_id . '",
            "Content": "' . $message . '",
            "Embed": true,
            "IsConfirmation": true,
            "Fields": [],
            "Notes": "Some notes",
            "Tags": [],
            "TrackingInfo": {
                "CustomParams": "",
                "Enabled": true,
                "Protocols": [
                    "http"
                ]
            }
        }';
        // Create and save "hello" message
        $result = $this->getResult(
            "POST",
            "JSON",
            $email,
            "Console",
            "/Console/List/" . $list_id . "/Email",
            "Create and save \"hello\" message"
        );
        $_SESSION['examples']['example_5'][] = $result;

        $obj_result = json_decode($result['res_body']);

        if (count($obj_result) > 0) {
            $email_id = $obj_result->idMessage;
        }

        $_SESSION['email_id'] = $email_id;
        
        $attachment = "QmFzZSA2NCBTdHJlYW0=";
        $body = '{
            "Base64Data": "' . $attachment . '",
            "Name": "TestFile.txt", 
            "Slot": 1,
            "idList": 1,
            "idMessage": ' . $email_id . 
        '}';
        // Add an attachment
        $result = $this->getResult(
            "POST",
            "JSON",
            $body,
            "Console",
            "/Console/List/" . $list_id . "/Email/" . $email_id . "/Attachment/1",
            "Add an attachment"
        );
        $_SESSION['examples']['example_5'][] = $result;
        // Retreive message details
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Email/" . $email_id,
            "Retreive message detail"
        );
        $_SESSION['examples']['example_5'][] = $result;
    }

    public function runExample6($list_id, $email_id)
    {
        $_SESSION['examples']['example_6'] = array();
        // Create a new tag
        $result = $this->getResult(
            "POST",
            "JSON",
            '"test tag"',
            "Console",
            "/Console/List/" . $list_id . "/Tag",
            "Create a new tag"
        );
        $_SESSION['examples']['example_6'][] = $result;

        $tag_id = 1;
        $obj_result = json_decode($result['res_body']);

        if (count($obj_result) > 0) {
            $tag_id = $obj_result->Id;
        }
        // Pick up a message and retrieve detailed informations
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Email" . "/" . $email_id,
            "Pick up a message and retrieve detailed informations"
        );
        $_SESSION['examples']['example_6'][] = $result;

        $obj_result = json_decode($result['res_body']);
        $tags = '[
            {
                "Id": "' . $tag_id . '",
                "Enabled": true,
                "Name": "test tag"
            }
        ]';
        $obj_result->Tags = json_decode($tags);

        // Add the tag to the message details and save
        $result = $this->getResult(
            "PUT",
            "JSON",
            json_encode($obj_result),
            "Console",
            "/Console/List/" . $list_id . "/Email/" . $email_id,
            "Add the tag to the message details and save"
        );
        $_SESSION['examples']['example_6'][] = $result;
    }

    public function runExample7($list_id, $email_id)
    {
        $_SESSION['examples']['example_7'] = array();
        // Get the list of the existing messages
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Emails",
            "Get the list of the existing messages"
        );
        $_SESSION['examples']['example_7'][] = $result;

        $obj_result = json_decode($result['res_body']);
        $items = $obj_result->Items;

        if (count($items) > 0) {
            $email_id = $items[0]->idMessage;
        }

        $_SESSION["email_id"] = $email_id;
        
        // Send email to all recipients in the list
        $result = $this->getResult(
            "POST",
            "JSON",
            null,
            "Console",
            "/Console/List/" . $list_id . "/Email" . "/" . $email_id . "/Send",
            "Send email to all recipients in the list"
        );
        $_SESSION['examples']['example_7'][] = $result; 
    }

    public function runExample8($email_id)
    {
        $_SESSION['examples']['example_8'] = array();
        // Request (to MailStatisticsService.svc) for paged message views list for the previously sent message
        $result = $this->getResult(
            "GET",
            "JSON",
            null,
            "MailStatistics",
            "/Message" . "/" . $email_id . "/List/Views?pageSize=5&pageNum=0",
            "Request (to MailStatisticsService.svc) for paged message views list for the previously sent message"
        );
        $_SESSION['examples']['example_8'][] = $result;
    }

    private function getResult($method, $type, $body, $env, $ep, $text)
    {
        $result = array();
        $url = "Console" === $env ? $this->api['console'] : $this->api['mail_stats'];
        $url = $url . $ep;
        $this->errorUrl = $url;
        $result['res_body']  = $this->makeRequest($method, $type, $url, $body);
        $result['url'] = $env;
        $result['content_type'] = $type;
        $result['method'] = $method;
        $result['endpoint'] = $ep;
        $result['text'] = $text;
        $result['req_body'] = $body;
        return $result;
    }
}