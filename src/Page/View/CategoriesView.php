<?php

namespace Rcms\Page\View;

use Rcms\Core\Website;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class CategoriesView extends View {

    protected $categories;

    public function __construct(Website $oWebsite, array $categories) {
        parent::__construct($oWebsite);
        $this->categories = $categories;
    }

    public function getText() {
        $oWebsite = $this->oWebsite;
        $output = '<ul class="no_bullets">';
        foreach ($this->categories as $id => $name) {
            $output.= '<li><a href="' . $oWebsite->getUrlPage("category", $id);
            $output.= '" class="arrow">' . htmlSpecialChars($name) . "</a></li>\n";
        }
        $output.= "</ul>";
        return $output;
    }

}

?>
