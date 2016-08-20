<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Template for the 404 page.
 */
class Error404Template extends Template {

    public function __construct(Text $text) {
        parent::__construct($text);
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<PAGE
        <p>
            {$text->t("errors.404_page.body")}
        </p>
        <p>
            <a href="{$text->e($text->getUrlMain())}" class="arrow">
                {$text->t("main.home")}
            </a>
            <br />
            <a href="{$text->e($text->getUrlPage("search"))}" class="arrow">
                {$text->t("main.search")}
            </a>
            <br />
            <a href="{$text->e($text->getUrlPage("archive"))}" class="arrow">
                {$text->t("articles.archive")}
            </a>
        </p>
PAGE
        );
    }

}
