<?php

namespace Rcms\Page\View;

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

    public function getText() {
        if (empty($this->documents)) {
            $returnValue = $this->getEmptyPage();
        } else {
            $returnValue = $this->getDocumentIntros($this->documents);
        }

        if ($this->editLinks) {
            $returnValue.= $this->getCreateNewDocumentLink();
        }

        return $returnValue;
    }

    private function getDocumentIntros(array $documents) {
        $returnValue = "";
        foreach ($documents as $document) {
            $returnValue.= $this->getDocumentIntro($document);
        }
        return $returnValue;
    }

    private function getCreateNewDocumentLink() {
        return <<<CREATE_NEW
            <p>
                <a class="arrow" href="{$this->text->getUrlPage("edit_document", 0)}">
                    {$this->text->t("documents.create")}
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
                    <a class="arrow" href="{$document->getUrl($this->text)}">
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
        return <<<HTML
            <a class="arrow" href="{$this->text->getUrlPage("edit_document", $document->getId())}">
                {$this->text->t("main.edit")}
            </a>
            <a class="arrow" href="{$this->text->getUrlPage("delete_document", $document->getId())}">
                {$this->text->t("main.delete")}
            </a>
HTML;
    }

}
