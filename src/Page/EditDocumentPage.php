<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Document\Document;
use Rcms\Core\Document\DocumentRepository;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\View\DocumentEditView;

/**
 * The page that provides the admin an editor to edit the documents.
 */
class EditDocumentPage extends Page {

    /**
     * @var Document The document that is being edited.
     */
    private $document;

    /**
     * @var PlacedWidget[] Widgets added to this document.
     */
    private $widgets;

    /**
     * @var InstalledWidgets The widget loader.
     */
    private $widgetLoader;

    /**
     * @var RequestToken The request token that will be sent, placed in the form.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $id = $request->getParamInt(0, 0);

        // Load document
        $documentRepo = new DocumentRepository($website->getDatabase(), true);
        $user = $website->getAuth()->getCurrentUser();
        // ^ this is never null, as the required rank for this page is moderator
        $this->document = $this->retrieveDocument($website, $documentRepo, $id, $user);

        // Load document widgets
        $this->widgetLoader = $website->getWidgets();
        $widgetRepo = new WidgetRepository($website);
        $this->widgets = $widgetRepo->getWidgetsInDocumentWithId($id);

        // Check for edits
        $this->saveData($website->getText(), $request, $this->document, $documentRepo);

        // Store new request token
        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function saveData(Text $text, Request $request, Document $document,
            DocumentRepository $documentRepo) {
        if (!$request->hasRequestValue("intro") || !$request->hasRequestValue("title")) {
            return;
        }
        if ($document->isForWidgetArea()) {
            $text->addError($text->t("main.document") . ' ' . $text->t("errors.not_editable"));
            return;
        }

        $document->setIntro($request->getRequestString("intro", ''));
        $document->setTitle($request->getRequestString("title", ''));

        $valid = true;

        if (!Validate::requestToken($request)) {
            $valid = false;
        }

        if (!Validate::stringLength($document->getIntro(), Document::INTRO_MIN_LENGTH, Document::INTRO_MAX_LENGTH)) {
            $website->addError($text->t("documents.intro") . ' ' . Validate::getLastError($text));
            $valid = false;
        }

        if (!Validate::stringLength($document->getIntro(), Document::TITLE_MIN_LENGTH, Document::TITLE_MAX_LENGTH)) {
            $website->addError($text->t("documents.title") . ' ' . Validate::getLastError($text));
            $valid = false;
        }

        if (!$valid) {
            return;
        }

        $isNew = $document->getId() == 0;
        $documentRepo->saveDocument($document);
        if ($isNew) {
            $text->addMessage($text->t("main.document") . ' ' . $text->t("editor.is_created"));
        } else {
            $text->addMessage($text->t("main.document") . ' ' . $text->t("editor.is_edited"));
        }
    }

    private function retrieveDocument(Website $website,
            DocumentRepository $documentRepo, $id, User $user) {
        if ($id === 0) {
            // New document
            return Document::createNew("", "", $user);
        }

        return $documentRepo->getDocumentOrWidgetArea($website->getWidgets(), $website->getText(), $id);
    }

    public function getPageTitle(Text $text) {
        return "";
    }
    
    public function getShortPageTitle(Text $text) {
        return $this->document->getTitle();
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getView(Text $text) {
        return new DocumentEditView($text, $this->document, $this->requestToken, $this->widgetLoader, $this->widgets);
    }

}
