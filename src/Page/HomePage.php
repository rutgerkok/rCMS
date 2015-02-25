<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\View\WidgetsView;

class HomePage extends Page {

    /**
     * @var PlacedWidget[] The widgets to display. 
     */
    private $placedWidgets;

    /**
     *
     * @var InstalledWidgets Loaded widget cache.
     */
    private $loadedWidgets;

    /**
     * @var boolean Whether edit/delete links are shown.
     */
    private $editLinks;

    public function init(Website $website, Request $request) {
        $this->loadedWidgets = $website->getWidgets();

        $widgetsRepo = new WidgetRepository($website);
        $this->widgets = $widgetsRepo->getWidgetsInDocumentWithId(1);

        $this->editLinks = $website->isLoggedInAsStaff();
    }

    public function getPageTitle(Text $text) {
        return ""; // The widgets will already provide a title
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.home");
    }

    public function getView(Text $text) {
        return new WidgetsView($text, $this->loadedWidgets, $this->widgets, $this->editLinks);
    }

    public function getPageType() {
        return Page::TYPE_HOME;
    }

}
