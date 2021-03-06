<?php

require "brug.php";

function info() {
    projectName("rcms/rcms");
    projectDescription("Yet another content management system.");
    projectLicense("MIT");
}

function folders() {
    phpPsr4Src("src/", "Rcms\\");
    phpSrc("extend/");
    phpSrc("config.sample.php");
    phpSrc("environment.php");
    webSrc("public_html/");
    phpUnitSrc("tests/");
}

function dependencies() {
    composer("php", ">=5.5.0");
    composer("ext-pdo", "*");
    composer("zendframework/zend-diactoros", "^1.3.2");
    composer("mindplay/middleman", "^1.1.0");
    composer("phpmailer/phpmailer", "^6.0.2");
    composer("paragonie/random_compat", "^2.0");
    composerDev("phpunit/phpunit", "^4.8.0");
    npm("@ckeditor/ckeditor5-build-balloon", "^1.0.0-alpha.2");
}

function build() {
    copyDirectory("node_modules/@ckeditor/ckeditor5-build-balloon/build/",
            "public_html/javascript/ckeditor");
}
