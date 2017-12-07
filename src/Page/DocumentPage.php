<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Document\Document;
use Rcms\Core\Document\DocumentRepository;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\WidgetRepository;

use Rcms\Template\DocumentTemplate;
use Rcms\Template\WidgetsPageTemplate;

/**
 * A page that displays a single document.
 */
class DocumentPage extends Page {

    /**
     * @var Document The document of this page.
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
     * @var boolean Whether edit links should be displayed.
     */
    private $editLinks;

    public function init(Website $website, Request $request) {
        $isStaff = $request->hasRank($website, Authentication::RANK_MODERATOR);
        $id = $request->getParamInt(0);
        $this->editLinks = $request->hasRank($website, Authentication::RANK_ADMIN);

        // Load document
        $documentRepo = new DocumentRepository($website->getDatabase(), $isStaff);
        $this->document = $documentRepo->getDocument($id);

        // Load document widgets
        $this->widgetLoader = $website->getWidgets();
        $widgetRepo = new WidgetRepository($website);
        $this->widgets = $widgetRepo->getWidgetsInDocumentWithId($id);
    }

    public function getPageTitle(Text $text) {
        return $this->document->getTitle();
    }

    public function getTemplates(Text $text) {
        return [
            new DocumentTemplate($text, $this->document, $this->editLinks),
            new WidgetsPageTemplate($text, $this->document->getId(), $this->widgetLoader, $this->widgets, false)
            ];
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
