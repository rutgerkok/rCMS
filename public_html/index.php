<?php

namespace Rcms\Core;

use mindplay\middleman\Dispatcher;
use Rcms\Page\Renderer\AccessKeyCheck;
use Rcms\Page\Renderer\HttpsWwwRedirector;
use Rcms\Page\Renderer\PageResponder;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

// Setup environment (change this 
require(__DIR__ . "/environment.php");

// We'll need this for every page
session_start();

// Display site
$website = new Website();

$dispatcher = new Dispatcher([
    new HttpsWwwRedirector($website),
    new AccessKeyCheck($website),
    new PageResponder($website)
]);

// Get the page response
$request = ServerRequestFactory::fromGlobals();
$response = $dispatcher->dispatch($request, new HtmlResponse(""));

// Output the response object to stdout
$responseEmitter = new SapiEmitter();
$responseEmitter->emit($response);
$response->getBody()->close();
