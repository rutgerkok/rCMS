<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Widgets;
use Rcms\Page\View\WidgetsView;

class HomePage extends Page {
    
    /**
     * @var Widgets The widgets instance. 
     */
    private $widgets;
    
    public function init(Request $request) {
        $this->widgets = $request->getWebsite()->getWidgets();
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
        return "HOME";
    }

}
