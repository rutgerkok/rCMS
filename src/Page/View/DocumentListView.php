<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Document\Document;
use Rcms\Core\Text;

/**
 * A view for a list of documents.
 */
class DocumentListView extends View {

    /**
     * @var Document[] The documents to display.
     */
    private $documents;

    /**
     * @var boolean Whether links to edit documents should be displayed.
     */
    private $editLinks;

    /**
     * Creates a new view for a list of documents.
     * @param Text $text The text instance.
     * @param Document[] $documents Documents to display.
     * @param boolean $editLinks Whether edit/delete/create new links must be
     * displayed.
     */
    public function __construct(Text $text, array $documents, $editLinks) {
        parent::__construct($text);
        $this->documents = $documents;
        $this->editLinks = (boolean) $editLinks;
    }

    public function writeText(StreamInterface $stream) {
        if (empty($this->documents)) {
            $stream->write($this->getEmptyPage());
        } else {
            $stream->write($this->getDocumentIntros($this->documents));
        }

        if ($this->editLinks) {
            $stream->write($this->getCreateNewDocumentLink());
        }
    }

    private function getDocumentIntros(array $documents) {
        $returnValue = "";
        foreach ($documents as $document) {
            $returnValue.= $this->getDocumentIntro($document);
        }
        return $returnValue;
    }

    private function getCreateNewDocumentLink() {
        $text = $this->text;
        return <<<CREATE_NEW
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_document", 0))}">
                    {$text->t("documents.create")}
                </a>
            </p>
CREATE_NEW;
    }

    private function getEmptyPage() {
        return <<<ERROR
            <p>
                <em>{$this->text->t("errors.nothing_found")}</em>
            </p>
ERROR;
    }

    private function getDocumentIntro(Document $document) {
        $titleHtml = htmlSpecialChars($document->getTitle());
        $introHtml = htmlSpecialChars($document->getIntro());
        return <<<DOCUMENT
            <article>
                <h3>$titleHtml</h3>
                <p>$introHtml</p>
                <p>
                    <a class="arrow" href="{$this->e($document->getUrl($this->text))}">
                        {$this->text->t("documents.view")}
                    </a>
                    {$this->getDocumentEditLinks($document)}
                </p>
            </article>
DOCUMENT;
    }
    
    private function getDocumentEditLinks(Document $document) {
        if (!$this->editLinks) {
            return "";
        }
        $text = $this->text;
        return <<<HTML
            <a class="arrow" href="{$text->e($text->getUrlPage("edit_document", $document->getId()))}">
                {$text->t("main.edit")}
            </a>
            <a class="arrow" href="{$text->e($text->getUrlPage("delete_document", $document->getId()))}">
                {$text->t("main.delete")}
            </a>
HTML;
    }

}
