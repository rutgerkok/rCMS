<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\Text;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRunner;

/**
 * Simple view with a button to move the widget.
 */
final class WidgetDetailTemplate extends Template {

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
     * @var WidgetRunner The widgets runner.
     */
    private $widgetRunner;

    public function __construct(Text $text, WidgetRunner $widgetRunner,
            PlacedWidget $placedWidget, Link $link) {
        parent::__construct($text);
        $this->widgetRunner = $widgetRunner;
        $this->placedWidget = $placedWidget;
        $this->link = $link;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        
        $stream->write("<blockquote>");
        $this->widgetRunner->writeOutput($stream, $this->placedWidget);
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
