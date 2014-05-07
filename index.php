<?php

namespace Rcms\Core;

// Report all errors
error_reporting(E_ALL);

// We'll need this for every page
session_start();

// Valid HTML please
ini_set('arg_separator.output', '&amp;');

// Classloader
spl_autoload_register(function($fullClassName) {
    $class = str_replace('\\', '/', subStr($fullClassName, strLen("Rcms\\")));

    // Try to see if it's a class in the library
    if (file_exists('src/' . $class . '.php')) {
        require_once('src/' . $class . '.php');
    }
});

// Display site
$oWebsite = new Website();
$oWebsite->echoPage();
