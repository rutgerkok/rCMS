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
downloadComposer();
runComposer();
Brug::init(__DIR__, BUILD_DIR, COMPOSER_FILE);

// Function definitions

function fatalError($msg) {
    echo $msg;
    exit(1);
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
    $alreadyDownloaded = file_exists(COMPOSER_FILE);
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
        if (trim($expectedHash) === hash_file('SHA384', COMPOSER_FILE)) {
            return;
        }
    }

    // Download Composer
    echo "Installing Composer...";
    @unlink(TEMP_DIR);
    if (!copy('https://getcomposer.org/installer', COMPOSER_FILE)) {
        fatalError("Failed to download composer");
    }

    // Check download
    $actualHash = hash_file('SHA384', COMPOSER_FILE);
    if (trim($expectedHash) !== $actualHash) {
        rename(TEMP_DIR . 'composer-setup.php', COMPOSER_FILE . '.corrupt_do_not_use');
        fatalError("Failed to download Composer. Expected hash: $expectedHash. Actual: $actualHash");
    }

    // Install
    installComposer();
}

function installComposer() {
    chdir(TEMP_DIR);
    shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(COMPOSER_INSTALLER_FILE));
}

function runComposer() {
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
    shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(TEMP_DIR . 'composer.phar') . ' install --no-dev');
    require(TEMP_DIR . "vendor/autoload.php");
}