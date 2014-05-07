<?php

namespace Rcms\Page\View;

use Rcms\Core\Website;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Represents a view. This class produces just an empty page.
 */
abstract class View {

    /** @var Website $oWebsite The website object. */
    protected $oWebsite;

    public function __construct(Website $oWebsite) {
        $this->oWebsite = $oWebsite;
    }

    /**
     * Renders this view.
     */
    public abstract function getText();
}
