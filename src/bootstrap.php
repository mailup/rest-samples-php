<?php

require_once 'MailUpClient.php';
// Include config
$auth_data = require_once 'config/auth.php';
$examples_text = require_once 'config/examples.php';
$api = require_once 'config/api.php';
$data = require_once 'config/data.php';

$mailUp = new MailUpClient($auth_data, $api);

if (isset($_POST['logon_by_key'])) {
    $mailUp->logonByKey($auth_data['callback_url']);
    // Code returned by MailUp
} else if (isset($_GET['code'])) {
    $mailUp->retrieveTokenByCode($_GET['code']);
}

try {
    if (isset($_POST['logon_by_password'])) {
        $mailUp->retrieveTokenByPassword($_POST['username'], $_POST['password']);
    }
} catch (MailUpException $e) {
    $error = "Exception Code: " . $e->getStatusCode() . "</br>" . $e->getMessage();
}

if (isset($_POST['refresh_token'])) {
    $mailUp->refreshToken();
}

$result = null;

if (isset($_POST['execute_request'])) {
    $uri = $_POST['url'] . str_replace(' ','%20', $_POST['endpoint']);
    try {
        $result = $mailUp->makeRequest($_POST['method'], $_POST['content_type'], $uri, $_POST['body']);
    } catch (MailUpException $e) {
        $result = "Exception Code: " . $e->getStatusCode() . "</br>" . $e->getMessage();
    }
}
// Examples
session_start();

$email_id = 1;
$group_id = 1;
$examples = array();
$errors = array();

if (isset($_SESSION['group_id'])) {
    $group_id = $_SESSION['group_id'];
}

if (isset($_SESSION['email_id'])) {
    $email_id = $_SESSION['email_id'];
}

// Example 1
try {
    if (isset($_POST['example_1'])) {
        $mailUp->runExample1($data['list_id']);
        unset($_SESSION['errors']['example_1']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_1'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 2
try {
    if (isset($_POST['example_2'])) {
        $mailUp->runExample2($group_id);
        unset($_SESSION['errors']['example_2']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_2'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 3
try {
    if (isset($_POST['example_3'])) {
        $mailUp->runExample3($data['list_id']);
        unset($_SESSION['errors']['example_3']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_3'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 4
try {
    if (isset($_POST['example_4'])) {
        $mailUp->runExample4($data['list_id']);
        unset($_SESSION['errors']['example_4']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_4'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 5
try {
    if (isset($_POST['example_5'])) {
        $mailUp->runExample5($data['list_id']);
        unset($_SESSION['errors']['example_5']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_5'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 6
try {
    if (isset($_POST['example_6'])) {
        $mailUp->runExample6($data['list_id'], $email_id);
        unset($_SESSION['errors']['example_6']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_6'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 7
try {
    if (isset($_POST['example_7'])) {
        $mailUp->runExample7($data['list_id'], $email_id);
        unset($_SESSION['errors']['example_7']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_7'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}
// Example 8
try {
    if (isset($_POST['example_8'])) {
        $mailUp->runExample8($email_id);
        unset($_SESSION['errors']['example_8']);
    }
} catch (MailUpException $e) {
    $_SESSION['errors']['example_8'] = array(
        "code" => $e->getStatusCode(),
        "message" => $e->getMessage(),
        "url" => $mailUp->getErrorUrl()
    );
}

if (isset($_SESSION['examples'])) {
    $examples = $_SESSION['examples'];
}

if (isset($_SESSION['errors'])) {
    $errors = $_SESSION['errors'];
}