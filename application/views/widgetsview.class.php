<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetsView extends View {

    protected $area;

    public function __construct(Website $oWebsite, $area) {
        parent::__construct($oWebsite);
        $this->area = $area;
    }

    public function getText() {
        return $this->oWebsite->getThemeManager()->getWidgets($this->area);
    }

}
