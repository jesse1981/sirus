<?php

// Session Cache Limiter
session_cache_limiter("nocache");

// Set UTC Date/Time Zone
date_default_timezone_set('Australia/Sydney');

// Upload TMP Dir
ini_set('upload_tmp_dir', '/tmp');

// Initialize and set variables
$settings = parse_ini_file('settings.ini',true);

if (!$settings) die('<h1>Settings file is missing, or cannot be parsed!</h1>');

$ignore_keys = array("SERVER","TYPE","PORT","NAME","USER","PASS","DSN");

foreach ($settings as $group) {
    foreach ($group as $k=>$v) {
        if (!in_array($k, $ignore_keys)) define($k,$v,true);
    }
}

define('EXECUTED_DATE',date('Y-m-d'),true);
define('EXECUTED_TIME',date('H:i:s'),true);
@define('CLIENT_IP',$_SERVER["REMOTE_ADDR"],true);
@define('CLIENT_BROWSER',$_SERVER["HTTP_USER_AGENT"],true);

// Error Reporing
//set_error_handler('error_handler');
//set_exception_handler('exception_handler');

// MVC
$module = (isset($_GET["module"]))  ? $_GET["module"]:"index";
if (!$module) $module = "index";
$action = (isset($_GET["action"]))  ? $_GET["action"]:"index";
$id     = (isset($_GET["id"]))      ? valIntIfNumeric($_GET["id"]):0;
$format = (isset($_GET["format"]))  ? $_GET["format"]:"";

define('MODULE',$module,true);
define('ACTION',$action,true);
define('ID',    $id,true);
define('FORMAT',$format,true);

/*
echo "Module = $module<br/>";
echo "Action = $action<br/>";
echo "id = $id<br/>";
echo "Format = $format<br/>";
*/

$classes = directoryToArray('classes', false, true);
sort($classes,SORT_STRING);
foreach ($classes as $class) {
    if (strpos($class,"user_")===false) {
        //echo "Loading $class...\n";
        require_once $class;
    }
}
require_once 'classes'.DIR_SEP.AUTH_CLASS.'.class.php';

//http://phpmailer.worxware.com/index.php?pg=tutorial

// Access-Control-Allow-Origin..
$server = explode("/", $_SERVER['HTTP_REFERER']);
$server = $server[2];
$allowed_domains = array(
	"www-lifestyle.logisofttech.com.au"	=> array(
		"login",
                "users",
		"services"
	)
);
if ((isset($allowed_domains[$server])) && (in_array(MODULE,$allowed_domains[$server]))) header('Access-Control-Allow-Origin: *');
?>