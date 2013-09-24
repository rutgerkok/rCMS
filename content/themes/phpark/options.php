<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class PrinsHendrikparkTheme extends Theme {

    public function getWidgetAreas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar") . " 1",
            3 => $oWebsite->t("widgets.sidebar") . " 2"
        );
    }

    public function getTextEditorColor() {
        return "#9a9a79";
    }

}

$this->registerTheme(new PrinsHendrikparkTheme());
?>
