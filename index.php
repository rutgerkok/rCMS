<?php

// Report all errors
error_reporting(E_ALL);

// We'll need this for every page
session_start();

// Valid HTML please
ini_set('arg_separator.output', '&amp;');

// Classloader
function __autoload($class) {
    //echo "<br />Loading " . $class;
    require_once('./code/classes/class_' . strtolower($class) . '.php');
}

// Display site
$oWebsite = new Website();
$oWebsite->echo_page();
?>