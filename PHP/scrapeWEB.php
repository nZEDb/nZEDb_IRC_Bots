<?php
// Download Pres from the Web.
define('pre_settings', true);
define('web_settings', true);
require_once('settings.php');
require_once('Classes/fetchWeb.php');
new fetchWeb();