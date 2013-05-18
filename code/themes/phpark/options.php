<?php
class PrinsHendrikparkTheme extends Theme {
    public function get_widget_areas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar") . " 1",
            3 => $oWebsite->t("widgets.sidebar") . " 2"
        );
    }
    
    public function get_text_editor_menu_color() {
        return "#9a9a79";
    }
}

$this->register_theme(new PrinsHendrikparkTheme());
?>
