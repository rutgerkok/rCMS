<?php

namespace Rcms\Core;

use Rcms\Page\Renderer\AccessKeyCheck;
use Rcms\Page\Renderer\PageResponder;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SapiEmitter;
use Zend\Diactoros\Response\HtmlResponse;

// Setup environment (change this 
require(__DIR__ . "/environment.php");

// We'll need this for every page
session_start();

// Display site
$website = new Website();
$pageResponder = new PageResponder($website);
$accessKeyCheck = new AccessKeyCheck($website);

$request = ServerRequestFactory::fromGlobals();
$response = $accessKeyCheck($request, new HtmlResponse(""), $pageResponder);

$responseEmitter = new SapiEmitter();
$responseEmitter->emit($response);
$response->getBody()->close();
