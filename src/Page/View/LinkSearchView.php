<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * View for the search screen of links.
 */
class LinkSearchView extends View {

    protected $links;

    /**
     * Constructs a new menu search view.
     * @param Website $oWebsite The website object.
     * @param array $links Array of links. id=>link, with link being an array
     * with the keys "url" and "text".
     */
    public function __construct(Website $oWebsite, array $links) {
        parent::__construct($oWebsite);
        $this->links = $links;
    }

    public function getText() {
        $result = "";
        if (!$this->links) {
            return "";
        }

        // Header and list start
        $result.= '<h3 class="notable">' . $this->oWebsite->t('articles.search.results_in_links') . "</h3>\n";
        $result.= '<ul class="linklist">';

        // Add each link
        foreach ($this->links as $id => $value) {
            $result.= "<li>";
            $result.= '<a href="' . htmlSpecialChars($value["url"]) . '">';
            $result.= htmlSpecialChars($value["text"]);
            $result.= "</a></li>\n";
        }

        // Close list and return the result
        $result.= "</ul>\n";
        return $result;
    }

}
