<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class LogoutPage extends Page {

    public function init(Website $oWebsite) {
        $oWebsite->getAuth()->logOut();
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.log_out") . '...';
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.log_out");
    }

    public function getView(Website $oWebsite) {
        return new LoggedOutView($oWebsite);
    }

}
