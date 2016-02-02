<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;

/**
 * Confirmation shown after deleting a document.
 */
class DocumentDeletedView extends View {

    public function __construct(Text $text) {
        parent::__construct($text);
    }

    public function getText() {
        $text = $this->text;
        return <<<HTML
            <p>
                <a class="arrow" href="{$text->e($text->getUrlMain())}">{$text->t("main.home")}</a><br />
                <a href="{$text->e($text->getUrlPage("document_list"))}" class="arrow">{$text->t("documents.list.title")}</a><br />
                <a href="{$text->e($text->getUrlPage("admin"))}" class="arrow">{$text->t("main.admin")}</a>
            </p>
HTML;
    }

}
