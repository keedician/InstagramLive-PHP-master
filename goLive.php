<?php
/** @noinspection PhpComposerExtensionStubsInspection */
/** @noinspection PhpUndefinedConstantInspection */

set_time_limit(0);
date_default_timezone_set('America/New_York');
if (php_sapi_name() !== "cli") {
    die("This script may not be run on a website!");
}

//Script version constants
define("scriptVersion", "2.1.2");
define("scriptVersionCode", "69");
define("scriptFlavor", "stable");

//Command Line Argument Registration
$helpData = [];
registerArgument($helpData, $argv, "help", "Help", "Displays this message.", "h", "help");
registerArgument($helpData, $argv, "bypassCheck", "Bypass OS Check", "Bypasses the operating system check. Please do not use this if don't know what you're doing!", "b", "bypass-check");
registerArgument($helpData, $argv, "forceLegacy", "Force Legacy Mode", "Forces legacy mode for Windows & Mac users.", "l", "force-legacy");
registerArgument($helpData, $argv, "bypassCutoff", "Bypass Cutoff", "Bypasses stream cutoff after one hour. Please do not use this if you are not verified!", "-bypass-cutoff");
registerArgument($helpData, $argv, "infiniteStream", "Infinite Stream", "Automatically starts a new stream after the hour cutoff.", "i", "infinite-stream");
registerArgument($helpData, $argv, "autoArchive", "Auto Archive", "Automatically archives a live stream after it ends.", "a", "auto-archive");
registerArgument($helpData, $argv, "autoDiscard", "Auto Discard", "Automatically discards a live stream after it ends.", "d", "auto-discard");
registerArgument($helpData, $argv, "logCommentOutput", "Log Comment Output", "Logs comment and like output into a text file.", "o", "comment-output");
registerArgument($helpData, $argv, "obsAutomationAccept", "Accept OBS Automation Prompt", "Automatically accepts the OBS prompt.", "-obs");
registerArgument($helpData, $argv, "obsNoStream", "Disable OBS Auto-Launch", "Disables automatic stream start in OBS.", "-obs-no-stream");
registerArgument($helpData, $argv, "obsNoIni", "Disable OBS Auto-Settings", "Disable automatic resolution changes and only modifies the stream url/key.", "-obs-only-key");
registerArgument($helpData, $argv, "obsNoWait", "Disable OBS Wait", "Disable waiting for OBS to launch.", "-obs-no-wait");
registerArgument($helpData, $argv, "disableObsAutomation", "Disable OBS Automation", "Disables OBS automation and subsequently disables the path check.", "-no-obs");
registerArgument($helpData, $argv, "startDisableComments", "Disable Comments", "Automatically disables commands when the stream starts.", "-dcomments");
registerArgument($helpData, $argv, "thisIsAPlaceholder", "Limit Stream Time", "Sets the amount of time to limit the stream to in seconds. (Example: --stream-sec=60).", "-stream-sec");
registerArgument($helpData, $argv, "thisIsAPlaceholder1", "Auto Pin Comment", "Sets a comment to automatically pin when the live stream starts. Note: Use underscores for spaces. (Example: --auto-pin=Hello_World!).", "-auto-pin");
registerArgument($helpData, $argv, "forceSlobs", "Force StreamLabs-OBS", "Forces OBS Integration to prefer Streamlabs OBS over normal OBS.", "-streamlabs-obs");
registerArgument($helpData, $argv, "promptLogin", "Prompt Username & Password", "Ignores config.php and prompts you for your username and password.", "p", "prompt-login");
registerArgument($helpData, $argv, "bypassPause", "Bypass Pause", "Dangerously bypasses pause before starting the livestream.", "-bypass-pause");
registerArgument($helpData, $argv, "noBackup", "Disable Stream Recovery", "Disables stream recovery for crashes or accidental window closes.", "-no-recovery");
registerArgument($helpData, $argv, "fightCopyright", "Bypass Copyright Takedowns", "Acknowledges Instagram copyright takedowns but lets you continue streaming. This is at your own risk although it should be safe.", "-auto-policy");
registerArgument($helpData, $argv, "experimentalQuestion", "Enable Stream Questions", "Experimental: Attempts to allow viewers to ask questions while streaming.", "q", "stream-ama");
registerArgument($helpData, $argv, "webMode", "Web Console Mode", "Starts and uses a website console rather than the command line.", "w", "web");
registerArgument($helpData, $argv, "debugMode", "Enable Debug Mode", "Displays all requests being sent to Instagram.", "-debug");
registerArgument($helpData, $argv, "discardRecovery", "Auto Discard Recovery", "Automatically discards stream recovery.", "-discard-recovery");
registerArgument($helpData, $argv, "acceptRecovery", "Auto Accept Recovery", "Automatically accepts stream recovery.", "-accept-recovery");
registerArgument($helpData, $argv, "dump", "Trigger Dump", "Forces an error dump for debug purposes.", "-dump");
registerArgument($helpData, $argv, "dumpVersion", "", "Dumps current release version.", "-dumpVersion");
registerArgument($helpData, $argv, "dumpFlavor", "", "Dumps current release flavor.", "-dumpFlavor");
registerArgument($helpData, $argv, "dumpCli", "", "Dumps current command-line arguments into json.", "-dumpCli");
registerArgument($helpData, $argv, "dumpCmds", "", "Dumps current commands into json.", "-dumpCmds");

//Parse special command line arguments
$streamTotalSec = 0;
$autoPin = null;
foreach ($argv as $curArg) {
    if (strpos($curArg, '--stream-sec=') !== false) {
        $streamTotalSec = (int)str_replace('--stream-sec=', '', $curArg);
    }
    if (strpos($curArg, '--auto-pin=') !== false) {
        $autoPin = str_replace('_', ' ', str_replace('--auto-pin=', '', $curArg));
    }
}

