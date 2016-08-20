<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Description of SearchFormTemplate
 */
class SearchFormTemplate extends Template {

    protected $keyword;

    public function __construct(Text $text, $keyword) {
        parent::__construct($text);
        $this->keyword = $keyword;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<SEARCHFORM
            <p>
                <form action="{$text->e($text->getUrlPage("search"))}" method="GET">
                    <input name="searchbox" id="searchbox-big" value="{$text->e($this->keyword)}" />
                    <input type="submit" class="button primary_button" value="{$text->t("main.search")}" />
                </form>
            </p>
            <p>{$text->t("articles.search.explained")}</p>
SEARCHFORM
        );
    }

}
