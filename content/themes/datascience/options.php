<?php

// Protect against calling this script directly
if (!isset($this)) {
    die();
}

// Define the options class
class DataScienceTheme extends Theme {

    public function getWidgetAreas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar") . " 1",
            3 => $oWebsite->t("widgets.sidebar") . " 2"
        );
    }

    public function getTextEditorColor() {
        return "#8592bb";
    }

}

// ...and register
$this->registerTheme(new DataScienceTheme());
?>