//Register commands
$commandData = [];
$commandInfo = [];
registerCommand($commandData, $commandInfo, 'ecomments', "Enables comments", "", function (StreamTick $tick) {
    $tick->ig->live->enableComments($tick->broadcastId);
    return "Enabled Comments.";
});
registerCommand($commandData, $commandInfo, 'dcomments', "Disables comments", "", function (StreamTick $tick) {
    $tick->ig->live->disableComments($tick->broadcastId);
    return "Disabled Comments.";
});
registerCommand($commandData, $commandInfo, 'end', "Ends the livestream", "(yes/no) Archive Stream", function (StreamTick $tick) {
    endLivestreamFlow($tick->ig, $tick->broadcastId, $tick->values[0], $tick->obsAuto, $tick->helper, $tick->pid, $tick->commentCount, $tick->likeCount, $tick->burstLikeCount);
});
registerCommand($commandData, $commandInfo, 'pin', "Pins a comment", "Comment ID", function (StreamTick $tick) {
    $commentId = $tick->values[0];
    if (strlen($commentId) === 17 && //Comment IDs are 17 digits
        is_numeric($commentId) && //Comment IDs only contain numbers
        strpos($commentId, '-') === false) { //Comment IDs are not negative
        $tick->ig->live->pinComment($tick->broadcastId, $commentId);
        return "Pinned a comment!";
    } else {
        var_dump($tick->values);
        return "You entered an invalid comment id!";
    }
}, false);
registerCommand($commandData, $commandInfo, 'unpin', "Unpins the currently pinned comment", "", function (StreamTick $tick) {
    if ($tick->lastCommentPin == -1) {
        return "You have no comment pinned!";
    } else {
        $tick->ig->live->unpinComment($tick->broadcastId, $tick->lastCommentPin);
        return "Unpinned the pinned comment!";
    }
});
registerCommand($commandData, $commandInfo, 'pinned', "Displays the currently pinned comment", "", function (StreamTick $tick) {
    if ($tick->lastCommentPin == -1) {
        return "There is no comment pinned!";
    } else {
        return "Pinned Comment:\n @" . $tick->lastCommentPinHandle . ': ' . $tick->lastCommentPinText;
    }
});
registerCommand($commandData, $commandInfo, 'comment', "Posts a comment on the stream", "Comment Text", function (StreamTick $tick) {
    $text = $tick->values[0];
    if ($text !== "") {
        $tick->ig->live->comment($tick->broadcastId, $text);
        return "Commented on stream!";
    } else {
        return "Comments may not be empty!";
    }
});
registerCommand($commandData, $commandInfo, 'url', "Displays the stream url", "", function (StreamTick $tick) {
    return "================================ Stream URL ================================\n" . $tick->streamUrl . "\n================================ Stream URL ================================";
});
registerCommand($commandData, $commandInfo, 'key', "Displays the stream key", "", function (StreamTick $tick) {
    if (Utils::isWindows()) {
        shell_exec("echo " . Utils::sanitizeStreamKey($tick->streamKey) . " | clip");
        Utils::log("Windows: Your stream key has been pre-copied to your clipboard.");
    }
    return "======================== Current Stream Key ========================\n" . $tick->streamKey . "\n======================== Current Stream Key ========================";
});
registerCommand($commandData, $commandInfo, 'info', "Displays general info about the stream", "", function (StreamTick $tick) {
    return "Info:\nStatus: $tick->broadcastStatus\nTop Live Eligible: " . ($tick->topLiveEligible === 1 ? "true" : "false") . "\nViewer Count: $tick->viewerCount\nTotal Unique Viewer Count: $tick->totalViewerCount";
});
registerCommand($commandData, $commandInfo, 'viewers', "Displays the list of people viewing the stream", "", function (StreamTick $tick) {
    $response = "Viewers:\n";
    $info = $tick->ig->live->getInfo($tick->broadcastId);
    foreach ($tick->ig->live->getViewerList($tick->broadcastId)->getUsers() as &$cuser) {
        $response = $response . "[" . $cuser->getPk() . "] @" . $cuser->getUsername() . " (" . $cuser->getFullName() . ")\n";
    }
    if ($info->getViewerCount() > 0) {
        return $response . "Total Viewers: " . $info->getViewerCount();
    } else {
        return $response . "There are no live viewers.";
    }
});
registerCommand($commandData, $commandInfo, 'questions', "Displays all questions from the stream", "", function (StreamTick $tick) {
    $response = "Questions:\n";
    foreach ($tick->ig->live->getLiveBroadcastQuestions($tick->broadcastId)->getQuestions() as $cquestion) {
        $response = $response . "[ID: " . $cquestion->getQid() . "] @" . $cquestion->getUser()->getUsername() . ": " . $cquestion->getText();
    }
    return $response;
});
registerCommand($commandData, $commandInfo, 'showquestion', "Shows a question on the stream", "Question ID", function (StreamTick $tick) {
    $questionId = $tick->values[0];
    if (strlen($questionId) === 17 && //Question IDs are 17 digits
        is_numeric($questionId) && //Question IDs only contain numbers
        strpos($questionId, '-') === false) { //Question IDs are not negative
        $tick->lastQuestion = $questionId;
        $tick->ig->live->showQuestion($tick->broadcastId, $questionId);
        return "Displayed question!";
    } else {
        return "Invalid question id!";
    }
});
registerCommand($commandData, $commandInfo, 'hidequestion', "Hides the currently hidden question from the stream", "", function (StreamTick $tick) {
    if ($tick->lastQuestion == -1) {
        return "There is no question displayed!";
    } else {
        $tick->ig->live->hideQuestion($tick->broadcastId, $tick->lastQuestion);
        $tick->lastQuestion = -1;
        return "Removed the displayed question!";
    }
});
registerCommand($commandData, $commandInfo, 'wave', "Waves at a user who has joined the stream", "User ID", function (StreamTick $tick) {
    $viewerId = $tick->values[0];
    try {
        @$tick->ig->live->wave($tick->broadcastId, $viewerId);
        return "Waved at a user!";
    } catch (Exception $waveError) {
        return "User does not exist or user has already been waved at.";
    }
});
registerCommand($commandData, $commandInfo, 'block', "Blocks a user from your account", "User ID", function (StreamTick $tick) {
    $userId = $tick->values[0];
    @$tick->ig->people->block($userId);
    return "Blocked a user!";
});
registerCommand($commandData, $commandInfo, 'hide', "Hides your streams and stories from a user", "User ID", function (StreamTick $tick) {
    $userId = $tick->values[0];
    @$tick->ig->people->blockMyStory($userId, 'InstaVideoComments');
    return "Hide streams and stories from user!";
});

