<?php

namespace Rcms\Page\View;

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

    public function getText() {
        $output = "";

        // Link to manage widgets
        $output.= $this->getWidgetsEditLinks();

        // Output widgets
        foreach ($this->placedWidgets as $widget) {
            $output.= $this->widgetLoader->getOutput($widget);
            $output.= $this->getWidgetEditLinks($widget);
        }

        return $output;
    }

    private function getWidgetEditLinks(PlacedWidget $widget) {
        if (!$this->editLinks) {
            return "";
        }
        $id = $widget->getId();
        return <<<HTML
            <p>
                <a class="arrow" href="{$this->text->getUrlPage("edit_widget", $id)}">
                    {$this->text->t("widgets.edit")}
                </a>
                <a class="arrow" href="{$this->text->getUrlPage("delete_widget", $id)}">
                    {$this->text->t("widgets.delete")}
                </a>
            </p>
HTML;
    }

    private function getWidgetsEditLinks() {
        if (!$this->editLinks) {
            return "";
        }
        return <<<EDIT_LINK
            <p>
                <a class="arrow" href="{$this->text->getUrlPage("edit_document", $this->documentId)}">
                    {$this->text->t("widgets.edit_page_layout")}
                </a>
            </p>
EDIT_LINK;
    }

}
