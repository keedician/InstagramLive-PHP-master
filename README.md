# InstagramLive-PHP [![Discord](https://img.shields.io/discord/476526599232159780.svg?style=flat-square)](https://discord.gg/EpkKFt3) [![](https://data.jsdelivr.com/v1/package/gh/JRoy/InstagramLive-PHP/badge)](https://www.jsdelivr.com/package/gh/JRoy/InstagramLive-PHP)
A PHP script that allows for you to go live on Instagram with any streaming program that supports RTMP!

Built with [mgp25's amazing Instagram Private API Wrapper for PHP](https://github.com/mgp25/Instagram-API/).
# Note
Please read this **entire** document as it has *very* important information about the script. If you create an issue that can be solved by reading this document, it will be ignored.

# Live Setup
It is suggested you watch [this video](https://www.youtube.com/watch?v=J6lp8g3zQeE) for a step-by-step process on how to install this script.

1. Install PHP, of course...
2. Goto the [most release release](https://github.com/JRoy/InstagramLive-PHP/releases/latest)
3. Download the `update.php` file and place it in its own folder
4. Run the script with `php update.php` and let it install the script
5. Edit the `USERNAME` and `PASSWORD` inside of the `config.php` file to your Instagram username/password.
6. Run the `goLive.php` script. (`php goLive.php`)
#### Video Tutorial
If you'd like a video version of this tutorial, see [this video](https://www.youtube.com/watch?v=J6lp8g3zQeE).
# Features
* Robust Installer/Updater
  * To install read the [Live Setup](#live-setup) section
  * To check for/apply an update just do `php update.php`
    * If you want to try beta features just do: `php update.php --beta`
* Web Control Panel
  * Use by doing `php goLive.php --web`
  * By default, you can access the control panel by going to `127.0.0.1` in your browser
    * You can change this by editing the `WEB_PORT` option in the `config.php` from `80` to some other number and then going to `127.0.0.1:PORT` where `PORT` is the number you changed it to
* Supports Accounts with 2FA
* View Live Chat/Likes (Windows/Mac Only)
* Execute Commands to Comment, Wave, Pin Comments, Show Questions, and more...
* Launch & Start OBS Automatically (Windows Only)
* Infinite Stream: Stream forever with no user input! (Windows/Mac Only)
  * Accomplished by doing: `php goLive.php -i -d`
  * Windows Users with OBS can do `php goLive.php -i -d --obs` for absolutely no input from the user required
* Archived Stream Statistics
  * Accomplished by doing: `php checkVod.php` 24 hours within archiving a stream
# Commands
To view what commands you can run while streaming: [Click Here](https://github.com/JRoy/InstagramLive-PHP/wiki/Commands)

To view what flags you can run the `goLive.php` script with: [Click Here](https://github.com/JRoy/InstagramLive-PHP/wiki/Command-Line-Arguments) 
# FAQ
#### I get "Parse error: syntax error" or "PHP Fatal error:  Uncaught TypeError" when running the script
You have an outdated version of PHP, please make sure your using PHP 7 or greater.
#### I get "Fatal error: require_once(): Failed opening required" when running the script
You didn't install the script correctly, please check the installation guide or video above.
#### OBS gives a "Failed to connect" error
This could be due to the following reasons:
* Your version of OBS doesn't support RTMPS. Update to the latest OBS Studio version.
#### I've stopped streaming but Instagram still shows me as live
Make sure you actually running the `stop` command when you're streaming and not close it.
#### I don't see or get an error in Instagram when archiving my story
This could be due to the following reasons:
* You didn't stream anything from OBS/your encoder. In this case, you should delete the archive.
* You streamed a disallowed aspect ratio or resolution. Make sure you're using 1080x1794. In this case, you should delete the archive.
* Your stream is still processing. This is normal for longer streams.
#### I get "CURL Error 60: SSL certificate problem" when trying to log into Instagram
This is due to CURL not having a valid CA. You can find a solution here: [https://stackoverflow.com/a/34883260](https://stackoverflow.com/a/34883260).
#### I get "CURL Error 28: Operation timed out after x milliseconds with 0 bytes received."
In this case, your IP is blocked by Instagram. There is nothing I can do in this situation, if you're using a VPN/Proxy (which are not supported), don't. 
### Question not listed here?
If your question is not listed here, [join our discord](https://discord.gg/EpkKFt3) so I can help support you faster. [https://discord.gg/EpkKFt3](https://discord.gg/EpkKFt3)
# Donate
If you would like to donate to me because you find what I do useful and would like to support me, you can do so through this methods:

Patreon: https://www.patreon.com/JRoy

PayPal.me: https://www.paypal.me/JoshuaRoy1

Bitcoin: `32J2AqJBDY1VLq6wfZcLrTYS8fCcHHVDKD`