//Load config and utils
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/config.php';

//Dump json-encoded command line arguments
if (dumpCli) {
    print json_encode($helpData);
    exit(0);
}

if (dumpCmds) {
    if (Utils::isWindows()) {
        print json_encode($commandInfo);
        exit(0);
    }
    require_once __DIR__ . '/json.php';
    $jsonHelper = new JsonHelper();
    print $jsonHelper->encode($commandInfo);
    exit(0);
}

//Dump script semantic version
if (dumpVersion) {
    Utils::log(scriptVersion);
    exit(0);
}

//Dump script flavor (update channel)
if (dumpFlavor) {
    Utils::log(scriptFlavor);
    exit(0);
}

//Dump system info
if (dump) {
    Utils::dump();
    exit(0);
}

//Ensure sub-modules exist
Utils::existsOrError(__DIR__ . '/vendor/autoload.php', "Instagram API Files");
Utils::existsOrError(__DIR__ . '/obs.php', "OBS Integration");

Utils::log("Loading InstagramLive-PHP v" . scriptVersion . "-" . scriptFlavor . " (" . scriptVersionCode . ")...");

//Check for a new update
if (Utils::checkForUpdate(scriptVersionCode, scriptFlavor)) {
    //Apply the update if auto update is set to true in config
    if (UPDATE_AUTO) {
        Utils::log("Update: A new version of InstagramLive-PHP is available and is being installed ...");
        exec("\"" . PHP_BINARY . "\" update.php");
        Utils::log("Update: Update Installed! Please re-run the script.");
        exit(0);
    }
    Utils::log("\nUpdate: A new update is available! Please run the `update.php` script to update.");
    Utils::log("Protip: You can set 'UPDATE_AUTO' to true in your config.php to have updates automatically install!\n");
}

//Ensure the target api commit is set in composer.lock
if (!Utils::isApiDevMaster()) {
    Utils::log("Update: Outdated Instagram-API version detected. Attempting a fix...");
    exec("\"" . PHP_BINARY . "\" update.php");
    Utils::log("Update: Update Installed! Please re-run the script.");
    exit(0);
}

//Show command line arguments when --help is used
if (help) {
    Utils::log("Command Line Arguments:");
    foreach ($helpData as $option) {
        $dOption = json_decode($option, true);
        Utils::log($dOption['tacks']['mini'] . ($dOption['tacks']['full'] !== null ? " (" . $dOption['tacks']['full'] . "): " : ": ") . $dOption['description']);
    }
    exit(0);
}

//Load composer and obs utils
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/obs.php';

//Imports
use InstagramAPI\Instagram;
use InstagramAPI\Request\Live;
use InstagramAPI\Response\FinalViewerListResponse;
use InstagramAPI\Response\GenericResponse;
use InstagramAPI\Response\Model\Comment;
use InstagramAPI\Signatures;

//Run preparation flow
preparationFlow(new ObsHelper(!obsNoStream, disableObsAutomation, forceSlobs, (!obsNoIni && OBS_MODIFY_SETTINGS)), $argv, $commandData, $streamTotalSec, $autoPin);

/**
 * Runs the preparation code such as logging in and creating the stream.
 * @param ObsHelper $helper The ObsHelper object used for obs actions.
 * @param array $args The array of arguments passed to the script.
 * @param array $commandData The array of commands registered.
 * @param int $streamTotalSec The amount of time to cap the stream at. 0 if no cap.
 * @param string|null $autoPin The comment to auto pin the stream
 */
