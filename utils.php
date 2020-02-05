<?php
/** @noinspection PhpUndefinedNamespaceInspection */
/** @noinspection PhpUndefinedConstantInspection */
/** @noinspection PhpComposerExtensionStubsInspection */

require_once __DIR__ . '/vendor/autoload.php';

use InstagramAPI\Exception\InstagramException;
use InstagramAPI\Instagram;
use InstagramAPI\Request;
use LazyJsonMapper\Exception\LazyJsonMapperException;

class Utils
{
    /**
     * Checks the current version code against the server's version code.
     * @param string $current The current version code.
     * @param string $flavor The current version flavor.
     * @return bool Returns true if update is available.
     */
    public static function checkForUpdate(string $current, string $flavor): bool
    {
        if ($flavor == "custom") {
            self::log("Update: You're running an in-dev build; Please note update checks will not work!");
            return false;
        }
        return (int)json_decode(file_get_contents("https://raw.githubusercontent.com/JRoy/InstagramLive-PHP/update/$flavor.json"), true)['versionCode'] > (int)$current;
    }

    /**
     * Checks if the script is using dev-master
     * @return bool Returns true if composer is using dev-master
     */
    public static function isApiDevMaster(): bool
    {
        clearstatcache();
        if (!file_exists('composer.lock')) {
            return false;
        }

        //Don't override private API
        foreach (@json_decode(file_get_contents('composer.json'), true)['require'] as $key => $value) {
            if (strpos($key, '-private/instagram') !== false) {
                return true;
            }
        }

        $pass = false;
        foreach (@json_decode(file_get_contents('composer.lock'), true)['packages'] as $package) {
            if (@$package['name'] === 'mgp25/instagram-php' &&
                @$package['version'] === 'dev-master' &&
                @$package['source']['reference'] === @explode('#', @json_decode(file_get_contents('composer.json'), true)['require']['mgp25/instagram-php'])[1]) {
                $pass = true;
                break;
            }
        }

        return $pass;
    }

    /**
     * Sanitizes a stream key for clip command on Windows.
     * @param string $streamKey The stream key to sanitize.
     * @return string The sanitized stream key.
     */
    public static function sanitizeStreamKey($streamKey): string
    {
        return str_replace("&", "^^^&", $streamKey);
    }

    /**
     * Logs information about the current environment.
     * @param string $exception Exception message to log.
     * @param Request $request Request object to log.
     */
    public static function dump(string $exception = null, Request $request = null)
    {
        clearstatcache();
        self::log("===========BEGIN DUMP===========");
        self::log("InstagramLive-PHP Version: " . (defined('scriptVersion') ? scriptVersion : 'Unknown'));
        self::log("InstagramLive-PHP Flavor: " . (defined('scriptFlavor') ? scriptFlavor : 'Unknown'));
        self::log("Instagram-API Version: " . @json_decode(file_get_contents('composer.json'), true)['require']['mgp25/instagram-php']);
        self::log("Operating System: " . PHP_OS);
        self::log("PHP Version: " . PHP_VERSION);
        self::log("PHP Runtime: " . php_sapi_name());
        self::log("PHP Binary: " . PHP_BINARY);
        self::log("Bypassing OS-Check: " . (defined('bypassCheck') ? (bypassCheck == true ? "true" : "false") : 'Unknown'));
        self::log("Composer Lock: " . (file_exists("composer.lock") == true ? "true" : "false"));
        self::log("Vendor Folder: " . (file_exists("vendor/") == true ? "true" : "false"));
        if ($request !== null) {
            self::log("Request Endpoint: " . $request->getUrl());
        }
        if ($exception !== null) {
            self::log("Exception: " . $exception);
        }
        self::log("============END DUMP============");
    }

    /**
     * Helper function to check if the current OS is Windows.
     * @return bool Returns true if running Windows.
     */
    public static function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * Helper function to check if the current OS is Mac.
     * @return bool Returns true if running Windows.
     */
    public static function isMac(): bool
    {
        return strtoupper(PHP_OS) === 'DARWIN';
    }

