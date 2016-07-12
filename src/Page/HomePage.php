<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Config;
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
    
    /**
     * @var string The title of the website.
     */
    private $siteTitle;

    public function init(Website $website, Request $request) {
        $this->installedWidgets = $website->getWidgets();
        $this->siteTitle = $website->getConfig()->get(Config::OPTION_SITE_TITLE);

        $widgetsRepo = new WidgetRepository($website);
        $this->widgets = $widgetsRepo->getWidgetsInDocumentWithId(self::DOCUMENT_ID);
        $this->editLinks = $website->isLoggedInAsStaff(true);
    }

    public function getPageTitle(Text $text) {
        return ""; // The widgets will already provide a title
    }

    public function getShortPageTitle(Text $text) {
        return $this->siteTitle;
    }

    public function getView(Text $text) {
        return new WidgetsColumnView($text, self::DOCUMENT_ID, $this->installedWidgets, $this->widgets, $this->editLinks);
    }

    public function getPageType() {
        return Page::TYPE_HOME;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
