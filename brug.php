<?php

// Welcome! Brug is a build system for websites. This file is just an installer,
// it downloads Composer (PHP package manager) and Brug. See 'build.php' for the
// actual build file. Simple execute that file using `php build.php` to build
// the website.

namespace RutgerKok\Brug;

// Constants. Change these to your liking
define("BUILD_DIR", __DIR__ . "/build/");
define("TEMP_DIR", BUILD_DIR . "temp/");

// Internal code below
















// Code that runs the installer
define("COMPOSER_INSTALLER_FILE", TEMP_DIR . "composer-setup.php");
define("COMPOSER_FILE", TEMP_DIR . "composer.phar");
checkPhpVersion();
createDir(BUILD_DIR);
createDir(TEMP_DIR);
runComposer();
require(TEMP_DIR . "vendor/autoload.php");
Brug::init(__DIR__, BUILD_DIR, COMPOSER_FILE, getCommandArgs());

// Function definitions

function fatalError($msg) {
    echo $msg;
    exit(1);
}

function runCommand($command) {
    // Runs the command. On failure, it prints the output and exit()s.
    $exitCode = exec($command);
    if ($exitCode != 0) {
        fatalError("Exiting script... Used command:\n$command");
    }
}

function checkPhpVersion() {
    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
        fatalError("PHP version 5.4 is required.");
        // Because of array syntax and PHP_BINARY constant
    }
}

function createDir($dir) {
    if (!is_dir($dir)) {
        if (file_exists($dir)) {
            fatalError($dir . " must be a directory, but was a file.");
        }
        mkdir($dir);
        if (!is_writeable($dir)) {
            fatalError($dir . " is not writable.");
        }
    }
}

function downloadComposer() {
    $alreadyDownloaded = file_exists(COMPOSER_INSTALLER_FILE);
    $expectedHash = @file_get_contents("https://composer.github.io/installer.sig");

    // Offline? Then use existing file, if available
    if ($expectedHash === false) {
        if ($alreadyDownloaded) {
            echo "Could not check for Composer updates at github.io. Assuming existing version of Composer is good to go.";
            return;
        }
        fatalError("Could not download Composer signature. Therefore, Composer could not be downloaded.");
    }

    // Skip download if existing file is good
    if ($alreadyDownloaded) {
        if (trim($expectedHash) === hash_file('SHA384', COMPOSER_INSTALLER_FILE)) {
            return;
        }
    }

    // Download Composer
    echo "Installing Composer...\n";
    @unlink(TEMP_DIR);
    if (!copy('https://getcomposer.org/installer', COMPOSER_INSTALLER_FILE)) {
        fatalError("Failed to download composer");
    }

    // Check download
    $actualHash = hash_file('SHA384', COMPOSER_INSTALLER_FILE);
    if (trim($expectedHash) !== $actualHash) {
        rename(TEMP_DIR . 'composer-setup.php', COMPOSER_INSTALLER_FILE . '.corrupt_do_not_use');
        fatalError("Failed to download Composer. Expected hash: $expectedHash. Actual: $actualHash");
    }
}

function installComposer() {
    chdir(TEMP_DIR);
    runCommand(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(COMPOSER_INSTALLER_FILE));
}

function createBrugDownloadFile() {
    if (file_exists(TEMP_DIR . "composer.json")) {
        return; // File already exists
    }
    $composerJson = [
        "name" => "brug-downloader",
        "description" => "Small project to download Brug files.",
        "type" => "project",
        "repositories" => [
            [
                "type" => "path",
                "url" => "../../../Brug"
            ]
        ],
        "require" => [
            "rutgerkok/brug" => "^0.1"
        ]
    ];
    file_put_contents(TEMP_DIR . "composer.json", json_encode($composerJson, JSON_PRETTY_PRINT));
}

function runComposer() {
    if (file_exists(TEMP_DIR . "vendor/autoload.php")) {
        return; // Already run
    }
    downloadComposer();
    installComposer();
    createBrugDownloadFile();
    runCommand(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(TEMP_DIR . 'composer.phar') . ' install --no-dev');
}

function getCommandArgs() {
    global $argv;
    $args = $argv;
    array_shift($args);
    return $args;
}