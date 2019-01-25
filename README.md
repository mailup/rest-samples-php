PHP Rest API Client 
================
PHP REST API integration/implementation samples

Requirements
------------------------
* Apache web server 
* A valid MailUp account ( trial accounts allowed )
* Your own API application keys [Get API application keys] [1] 


notes : 
* For further API information, please visit [MailUp REST API Help] [2] 
* For MailUp trial account activation please go to [MailUp web site] [3] 

  [1]: http://help.mailup.com/display/mailupapi/Get+a+Developer+Account        "Get API application keys" 
  [2]: http://help.mailup.com/display/mailupapi/REST+API        "MailUp REST API Help"
  [3]: http://www.mailup.com/p/pc/mailup-free-trial-d44.htm        "MailUp web site"  
  
Samples overview 
------------------------
This project encloses a short list of pre definied samples describing some of the most common processes within MailUp.

* Sample 1   - Importing recipients into a new group
* Sample 2   - Unsubscribing a recipient from a group
* Sample 3   - Updating a recipient information
* Sample 4   - Creating a message from a custom template ( at least one template must be saved on list 1 )
* Sample 5   - Building a message with images and attachments
* Sample 6   - Tagging an email message
* Sample 7   - Sending an email message
* Sample 8   - Displaying statistics with regards to message created in sample 4 or 5 and/or sent out in sample 7

Before starting 
------------------------
Now you have created a MailUp account and your API application keys, please set them into your local config file. You can find the path of the config file here: 
```
rest-samples-php/config/auth.php      
``` 

Debugging tool 
------------------------

Notes
------------------------
If you're interested to claim your API keys, please read more at the page [MailUp REST API Keys and endpoints] [4] 

  [4]: http://help.mailup.com/display/mailupapi/All+API+Keys+and+Endpoints+in+one+page        "MailUp REST API Keys and endpoints"

Revision history
------------------------
