<?php

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

$this->registerPage(new HomePage());

