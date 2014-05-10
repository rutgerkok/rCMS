<?php

namespace Rcms\Page;

use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\WidgetsView;

class HomePage extends Page {

    public function getPageTitle(Request $request) {
        return ""; // The widgets will already provide a title
    }
    
    public function getShortPageTitle(Request $request) {
        return $request->getWebsite()->t("main.home");
    }

    public function getView(Website $oWebsite) {
        return new WidgetsView($oWebsite, $oWebsite->getWidgets(), 1);
    }

    public function getPageType() {
        return "HOME";
    }

}
