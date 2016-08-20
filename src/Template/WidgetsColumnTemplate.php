<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * Displays widgets. This view is intended to be displayed on a single column:
 * edit links will fpr example say "Edit Column".
 *
 * To make the column not too crowded, individual widgets don't have edit/delete
 * links. The user has to click on "Ëdit Column" first for those links to appear.
 */
final class WidgetsColumnTemplate extends Template {

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
        }
    }

    private function getWidgetsEditLinks() {
        if (!$this->editLinks) {
            return "";
        }

        $text = $this->text;
        return <<<EDIT_LINK
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_document", $this->documentId))}">
                    {$text->t("widgets.edit_column_layout")}
                </a>
            </p>
EDIT_LINK;
    }

}
