<?php

namespace Rcms\Page;

use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\LoggedOutView;

class LogoutPage extends Page {

    public function init(Request $request) {
        $request->getWebsite()->getAuth()->logOut();
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("main.log_out") . '...';
    }

    public function getShortPageTitle(Request $request) {
        return $request->getWebsite()->t("main.log_out");
    }

    public function getView(Website $oWebsite) {
        return new LoggedOutView($oWebsite);
    }

}
