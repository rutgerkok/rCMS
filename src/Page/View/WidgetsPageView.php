<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * Displays widgets on a page.
 * 
 * @see WidgetsColumnView
 */
final class WidgetsPageView extends View {

    /** @var int The document the widgets are placed in. */
    private $documentId;

    /** @var PlacedWidget[] The widgets manager. */
    private $placedWidgets;

    /** @var boolean Whether create, edit and delete links are shown. */
    private $editLinks;

    /** @var WidgetLoader The widget loader. */
    private $widgetLoader;

    public function __construct(Text $text, $documentId,
            InstalledWidgets $widgetLoader, array $placedWidgets, $editLinks) {
        parent::__construct($text);
        $this->documentId = (int) $documentId;
        $this->widgetLoader = $widgetLoader;
        $this->placedWidgets = $placedWidgets;
        $this->editLinks = (boolean) $editLinks;
    }

    public function writeText(StreamInterface $stream) {
        // Link to manage widgets
        $stream->write($this->getWidgetsEditLinks());

        // Output widgets
        foreach ($this->placedWidgets as $widget) {
            $this->widgetLoader->writeOutput($stream, $widget);
            $stream->write($this->getWidgetEditLinks($widget));
        }
    }

    private function getWidgetEditLinks(PlacedWidget $widget) {
        if (!$this->editLinks) {
            return "";
        }

        $id = $widget->getId();
        $text = $this->text;
        return <<<HTML
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_widget", $id))}">
                    {$text->t("widgets.edit")}
                </a>
                <a class="arrow" href="{$text->e($text->getUrlPage("delete_widget", $id))}">
                    {$text->t("widgets.delete")}
                </a>
            </p>
HTML;
    }

    private function getWidgetsEditLinks() {
        if (!$this->editLinks) {
            return "";
        }

        $text = $this->text;
        return <<<EDIT_LINK
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_document", $this->documentId))}">
                    {$text->t("widgets.edit_page_layout")}
                </a>
            </p>
EDIT_LINK;
    }

}
