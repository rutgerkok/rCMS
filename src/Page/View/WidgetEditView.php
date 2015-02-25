<?php

namespace Rcms\Page\View;

use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;

/**
 * Description of WidgetEditView
 */
class WidgetEditView extends View {

    /**
     * @var InstalledWidgets The installed widgets on the site.
     */
    private $installedWidgets;

    /**
     * @var PlacedWidget The widget currently being edited.
     */
    private $editWidget;

    /**
     * @var RequestToken The request token for editing the widget.
     */
    private $requestToken;

    public function __construct(Text $text, InstalledWidgets $installedWidgets,
            PlacedWidget $editWidget, RequestToken $requestToken) {
        parent::__construct($text);
        $this->installedWidgets = $installedWidgets;
        $this->editWidget = $editWidget;
        $this->requestToken = $requestToken;
    }

    public function getText() {
        $editorHtml = $this->installedWidgets->getEditor($this->editWidget);
        $actionUrl = $this->text->getUrlPage("edit_widget", $this->editWidget->getId());
        $documentUrl = $this->text->getUrlPage("document", $this->editWidget->getSidebarId());
        
        $tokenName = RequestToken::FIELD_NAME;
        $tokenValue = htmlSpecialChars($this->requestToken->getTokenString());

        return <<<EDITOR
            <p>{$this->text->t("main.fields_required")}</p>
            <form method="POST" action="{$actionUrl}">
                {$editorHtml}

                <p>
                    <input type="hidden" name="{$tokenName}" value="{$tokenValue}" />
                    <input class="button primary_button" 
                        type="submit" 
                        name="submit"
                        value="{$this->text->t("editor.save")}" />
                    <a class="button" href="{$documentUrl}">
                        {$this->text->t("main.cancel")}
                    </a>
                </p>
            </form>
EDITOR;
    }

}
