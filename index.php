<?php

namespace Rcms\Core;

use Rcms\Page\Renderer\PageRenderer;

// Setup environment
require("environment.php");

// We'll need this for every page
session_start();

// Display site
$website = new Website();
$pageRenderer = new PageRenderer($website, PageRenderer::getPagePath());
$pageRenderer->render();
