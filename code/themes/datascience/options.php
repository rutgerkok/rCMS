<?php

// Define the options class
class DataScienceTheme extends Theme {

    public function get_widget_areas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar") . " 1",
            3 => $oWebsite->t("widgets.sidebar") . " 2"
        );
    }
}

// ...and register
$this->register_theme(new DataScienceTheme());

?>
