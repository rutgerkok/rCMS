<?php

class WidgetsView extends View {

    protected $oWebsite;
    protected $area;

    public function __construct(Website $oWebsite, $area) {
        $this->oWebsite = $oWebsite;
        $this->area = $area;
    }

    public function getText() {
        return $this->oWebsite->getThemeManager()->getWidgets($this->area);
    }

}

