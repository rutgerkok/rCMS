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
    public function get_page_type() {
        return "NORMAL";
    }
    
    /**
     * Gets the minimum rank required to view this page, like Authentication::$USER_RANK.
     * @return int The minimum rank required to view this page.
     */
    public function get_minimum_rank() {
        return -1;
    }
    
    /**
     * Gets the title of this page.
     * @return string The title of this page.
     */
    public abstract function get_page_title(Website $oWebsite);
    
    /**
     * Gets the HTML content of this page.
     * @return string The HTML content of this page.
     */
    public abstract function get_page_content(Website $oWebsite);
}

?>
