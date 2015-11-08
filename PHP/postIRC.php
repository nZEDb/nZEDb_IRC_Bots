<?php
// Posts Pres to IRC.
define('pre_settings', true);
define('post_bot_settings', true);
require_once('settings.php');
require_once('Classes/IRCServer.php');
new IRCServer();