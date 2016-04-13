<?php

namespace Rcms\Core;

use Rcms\Page\Renderer\AccessKeyCheck;
use Rcms\Page\Renderer\PageResponder;
use Relay\Middleware\SessionHeadersHandler;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\Response\HtmlResponse;

// Setup environment
require("environment.php");

// We'll need this for every page
session_start();

// Display site
$website = new Website();
$pageResponder = new PageResponder($website);
$accessKeyCheck = new AccessKeyCheck($website);
$sessionHeadersHandler = new SessionHeadersHandler();

$request = ServerRequestFactory::fromGlobals();
$response = new HtmlResponse("");

$response = $sessionHeadersHandler($request, $response, function($request, $response)
        use ($accessKeyCheck, $pageResponder) {
    return $accessKeyCheck($request, $response, $pageResponder);
});

$responseEmitter = new SapiEmitter();
$responseEmitter->emit($response);