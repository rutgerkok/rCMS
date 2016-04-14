<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

class CategoriesView extends View {

    protected $categories;

    public function __construct(Text $text, array $categories) {
        parent::__construct($text);
        $this->categories = $categories;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write('<ul class="no_bullets">');
        foreach ($this->categories as $id => $name) {
            $stream->write('<li><a href="' . $text->e($text->getUrlPage("category", $id)));
            $stream->write('" class="arrow">' . htmlSpecialChars($name) . "</a></li>\n");
        }
        $stream->write("</ul>");
    }

}

