<?php
// Download Pres from IRC.
if (!isset($argv[1]) || !in_array($argv[1], array('efnet', 'corrupt'))) {
	exit();
}
$silent = isset($argv[2]) && $argv[2] === 'true';
define('pre_settings', true);
if ($argv[1] === 'efnet') {
	define('efnet_bot_settings', true);
} else {
	define('corrupt_bot_settings', true);
}
require_once('settings.php');
require_once('Classes/IRCScraper.php');
new IRCScraper($argv[1], $silent);