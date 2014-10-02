<?php

namespace Rcms\Core;

use Rcms\Page\Renderer\PageRenderer;

// Setup environment
require("environment.php");

// We'll need this for every page
session_start();

// Display site
$oWebsite = new Website();
$pageRenderer = new PageRenderer($oWebsite, PageRenderer::getPagePath());
$pageRenderer->render();
