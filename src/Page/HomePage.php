<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\View\WidgetsColumnView;

class HomePage extends Page {
    
    const DOCUMENT_ID = 1;

    /**
     * @var PlacedWidget[] The widgets to display. 
     */
    private $widgets;

    /**
     *
     * @var InstalledWidgets Widgets installed on the website.
     */
    private $installedWidgets;

    /**
     * @var boolean Whether edit/delete links are shown.
     */
    private $editLinks;

    public function init(Website $website, Request $request) {
        $this->installedWidgets = $website->getWidgets();

        $widgetsRepo = new WidgetRepository($website);
        $this->widgets = $widgetsRepo->getWidgetsInDocumentWithId(self::DOCUMENT_ID);
        $this->editLinks = $website->isLoggedInAsStaff(true);
    }

    public function getPageTitle(Text $text) {
        return ""; // The widgets will already provide a title
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.home");
    }

    public function getView(Text $text) {
        return new WidgetsColumnView($text, self::DOCUMENT_ID, $this->installedWidgets, $this->widgets, $this->editLinks);
    }

    public function getPageType() {
        return Page::TYPE_HOME;
    }

}
