<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
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

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        
        $stream->write("<blockquote>");
        $this->installedWidgets->writeOutput($stream, $this->placedWidget);
        $stream->write("</blockquote>");

        $buttonTextHtml = $text->e($this->link->getText());
        $buttonUrl = $text->e($this->link->getUrl());
        $stream->write(<<<HTML
            <p>
                <a href="{$buttonUrl}" class="button primary_button">{$buttonTextHtml}</a>
            </p>
HTML
        );
    }

}
