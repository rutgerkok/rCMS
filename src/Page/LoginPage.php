<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\LoggedInView;
use Rcms\Page\View\LoginView;

class LoginPage extends Page {

    public function init(Request $request) {
        // Handle login ourselves
        // (Using the provided getMinimumRank helper gives an ugly
        // "You need to be logged in to view this page" message.)
        $request->getWebsite()->getAuth()->check(Authentication::$USER_RANK, false);
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("main.log_in") . '...';
    }

    public function getShortPageTitle(Request $request) {
        return $request->getWebsite()->t("main.log_in");
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getView(Website $oWebsite) {
        if ($oWebsite->isLoggedIn()) {
            return new LoggedInView($oWebsite);
        } else {
            // Return a login view, but without the "Must be logged in" message
            // at the top.
            return new LoginView($oWebsite, Authentication::$LOGGED_OUT_RANK);
        }
    }

}
