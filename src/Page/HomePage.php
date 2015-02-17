<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Core\WidgetRepository;
use Rcms\Page\View\WidgetsView;

class HomePage extends Page {
    
    /**
     * @var WidgetRepository The widgets instance. 
     */
    private $widgets;
    
    public function init(Website $website, Request $request) {
        $this->widgets = $website->getWidgets();
    }

    public function getPageTitle(Text $text) {
        return ""; // The widgets will already provide a title
    }
    
    public function getShortPageTitle(Text $text) {
        return $text->t("main.home");
    }

    public function getView(Text $text) {
        return new WidgetsView($text, $this->widgets, 1);
    }

    public function getPageType() {
        return Page::TYPE_HOME;
    }

}
