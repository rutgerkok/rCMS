<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Document\DocumentRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Core\Widget\WidgetRunner;
use Rcms\Template\WidgetEditTemplate;

/**
 * Edits a single widget.
 */
class EditWidgetPage extends Page {

    /**
     * @var PlacedWidget The widget being edited.
     */
    private $placedWidget;

    /**
     * @var WidgetRunner The widgets installed on the website.
     */
    private $widgetRunner;

    /**
     * @var RequestToken Token against CSRF attacks.
     */
    private $requestToken;

    /**
     * Creates a new widget based on the request paramaters, or throws an
     * exception on error.
     * @param Website $website The website object.
     * @param Request $request The request.
     * @return PlacedWidget A new widget, still needs to be saved in the database.
     * @throws NotFoundException If the document or widget type in the request
     * is non-existant.
     */
    private function getNewWidget(Website $website, Request $request) {
        $directoryName = $request->getRequestString("directory_name", "");
        if ($directoryName === "") {
            throw new NotFoundException();
        }
        
        // Get document
        $documentRepo = new DocumentRepository($website->getDatabase(), true);
        $documentId = $request->getRequestInt("document_id", 0);
        $document = $documentRepo->getDocumentOrWidgetArea($website->getWidgets(), $website->getText(), $documentId);

        return PlacedWidget::newPlacedWidget($website->getUriWidgets(), $directoryName, $document);
    }

    public function init(Website $website, Request $request) {
        $this->widgetRunner = new WidgetRunner($website, $request);

        $widgetRepo = new WidgetRepository($website);
        $widgetId = $request->getParamInt(0);
        if ($widgetId === 0) {
            // New widget
            $this->placedWidget = $this->getNewWidget($website, $request);
        } else {
            $this->placedWidget = $widgetRepo->getPlacedWidget($widgetId);
        }

        if ($request->hasRequestValue("submit") && Validate::requestToken($request)) {
            // Use incoming data
            $widgetDefinition = $website->getWidgets()->getDefinition($this->placedWidget);
            $data = $widgetDefinition->parseData($website, $widgetId);
            $this->placedWidget->setData($data);

            if ($this->isValid($data)) {
                // Save widget
                $widgetRepo->savePlacedWidget($this->placedWidget);
                $this->addSaveMessage($this->placedWidget, $website->getText());
            }
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function addSaveMessage(PlacedWidget $placedWidget, Text $text) {
        $homeLink = Link::of($text->getUrlMain(), $text->t("main.home"));
        $documentLink = Link::of($text->getUrlPage("edit_document", $placedWidget->getDocumentId()), $text->t("widgets.view_in_document"));

        $message = "";
        if ($placedWidget->getId() === 0) {
            // New widget
            $message = $text->t("main.widget") . " " . $text->t("editor.is_created");
        } else {
            // Updating existing widget
            $message = $text->t("main.widget") . " " . $text->t("editor.is_edited");
        }

        $text->addMessage($message, $homeLink, $documentLink);
    }

    private function isValid(array $data) {
        if (isSet($data["valid"])) {
            return (boolean) $data["valid"];
        }
        return true;
    }

    public function getPageTitle(Text $text) {
        return $text->t("widgets.edit");
    }

    public function getTemplate(Text $text) {
        return new WidgetEditTemplate($text, $this->widgetRunner, $this->placedWidget, $this->requestToken);
    }

    public function getMinimumRank() {
        return Ranks::MODERATOR;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
