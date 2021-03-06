<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRunner;

/**
 * Description of WidgetEditTemplate
 */
class WidgetEditTemplate extends Template {

    /**
     * @var WidgetRunner The widget runner.
     */
    private $widgetRunner;

    /**
     * @var PlacedWidget The widget currently being edited.
     */
    private $editWidget;

    /**
     * @var RequestToken The request token for editing the widget.
     */
    private $requestToken;

    public function __construct(Text $text, WidgetRunner $widgetRunner,
            PlacedWidget $editWidget, RequestToken $requestToken) {
        parent::__construct($text);
        $this->widgetRunner = $widgetRunner;
        $this->editWidget = $editWidget;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $editorHtml = $this->widgetRunner->getEditor($this->editWidget);
        $actionUrl = $text->getUrlPage("edit_widget", $this->editWidget->getId());
        $documentEditUrl = $text->getUrlPage("edit_document", $this->editWidget->getDocumentId());

        $tokenNameHtml = $text->e(RequestToken::FIELD_NAME);
        $tokenValueHtml = $text->e($this->requestToken->getTokenString());

        $stream->write(<<<EDITOR
            <p>{$this->text->t("main.fields_required")}</p>
            <form method="POST" action="{$text->e($actionUrl)}">
                {$editorHtml}

                <p>
                    <input type="hidden" name="{$tokenNameHtml}" value="{$tokenValueHtml}" />
                    <input type="hidden" name="document_id" value="{$this->editWidget->getDocumentId()}" />
                    <input type="hidden" name="directory_name" value="{$this->editWidget->getDirectoryName()}" />
                    <input class="button primary_button" 
                        type="submit" 
                        name="submit"
                        value="{$this->text->t("editor.save")}" />
                    <a class="button" href="{$text->e($documentEditUrl)}">
                        {$this->text->t("main.cancel")}
                    </a>
                </p>
            </form>
EDITOR
        );
    }

}
