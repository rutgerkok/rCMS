<?php

// Must be included before any of the other code is run. This file does some
// basic changes to the environment, like setting an autoloader.

// Classloader
spl_autoload_register(function($fullClassName) {
    $class = str_replace('\\', '/', subStr($fullClassName, strLen("Rcms\\")));

    // Try to see if it's a class in the library
    if (file_exists('src/' . $class . '.php')) {
        require_once('src/' . $class . '.php');
    }
});

// Report all errors
error_reporting(E_ALL);

// Valid HTML please
ini_set('arg_separator.output', '&amp;');
