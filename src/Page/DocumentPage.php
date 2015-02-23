<?php

namespace Rcms\Page;

use Rcms\Core\Document\Document;
use Rcms\Core\Document\DocumentRepository;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\WidgetRepository;

use Rcms\Page\View\DocumentView;
use Rcms\Page\View\WidgetsView;

/**
 * A page that displays a single document.
 */
class DocumentPage extends Page {

    /**
     * @var Document The document of this page.
     */
    private $document;
    
    /**
     *
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
        $isStaff = $website->isLoggedInAsStaff();
        $id = $request->getParamInt(0);
        $this->editLinks = $isStaff;

        // Load document
        $documentRepo = new DocumentRepository($website->getDatabase(), $isStaff);
        $this->document = $documentRepo->getDocument($id);

        // Load document widgets
        $this->widgetLoader = $website->getWidgets();
        $widgetRepo = new WidgetRepository($website);
        $this->widgets = $widgetRepo->getPlacedWidgetsFromSidebar($id);
    }

    public function getPageTitle(Text $text) {
        return $this->document->getTitle();
    }

    public function getViews(Text $text) {
        return array(
            new DocumentView($text, $this->document),
            new WidgetsView($text, $this->widgetLoader, $this->widgets, $this->editLinks)
            );
    }

}
