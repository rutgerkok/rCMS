<?php

namespace Rcms\Core;

use Rcms\Page\Renderer\ResponseFactory;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response\SapiEmitter;

// Setup environment
require("environment.php");

// We'll need this for every page
session_start();

// Display site
$website = new Website();
$responseFactory = new ResponseFactory($website);
$response = $responseFactory(ServerRequestFactory::fromGlobals());
$responseEmitter = new SapiEmitter();
$responseEmitter->emit($response);