<?php

namespace Rcms\Page\View;

use Rcms\Core\Link;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * Simple view with a button to move the widget.
 */
final class WidgetDetailView extends View {

    /**
     * @var PlacedWidget The widget being viewed.
     */
    private $placedWidget;

    /**
     * @var Link Link for the primary button.
     */
    private $link;

    /**
     *
     * @var InstalledWidgets The widgets installed on the website.
     */
    private $installedWidgets;

    public function __construct(Text $text, InstalledWidgets $installedWidgets,
            PlacedWidget $placedWidget, Link $link) {
        parent::__construct($text);
        $this->installedWidgets = $installedWidgets;
        $this->placedWidget = $placedWidget;
        $this->link = $link;
    }

    public function getText() {
        $widgetHtml = $this->installedWidgets->getOutput($this->placedWidget);
        $buttonTextHtml = htmlSpecialChars($this->link->getText());
        $buttonUrl = $this->link->getUrl();
        return <<<HTML
            <blockquote>{$widgetHtml}</blockquote>
            <p>
                <a href="{$buttonUrl}" class="button primary_button">{$buttonTextHtml}</a>
            </p>
HTML;
    }

}