    /**
     * Logs message to a output file.
     * @param string $message message to be logged to file.
     * @param string $file file to output to.
     */
    public static function logOutput($message, $file = 'output.txt')
    {
        file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Checks for a file existance, if it doesn't exist throw a dump and exit the script.
     * @param $path string Path to the file.
     * @param $reason string Reason the file is needed.
     */
    public static function existsOrError($path, $reason)
    {
        if (!file_exists($path)) {
            self::log("The following file, `" . $path . "` is required and not found by the script for the following reason: " . $reason);
            self::log("Please make sure you follow the setup guide correctly.");
            self::dump();
            exit(1);
        }
    }

    /**
     * Checks to see if characters are at the start of the string.
     * @param string $haystack The string to for the needle.
     * @param string $needle The string to search for at the start of haystack.
     * @return bool Returns true if needle is at start of haystack.
     */
    public static function startsWith($haystack, $needle)
    {
        return (substr($haystack, 0, strlen($needle)) === $needle);
    }

    /**
     * Prompts for user input. (Note: Holds the current thread!)
     * @param string $prompt The prompt for the input.
     * @return string The collected input.
     */
    public static function promptInput($prompt = '>'): string
    {
        print "$prompt ";
        return stream_get_line(STDIN, 1024, PHP_EOL);
    }

    /**
     * Preforms an analytics call.
     * @param string $action
     * @param string $ver
     * @param string $flavor
     * @param string $os
     * @param int $argCount
     * @param int $followCount
     */
    public static function analytics(string $action, string $ver, string $flavor, string $os, int $argCount, int $followCount)
    {
        @file_get_contents(strrev(str_rot13(base64_decode(convert_uudecode("@3'I%=TU3-'E.:D5U3D11=4UJ47A,>3@V63)D;F11/3T``")))) . 'action.php', false, stream_context_create(array('http' => array('header' => "Content-type: application/x-www-form-urlencoded", 'method' => 'POST', 'content' => http_build_query(array('action' => $action, 'data' => json_encode(array("version" => $ver, "flavor" => $flavor, "os" => $os, "args" => $argCount, "count" => $followCount)))), 'timeout' => '2'))));
    }

    /**
     * Saves the stream's current state to prevent creating phantom streams.
     * @param string $broadcastId Broadcast ID of the stream.
     * @param string $streamUrl Stream URL of the stream.
     * @param string $streamKey Stream Key of the stream.
     * @param int $lastCommentTs Recent Max ID of comments
     * @param int $lastLikeTs Recent Max ID of likes.
     * @param string|int $lastQuestion Last Question displayed.
     * @param int $startTime Epoch Time at which the stream started.
     * @param bool $obs True if the user is using obs.
     * @param string $obsObject
     */
    public static function saveRecovery(string $broadcastId, string $streamUrl, string $streamKey, int $lastCommentTs, int $lastLikeTs, $lastQuestion, int $startTime, bool $obs, string $obsObject)
    {
        file_put_contents('backup.json', json_encode(array(
            'broadcastId' => $broadcastId,
            'streamUrl' => $streamUrl,
            'streamKey' => $streamKey,
            'lastCommentTs' => $lastCommentTs,
            'lastLikeTs' => $lastLikeTs,
            'lastQuestion' => $lastQuestion,
            'startTime' => $startTime,
            'obs' => $obs,
            'obsObject' => $obsObject
        )));
    }

    /**
     * Gets the json decoded recovery data.
     * @return array Json-Decoded Recovery Data.
     */
    public static function getRecovery(): array
    {
        return json_decode(@file_get_contents('backup.json'), true);
    }

    /**
     * Checks if the recovery file is present.
     * @return bool True if recovery file is present.
     */
    public static function isRecovery(): bool
    {
        clearstatcache();
        if (!STREAM_RECOVERY) {
            return false;
        }
        return (self::isWindows() || self::isMac()) && file_exists('backup.json');
    }

    /**
     * Deletes the recovery data if present.
     */
    public static function deleteRecovery()
    {
        @unlink('backup.json');
    }

    /**
     * Kills a process with
     * @param $pid
     */
    public static function killPid($pid)
    {
        exec((self::isWindows() ? "taskkill /F /PID" : "kill -9") . " $pid");
    }

    /**
     * Runs our login flow to authenticate the user as well as resolve all two-factor/challenge items.
     * @param string $username Username of the target account.
     * @param string $password Password of the target account.
     * @param bool $debug Debug
     * @param bool $truncatedDebug Truncated Debug
     * @return ExtendedInstagram Authenticated Session.
     */
    public static function loginFlow($username, $password, $debug = false, $truncatedDebug = false): ExtendedInstagram
    {
        $ig = new ExtendedInstagram($debug, $truncatedDebug);

        $privateApi = property_exists($ig, 'checkpoint');

        try {
            $loginResponse = $ig->login($username, $password);

            if ($loginResponse !== null && $loginResponse->isTwoFactorRequired()) {
                self::log("Login Flow: Two-Factor Authentication Required! Please provide your verification code from your texts/other means.");
                $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
                $verificationMethod = '1';
                if ($loginResponse->getTwoFactorInfo()->getTotpTwoFactorOn() === true) {
                    $verificationMethod = '3';
                }
                self::log("Login Flow: We've detected that you're using " . ($verificationMethod === '3' ? 'authenticator app' : 'text message') . " verification. If you are actually using " . ($verificationMethod === '3' ? 'text message' : 'authenticator app') . " verification, please type 'yes', otherwise press enter.");
                $choice = self::promptInput();
                if ($choice === "yes") {
                    $verificationMethod = ($verificationMethod === '3' ? '1' : '3');
                }
                $verificationCode = self::promptInput("Type your verification code>");
                self::log("Login Flow: Logging in with verification token...");
                $ig->finishTwoFactorLogin($username, $password, $twoFactorIdentifier, $verificationCode, $verificationMethod);
            }
        } catch (InstagramException $e) {
            try {
                /** @noinspection PhpUndefinedClassInspection */
                if ((class_exists('InstagramAPI\\Exception\\ChallengeRequiredException') && $e instanceof InstagramAPI\Exception\ChallengeRequiredException) || (class_exists('InstagramAPI\Exception\Checkpoint\ChallengeRequiredException') && $e instanceof InstagramAPI\Exception\Checkpoint\ChallengeRequiredException)) {
                    $response = $e->getResponse();

                    self::log("Suspicious Login: Would you like to verify your account via text or email? Type \"yes\" or just press enter to ignore.");
                    self::log("Suspicious Login: Please only attempt this once or twice if your attempts are unsuccessful. If this keeps happening, this script is not for you :(.");
                    $attemptBypass = self::promptInput();
                    if ($attemptBypass !== 'yes') {
                        self::log("Suspicious Login: Account Challenge Failed :(.");
                        self::dump(null, $ig->client->getLastRequest());
                        exit(1);
                    }
                    self::log("Suspicious Login: Preparing to verify account...");
                    sleep(3);

                    if ($privateApi) {
                        $ig->challengePrivate($response);
                    } else {
                        $ig->challengePublic($response, $username, $password);
                    }
                } else {
                    self::log("Error while logging into Instagram: " . $e->getMessage());
                    self::dump($e->getMessage(), $ig->client->getLastRequest());
                    exit(1);
                }
            } catch (LazyJsonMapperException $mapperException) {
                self::log("Error while decoding challenge response: " . $e->getMessage());
                self::dump();
                exit(1);
            }
        }
        return $ig;
    }

    /**
     * Logs a message in console but it actually uses new lines.
     * @param string $message message to be logged.
     * @param string $outputFile
     */
    public static function log($message, $outputFile = '')
    {
        print $message . "\n";
        if ($outputFile !== '') {
            self::logOutput($message, $outputFile);
        }
    }
}

class ExtendedInstagram extends Instagram
{
    public function changeUser($username, $password)
    {
        $this->_setUser($username, $password);
    }

