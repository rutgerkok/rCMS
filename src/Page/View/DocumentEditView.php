<?php

namespace Rcms\Page\View;

use Rcms\Core\Document\Document;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * The HTML view of a single document. Only includes the intro of the document,
 * the widgets are not shown. Use `WidgetsView` for that.
 */
final class DocumentEditView extends View {

    /**
     * @var Document The document.
     */
    private $document;

    /**
     * @var RequestToken The request token.
     */
    private $requestToken;

    /**
     * @var PlacedWidget[] The widgets. 
     */
    private $placedWidgets;

    /**
     *
     * @var InstalledWidgets The installed widgets on the website.
     */
    private $installedWidgets;

    public function __construct(Text $text, Document $document, RequestToken $requestToken,
            InstalledWidgets $installedWidgets, array $placedWidgets) {
        parent::__construct($text);
        $this->document = $document;
        $this->requestToken = $requestToken;
        $this->installedWidgets = $installedWidgets;
        $this->placedWidgets = $placedWidgets;
    }

    public function getText() {
        return <<<HTML
            {$this->getDocumentTitleAndIntroEditor()}

            {$this->getWidgetsHtml()}

            {$this->getNewWidgetChoicesHtml()}
HTML;
    }
    
    private function getDocumentTitleAndIntro() {
        $titleHtml = htmlSpecialChars($this->document->getTitle());
        $introHtml = nl2br(htmlSpecialChars($this->document->getIntro()), true);
        return <<<HTML
            <h2>{$titleHtml}</h2>
            <p class="intro">{$introHtml}</p>
HTML;
    }
    
    private function getDocumentTitleAndIntroEditor() {
        if ($this->document->isForWidgetArea()) {
            return $this->getDocumentTitleAndIntro();
        }
        $documentUrlHtml = $this->text->getUrlPage("edit_document", $this->document->getId());
        $titleHtml = htmlSpecialChars($this->document->getTitle());
        $introHtml = nl2br(htmlSpecialChars($this->document->getIntro()), true);
        $tokenNameHtml = htmlSpecialChars(RequestToken::FIELD_NAME);
        $tokenHtml = htmlSpecialChars($this->requestToken->getTokenString());

        return <<<HTML
            <form action="{$documentUrlHtml}" method="POST">
                <p>
                    <label for="title">
                        {$this->text->t("documents.title")}:
                        <span class="required">*</span>
                    </label>
                    <br />
                    <input type="text" value="{$titleHtml}" name="title" id="title" class="full_width" />
                </p>
                <p>
                    {$this->text->t("main.fields_required")}
                </p>
                <p>
                    <label for="intro">
                        {$this->text->t("documents.intro")}:
                        <span class="required">*</span>
                    </label>
                    <textarea class="full_width" rows="3" name="intro" id="intro">$introHtml</textarea>
                </p>
                <p>
                    <input type="hidden" name="{$tokenNameHtml}" value="{$tokenHtml}" />
                    <input type="submit" class="button primary_button" value="{$this->text->t("editor.save")}" />
                    <a href="{$this->text->getUrlPage("document", $this->document->getId())}" class="button">
                        {$this->text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML;
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
    
    private function getNewWidgetChoicesHtml() {
        if ($this->document->getId() === 0) {
            return "";
        }
        $returnValue = <<<HTML
            <p>
                {$this->text->t("widgets.add_new_widget")}:
            </p>
HTML;
        if (empty($this->placedWidgets)) {
            $returnValue = "<p>{$this->text->t("documents.no_widgets_added_yet")}</p>";
        }
        
        $installedWidgets = $this->installedWidgets->getInstalledWidgets();
        foreach ($installedWidgets as $installedWidget) {
            $widgetNameHtml = htmlSpecialChars($installedWidget->getName());
            $descriptionHtml = htmlSpecialChars($installedWidget->getDescription());
            
            $widgetUrlHtml = htmlSpecialChars($installedWidget->getWidgetWebsite());
            $authorNameHtml = htmlSpecialChars($installedWidget->getAuthor());
            $authorUrlHtml = htmlSpecialChars($installedWidget->getAuthorWebsite());
            
            $addToDocumentUrlHtml = $this->text->getUrlPage("edit_widget", null,
                    array("directory_name" => $installedWidget->getDirectoryName(),
                        "document_id" => $this->document->getId()));
            
            $returnValue.= <<<HTML
                <h3>{$widgetNameHtml}</h3>
                <p>
                    {$descriptionHtml}
                    {$this->text->t("widgets.created_by")}
                    <a href="{$authorUrlHtml}"">{$authorNameHtml}</a>.
                    <a href="{$widgetUrlHtml}" class="arrow">{$this->text->t("widgets.view_more_information")}</a>
                    
                </p>
                <p>
                    <a href="{$addToDocumentUrlHtml}" class="arrow">{$this->text->t("widgets.add_to_document")}</a>
                </p>
HTML;
        }
        
        return $returnValue;
    }

}