function preparationFlow($helper, $args, $commandData, $streamTotalSec = 0, $autoPin = null)
{
    //Ensure that static files are here in webMode
    if (webMode && !file_exists('static/')) {
        Utils::log("Web Server: Static files for the web server are missing, attempting to fetch them");
        exec("\"" . PHP_BINARY . "\" update.php");
        Utils::log("Web Server: Static files *probably* fetched, please re-run this script.");
    }

    $username = trim(IG_USERNAME);
    $password = trim(IG_PASS);
    if (promptLogin) {
        Utils::log("Credentials: Please enter your credentials...");
        $username = Utils::promptInput("Username:");
        $password = Utils::promptInput("Password:");
    }

    if ($username === "USERNAME" || $password == "PASSWORD") {
        Utils::log("Credentials: The default username or password has not been changed, exiting...");
        Utils::log("Protip: You can run the script like 'php goLive.php -p' to enter your credentials when the script loads.");
        exit(1);
    }

    Utils::log("Login: Starting Instagram logon, please wait...");
    //Run our login flow to handle two-factor and challenges
    $ig = Utils::loginFlow($username, $password, debugMode);
    if (!$ig->isMaybeLoggedIn) {
        Utils::log("Login: Unsuccessful login.");
        Utils::dump(null, $ig->client->getLastRequest());
        exit(1);
    }
    Utils::log("Login: Successfully logged in as " . $ig->username . "!");

    //Parse and validate recovery if present
    try {
        if (Utils::isRecovery() && ($ig->live->getInfo(Utils::getRecovery()['broadcastId'])->getBroadcastStatus() !== 'interrupted')) {
            Utils::log("Recovery: Detected recovery was outdated, deleting...");
            Utils::deleteRecovery();
            Utils::log("Recovery: Deleted Outdated Recovery!");
        }
    } catch (Exception $e) {
        Utils::log("Recovery: Detected recovery was outdated, deleting...");
        Utils::deleteRecovery();
        Utils::log("Recovery: Deleted Outdated Recovery!");
    }

    if (discardRecovery) {
        Utils::deleteRecovery();
    }

    if (Utils::isRecovery() && !acceptRecovery) {
        Utils::log("Recovery: Detected a previous stream that exited improperly! Would you like to pick up where you left off or start from scratch?");
        Utils::log("Recovery: Type\"yes\" to pick up where you left off or enter to start from scratch...");
        if (Utils::promptInput() !== "yes") {
            Utils::deleteRecovery();
        }
    }

    try {
        //Livestream creation
        $obsAutomation = true;
        if (!Utils::isRecovery()) {
            //Normal livestream creation flow
            $info = $ig->people->getSelfInfo();
            Utils::log("Livestream: Creating livestream...");
            $stream = $ig->live->create(OBS_X, OBS_Y);
            @define('maxTime', $stream->isMaxTimeInSeconds() ? ($stream->getMaxTimeInSeconds() - 100) : 3480);
            @define('heartbeatInterval', $stream->isHeartbeatInterval() ? $stream->getHeartbeatInterval() : 2);

            $broadcastId = $stream->getBroadcastId();
            $streamUploadUrl = $stream->getUploadUrl();

            //Split the stream key from the stream url
            $split = preg_split("[" . $broadcastId . "]", $streamUploadUrl);
            $streamUrl = trim($split[0]);
            $streamKey = trim($broadcastId . $split[1]);

            Utils::log("Livestream: Created livestream!");

            if (!ANALYTICS_OPT_OUT) {
                Utils::analytics("live", scriptVersion, scriptFlavor, PHP_OS, count($args), $info->getUser()->getFollowerCount());
            }
        } else {
            //Recovery livestream flow
            Utils::log("Recovery: Restarting previous stream...");
            $recoveryData = Utils::getRecovery();
            $broadcastId = $recoveryData['broadcastId'];
            $streamUrl = $recoveryData['streamUrl'];
            $streamKey = $recoveryData['streamKey'];
            $obsAutomation = (bool)$recoveryData['obs'];
            $helper = unserialize($recoveryData['obsObject']);
            define('maxTime', 3480);
            define('heartbeatInterval', 2);
        }

        //OBS integration prompt
        if (!Utils::isRecovery()) {
            if ($helper->obs_path === null) {
                Utils::log("OBS Integration: OBS not detected, disabling! " . (!Utils::isWindows() ? "Please note macOS is not supported!" : "Please make a ticket on GitHub if you have OBS installed."));
                $obsAutomation = false;
            } else {
                if (!obsAutomationAccept) {
                    Utils::log("OBS Integration: Would you like to automatically start streaming to OBS? Type \"yes\" or press enter to ignore.");
                    Utils::log("Protip: You can run the script like 'php goLive.php --obs' to automatically accept this prompt or 'php goLive.php --no-obs' to automatically reject it.");
                    if (Utils::promptInput() !== "yes") {
                        $obsAutomation = false;
                    }
                }
            }
        }

        Utils::log("================================ Stream URL ================================\n" . $streamUrl . "\n================================ Stream URL ================================");
        Utils::log("======================== Current Stream Key ========================\n" . $streamKey . "\n======================== Current Stream Key ========================\n");

        //OBS integration
        if (!Utils::isRecovery()) {
            if (!$obsAutomation) {
                if (Utils::isWindows()) {
                    shell_exec("echo " . Utils::sanitizeStreamKey($streamKey) . " | clip");
                    Utils::log("Windows: Your stream key has been pre-copied to your clipboard.");
                }
            } else {
                if ($helper->isObsRunning()) {
                    Utils::log("OBS Integration: Killing OBS...");
                    $helper->killOBS();
                }
                if (!$helper->attempted_settings_save) {
                    Utils::log("OBS Integration: Backing-up your old OBS basic.ini...");
                    $helper->saveSettingsState();
                }
                Utils::log("OBS Integration: Loading basic.ini with optimal OBS settings...");
                $helper->updateSettingsState();
                if (!$helper->attempted_service_save) {
                    Utils::log("OBS Integration: Backing-up your old OBS service.json...");
                    $helper->saveServiceState();
                }
                Utils::log("OBS Integration: Populating service.json with new stream url & key.");
                $helper->setServiceState($streamUrl, $streamKey);
                if (!$helper->slobsPresent) {
                    Utils::log("OBS Integration: Re-launching OBS...");
                    $helper->spawnOBS();
                    if (!obsNoWait) {
                        Utils::log("OBS Integration: Waiting up to 5 seconds for OBS...");
                        if ($helper->waitForOBS()) {
                            sleep(1);
                            Utils::log("OBS Integration: OBS Launched Successfully! Starting Stream...");
                        } else {
                            Utils::log("OBS Integration: OBS was not detected! Press enter once you confirm OBS is streaming...");
                            if (!bypassPause) {
                                Utils::promptInput("");
                            }
                        }
                    }
                }
            }
        }

        //Stream Pause
        if ((!$obsAutomation || obsNoStream || $helper->slobsPresent) && !Utils::isRecovery() && !bypassPause) {
            Utils::log("Please " . ($helper->slobsPresent ? "launch Streamlabs OBS and " : " ") . "start streaming to the url and key above! Once you are live, please press enter!");
            Utils::promptInput("");
        }

        //Start stream
        if (!Utils::isRecovery()) {
            Utils::log("Livestream: Starting livestream...");
            $ig->live->start($broadcastId);
            Utils::log("Livestream: Started livestream!");
            if (experimentalQuestion && !$ig->isExperimentEnabled('ig_android_live_qa_broadcaster_v1_universe', 'is_enabled')) {
                try {
                    $ig->request("live/{$broadcastId}/question_status/")
                        ->setSignedPost(false)
                        ->addPost('_csrftoken', $ig->client->getToken())
                        ->addPost('_uuid', $ig->uuid)
                        ->addPost('allow_question_submission', true)
                        ->getResponse(new GenericResponse());
                    Utils::log("Livestream: Successfully enabled experimental viewer AMA.");
                } catch (Exception $e) {
                    Utils::log("Livestream: Unable to enable experimental viewer AMA!");
                }
            }
        }

        //Auto-pin comment
        if ($autoPin !== null) {
            $ig->live->pinComment($broadcastId, $ig->live->comment($broadcastId, $autoPin)->getComment()->getPk());
            Utils::log("Livestream: Automatically pinned requested comment.");
        }

        //Auto disable comments
        if (startDisableComments) {
            $ig->live->disableComments($broadcastId);
            Utils::log("Livestream: Automatically disabled comments.");
        }

        if ((Utils::isWindows() || Utils::isMac() || bypassCheck) && !forceLegacy) {
            Utils::log("Command Line: Windows/macOS Detected! A new console will open for command input and this will display command and livestream output.");
            $startCommentTs = 0;
            $startLikeTs = 0;
            $startingQuestion = -1;
            $startingTime = -1;
            if (Utils::isRecovery()) {
                $recoveryData = Utils::getRecovery();
                $startCommentTs = $recoveryData['lastCommentTs'];
                $startLikeTs = $recoveryData['lastLikeTs'];
                $startingQuestion = $recoveryData['lastQuestion'];
                $startingTime = $recoveryData['startTime'];
            }
            livestreamingFlow($ig, $broadcastId, $streamUrl, $streamKey, $obsAutomation, $helper, $streamTotalSec, $autoPin, $args, $commandData, $startCommentTs, $startLikeTs, $startingQuestion, $startingTime);
        } else {
            Utils::log("Command Line: Linux Detected! The script has entered legacy mode. Please use Windows or macOS for all the latest features.");
            legacyLivestreamingFlow($ig->live, $broadcastId, $streamUrl, $streamKey, $obsAutomation, $helper);
        }

        Utils::log("Livestream: Something has gone wrong!");
        endLivestreamFlow($ig, $broadcastId, '', $obsAutomation, $helper, 0, 0, 0, 0);
    } catch (Exception $e) {
        Utils::log("Error: An error occurred during livestream initialization.");
        Utils::dump($e->getMessage(), $ig->client->getLastRequest());
        exit(1);
    }
}

