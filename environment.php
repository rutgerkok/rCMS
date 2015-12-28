<?php

// Must be included before any of the other code is run. This file does some
// basic changes to the environment, like setting an autoloader.

// Classloader
require __DIR__ . '/vendor/autoload.php';

// Report all errors
error_reporting(E_ALL);

// Valid HTML please
ini_set('arg_separator.output', '&amp;');
