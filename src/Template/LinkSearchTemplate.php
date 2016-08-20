<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\Text;

/**
 * Template for the search screen of links.
 */
class LinkSearchTemplate extends Template {

    /** @var Link[] Array of links. */
    protected $links;

    /**
     * Constructs a new menu search view.
     * @param Text $text The website object.
     * @param Link[] $links Array of links.
     */
    public function __construct(Text $text, array $links) {
        parent::__construct($text);
        $this->links = $links;
    }

    public function writeText(StreamInterface $stream) {
        if (!$this->links) {
            return;
        }

        // Header and list start
        $stream->write('<h3 class="notable">' . $this->text->t('articles.search.results_in_links') . "</h3>\n");
        $stream->write('<ul class="linklist">');

        // Add each link
        $text = $this->text;
        foreach ($this->links as $link) {
            $stream->write("<li>");
            $stream->write('<a href="' . $text->e($link->getUrl()) . '">');
            $stream->write($text->e($link->getText()));
            $stream->write("</a></li>\n");
        }

        // Close list and return the result
        $stream->write("</ul>\n");
    }

}
