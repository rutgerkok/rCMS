<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Document\Document;
use Rcms\Core\Text;

/**
 * The HTML view of a single document. Only includes the intro of the document,
 * the widgets are not shown. Use `WidgetsView` for that.
 */
class DocumentView extends View {

    /**
     * @var Document The document.
     */
    private $document;

    /**
     * @var boolean Whether edit and delete links should be shown.
     */
    private $editLinks;

    public function __construct(Text $text, Document $document, $editLinks) {
        parent::__construct($text);
        $this->document = $document;
        $this->editLinks = (boolean) $editLinks;
    }

    public function writeText(StreamInterface $stream) {
        $introHtml = nl2br($this->text->e($this->document->getIntro()), true);
        $editDeleteHtml = "";
        if ($this->editLinks) {
            $editDeleteHtml = $this->getEditDeleteHtml();
        }
        $stream->write(<<<INTRO
            <p class="intro">
                $introHtml
            </p>
            {$editDeleteHtml}
INTRO
        );
    }

    private function getEditDeleteHtml() {
        $id = $this->document->getId();
        $text = $this->text;

        return <<<EDIT
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_document", $id))}">
                    {$text->t("main.edit")}
                </a>
                <a class="arrow" href="{$text->e($text->getUrlPage("delete_document", $id))}">
                    {$text->t("main.delete")}
                </a>
            </p>
EDIT;
    }

}