/**
 * Starts the livestreaming flow.
 * @param Instagram $ig Authenticated instagram object.
 * @param string $broadcastId Livestream broadcast id.
 * @param string $streamUrl Livestream stream url.
 * @param string $streamKey Livestream stream key.
 * @param bool $obsAuto True if obs automation is enabled.
 * @param ObsHelper $helper The ObsHelper object.
 * @param int $streamTotalSec The amount of time to cap the stream at. 0 if no cap.
 * @param string|null $autoPin The comment to auto pin the stream.
 * @param array $args The array of arguments passed to the script.
 * @param array $commandData Command callbacks.
 * @param int $startCommentTs
 * @param int $startLikeTs
 * @param int $startingQuestion
 * @param int $startingTime
 */
function livestreamingFlow($ig, $broadcastId, $streamUrl, $streamKey, $obsAuto, $helper, $streamTotalSec, $autoPin, $args, $commandData, $startCommentTs = 0, $startLikeTs = 0, $startingQuestion = -1, $startingTime = -1)
{
    $pid = 0;
    if (bypassCheck && !Utils::isMac() && !Utils::isWindows()) {
        Utils::log("Command Line: You are forcing the new command line. This is unsupported and may result in issues.");
        Utils::log("Command Line: To start the new command line, please run the commandLine.php script.");
    } else {
        $consoleCommand = PHP_BINARY . (Utils::isWindows() ? "\" " : (Utils::isMac() ? (" " . __DIR__ . "/") : "")) . "commandLine.php" . (autoArchive === true ? " -a" : "") . (autoDiscard === true ? " -d" : "");
        if (webMode) {
            $consoleCommand = PHP_BINARY . (Utils::isWindows() ? "\"" : "") . " -S " . WEB_HOST . ":" . WEB_PORT . " " . (Utils::isMac() ? __DIR__ . "/" : "") . "webServer.php" . (autoArchive === true ? " -a" : "") . (autoDiscard === true ? " -d" : "");
        }
        $cmd = "";
        if (Utils::isWindows()) {
            $cmd = "start \"InstagramLive-PHP: Command Line\" \"" . $consoleCommand;
        } elseif (Utils::isMac()) {
            $cmd = "osascript -e 'tell application \"Terminal\" to do script \"" . $consoleCommand . "\"'";
        }
        $process = proc_open($cmd, array(), $pipe);
        $pid = intval(proc_get_status($process)['pid']);
        if (Utils::isWindows()) {
            $wmic = array_filter(explode(" ", shell_exec("wmic process get parentprocessid,processid | find \"" . $pid . "\"")));
            array_pop($wmic);
            $pid = end($wmic);
        } else {
            $pid += 1;
        }
        proc_close($process);
    }

    //Initialize variables
    @cli_set_process_title("InstagramLive-PHP: Live Chat & Likes");
    $broadcastStatus = 'Unknown';
    $topLiveEligible = 0;
    $viewerCount = 0;
    $totalViewerCount = 0;
    $lastCommentTs = $startCommentTs;
    $lastLikeTs = $startLikeTs;
    $lastCommentPin = -1;
    $lastCommentPinHandle = '';
    $lastCommentPinText = '';
    $exit = false;
    $startTime = ($startingTime === -1 ? time() : $startingTime);
    $userCache = array();
    $attemptedFight = false;
    $commentCount = 0;
    $likeCount = 0;
    $likeBurstCount = 0;

    //Remove old command requests
    @unlink(__DIR__ . '/request');
    @unlink(__DIR__ . '/webLink.json');

    //Log new streaming session if comment output is enabled
    if (logCommentOutput) {
        Utils::logOutput(PHP_EOL . "--- New Session At Epoch: " . time() . " ---" . PHP_EOL);
    }

    //Begin livestream loop
    $streamTick = new StreamTick();
    if ($startingQuestion !== -1) {
        $streamTick->lastQuestion = $startingQuestion;
    }
    do {
        $consoleOutput = array();
        $request = json_decode(@file_get_contents(__DIR__ . '/request'), true);
        if (!empty($request)) {
            try {
                $cmd = $request['cmd'];
                if (isset($commandData[$cmd]) && is_callable($commandData[$cmd])) {
                    $streamTick = $streamTick->doTick($request['values'], $ig, $broadcastId, $helper, $obsAuto, $lastCommentPin, $lastCommentPinHandle, $lastCommentPinText, $streamUrl, $streamKey, $broadcastStatus, $topLiveEligible, $viewerCount, $totalViewerCount, $pid, $commentCount, $likeCount, $likeBurstCount);
                    $response = @call_user_func($commandData[$cmd], $streamTick);
                    @file_put_contents(__DIR__ . '/response', $response);
                    Utils::log($response);
                    $consoleOutput[] = $response;
                }
                @unlink(__DIR__ . '/request');
            } catch (Exception $e) {
                Utils::log("Error: An error occurred during command execution.");
                Utils::dump($e->getMessage(), $ig->client->getLastRequest());
            }
        }

        //Process Comments
        try {
            $commentsResponse = $ig->live->getComments($broadcastId, $lastCommentTs); //Request comments since the last time we checked
        } catch (Exception $e) {
            Utils::log("Error while getting comments:");
            Utils::dump($e->getMessage(), $ig->client->getLastRequest());
        }
        $systemComments = $commentsResponse->getSystemComments(); //Metric data about comments and likes
        $comments = $commentsResponse->getComments(); //Get the actual comments from the request we made
        if (!empty($systemComments)) {
            $lastCommentTs = $systemComments[0]->getCreatedAt();
        }
        if (!empty($comments) && $comments[0]->getCreatedAt() > $lastCommentTs) {
            $lastCommentTs = $comments[0]->getCreatedAt();
        }

        if ($commentsResponse->isPinnedComment()) {
            $pinnedComment = $commentsResponse->getPinnedComment();
            $lastCommentPin = $pinnedComment->getPk();
            $lastCommentPinHandle = $pinnedComment->getUser()->getUsername();
            $lastCommentPinText = $pinnedComment->getText();
        } else {
            $lastCommentPin = -1;
        }

        $commentsArray = array();
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                addComment($comment);
                $commentsArray[] = [
                    "commentId" => $comment->getPk(),
                    "userId" => $comment->getUser()->getPk(),
                    "username" => $comment->getUser()->getUsername(),
                    "text" => $comment->getText()
                ];
                $commentCount++;
            }
        }
        if (!empty($systemComments)) {
            foreach ($systemComments as $systemComment) {
                if (strpos($systemComment->getPk(), "joined_at") !== false) {
                    if (!isset($userCache[$systemComment->getUser()->getPk()])) {
                        $userCache[$systemComment->getUser()->getPk()] = $systemComment->getUser()->getUsername();
                    }
                }
                addComment($systemComment, true);
                $consoleOutput[] = $systemComment->getText();
            }
        }

        //Process Likes
        $likesArray = array();
        $likeCountResponse = $ig->live->getLikeCount($broadcastId, $lastLikeTs); //Get our current batch for likes
        $lastLikeTs = $likeCountResponse->getLikeTs();
        foreach ($likeCountResponse->getLikers() as $user) {
            addLike((isset($userCache[$user->getUserId()]) ? ("@" . $userCache[$user->getUserId()]) : "An Unknown User"));
            $likesArray[] = (isset($userCache[$user->getUserId()]) ? ("@" . $userCache[$user->getUserId()]) : "An Unknown User") . " has liked the stream!";
        }

        $likeCount = $likeCountResponse->getLikes();
        $likeBurstCount = $likeCountResponse->getBurstLikes();

        //Send Heartbeat and Fetch Info
        $heartbeatResponse = $ig->live->getHeartbeatAndViewerCount($broadcastId); //Maintain :clap: comments :clap: and :clap: likes :clap: after :clap: stream
        $broadcastStatus = $heartbeatResponse->getBroadcastStatus();
        $topLiveEligible = $heartbeatResponse->getIsTopLiveEligible();
        $viewerCount = $heartbeatResponse->getViewerCount();
        $totalViewerCount = $heartbeatResponse->getTotalUniqueViewerCount();

        $ig->live->getJoinRequestCounts($broadcastId);

        //Handle Livestream Takedowns
        if ($heartbeatResponse->isIsPolicyViolation() && (int)$heartbeatResponse->getIsPolicyViolation() === 1) {
            Utils::log("Policy: Instagram has sent a policy violation" . ((fightCopyright && !$attemptedFight) ? "." : " and you stream has been stopped!") . " The following policy was broken: " . ($heartbeatResponse->getPolicyViolationReason() == null ? "Unknown" : $heartbeatResponse->getPolicyViolationReason()));
            if ($attemptedFight || !fightCopyright) {
                Utils::dump("Policy Violation: " . ($heartbeatResponse->getPolicyViolationReason() == null ? "Unknown" : $heartbeatResponse->getPolicyViolationReason()), $ig->client->getLastRequest());
                endLivestreamFlow($ig, $broadcastId, '', $obsAuto, $helper, $pid, $commentCount, $likeCount, $likeBurstCount);
            }
            $ig->live->resumeBroadcastAfterContentMatch($broadcastId);
            $attemptedFight = true;
        }

        //Calculate Times for Limiter Argument
        if ($streamTotalSec > 0 && (time() - $startTime) >= $streamTotalSec) {
            endLivestreamFlow($ig, $broadcastId, '', $obsAuto, $helper, $pid, $commentCount, $likeCount, $likeBurstCount, false);
            Utils::log("Livestream: The livestream has ended due to user requested stream limit of $streamTotalSec seconds!");

            $archived = "yes";
            if (!autoArchive && !autoDiscard) {
                Utils::log("Livestream: Would you like to archive this stream?");
                $archived = Utils::promptInput();
            }
            if (autoArchive || $archived == 'yes' && !autoDiscard) {
                $ig->live->addToPostLive($broadcastId);
                Utils::log("Livestream: Added livestream to archive!");
            }
            exit(0);
        }

        //Calculate Times for Hour-Cutoff
        if (!bypassCutoff && (time() - $startTime) >= maxTime) {
            endLivestreamFlow($ig, $broadcastId, '', $obsAuto, $helper, $pid, $commentCount, $likeCount, $likeBurstCount, false);
            Utils::log("Livestream: The livestream has ended due to Instagram's one hour time limit!");
            $archived = "yes";
            if (!autoArchive && !autoDiscard) {
                Utils::log("Livestream: Would you like to archive this stream?");
                $archived = Utils::promptInput();
            }
            if (autoArchive || $archived == 'yes' && !autoDiscard) {
                $ig->live->addToPostLive($broadcastId);
                Utils::log("Livestream: Added livestream to archive!");
            }
            $restart = "yes";
            if (!infiniteStream) {
                Utils::log("Livestream: Would you like to go live again?");
                $restart = Utils::promptInput();
            }
            if ($restart == 'yes') {
                Utils::log("Livestream: Restarting livestream!");
                Utils::deleteRecovery();
                preparationFlow($helper, $args, $commandData, $streamTotalSec, $autoPin);
            }
            Utils::log("Command Line: Please close the console window!");
            sleep(heartbeatInterval);
            exit(0);
        }

        if (webMode) {
            file_put_contents('webLink.json', json_encode(array(
                'uuid' => Signatures::generateUUID(),
                'username' => $ig->username,
                'consoleOutput' => $consoleOutput,
                'comments' => $commentsArray,
                'likes' => $likesArray
            )));
        }

        //Backup stream data
        if (!noBackup && STREAM_RECOVERY) {
            Utils::saveRecovery($broadcastId, $streamUrl, $streamKey, $lastCommentTs, $lastLikeTs, $streamTick->lastQuestion, $startTime, $obsAuto, serialize($helper));
        }

        sleep(2);
    } while (!$exit);
}

