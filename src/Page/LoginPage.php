<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Website;
use Rcms\Page\View\LoggedInView;
use Rcms\Page\View\LoginView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class LoginPage extends Page {

    public function init(Website $oWebsite) {
        // Handle login ourselves
        // (Using the provided getMinimumRank helper gives an ugly
        // "You need to be logged in to view this page" message.)
        $oWebsite->getAuth()->check(Authentication::$USER_RANK, false);
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.log_in") . '...';
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.log_in");
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
