<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Document\Document;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRunner;

/**
 * The HTML view of a single document. Only includes the intro of the document,
 * the widgets are not shown. Use `WidgetsTemplate` for that.
 */
final class DocumentEditTemplate extends Template {

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
     * @var WidgetRunner The widget runner.
     */
    private $widgetRunner;

    public function __construct(Text $text, Document $document,
            RequestToken $requestToken, WidgetRunner $widgetRunner,
            array $placedWidgets) {
        parent::__construct($text);
        $this->document = $document;
        $this->requestToken = $requestToken;
        $this->widgetRunner = $widgetRunner;
        $this->placedWidgets = $placedWidgets;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write($this->getDocumentTitleAndIntroEditor());
        $this->writeWidgetsHtml($stream);
        $stream->write($this->getNewWidgetChoicesHtml());
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
        $documentUrlHtml = $this->text->e($this->text->getUrlPage("edit_document", $this->document->getId()));
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
                    <a href="{$this->text->e($this->text->getUrlPage("document", $this->document->getId()))}" class="button">
                        {$this->text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML;
    }

    private function writeWidgetsHtml(StreamInterface $stream) {
        $placedWidgets = array_values($this->placedWidgets);
        for ($i = 0; $i < count($placedWidgets); $i++) {
            $placedWidget = $placedWidgets[$i];
            $this->writeWidgetHtml($stream, $placedWidget, $i);
        }
    }

    private function writeWidgetHtml(StreamInterface $stream, PlacedWidget $placedWidget, $widgetNumber) {
        $text = $this->text;

        $id = $placedWidget->getId();

        $tokenName = RequestToken::FIELD_NAME;
        $token = $this->requestToken->getTokenString();

        $stream->write("<blockquote>");
        $this->widgetRunner->writeOutput($stream, $placedWidget);
        $stream->write("</blockquote>");
        
        $stream->write(<<<HTML
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_widget", $id))}">
                    {$text->t("main.edit")}
                </a>
                <a class="arrow" href="{$text->e($text->getUrlPage("delete_widget", $id))}">
                    {$text->t("main.delete")}
                </a>
HTML
        );
        if ($widgetNumber != 0) {
            $stream->write(<<<HTML
                <a class="arrow" href="{$text->e($text->getUrlPage("move_widget", $id, ["direction" => "up", $tokenName => $token]))}">
                    {$text->t("widgets.move_up")}
                </a>   
HTML
            );
        }
        if ($widgetNumber < count($this->placedWidgets) - 1) {
            $stream->write(<<<HTML
                <a class="arrow" href="{$text->e($text->getUrlPage("move_widget", $id, ["direction" => "down", $tokenName => $token]))}">
                    {$text->t("widgets.move_down")}
                </a>  
HTML
            );
        }
        $stream->write(<<<HTML
            </p>
            <hr />
HTML
        );
    }

    private function getNewWidgetChoicesHtml() {
        if ($this->document->getId() === 0) {
            return "";
        }

        $returnValue = "";
        if (empty($this->placedWidgets)) {
            $returnValue.= "<p>{$this->text->t("documents.no_widgets_added_yet")}</p>";
        }
        $returnValue.= <<<HTML
            <h3 class="notable">
                {$this->text->t("widgets.add_new_widget")}
            </h3>
HTML;

        $installedWidgets = $this->widgetRunner->getInstalledWidgets();
        foreach ($installedWidgets as $installedWidget) {
            $widgetNameHtml = htmlSpecialChars($installedWidget->getDisplayName());
            $descriptionHtml = htmlSpecialChars($installedWidget->getDescription());

            $widgetUrlHtml = htmlSpecialChars($installedWidget->getWidgetWebsite());
            $authorNameHtml = htmlSpecialChars($installedWidget->getAuthor());
            $authorUrlHtml = htmlSpecialChars($installedWidget->getAuthorWebsite());

            $addToDocumentUrlHtml = $this->text->e($this->text->getUrlPage("edit_widget", null, ["directory_name" => $installedWidget->getDirectoryName(),
                "document_id" => $this->document->getId()]));

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
