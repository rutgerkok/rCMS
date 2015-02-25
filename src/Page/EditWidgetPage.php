<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\View\WidgetEditView;

/**
 * Edits a single widget.
 */
class EditWidgetPage extends Page {

    /**
     * @var PlacedWidget The widget being edited.
     */
    private $placedWidget;

    /**
     * @var InstalledWidgets The widgets installed on the website.
     */
    private $installedWidgets;

    /**
     * @var RequestToken Token against CSRF attacks.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $widgetRepo = new WidgetRepository($website);
        $widgetId = $request->getParamInt(0);
        $this->placedWidget = $widgetRepo->getPlacedWidget($widgetId);
        $this->installedWidgets = $website->getWidgets();

        if ($request->hasRequestValue("submit") && Validate::requestToken($request)) {
            // Use incoming data
            $widgetDefinition = $this->installedWidgets->getDefinition($this->placedWidget);
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
        $documentLink = Link::of(
                        $text->getUrlPage("document", $placedWidget->getSidebarId()), $text->t("widgets.view_in_document"));

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

    public function getView(Text $text) {
        return new WidgetEditView($text, $this->installedWidgets, $this->placedWidget, $this->requestToken);
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
