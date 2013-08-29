<?php
class MetroTheme extends Theme {
    public function getWidgetAreas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar")
        );
    }
}

$this->register_theme(new MetroTheme());
?>
