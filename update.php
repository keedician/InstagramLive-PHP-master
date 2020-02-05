<?php /** @noinspection PhpComposerExtensionStubsInspection */

logTxt("Loading Updater...");
clearstatcache();

define("noComposer", in_array("-p", $argv), in_array("--private-api", $argv));

$requestedTag = null;

foreach ($argv as $curArg) {
    if (strpos($curArg, '--tag=') !== false) {
        $requestedTag = str_replace('--tag=', '', $curArg);
    }
}

if ($requestedTag !== null) {
    logTxt("Looking up requested tag: $requestedTag");
    $betaResponse = json_decode(file_get_contents("https://raw.githubusercontent.com/JRoy/InstagramLive-PHP/update/beta.json"), true);
    $tagUrl = sprintf($betaResponse['tagValidationUrl'], $requestedTag);
    @file_get_contents($tagUrl);
    if (is404($http_response_header)) {
        logTxt("Requested tag does not exist! Exiting updater...");
        exit();
    }
    logTxt("Tag Fetched! Processing File Queue...");
    $composer = false;
    foreach ($betaResponse['files'] as $file) {
        $downloaded = @file_get_contents($tagUrl . $file);
        if (is404($http_response_header)) {
            @unlink($file);
            logTxt("$file - Deleted");
            continue;
        }
        if ($file == 'composer.json') {
            $localMd5 = md5(preg_replace("/[\r|\n]/", "", trim(file_get_contents($file))));
            $remoteMd5 = md5(preg_replace("/[\r|\n]/", "", trim($downloaded)));
            if ($localMd5 === $remoteMd5) {
                continue;
            }
            $composer = true;
        }
        file_put_contents($file, $downloaded);
        logTxt("$file - Modified");
    }

    if (!file_exists("vendor/") || $composer) {
        doComposerInstall($composer);
    }

    logTxt("Successfully fetched & updated to tag $requestedTag");
    exit();
}

if (file_exists('goLive.php')) {
    $cachedFlavor = exec("\"" . PHP_BINARY . "\" goLive.php --dumpFlavor");
}

if (@$cachedFlavor == 'custom') {
    logTxt("Custom build flavor located! Exiting updater...");
    exit();
}

$beta = false;
if (@$cachedFlavor == 'beta') {
    $beta = true;
}
if (in_array('-b', $argv) || in_array('--beta', $argv)) {
    $beta = true;
} elseif (in_array('-s', $argv) || in_array('--stable', $argv)) {
    $beta = false;
}

logTxt("Fetching Latest " . ($beta === true ? "Beta" : "Stable") . " Release Data");
$release = json_decode(file_get_contents("https://raw.githubusercontent.com/JRoy/InstagramLive-PHP/update/" . ($beta === true ? "beta" : "stable") . ".json"), true);
logTxt("Fetched Version: " . $release['version'] . " (Version Code " . $release['versionCode'] . ")");

logTxt("Validating directories...");
foreach ($release['dirs'] as $dir) {
    if (!file_exists($dir . '/')) {
        mkdir($dir);
        logTxt("Directory " . $dir . " Created");
    }
}

logTxt("Comparing Files...");
$queue = [];
$composer = false;
foreach ($release['files'] as $file) {
    if (!file_exists($file)) {
        logTxt("File Queued: " . $file);
        array_push($queue, $file);
        continue;
    }
    $localMd5 = md5(preg_replace("/[\r|\n]/", "", trim(file_get_contents($file))));
    $remoteMd5 = md5(preg_replace("/[\r|\n]/", "", trim(file_get_contents($release['links'][$file]))));
    logTxt($file . ": " . $localMd5 . " - " . $remoteMd5);
    if ($localMd5 !== $remoteMd5) {
        array_push($queue, $file);
    }
}

logTxt("Checking for config updates...");
if (file_exists("config.php")) {
    include_once 'config.php';
    if ((int)configVersionCode < (int)$release['config']['versionCode']) {
        logTxt("Outdated config version code, updating...");
        file_put_contents("config.php", file_get_contents($release['config']['url']));
        logTxt("Updated config, you'll need to re-populate your config username and password.");
    }
} else {
    logTxt("No config detected, downloading...");
    file_put_contents("config.php", file_get_contents($release['config']['url']));
    logTxt("Downloaded config!");
}

if (count($queue) != 0) {
    logTxt("Updating " . count($queue) . " files...");
    foreach ($queue as $file) {
        if ($file == 'composer.json') {
            if (noComposer) {
                logTxt("Ignoring composer...");
                continue;
            }
            $composer = true;
        }
        file_put_contents($file, file_get_contents($release['links'][$file]));
    }
    logTxt("Files Updated!");
}

if (!noComposer && (!file_exists("vendor/") || $composer)) {
    doComposerInstall($composer);
}

logTxt("InstagramLive-PHP is now up-to-date!");

function doComposerInstall($composer)
{
    logTxt($composer ? "Detected composer update, re-installing..." : "No vendor folder detected, attempting to recover...");

    if (!file_exists('composer.phar')) {
        logTxt("Composer not found, installing...");
        copy('https://getcomposer.org/installer', 'composer-install.php');
        exec("\"" . PHP_BINARY . "\" composer-install.php --quiet");
        unlink('composer-install.php');
        logTxt("Installed composer!");
    }

    exec((file_exists("composer.phar") ? ("\"" . PHP_BINARY . "\" composer.phar") : "composer") . " update");
    if (!file_exists("vendor/")) {
        logTxt("Composer install was unsuccessful! Please make sure composer is ACTUALLY INSTALLED!");
        exit();
    }
}

function is404($http_response_header): bool
{
    if (!is_array($http_response_header) || count(explode(' ', $http_response_header[0])) <= 1 || intval(explode(' ', $http_response_header[0])[1]) === 404) {
        return true;
    }
    return false;
}

function logTxt($message)
{
    print($message . PHP_EOL);
}