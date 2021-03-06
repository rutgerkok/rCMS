<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Document\Document;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * Show a preview of the document, along with links to delete the document or
 * to cancel deletion.
 */
class DocumentDeleteTemplate extends Template {
    
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
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $title = $this->document->getTitle();
        $intro = $this->document->getIntro();
        $deleteUrl = $text->getUrlPage("delete_document", $this->document->getId(),
                [RequestToken::FIELD_NAME => $this->requestToken->getTokenString()]);
        $stream->write(<<<HTML
            <p>{$text->t("documents.delete.are_you_sure")}</p>
            <blockquote>
                <h3 class="notable">{$text->e($title)}</h3>
                <p class="intro">{$text->e($intro)}</p>
            </blockquote>
            <p>
                <a class="button dangerous_button" href="{$text->e($deleteUrl)}">{$text->t("editor.delete_permanently")}</a>
                <a class="button" href="{$text->e($this->document->getUrl($text))}">{$text->t("main.cancel")}</a>
            </p>
HTML
        );
    }
}
