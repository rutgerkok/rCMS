<?php
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

$this->register_theme(new PrinsHendrikparkTheme());
?>
