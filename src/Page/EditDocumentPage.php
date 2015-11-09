<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Document\Document;
use Rcms\Core\Document\DocumentRepository;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\User;
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

    public function init(Website $website, Request $request) {
        $id = $request->getParamInt(0, -1);

        // Load document
        $user = $website->getAuth()->getCurrentUser();
        // ^ this is never null, as the required rank for this page is moderator
        $this->document = $this->retrieveDocument($website, $id, $user);

        // Load document widgets
        $this->widgetLoader = $website->getWidgets();
        $widgetRepo = new WidgetRepository($website);
        $this->widgets = $widgetRepo->getWidgetsInDocumentWithId($id);
    }

    private function retrieveDocument(Website $website, $id, User $user) {
        if ($id === 0) {
            // New document
            return Document::createNew("", "", $user->getId());
        }

        $documentRepo = new DocumentRepository($website->getDatabase(), true);
        try {
            return $documentRepo->getDocument($id);
        } catch (NotFoundException $e) {
            // Check if document should be created for widget area
            // (method below throws NotFoundException if no such widget area exist)
            return Document::createForWidgetArea($website, $user, $id);
        }
    }

    public function getPageTitle(Text $text) {
        return $this->document->getTitle();
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getView(Text $text) {
        return new DocumentEditView($text, $this->document, $this->widgetLoader, $this->widgets);
    }

}
