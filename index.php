<?php

// Report all errors
error_reporting(E_ALL);

// We'll need this for every page
session_start();

// Valid HTML please
ini_set('arg_separator.output', '&amp;');

// Classloader
function __autoLoad($class) {
    $class = strToLower($class);

    // Try to see if it's a view
    if (subStr($class, -4) == "view") {
        require_once('application/views/' . $class . '.class.php');
        return;
    }

    // Try to see if it's a class in the library
    if (file_exists('application/library/' . $class . '.class.php')) {
        require_once('application/library/' . $class . '.class.php');
        return;
    }

    // Try to load a model
    require_once('application/models/' . $class . '.class.php');
}

// Display site
$oWebsite = new Website();
$oWebsite->echoPage();
?>