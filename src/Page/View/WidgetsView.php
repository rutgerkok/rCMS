<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;
use Rcms\Core\Widgets;

class WidgetsView extends View {

    protected $area;

    /** @var Widgets The widgets manager. */
    protected $widgets;

    public function __construct(Text $text, Widgets $widgets, $area) {
        parent::__construct($text);
        $this->area = $area;
        $this->widgets = $widgets;
    }

    public function getText() {
        return $this->widgets->getWidgetsHTML($this->area);
    }

}
