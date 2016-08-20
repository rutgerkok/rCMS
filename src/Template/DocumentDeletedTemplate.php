<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;

/**
 * Confirmation shown after deleting a document.
 */
class DocumentDeletedTemplate extends Template {

    public function __construct(Text $text) {
        parent::__construct($text);
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
            <p>
                <a class="arrow" href="{$text->e($text->getUrlMain())}">{$text->t("main.home")}</a><br />
                <a href="{$text->e($text->getUrlPage("document_list"))}" class="arrow">{$text->t("documents.list.title")}</a><br />
                <a href="{$text->e($text->getUrlPage("admin"))}" class="arrow">{$text->t("main.admin")}</a>
            </p>
HTML
        );
    }

}
