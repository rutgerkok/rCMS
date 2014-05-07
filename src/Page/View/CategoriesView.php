<?php

namespace Rcms\Page\View;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class CategoriesView extends View {

    protected $oCategories;

    public function __construct(Website $oWebsite, Categories $oCategories) {
        parent::__construct($oWebsite);
        $this->oCategories = $oCategories;
    }

    public function getText() {
        $oWebsite = $this->oWebsite;
        $categories = $this->oCategories->getCategories();
        $output = '<ul class="no_bullets">';
        foreach ($categories as $id => $name) {
            $output.= '<li><a href="' . $oWebsite->getUrlPage("category", $id);
            $output.= '" class="arrow">' . htmlSpecialChars($name) . "</a></li>\n";
        }
        $output.= "</ul>";
        return $output;
    }

}

?>
