<?php

namespace Rcms\Core;

use mindplay\middleman\Dispatcher;
use Rcms\Middleware\AccessKeyCheck;
use Rcms\Middleware\Authenticator;
use Rcms\Middleware\HttpsWwwRedirector;
use Rcms\Middleware\PageResponder;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\ServerRequestFactory;

// Setup environment
require(__DIR__ . "/environment.php");

// Display site
$website = new Website();


$dispatcher = new Dispatcher([
    new HttpsWwwRedirector($website),
    new AccessKeyCheck($website),
    new Authenticator($website),
    new PageResponder($website)
]);

// Get the page response
$request = ServerRequestFactory::fromGlobals();
$response = $dispatcher->dispatch($request, new HtmlResponse(""));

// Output the response object to stdout
$responseEmitter = new SapiEmitter();
$responseEmitter->emit($response);
$response->getBody()->close();
