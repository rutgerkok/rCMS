<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class HomePage extends Page {

    public function getPageTitle(Website $oWebsite) {
        return ""; // The widgets will already provide a title
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.home");
    }

    public function getView(Website $oWebsite) {
        return new WidgetsView($oWebsite, 1);
    }

    public function getPageType() {
        return "HOME";
    }

}
