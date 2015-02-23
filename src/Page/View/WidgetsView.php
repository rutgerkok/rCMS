<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

class WidgetsView extends View {

    /** @var PlacedWidget[] The widgets manager. */
    private $placedWidgets;

    /** @var boolean Whether create, edit and delete links are shown. */
    private $editLinks;

    /** @var WidgetLoader The widget loader. */
    private $widgetLoader;

    public function __construct(Text $text, InstalledWidgets $widgetLoader,
            array $placedWidgets, $editLinks) {
        parent::__construct($text);
        $this->widgetLoader = $widgetLoader;
        $this->placedWidgets = $placedWidgets;
        $this->editLinks = (boolean) $editLinks;
    }

    public function getText() {
        $output = "";

        // Output widgets
        foreach ($this->placedWidgets as $widget) {
            $output.= $this->widgetLoader->getOutput($widget);
            if ($this->editLinks) {
                $output.= $this->getWidgetEditLinks($widget);
            }
        }

        // Link to manage widgets
        if ($this->editLinks) {
            $output.= $this->getWidgetsEditLinks();
        }

        return $output;
    }

    private function getWidgetEditLinks(PlacedWidget $widget) {
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
        return <<<HTML
            <p>
                <a class="arrow" href="{$this->text->getUrlPage("widgets")}">
                    {$this->text->t("widgets.manage")}
                </a>
            </p>
HTML;
    }

}
