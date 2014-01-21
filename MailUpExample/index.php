<?php
    require_once 'MailUpClient.php';
    
    $MAILUP_CLIENT_ID = "5a800771-0fb1-4763-977b-736a945f73cd";
    $MAILUP_CLIENT_SECRET = "bfe2ec3c-acab-4149-9297-a5901e5b244e";
    $MAILUP_CALLBACK_URI = "http://127.0.0.1/index.php";
	
    // Initializing MailUpClient
    $mailUp = new MailUpClient($MAILUP_CLIENT_ID, $MAILUP_CLIENT_SECRET, $MAILUP_CALLBACK_URI);
    
    // Logging In
    if (isset($_REQUEST["LogOn"])) { // LogOn button clicked
        $mailUp->logOn();
    } else if (isset($_REQUEST["code"])) { // code returned by MailUp
        $mailUp->retreiveAccessTokenWithCode($_REQUEST["code"]);
    }
    if (isset($_REQUEST["LogOnWithPassword"])) { // LogOnWithPassword button clicked
        $mailUp->logOnWithPassword($_REQUEST["txtUsr"],$_REQUEST["txtPwd"]);
    }
    // Calling Method
    $callResult = "";
    if (isset($_REQUEST["CallMethod"])) { // CallMethod button clicked
        try {
		$vartext = preg_replace("/\r\n|\r|\n/", ' ', $_REQUEST["txtBody"]);
		$callResult = $mailUp->callMethod($_REQUEST["lstEndpoint"] . $_REQUEST["txtPath"],
                                              $_REQUEST["lstVerb"],
                                              $vartext,
                                              $_REQUEST["lstContentType"]);
		
        } catch (MailUpException $ex) {
            $callResult = "Exception with code " . $ex->getStatusCode() . " and message: " . $ex->getMessage();
        }
    }
    
    // Running Examples
    $exampleResult = "";
    $groupId = -1;
    $emailId = -1;
    
    session_start();
    if (isset($_SESSION["groupId"])) $groupId = $_SESSION["groupId"];
    if (isset($_SESSION["emailId"])) $emailId = $_SESSION["emailId"];
    
    // EXAMPLE 1 - IMPORT RECIPIENTS INTO NEW GROUP
    // List ID = 1 is used in all example calls
    if (isset($_REQUEST["RunExample1"])) { 
        try {
            
            // Given a default list id (use idList = 1), request for user visible groups
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Groups";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            $arr = $result->Items;
            for ($i = 0; $i < count($arr); $i++) {
                $group = $arr[$i];
                if ("test import" == $group->Name) $groupId = $group->idGroup;
            }
            
            $exampleResult .= "Given a default list id (use idList = 1), request for user visible groups<br/>GET ".$url." - OK<br/>";
            
            // If the list does not contain a group named “test import”, create it
            if ($groupId == -1) {
                $groupId = 100;
                $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Group";
                $groupRequest = "{\"Deletable\":true,\"Name\":\"test import\",\"Notes\":\"test import\"}";
                $result = $mailUp->callMethod($url, "POST", $groupRequest, "JSON");
                $result = json_decode($result);
                $arr = $result->Items;
                for ($i = 0; $i < count($arr); $i++) {
                    $group = $arr[$i];
                    if ("test import" == $group->Name) $groupId = $group->idGroup;
                }
                
                $exampleResult .= "If the list does not contain a group named “test import”, create it<br/>POST ".$url." - OK<br/>";
            }
            $_SESSION["groupId"] = $groupId;
            
            // Request for dynamic fields to map recipient name and surname
            $url = $mailUp->getConsoleEndpoint() . "/Console/Recipient/DynamicFields";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            
            $exampleResult .= "Request for dynamic fields to map recipient name and surname<br/>GET ".$url." - OK<br/>";
            
            // Import recipients to group
            $url = $mailUp->getConsoleEndpoint() . "/Console/Group/" . $groupId . "/Recipients";
            $recipientRequest = "[{\"Email\":\"test@test.test\",\"Fields\":[{\"Description\":\"String description\",\"Id\":1,\"Value\":\"String value\"}],\"MobileNumber\":\"\",\"MobilePrefix\":\"\",\"Name\":\"John Smith\"}]";
            $result = $mailUp->callMethod($url, "POST", $recipientRequest, "JSON");
            $importId = $result;
            
            $exampleResult .= "Import recipients to group<br/>POST ".$url." - OK<br/>";
            
            // Check the import result
            $url = $mailUp->getConsoleEndpoint() . "/Console/Import/" . $importId;
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            
            $exampleResult .= "Check the import result<br/>GET ".$url." - OK<br/>";
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 2 - UNSUBSCRIBE A RECIPIENT FROM A GROUP
    if (isset($_REQUEST["RunExample2"])) {
        try {
            
            // Request for recipient in a group
            $url = $mailUp->getConsoleEndpoint() . "/Console/Group/" . $groupId . "/Recipients";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Request for recipient in a group<br/>GET ".$url." - OK<br/>";
            $arr = $result->Items;
            if (count($arr) > 0) {
                $recipientId = $arr[0]->idRecipient;
                
                // Pick up a recipient and unsubscribe it
                $url = $mailUp->getConsoleEndpoint() . "/Console/Group/" . $groupId . "/Unsubscribe/" . $recipientId;
                $result = $mailUp->callMethod($url, "DELETE", null, "JSON");
                
                $exampleResult .= "Pick up a recipient and unsubscribe it<br/>DELETE ".$url." - OK<br/>";
            }
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 3 - UPDATE A RECIPIENT DETAIL
    if (isset($_REQUEST["RunExample3"])) {
        try {
            
            // Request for existing subscribed recipients
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Recipients/Subscribed";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Request for existing subscribed recipients<br/>GET ".$url." - OK<br/>";
            
            if (count($result->Items) > 0) {
                // Modify a recipient from the list
                $fields = '{"Id":1, "Value":"Updated value", "Description":""}';
                $result->Items[0]->Fields[0] = json_decode($fields);
                
                $exampleResult .= "Modify a recipient from the list - OK<br/>";
                
                // Update the modified recipient
                $url = $mailUp->getConsoleEndpoint() . "/Console/Recipient/Detail";
                $result = $mailUp->callMethod($url, "PUT", json_encode($result->Items[0]), "JSON");
                
                $exampleResult .= "Update the modified recipient<br/>PUT ".$url." - OK<br/>";
            }
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 4 - CREATE A MESSAGE FROM TEMPLATE
    if (isset($_REQUEST["RunExample4"])) {
        try {
            
            // Get the available template list
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Templates";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Get the available template list<br/>GET ".$url." - OK<br/>";
            
            $templateId = -1;
            if (count($result) > 0) $templateId = $result[0]->Id;
            
            // Create the new message
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email/Template/" . $templateId;
            $result = $mailUp->callMethod($url, "POST", null, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Create the new message<br/>POST ".$url." - OK<br/>";
            
            if (count($result->Items) > 0) {
                $emailId = $result->Items[0]->idMessage;
            }
            $_SESSION["emailId"] = $emailId;
            
            // Request for messages list
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Emails";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            
            $exampleResult .= "Request for messages list<br/>GET ".$url." - OK<br/>";
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 5 - CREATE A MESSAGE WITH IMAGES AND ATTACHMENTS
    if (isset($_REQUEST["RunExample5"])) {
        try {
            
            // Image bytes can be obtained from file, database or any other source
            $img = file_get_contents("http://images.apple.com/home/images/ios_title_small.png");
            $img = base64_encode($img);
            
            // Upload an image
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Images";
            $imageRequest = "{\"Base64Data\":\"".$img."\",\"Name\":\"Avatar\"}";
            $result = $mailUp->callMethod($url, "POST", $imageRequest, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Upload an image<br/>POST ".$url." - OK<br/>";
            
            // Get the images available
            $url = $mailUp->getConsoleEndpoint() . "/Console/Images";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            
            $imgSrc = "";
            if (count($result) > 0) $imgSrc = $result[0];
            
            $exampleResult .= "Get the images available<br/>GET ".$url." - OK<br/>";
            
            // Create and save "hello" message
            $imgSrc = str_replace("\\", "\\\\", $imgSrc);
            $imgSrc = str_replace("/", "\\/", $imgSrc);
            $message = "<html><body><p>Hello<\\/p><img src=\\\"".$imgSrc."\\\" \\/><\\/body><\\/html>";
            
            $email = '{"Subject":"Test Message Objective-C","idList":1,"Content":"'.$message.
                '","Embed":true,"IsConfirmation":true,"Fields":[],"Notes":"Some notes","Tags":[],"TrackingInfo":{"CustomParams":"","Enabled":true,"Protocols":["http"]}}';
            
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email";
            $result = $mailUp->callMethod($url, "POST", $email, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Create and save \"hello\" message<br/>POST ".$url." - OK<br/>";
            
            if (count($result->Items) > 0) {
                $emailId = $result->Items[0]->idMessage;
            }
            $_SESSION["emailId"] = $emailId;
            
            // Add an attachment
            $attachment = "QmFzZSA2NCBTdHJlYW0="; // Base64 String
            $attachmentRequest = '{"Base64Data":"'.$attachment.'","Name":"TestFile.txt","Slot":1,"idList":1,"idMessage":'.$emailId.'}';
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email/" . $emailId . "/Attachment/1";
            $result = $mailUp->callMethod($url, "POST", $attachmentRequest, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Add an attachment<br/>POST ".$url." - OK<br/>";
            
            // Retreive message details
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email/" . $emailId;
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            
            $exampleResult .= "Retreive message details<br/>GET ".$url." - OK<br/>";
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 6 - TAG A MESSAGE
    if (isset($_REQUEST["RunExample6"])) {
        try {
            
            // Create a new tag
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Tag";
            $result = $mailUp->callMethod($url, "POST", "\"test tag\"", "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Create a new tag<br/>POST ".$url." - OK<br/>";
            
            $tagId = -1;
            if (count($result) > 0) $tagId = $result[0]->Id;
            
            // Pick up a message and retrieve detailed informations
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email/" . $emailId;
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Pick up a message and retrieve detailed informations<br/>GET ".$url." - OK<br/>";
            
            // Add the tag to the message details and save
            $tags = "[{\"Id\":".$tagId.",\"Enabled\":true,\"Name\":\"test tag\"}]";
            $result->Tags = json_decode($tags);
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email/" . $emailId;
            $result = $mailUp->callMethod($url, "PUT", json_encode($result), "JSON");
            
            $exampleResult .= "Add the tag to the message details and save<br/>PUT ".$url." - OK<br/>";
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 7 - SEND A MESSAGE
    if (isset($_REQUEST["RunExample7"])) {
        try {
            
            // Get the list of the existing messages
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Emails";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            $result = json_decode($result);
            
            $exampleResult .= "Get the list of the existing messages<br/>GET ".$url." - OK<br/>";
            
            if (count($result->Items) > 0) {
                $emailId = $result->Items[0]->idMessage;
            }
            $_SESSION["emailId"] = $emailId;
            
            // Send email to all recipients in the list
            $url = $mailUp->getConsoleEndpoint() . "/Console/List/1/Email/" . $emailId . "/Send";
            $result = $mailUp->callMethod($url, "POST", null, "JSON");
            
            $exampleResult .= "Send email to all recipients in the list<br/>POST ".$url." - OK<br/>";
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    // EXAMPLE 8 - DISPLAY STATISTICS FOR A MESSAGE SENT AT EXAMPLE 7
    if (isset($_REQUEST["RunExample8"])) {
        try {
            
            // Request (to MailStatisticsService.svc) for paged message views list for the previously sent message
            $hours = 4;
            $url = $mailUp->getMailstatisticsEndpoint() . "/Message/" . $emailId . "/Views/List/Last/" . $hours . "?pageSize=5&pageNum=0";
            $result = $mailUp->callMethod($url, "GET", null, "JSON");
            
            $exampleResult .= "Request (to MailStatisticsService.svc) for paged message views list for the previously sent message<br/>GET ".$url." - OK<br/>";
            
            $exampleResult .= "Example methods completed successfully<br/>";
            
        } catch (MailUpException $ex) {
            $exampleResult = "Error " . $ex->getStatusCode() . ": " . $ex->getMessage();
        }
    }
    
    
    // Writing page output
    
?><!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>MailUp Demo Client</title>
</head>
<body>
<h2>
MailUp Demo Client
</h2>

<form action="index.php" method="POST">
<p>
<input type="submit" name="LogOn" value="Sign in to MailUp"/>
</p>
<p>
Username: <input type="text" name="txtUsr" value="type your MailUp username" style="width:400px;"/><br/>
Password: <input type="text" name="txtPwd" value="type your MailUp password" style="width:400px;"/><br/>
<input type="submit" name="LogOnWithPassword" value="Sign in to MailUp using Password flow"/>
</p>

<p id="pAuthorization"><?php echo ($mailUp->getAccessToken()==null)?"Unauthorized":("Authorized. Token: ".$mailUp->getAccessToken()) ?></p><br /><br />

<p><b>Custom method call</b></p>
<table>
<thead>
<td>Verb</td>
<td>Content-Type</td>
<td>Endpoint</td>
<td>Path</td>
</thead>
<tr>
<td><select name="lstVerb">
<option value="GET">GET</option>
<option value="PUT">PUT</option>
<option value="POST">POST</option>
<option value="DELETE">DELETE</option>
</select></td>
<td><select name="lstContentType">
<option value="JSON">JSON</option>
<option value="XML">XML</option>
</select></td>
<td><select name="lstEndpoint">
<option value="<?php echo $mailUp->getConsoleEndpoint() ?>">Console</option>
<option value="<?php echo $mailUp->getMailstatisticsEndpoint() ?>">MailStatistics</option>
</select></td>
<td><input type="text" name="txtPath" value="/Console/Authentication/Info" style="width:200px;"/></td>
</tr>
</table>

<p>Body</p><p><textarea name="txtBody" rows="5" cols="60"></textarea></p>
<p>
<input type="submit" name="CallMethod" value="Call Method"/>
</p>

<p id="pResultString"><?php echo $callResult ?></p><br /><br />

<p><b>Run example set of calls</b></p>

<p id="pExampleResultString"><?php echo $exampleResult ?></p>
<p>
<input type="submit" name="RunExample1" value="Run example code 1 - Import recipients"/>
</p>
<p>
<input type="submit" name="RunExample2" value="Run example code 2 - Unsubscripe a recipient"/>
</p>
<p>
<input type="submit" name="RunExample3" value="Run example code 3 - Update a recipient"/>
</p>
<p>
<input type="submit" name="RunExample4" value="Run example code 4 - Create a message from template"/>
</p>
<p>
<input type="submit" name="RunExample5" value="Run example code 5 - Create a message from scratch"/>
</p>
<p>
<input type="submit" name="RunExample6" value="Run example code 6 - Tag a message"/>
</p>
<p>
<input type="submit" name="RunExample7" value="Run example code 7 - Send a message"/>
</p>
<p>
<input type="submit" name="RunExample8" value="Run example code 8 - Retreive statistics"/>
</p>
</form>
</body>
</html>