<?php

namespace Rcms\Page\View;

use Rcms\Core\Document\Document;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * Show a preview of the document, along with links to delete the document or
 * to cancel deletion.
 */
class DocumentDeleteView extends View {
    
    /**
     * @var Document Document being deleted.
     */
    private $document;
    
    /**
     *
     * @var RequestToken Request token for delete link.
     */
    private $requestToken;
    
    public function __construct(Text $text, Document $document, RequestToken $requestToken) {
        parent::__construct($text);
        $this->document = $document;
        $this->requestToken = $requestToken;
    }
    
    public function getText() {
        $titleHtml = htmlSpecialChars($this->document->getTitle());
        $introHtml = htmlSpecialChars($this->document->getIntro());
        $deleteUrlHtml = $this->text->getUrlPage("delete_document", $this->document->getId(),
                array(RequestToken::FIELD_NAME => $this->requestToken->getTokenString()));
        return <<<HTML
            <p>{$this->text->t("documents.delete.are_you_sure")}</p>
            <blockquote>
                <h3 class="notable">{$titleHtml}</h3>
                <p class="intro">{$introHtml}</p>
            </blockquote>
            <p>
                <a class="button primary_button" href="{$deleteUrlHtml}">{$this->text->t("editor.delete_permanently")}</a>
                <a class="button" href="{$this->document->getUrl($this->text)}">{$this->text->t("main.cancel")}</a>
            </p>
HTML;
    }
}