/**
 * Starts the legacy livestreaming flow for linux devices.
 * @param Live $live Livestream requests instance.
 * @param string $broadcastId Broadcast id of the stream.
 * @param string $streamUrl Stream url.
 * @param string $streamKey Stream key.
 * @param bool $obsAuto True if obs automation is enabled.
 * @param ObsHelper $helper The ObsHelper object used for obs actions.
 */
function legacyLivestreamingFlow($live, $broadcastId, $streamUrl, $streamKey, $obsAuto, $helper)
{
    $line = Utils::promptInput();
    switch ($line) {
        case 'ecomments':
        {
            $live->enableComments($broadcastId);
            Utils::log("Enabled Comments!");
            break;
        }
        case 'dcomments':
        {
            $live->disableComments($broadcastId);
            Utils::log("Disabled Comments!");
            break;
        }
        case 'stop':
        case 'end':
        {
            endLivestreamFlow($live->ig, $broadcastId, '', $obsAuto, $helper, 0, 0, 0, 0, false);
            $archived = "yes";
            if (!autoArchive && !autoDiscard) {
                Utils::log("Livestream: Would you like to archive this stream?");
                $archived = Utils::promptInput();
            }
            if (autoArchive || $archived == 'yes' && !autoDiscard) {
                $live->addToPostLive($broadcastId);
                Utils::log("Livestream: Added livestream to archive!");
            }
            exit(0);
            break;
        }
        case 'url':
        {
            Utils::log("================================ Stream URL ================================\n" . $streamUrl . "\n================================ Stream URL ================================");
            break;
        }
        case 'key':
        {
            Utils::log("======================== Current Stream Key ========================\n" . $streamKey . "\n======================== Current Stream Key ========================");
            if (Utils::isWindows()) {
                shell_exec("echo " . Utils::sanitizeStreamKey($streamKey) . " | clip");
                Utils::log("Windows: Your stream key has been pre-copied to your clipboard.");
            }
            break;
        }
        case 'info':
        {
            $info = $live->getInfo($broadcastId);
            $status = $info->getStatus();
            $muted = var_export($info->is_Messages(), true);
            $count = $info->getViewerCount();
            Utils::log("Info:\nStatus: $status\nMuted: $muted\nViewer Count: $count");
            break;
        }
        case 'viewers':
        {
            Utils::log("Viewers:");
            $live->getInfo($broadcastId);
            $vCount = 0;
            foreach ($live->getViewerList($broadcastId)->getUsers() as &$cuser) {
                Utils::log("[" . $cuser->getPk() . "] @" . $cuser->getUsername() . " (" . $cuser->getFullName() . ")\n");
                $vCount++;
            }
            if ($vCount > 0) {
                Utils::log("Total Viewers: " . $vCount);
            } else {
                Utils::log("There are no live viewers.");
            }
            break;
        }
        case 'wave':
        {
            Utils::log("Please enter the user id you would like to wave at.");
            $viewerId = Utils::promptInput();
            try {
                $live->wave($broadcastId, $viewerId);
                Utils::log("Waved at a user!");
            } catch (Exception $waveError) {
                Utils::log("Could not wave at user! Make sure you're waving at people who are in the stream. Additionally, you can only wave at a person once per stream!");
                Utils::dump($waveError->getMessage(), $live->ig->client->getLastRequest());
            }
            break;
        }
        case 'comment':
        {
            Utils::log("Please enter the text you wish to comment.");
            $text = Utils::promptInput();
            if ($text !== "") {
                $live->comment($broadcastId, $text);
                Utils::log("Commented on stream!");
            } else {
                Utils::log("Comments may not be empty!");
            }
            break;
        }
        case 'help':
        {
            Utils::log("Commands:\nhelp - Prints this message\nurl - Prints Stream URL\nkey - Prints Stream Key\ninfo - Grabs Stream Info\nviewers - Grabs Stream Viewers\necomments - Enables Comments\ndcomments - Disables Comments\ncomment - Leaves a comment on your stream\nwave - Waves at a User\nstop - Stops the Live Stream");
            break;
        }
        default:
        {
            Utils::log("Invalid Command. Type \"help\" for help!");
            break;
        }
    }
    legacyLivestreamingFlow($live, $broadcastId, $streamUrl, $streamKey, $obsAuto, $helper);
}

