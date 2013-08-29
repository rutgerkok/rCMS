<?php

// Report all errors
error_reporting(E_ALL);

// We'll need this for every page
session_start();

// Valid HTML please
ini_set('arg_separator.output', '&amp;');

// Classloader
function __autoload($class) {
    $class = strToLower($class);
    if(file_exists('../library/' . $class . '.class.php')) {
        require_once('../library/' . $class . '.class.php');
    } else {
        require_once('../application/models/' . $class . '.class.php');
    }
}

// Display site
$oWebsite = new Website();
$oWebsite->echoPage();
?>