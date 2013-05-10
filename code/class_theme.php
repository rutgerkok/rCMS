<?php

abstract class Theme {
    /**
     * The function should return an array (id=>name) with all places for widgets.
     * Ids must be a whole number larger than 1. (Id 0 is unused, id 1 is used on homepage)
     * @param Website $oWebsite The current website.
     */
    public abstract function get_widget_areas(Website $oWebsite);
}

?>
