<?php

// Protect against calling this script directly
if (!isset($this)) {
    die();
}

class MetroTheme extends Theme {
    public function getWidgetAreas(Website $oWebsite) {
        return array(
            2 => $oWebsite->t("widgets.sidebar")
        );
    }
}

$this->registerTheme(new MetroTheme());
?>
