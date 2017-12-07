<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Rcms\Core\Widget;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Request;
use Rcms\Core\Website;

/**
 * Runs the code of a widget.
 */
class WidgetRunner {

    /**
     * @var Website The website.
     */
    private $website;
    
    /**
     * @var Request The request.
     */
    private $request;
    
    public function __construct(Website $website, Request $request) {
        $this->website = $website;
        $this->request = $request;
    }
    
    /**
     * Gets the HTML output of the widget.
     * @param StreamInterface $stream The stream to write to
     * @param PlacedWidget $placedWidget The widget.
     * @return string The HTML output.
     */
    public function writeOutput(StreamInterface $stream, PlacedWidget $placedWidget) {
        $widgetDefinition = $this->website->getWidgets()->getDefinition($placedWidget);

        $widgetDefinition->writeText($stream, $this->website, $this->request,
                $placedWidget->getId(), $placedWidget->getData());
    }
    
    /**
     * Gets the HTML for the editor of the widget.
     * @param PlacedWidget $placedWidget The widget.
     * @return string The HTML output.
     */
    public function getEditor(PlacedWidget $placedWidget) {
        $widgetDefinition = $this->website->getWidgets()->getDefinition($placedWidget);
        return $widgetDefinition->getEditor($this->website, $placedWidget->getId(), $placedWidget->getData());
    }
}
