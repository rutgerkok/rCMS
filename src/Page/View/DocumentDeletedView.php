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
        return <<<HTML
            <p>
                <a class="arrow" href="{$this->text->getUrlMain()}">{$this->text->t("main.home")}</a><br />
                <a href="{$this->text->getUrlPage("document_list")}" class="arrow">{$this->text->t("documents.list.title")}</a><br />
                <a href="{$this->text->getUrlPage("admin")}" class="arrow">{$this->text->t("main.admin")}</a>
            </p>
HTML;
    }

}
