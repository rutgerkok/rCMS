<?php

namespace Rcms\Page\View;

use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * Displays widgets. This view is intended to be displayed on a single column:
 * edit links will fpr example say "Edit Column".
 *
 * To make the column not too crowded, individual widgets don't have edit/delete
 * links. The user has to click on "Ëdit Column" first for those links to appear.
 *
 * @see WidgetsPageView
 */
final class WidgetsColumnView extends View {

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
        }

        return $output;
    }

    private function getWidgetsEditLinks() {
        if (!$this->editLinks) {
            return "";
        }
        return <<<EDIT_LINK
            <p>
                <a class="arrow" href="{$this->text->getUrlPage("edit_document", $this->documentId)}">
                    {$this->text->t("widgets.edit_column_layout")}
                </a>
            </p>
EDIT_LINK;
    }

}
