<?php

namespace Rcms\Page\View;

use Rcms\Core\Document\Document;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * The HTML view of a single document. Only includes the intro of the document,
 * the widgets are not shown. Use `WidgetsView` for that.
 */
class DocumentEditView extends View {

    /**
     * @var Document The document.
     */
    private $document;

    /**
     * @var PlacedWidget[] The widgets. 
     */
    private $placedWidgets;

    /**
     *
     * @var InstalledWidgets The installed widgets on the website.
     */
    private $installedWidgets;

    public function __construct(Text $text, Document $document,
            InstalledWidgets $installedWidgets, array $placedWidgets) {
        parent::__construct($text);
        $this->document = $document;
        $this->installedWidgets = $installedWidgets;
        $this->placedWidgets = $placedWidgets;
    }

    public function getText() {
        $introHtml = nl2br(htmlSpecialChars($this->document->getIntro()), true);
        return <<<TEXT
        
        <form>
            <p>
                    <textarea class="full_width" rows="3">$introHtml</textarea>
                    <input type="submit" class="button primary_button" value="{$this->text->t("editor.save")}" />
                    <a href="{$this->text->getUrlPage("document", $this->document->getId())}" class="button">
                        {$this->text->t("main.cancel")}
                    </a>
            </p>
        </form>

        {$this->getWidgetsHtml()}
        <p>
            {$this->text->t("widgets.add_new_widget")}:
        </p>
TEXT;
    }

    private function getWidgetsHtml() {
        $output = "";
        foreach ($this->placedWidgets as $placedWidget) {
            $widgetInfo = $placedWidget->getWidgetInfo();
            $nameHtml = htmlSpecialChars($widgetInfo->getName());
            $widgetData = $placedWidget->getData();
            if (isSet($widgetData["title"])) {
                $nameHtml.= htmlSpecialChars(": " . $widgetData["title"]);
            }
            $id = $placedWidget->getId();

            $output.= <<<HTML
                <blockquote>
                     {$this->installedWidgets->getOutput($placedWidget)}
                </blockquote>
                <p>
                    <a class="arrow" href="{$this->text->getUrlPage("edit_widget", $id)}">
                        {$this->text->t("main.edit")}
                    </a>
                    <a class="arrow" href="{$this->text->getUrlPage("delete_widget", $id)}">
                        {$this->text->t("main.delete")}
                    </a>
                </p>
                <hr>
HTML;
        }
        return $output;
    }

}
