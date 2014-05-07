<?php

namespace Rcms\Page;

use Rcms\Core\Website;
use Rcms\Page\View\WidgetsView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class HomePage extends Page {

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.home");
    }

    public function getView(Website $oWebsite) {
        return new WidgetsView($oWebsite, 1);
    }

    public function getPageType() {
        return "HOME";
    }

}
