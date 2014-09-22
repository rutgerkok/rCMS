<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;
use Rcms\Core\WidgetRepository;

class WidgetsView extends View {

    protected $area;

    /** @var WidgetRepository The widgets manager. */
    protected $widgets;

    public function __construct(Text $text, WidgetRepository $widgets, $area) {
        parent::__construct($text);
        $this->area = $area;
        $this->widgets = $widgets;
    }

    public function getText() {
        return $this->widgets->getWidgetsHTML($this->area);
    }

}
