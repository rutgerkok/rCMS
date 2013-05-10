<?php
class MetroTheme extends Theme {
    public function get_widget_areas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar")
        );
    }
}

$this->register_theme(new MetroTheme());
?>
