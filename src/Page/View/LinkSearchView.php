<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * View for the search screen of links.
 */
class LinkSearchView extends View {

    protected $links;

    /**
     * Constructs a new menu search view.
     * @param Text $text The website object.
     * @param array $links Array of links. id=>link, with link being an array
     * with the keys "url" and "text".
     */
    public function __construct(Text $text, array $links) {
        parent::__construct($text);
        $this->links = $links;
    }

    public function getText() {
        $result = "";
        if (!$this->links) {
            return "";
        }

        // Header and list start
        $result.= '<h3 class="notable">' . $this->text->t('articles.search.results_in_links') . "</h3>\n";
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
