<?php
$error_level = error_reporting();
error_reporting(E_ALL);
define('DIR_SEP',"/",true);
chdir(realpath(dirname(__FILE__)));
include 'php'.DIR_SEP.'functions.php';
include 'config.php';

$user               = new user();
$module             = MODULE;

//=========================================================
if (file_exists("controllers".DIR_SEP."$module.class.php")) require_once "controllers".DIR_SEP."$module.class.php";
else die('File Doesnt Exist: '."controllers".DIR_SEP."$module.class.php");
//else require_once "controllers".DIR_SEP."index.class.php";
//=========================================================
$template = new $module;
if (method_exists($template, "output")) $template->output("");
else {
	echo "METHOD DOES NOT EXIST?";
    $action = ACTION;
    if (ID)     $template->$action(ID);
    else        $template->$action();
}
error_reporting($error_level);
?>