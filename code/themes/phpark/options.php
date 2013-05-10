<?php
class PrinsHendrikparkTheme extends Theme {
    public function get_widget_areas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar") . " 1",
            3 => $oWebsite->t("widgets.sidebar") . " 2"
        );
    }
}

$this->register_theme(new PrinsHendrikparkTheme());
?>
