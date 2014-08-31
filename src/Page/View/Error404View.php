<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * View for the 404 page.
 */
class Error404View extends View {

    public function __construct(Text $text) {
        parent::__construct($text);
    }

    public function getText() {
        $text = $this->text;
        return <<<PAGE
        <p>
            {$text->t("errors.404_page.body")}
        </p>
        <p>
            <a href="{$text->getUrlMain()}" class="arrow">
                {$text->t("main.home")}
            </a>
            <br />
            <a href="{$text->getUrlPage("search")}" class="arrow">
                {$text->t("main.search")}
            </a>
            <br />
            <a href="{$text->getUrlPage("archive")}" class="arrow">
                {$text->t("articles.archive")}
            </a>
        </p>
PAGE;
    }

}
