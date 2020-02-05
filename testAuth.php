<?php

set_time_limit(0);
date_default_timezone_set('America/New_York');
if (php_sapi_name() !== "cli") {
    die("This script may not be run on a website!");
}

//Load config and utils
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/config.php';

$username = trim(IG_USERNAME);
$password = trim(IG_PASS);
if (in_array('-p', $argv) || in_array('--prompt-login', $argv)) {
    Utils::log("Credentials: Please enter your credentials...");
    $username = Utils::promptInput("Username:");
    $password = Utils::promptInput("Password:");
}

if ($username === "USERNAME" || $password == "PASSWORD") {
    Utils::log("Credentials: The default username or password has not been changed, exiting...");
    Utils::log("Protip: You can run the script like 'php testAuth.php -p' to enter your credentials when the script loads.");
    exit(1);
}

Utils::log("Login: Starting Instagram logon, please wait...");
$ig = Utils::loginFlow($username, $password);
if (!$ig->isMaybeLoggedIn) {
    Utils::log("Login: Unsuccessful login.");
    Utils::dump();
    exit(1);
}
Utils::log("Login: Successfully logged in as " . $ig->username . "!");
Utils::log("Goodbye: Thanks for testing your account authentication! Consider donating @ https://www.paypal.me/JoshuaRoy1 <3");