<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Simple view used on the bottom of most link editing pages.
 */
class LinkEditFooterTemplate extends Template {
    
    public function __construct(Text $text) {
        parent::__construct($text);
    }
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
            <hr />
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("links"))}">
                    {$text->t("main.links")}
                </a>
                <br />
                <a class="arrow" href="{$text->e($text->getUrlPage("admin"))}">
                    {$text->t("main.admin")}
                </a>
            </p>    
HTML
                );
    }
}
