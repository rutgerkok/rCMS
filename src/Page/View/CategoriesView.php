<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

class CategoriesView extends View {

    protected $categories;

    public function __construct(Text $text, array $categories) {
        parent::__construct($text);
        $this->categories = $categories;
    }

    public function getText() {
        $text = $this->text;
        $output = '<ul class="no_bullets">';
        foreach ($this->categories as $id => $name) {
            $output.= '<li><a href="' . $text->getUrlPage("category", $id);
            $output.= '" class="arrow">' . htmlSpecialChars($name) . "</a></li>\n";
        }
        $output.= "</ul>";
        return $output;
    }

}

?>
