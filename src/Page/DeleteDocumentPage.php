<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Document\Document;
use Rcms\Core\Document\DocumentRepository;
use Rcms\Core\Website;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\View\DocumentDeleteView;
use Rcms\Page\View\DocumentDeletedView;

/**
 * Page that allows deletion of documents.
 */
class DeleteDocumentPage extends Page {
    
    /**
     * @var Document The document being deleted.
     */
    private $document;
    
    /**
     * @var RequestToken The request token.
     */
    private $requestToken;
    
    private $deleted = false;
    
    public function init(Website $website, Request $request) {
        $documentId = $request->getParamInt(0, 0);
        $documentRepo = new DocumentRepository($website->getDatabase(), true);

        $this->document = $documentRepo->getDocument($documentId);
        if (Validate::requestToken($request)) {
            $widgetRepo = new WidgetRepository($website);
            $documentRepo->deleteDocument($this->document, $widgetRepo);
            $text = $website->getText();
            $text->addMessage($text->t("main.document") . ' ' . $text->t("editor.is_deleted"));
            $this->deleted = true;
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }
    
    public function getView(Text $text) {
        if ($this->deleted) {
            return new DocumentDeletedView($text);
        } else {
            return new DocumentDeleteView($text, $this->document, $this->requestToken);
        }
    }

    public function getPageTitle(Text $text) {
        return $text->tReplaced("documents.delete.title", $this->document->getTitle());
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }
}