/**
 * Parses and displays a like in output.
 * @param string $username Username of liker.
 */
function addLike($username)
{
    $cmt = "$username has liked the stream!";
    Utils::log($cmt);
    if (logCommentOutput) {
        Utils::logOutput($cmt);
    }
}

/**
 * Parses and displays a comment.
 * @param Comment $comment Comment object.
 * @param bool $system True if comment is not from user.
 */
function addComment($comment, $system = false)
{
    $cmt = ($system ? "" : ("Comment [ID " . $comment->getPk() . "] @" . $comment->getUser()->getUsername() . ": ")) . $comment->getText();
    Utils::log($cmt);
    if (logCommentOutput) {
        Utils::logOutput($cmt);
    }
}

/**
 * Ends the livestream while taking obs into account.
 * @param Instagram $ig Authenticated instagram object.
 * @param string $broadcastId Livestream broadcast id.
 * @param string $archived 'yes' if stream is archived.
 * @param bool $obsAuto True if obs automation is enabled.
 * @param ObsHelper $helper The ObsHelper object used for obs actions.
 * @param string|int $pid The process id of the web server or command line.
 * @param int $commentCount The amount of comments left on the stream.
 * @param int $likeCount The amount of likes left on the stream.
 * @param int $likeBurstCount The amount of burst likes left on the stream.
 * @param bool $exit True if script should exit after ending stream.
 */
