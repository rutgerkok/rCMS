<?php

namespace Rcms\Page\View;

use Rcms\Core\Website;
use Rcms\Core\Widgets;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetsView extends View {

    protected $area;
    /** @var Widgets The widgets manager. */
    protected $widgets;

    public function __construct(Website $website,  Widgets $widgets, $area) {
        parent::__construct($website);
        $this->area = $area;
        $this->widgets = $widgets;
    }

    public function getText() {
        return $this->widgets->getWidgetsHTML($this->area);
    }

}
