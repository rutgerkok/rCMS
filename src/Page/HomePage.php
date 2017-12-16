<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Config;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Core\Widget\WidgetRunner;
use Rcms\Template\WidgetsColumnTemplate;

class HomePage extends Page {
    
    const DOCUMENT_ID = 1;

    /**
     * @var PlacedWidget[] The widgets to display. 
     */
    private $widgets;

    /**
     *
     * @var WidgetRunner Widgets installed on the website.
     */
    private $widgetRunner;

    /**
     * @var boolean Whether edit/delete links are shown.
     */
    private $editLinks;
    
    /**
     * @var string The title of the website.
     */
    private $siteTitle;

    public function init(Website $website, Request $request) {
        $this->widgetRunner = new WidgetRunner($website, $request);
        $this->siteTitle = $website->getConfig()->get(Config::OPTION_SITE_TITLE);

        $widgetsRepo = new WidgetRepository($website);
        $this->widgets = $widgetsRepo->getWidgetsInDocumentWithId(self::DOCUMENT_ID);
        $this->editLinks = $request->hasRank(Ranks::ADMIN);
    }

    public function getPageTitle(Text $text) {
        return ""; // The widgets will already provide a title
    }

    public function getShortPageTitle(Text $text) {
        return $this->siteTitle;
    }

    public function getTemplate(Text $text) {
        return new WidgetsColumnTemplate($text, self::DOCUMENT_ID, $this->widgetRunner, $this->widgets, $this->editLinks);
    }

    public function getPageType() {
        return Page::TYPE_HOME;
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

}
