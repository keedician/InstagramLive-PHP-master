<?php
/** @noinspection PhpComposerExtensionStubsInspection */
include_once __DIR__ . '/utils.php';
define("autoArchive", in_array("-a", $argv), in_array("--auto-archive", $argv));
define("autoDiscard", in_array("-d", $argv), in_array("--auto-discard", $argv));

@unlink(__DIR__ . '/request');
@unlink(__DIR__ . '/response');

Utils::log("Command Line: Processing commands...");
Utils::log("Command Line: Fetching available commands...");
$commandInfo = json_decode(exec("php goLive.php --dumpCmds"), true);
Utils::log("Command Line: Fetched " . count($commandInfo) . " commands!");
Utils::log("Command Line: Mapping Commands...");
$commands = [];
foreach ($commandInfo as $cmd) {
    $cmd = json_decode($cmd, true);
    $commands[$cmd['name']] = $cmd;
}
Utils::log("Command Line: Mapped Commands!");
Utils::log("Command Line: Finished processing commands!");

Utils::log("Command Line: Please wait while the stream starts...");
sleep(2);
Utils::log("Command Line: Ready! Type \"help\" for help.");
newCommand($commands);

function newCommand($commands)
{
    $line = Utils::promptInput("\n>");
    switch ($line) {
        case 'help':
        {
            Utils::log("Commands:");
            Utils::log("help - Displays this message");
            foreach ($commands as $cmd) {
                Utils::log($cmd['name'] . " - " . $cmd['description']);
            }
            break;
        }
        case 'stop':
        case 'end':
        {
            $archived = "yes";
            if (!autoArchive && !autoDiscard) {
                Utils::log("Would you like to keep the stream archived for 24 hours? Type \"yes\" to do so or anything else to not.");
                $archived = Utils::promptInput();
            }
            sendRequest("end", [(autoArchive || $archived == 'yes' && !autoDiscard) ? "yes" : "no"]);
            Utils::log("Command Line Exiting! Stream *should* be ended.");
            sleep(2);
            exit(1);
            break;
        }
        default:
        {
            if (!isset($commands[$line])) {
                Utils::log("Command Line: Invalid Command. Type \"help\" for help!");
                break;
            }
            $cmd = $commands[$line];

            $argument = null;
            if ($cmd['argument'] !== "") {
                Utils::log("Command Line: Please enter the following argument: " . $cmd['argument']);
                $argument = Utils::promptInput();
            }

            sendRequest($cmd['name'], [$argument]);
        }
    }
    newCommand($commands);
}

function sendRequest(string $cmd, $values)
{
    /** @noinspection PhpComposerExtensionStubsInspection */
    file_put_contents(__DIR__ . '/request', json_encode([
        'cmd' => $cmd,
        'values' => isset($values) ? $values : [],
    ]));
    Utils::log("Command Line: Waiting up to 5 seconds for a response...");

    $response = "";
    for ($x = 0; $x <= 5; $x++) {
        if (!file_exists(__DIR__ . '/response')) {
            sleep(1);
            continue;
        }

        $response = file_get_contents(__DIR__ . '/response');
        @unlink(__DIR__ . '/response');
        break;
    }

    if (empty($response)) {
        Utils::log("Command Line: No response in 5 seconds, please check the other window!");
        return;
    }
    Utils::log("$cmd Response:\n$response");
}