function endLivestreamFlow($ig, $broadcastId, $archived, $obsAuto, $helper, $pid, $commentCount, $likeCount, $likeBurstCount, $exit = true)
{
    if ($obsAuto) {
        Utils::log("OBS Integration: Killing OBS...");
        $helper->killOBS();
        Utils::log("OBS Integration: Restoring old basic.ini...");
        $helper->resetSettingsState();
        Utils::log("OBS Integration: Restoring old service.json...");
        $helper->resetServiceState();
    }
    Utils::log("Livestream: Ending livestream...");
    parseFinalViewers($ig->live->getFinalViewerList($broadcastId));
    Utils::log("Analytics: Your stream ended with $commentCount comments, $likeCount likes, and $likeBurstCount burst likes!");
    $ig->live->end($broadcastId);
    Utils::log("Livestream: Ended livestream!");
    if ($archived == 'yes') {
        $ig->live->addToPostLive($broadcastId);
        Utils::log("Livestream: Added livestream to archive!");
    }
    Utils::deleteRecovery();
    @unlink(__DIR__ . '/request');
    @unlink(__DIR__ . '/webLink.json');
    if (intval($pid) !== 0) {
        Utils::killPid($pid);
    }
    if ($exit) {
        Utils::log("Goodbye: Thanks for streaming with InstagramLive-PHP! Consider donating @ https://www.paypal.me/JoshuaRoy1 <3");
        sleep(2);
        exit(0);
    }
}

/**
 * Parses and displays final viewer list.
 * @param FinalViewerListResponse $finalResponse
 */
function parseFinalViewers($finalResponse)
{
    $finalViewers = '';
    foreach ($finalResponse->getUsers() as $user) {
        $finalViewers = $finalViewers . '@' . $user->getUsername() . ', ';
    }
    $finalViewers = rtrim($finalViewers, " ,");

    if ($finalResponse->getTotalUniqueViewerCount() > 0) {
        Utils::log("Viewers: " . $finalResponse->getTotalUniqueViewerCount() . " Final Viewer(s).");
        Utils::log("Top Viewers: $finalViewers");
    } else {
        Utils::log("Viewers: Your stream had no viewers :(");
    }


    if (logCommentOutput) {
        Utils::logOutput($finalResponse->getTotalUniqueViewerCount() . " Final Viewer(s): $finalViewers");
    }
}

/**
 * Registers a command line argument to a global variable.
 * @param array $helpData The array which holds the command data for the help menu.
 * @param array $argv The array of arguments passed to the script.
 * @param string $name The name to be used in the global variable.
 * @param string $humanName The name to be used in the docs.
 * @param string $description The description of the argument to be used in the help menu.
 * @param string $tack The mini-tack argument name.
 * @param string|null $fullTack The full-tack argument name.
 */
function registerArgument(&$helpData, $argv, $name, $humanName, $description, $tack, $fullTack = null)
{
    if ($fullTack !== null) {
        $fullTack = '--' . $fullTack;
    }
    define($name, in_array('-' . $tack, $argv) || in_array($fullTack, $argv));
    array_push($helpData, json_encode([
        'name' => $name,
        'humanName' => $humanName,
        'description' => $description,
        'tacks' => [
            'mini' => '-' . $tack,
            'full' => $fullTack
        ]
    ]));
}

/**
 * Registers a callable into the command data map.
 * @param array $commandData Target command map.
 * @param array $commandInfo Target command info map.
 * @param string $commandName Name of command.
 * @param string $description Description of command.
 * @param string $argument Argument of command.
 * @param callable $onCommand Callable to execute when the command is run.
 * @param bool $auto If true, command will be automatically handled.
 */
function registerCommand(&$commandData, &$commandInfo, $commandName, $description, $argument, $onCommand, $auto = true)
{
    $commandData[$commandName] = $onCommand;
    array_push($commandInfo, json_encode([
        'name' => $commandName,
        'description' => $description,
        'argument' => $argument,
        'auto' => $auto
    ]));
}

class StreamTick
{
    /**
     * @var array
     */
    public $values;

    /**
     * @var Instagram
     */
    public $ig;

    /**
     * @var string
     */
    public $broadcastId;

    /**
     * @var ObsHelper
     */
    public $helper;

    /**
     * @var bool
     */
    public $obsAuto;

    /**
     * @var int|string
     */
    public $lastCommentPin;

    /**
     * @var string
     */
    public $lastCommentPinHandle;

    /**
     * @var string
     */
    public $lastCommentPinText;

    /**
     * @var string
     */
    public $streamUrl;

    /**
     * @var string
     */
    public $streamKey;

    /**
     * @var string
     */
    public $broadcastStatus;

    /**
     * @var int|string
     */
    public $topLiveEligible;

    /**
     * @var int|string
     */
    public $viewerCount;

    /**
     * @var int|string
     */
    public $totalViewerCount;

    /**
     * @var int|string
     */
    public $pid;

    /**
     * @var int
     */
    public $commentCount;

    /**
     * @var int
     */
    public $likeCount;

    /**
     * @var int
     */
    public $burstLikeCount;

    // Non-ticked variables

    /**
     * @var int|string
     */
    public $lastQuestion = -1;

    public function doTick($values, $ig, $broadcastId, $helper, $obsAuto, $lastCommentPin, $lastCommentPinHandle, $lastCommentPinText, $streamUrl, $streamKey, $broadcastStatus, $topLiveEligible, $viewerCount, $totalViewerCount, $pid, $commentCount, $likeCount, $burstLikeCount): self
    {
        $this->values = $values;
        $this->ig = $ig;
        $this->broadcastId = $broadcastId;
        $this->helper = $helper;
        $this->obsAuto = $obsAuto;
        $this->lastCommentPin = $lastCommentPin;
        $this->lastCommentPinHandle = $lastCommentPinHandle;
        $this->lastCommentPinText = $lastCommentPinText;
        $this->streamUrl = $streamUrl;
        $this->streamKey = $streamKey;
        $this->broadcastStatus = $broadcastStatus;
        $this->topLiveEligible = $topLiveEligible;
        $this->viewerCount = $viewerCount;
        $this->totalViewerCount = $totalViewerCount;
        $this->pid = $pid;
        $this->commentCount = $commentCount;
        $this->likeCount = $likeCount;
        $this->burstLikeCount = $burstLikeCount;
        return $this;
    }
}