    public function updateLoginState($userId)
    {
        $this->isMaybeLoggedIn = true;
        $this->account_id = $userId;
        $this->settings->set('account_id', $this->account_id);
        $this->settings->set('last_login', time());
    }

    public function sendLoginFlow()
    {
        $this->_sendLoginFlow(true);
    }

    public function challengePublic($response, $username, $password)
    {
        Utils::log("Suspicious Login: Please select your verification option by typing \"sms\" or \"email\" respectively. Otherwise press enter to abort.");
        $choice = Utils::promptInput();
        if ($choice === "sms") {
            $verification_method = 0;
        } elseif ($choice === "email") {
            $verification_method = 1;
        } else {
            Utils::log("Suspicious Login: Aborting!");
            exit(1);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $checkApiPath = trim(substr($response->getChallenge()->getApiPath(), 1));
        $customResponse = $this->request($checkApiPath)
            ->setNeedsAuth(false)
            ->addPost('choice', $verification_method)
            ->addPost('guid', $this->uuid)
            ->addPost('device_id', $this->device_id)
            ->addPost('_csrftoken', $this->client->getToken())
            ->getDecodedResponse();

        try {
            if ($customResponse['status'] === 'ok' && isset($customResponse['action']) && $customResponse['action'] === 'close') {
                Utils::log("Suspicious Login: Account challenge unsuccessful!");
                exit(1);
            }

            Utils::log("Suspicious Login: Please enter the code you received via " . ($verification_method ? 'email' : 'sms') . "...");
            $cCode = Utils::promptInput();
            $this->changeUser($username, $password);
            $response = $this->request($checkApiPath)
                ->setNeedsAuth(false)
                ->addPost('security_code', $cCode)
                ->addPost('guid', $this->uuid)
                ->addPost('device_id', $this->device_id)
                ->addPost('_csrftoken', $this->client->getToken())
                ->getDecodedResponse();
            if (!isset($response['logged_in_user']) || !isset($response['logged_in_user']['pk'])) {
                Utils::log("Suspicious Login: Checkpoint likely failed, re-run script.");
                exit(1);
            }
            $this->updateLoginState((string)$response['logged_in_user']['pk']);
            $this->sendLoginFlow();
            Utils::log("Suspicious Login: Attempted to bypass checkpoint, good luck!");
        } catch (Exception $ex) {
            Utils::log("Suspicious Login: Account Challenge Failed :(.");
            Utils::dump($ex->getMessage());
            exit(1);
        }
    }

    /**
     * This adds support for Instagram-API's private code subscription.
     *
     * @see https://github.com/mgp25/Instagram-API/issues/2655
     */
    public function challengePrivate($response)
    {
        $iterations = 0;
        /** @noinspection PhpUndefinedMethodInspection */
        $checkApiPath = substr($response->getChallenge()->getApiPath(), 1);
        $challengeEx = null;
        while (true) {
            try {
                /** @noinspection PhpUndefinedClassInspection */
                if (++$iterations >= InstagramAPI\Request\Checkpoint::MAX_CHALLENGE_ITERATIONS) {
                    /** @noinspection PhpUndefinedClassInspection */
                    throw new InstagramAPI\Exception\Checkpoint\ChallengeIterationsLimitException();
                }
                switch (true) {
                    /** @noinspection PhpUndefinedClassInspection */ case $challengeEx instanceof InstagramAPI\Exception\Checkpoint\ChallengeRequiredException:
                    /** @noinspection PhpUndefinedFieldInspection */
                    $this->checkpoint->sendChallenge($checkApiPath);
                    break;
                    /** @noinspection PhpUndefinedClassInspection */ case $challengeEx instanceof InstagramAPI\Exception\Checkpoint\SelectVerifyMethodException:
                    /** @noinspection PhpUndefinedMethodInspection */
                    if ($challengeEx->getResponse()->getStepData()->getPhoneNumber() !== null) {
                        $method = 0;
                    } else {
                        $method = 1;
                    }
                    /** @noinspection PhpUndefinedFieldInspection */
                    $this->checkpoint->requestVerificationCode($checkApiPath, $method);
                    break;
                    /** @noinspection PhpUndefinedClassInspection */ case $challengeEx instanceof InstagramAPI\Exception\Checkpoint\VerifyCodeException:
                    Utils::log("Suspicious Login: Please enter the code you received...");
                    $cCode = Utils::promptInput();
                    /** @noinspection PhpUndefinedFieldInspection */
                    $challenge = $this->checkpoint->sendVerificationCode($checkApiPath, $cCode);
                    /** @noinspection PhpUndefinedMethodInspection */
                    $this->finishCheckpoint($challenge);
                    Utils::log("Suspicious Login: Attempted to bypass checkpoint, good luck!");
                    break 2;
                    /** @noinspection PhpUndefinedClassInspection */ case $challengeEx instanceof InstagramAPI\Exception\Checkpoint\SubmitPhoneException:
                    Utils::log("Suspicious Login: Please enter enter the phone number on the account for verification...");
                    $phone = Utils::promptInput();
                    /** @noinspection PhpUndefinedFieldInspection */
                    $this->checkpoint->sendVerificationPhone($checkApiPath, $phone);
                    break;
                    /** @noinspection PhpUndefinedClassInspection */ case $challengeEx instanceof InstagramAPI\Exception\Checkpoint\SubmitEmailException:
                    Utils::log("Suspicious Login: Please enter enter the email address on the account for verification...");
                    $email = Utils::promptInput();
                    /** @noinspection PhpUndefinedFieldInspection */
                    $this->checkpoint->sendVerificationEmail($checkApiPath, $email);
                    break;
                    default:
                        Utils::log("Suspicious Login: Reached unknown challenge step :(.");
                        Utils::dump();
                        exit(1);
                }
            } /** @noinspection PhpUndefinedClassInspection */ catch (InstagramAPI\Exception\Checkpoint\ChallengeIterationsLimitException $ex) {
                Utils::log("Suspicious Login: Account Challenge Failed :(.");
                Utils::dump($ex->getMessage());
                exit(1);
            } catch (Exception $ex) {
                $challengeEx = $ex;
            }
        }
    }
}