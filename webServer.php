<?php
$requestUri = $_SERVER['REQUEST_URI'];

//Send dummy data for now
if ($requestUri === '/tick') {
    header('Content-Type: application/json', true);
    http_response_code(200);
    die(@file_get_contents(__DIR__ . '/webLink.json'));
}

if ($requestUri === '/request') {
    /** @noinspection PhpComposerExtensionStubsInspection */
    file_put_contents(__DIR__ . '/request', json_encode([
        'cmd' => $_POST['cmd'],
        'values' => $_POST['values'],
    ]));
    if ('cmd' === 'end') {
        exit(0);
    }
    header('Content-Type: application/json', true);
    http_response_code(200);
    die('{"status": "ok"}');
}

if ($requestUri === '/static/style.css') {
    header('Content-Type: text/css', true);
    die(@file_get_contents(__DIR__ . '/static/style.css'));
}

if ($requestUri === '/static/jquery.min.js') {
    header('Content-Type: text/javascript', true);
    die(@file_get_contents(__DIR__ . '/static/jquery.min.js'));
}
if ($requestUri === '/static/script.js') {
    header('Content-Type: text/javascript', true);
    die(@file_get_contents(__DIR__ . '/static/script.js'));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/ebc94e6365.js"></script>
    <link rel="stylesheet" href="static/style.css"/>
    <title>InstagramLive-PHP Console</title>
</head>

<body>
<div id="modal-waiting" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">Waiting for livestream...</h2>
    </div>
    <p class="mb-20">InstagramLive-PHP Console is waiting for your stream to start...</p>
    <p class="mb-20">Please make sure you started this console with your goLive.php script and that the goLive.php
        script is running in --web mode</p>
</div>
<div id="modal-about" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">About</h2>
        <i class="fas fa-times float-right badge badge-red mt-10 close"></i>
    </div>
    <p class="mb-20">This web console for InstagramLive-PHP provides a web-facing control panel for those who do not
        prefer dealing with the command line.</p>
    <p class="mb-20">This panel is open source, licenced under the Apache 2.0 License, and made possible with the
        following contributors and open-source software(s):</p>
    <ul class="br-20 b-none w-100 p-20 o-none">
        <li><a target="_blank" href="https://github.com/JRoy">JRoy</a> - Backend</li>
        <li><a target="_blank" href="https://www.instagram.com/eenjesta">Eenjesta</a> - Frontend/Design</li>
        <li><a target="_blank" href="https://fontawesome.com/">Font Awesome</a></li>
        <li><a target="_blank" href="https://jquery.com/">jQuery</a></li>
    </ul>
</div>
<div id="modal-stop-streaming" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">Stop streaming</h2>
        <i class="fas fa-times float-right badge badge-red mt-10 close"></i>
    </div>
    <p class="mb-20">Would you like to archive this stream? Stream archives last up to twenty-four hours after the end.
        You can delete the archive later if you change your mind.</p>
    <div class="btn-group bottom">
        <button id="archive" class="btn btn-red br-25 float-left w-50">Archive Stream</button>
        <button id="discard" class="btn btn-grey br-25 float-right w-50">Discard Stream</button>
    </div>
</div>
<div id="modal-comment" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">Comment</h2>
        <i class="fas fa-times float-right badge badge-red mt-10 close"></i>
    </div>
    <p class="mb-20">Please enter a comment to leave of the stream...</p>
    <div class="btn-group bottom">
        <input id="comment-text" type="text" class="btn btn-grey br-25 mb-20 w-100" placeholder="Enter comment..."/>
        <button id="comment-button" class="btn btn-red br-25 w-100">Comment</button>
    </div>
</div>
<div id="modal-question" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">Show Question</h2>
        <i class="fas fa-times float-right badge badge-red mt-10 close"></i>
    </div>
    <p class="mb-20">Please enter the question id to show on the stream... This will show as a card with the question
        and user who asked the question on the bottom of the stream.</p>
    <div class="btn-group bottom">
        <input id="question-text" type="text" class="btn btn-grey br-25 mb-20 w-100"
               placeholder="Enter question id..."/>
        <button id="question-button" class="btn btn-red br-25 w-100">Show Question</button>
    </div>
</div>
<div id="modal-block" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">Block User</h2>
        <i class="fas fa-times float-right badge badge-red mt-10 close"></i>
    </div>
    <p class="mb-20">Please enter the user id to block... Please note that this blocks the user from your entire
        account; Just like a profile block in the app.</p>
    <div class="btn-group bottom">
        <input id="block-text" type="text" class="btn btn-grey br-25 mb-20 w-100" placeholder="Enter user id..."/>
        <button id="block-button" class="btn btn-red br-25 w-100">Block User</button>
    </div>
</div>
<div id="modal-hide" class="modal">
    <div class="modal-header mb-20">
        <h2 class="dark-blue">Hide Stream & Story from User</h2>
        <i class="fas fa-times float-right badge badge-red mt-10 close"></i>
    </div>
    <p class="mb-20">Please enter the user id to hide your stream from... Please note that this also hides your story from
        said user; This can be undone in story settings in the app.</p>
    <div class="btn-group bottom">
        <input id="hide-text" type="text" class="btn btn-grey br-25 mb-20 w-100" placeholder="Enter user id..."/>
        <button id="hide-button" class="btn btn-red br-25 w-100">Hide Story from User</button>
    </div>
</div>
<div id="black-overlay"></div>
<div class="container">
    <nav>
        <h1 class="dark-blue">InstagramLive-PHP</h1>
        <h2 id="username" class="dark-blue float-right"></h2>
    </nav>
    <main>
        <div id="commands" class="bg-white br-25 mb-50 p-50">
            <h2 class="dark-blue mb-20">Commands</h2>
            <div id="command-buttons">
                <button id="comment" class="btn btn-green br-25 mr-10 mb-20">Comment</button>
                <button id="dcomments" class="btn btn-green br-25 mr-10 mb-20">Disable comments</button>
                <button id="ecomments" class="btn btn-green br-25 mr-10 mb-20">Enable comments</button>
                <button id="show-question" class="btn btn-green br-25 mr-10 mb-20">Show question</button>
                <button id="hidequestion" class="btn btn-green br-25 mr-10 mb-20">Hide question</button>
                <button id="block" class="btn btn-red br-25 mr-10 mb-20">Block</button>
                <button id="hide" class="btn btn-red br-25 mr-10 mb-20">Hide Stream</button>
                <button id="stop-streaming" class="btn btn-red br-25 mr-10 mb-20">Stop streaming</button>
                <button id="info" class="btn btn-blue br-25 mr-10 mb-20">Info</button>
                <button id="questions" class="btn btn-blue br-25 mr-10 mb-20">Questions</button>
                <button id="viewers" class="btn btn-blue br-25 mr-10 mb-20">Viewers</button>
                <button id="url" class="btn btn-blue br-25 mr-10 mb-20">Stream URL</button>
                <button id="key" class="btn btn-blue br-25 mr-10 mb-20">Stream Key</button>
            </div>
            <textarea id="command-response" class="bg-grey br-25 b-none w-100 p-20 o-none dark-blue"
                      placeholder="Response" rows="10" disabled></textarea>
        </div>
        <div id="likes" class="bg-white br-25 w-50 float-left p-50">
            <h2 class="dark-blue mb-20">Likes</h2>
            <textarea id="like-response" class="bg-grey br-25 b-none w-100 p-20 o-none dark-blue" placeholder="Response"
                      rows="14" disabled></textarea>
        </div>
        <div id="comments" class="bg-white br-25 w-50 float-right p-50">
            <h2 class="dark-blue mb-20">Comments</h2>
            <p hidden id="pinned"><span id="pinned-span" class="comment">.</span><i
                        class="fas fa-times float-right badge badge-red" onclick="unpinComment()"><span
                            class="tooltip-red">Unpin comment</span></i></p>
            <ul id="comment-box" class="bg-grey br-25 b-none w-100 p-20 o-none dark-blue">
            </ul>
        </div>
    </main>
    <footer class="text-center">
        <p>&copy; 2019<?php echo((date("Y") !== "2019" ? (" - " . date("Y")) : "")); ?> JRoy &amp; Eenjesta - <span
                    id="about" class="red">About</span></p>
    </footer>
    <div class="display-data">

    </div>
</div>
<script src="static/jquery.min.js"></script>
<script src="static/script.js"></script>
</body>

</html>
