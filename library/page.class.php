<?php

abstract class Page {
    /**
     * Called before any output is done. Can be used to set cookies, for example
     * @param Website $oWebsite The website object.
     */
    public function init(Website $oWebsite) {
    }

    /**
     * Gets the page type. HOME, NORMAL or BACKSTAGE
     * $return string The page type.
     */
    public function getPageType() {
        return "NORMAL";
    }

    /**
     * Gets the minimum rank required to view this page, like Authentication::$USER_RANK.
     * @return int The minimum rank required to view this page.
     */
    public function get_minimum_rank(Website $oWebsite) {
        return -1;
    }

    /**
     * Gets the title of this page. Empty titles are allowed.
     * @return string The title of this page.
     */
    public abstract function getPageTitle(Website $oWebsite);

    /**
     * Gets a shorter title for this page, for example for in the breadcrumbs.
     * Empty titles are highly discouraged.
     * @param Website $oWebsite The website object.
     * @return string The short title of this page.
     */
    public function get_short_page_title(Website $oWebsite) {
        return $this->getPageTitle($oWebsite);
    }

    /**
     * Gets the HTML content of this page.
     * @return string The HTML content of this page.
     */
    public abstract function get_page_content(Website $oWebsite);
}

?>
