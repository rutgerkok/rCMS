<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Exception\RedirectException;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\InstalledWidgets;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Page\View\WidgetDetailView;

/**
 * Page that moves a widget and then redirects to the document edit page.
 */
final class MoveWidgetPage extends Page {

    /**
     * @var InstalledWidgets Widgets installed on the website.
     */
    private $installedWidgets;

    /**
     * @var PlacedWidget The widget being moved.
     */
    private $placedWidget;

    /**
     * @var Link Link to move the widget.
     */
    private $moveLink;

    public function getPageTitle(Text $text) {
        return $text->t("widgets.moving_a_widget");
    }

    public function getView(Text $text) {
        return new WidgetDetailView($text, $this->installedWidgets, $this->placedWidget, $this->moveLink);
    }

    public function init(Website $website, Request $request) {
        $text = $website->getText();

        $widgetId = $request->getParamInt(0);
        $moveUp = $request->getRequestString("direction", "up") === "up";
        $widgetRepository = new WidgetRepository($website);

        $this->placedWidget = $widgetRepository->getPlacedWidget($widgetId);
        $this->installedWidgets = $website->getWidgets();

        if (Validate::requestToken($request)) {
            // move
            $this->moveWidget($widgetRepository, $moveUp);
            throw new RedirectException($text->getUrlPage("edit_document", $this->placedWidget->getDocumentId()));
        } else {
            $text->addError(Validate::getLastError($text));
            
            $linkText = $text->t("widgets.move_down");
            if ($moveUp) {
                $linkText = $text->t("widgets.move_up");
            }

            // Generate new request token, allowing user to perform action again
            $newRequestToken = RequestToken::generateNew();
            $this->moveLink = Link::of($text->getUrlPage("move_widget", $widgetId, array(
                                "direction" => $moveUp? "up" : "down",
                                RequestToken::FIELD_NAME => $newRequestToken->getTokenString()
                            )), $linkText);
            $newRequestToken->saveToSession();
        }
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }
    
    private function moveWidget(WidgetRepository $repo, $moveUp) {
        $delta = $moveUp? -1 : 1;
        $documentId = $this->placedWidget->getDocumentId();
        
        $allWidgetsInDocument = array_values($repo->getWidgetsInDocumentWithId($documentId));

        $iterationStartPos = $moveUp? 1 : 0;
        $iterationEndPos = $moveUp? count($allWidgetsInDocument) : count($allWidgetsInDocument) - 1;

        $madeChanges = false;
        
        for ($i = $iterationStartPos; $i < $iterationEndPos; $i++) {
            $placedWidget = $allWidgetsInDocument[$i];
            if ($this->placedWidget->getId() == $placedWidget->getId()) {
                // Swap
                // Moving up:   place widget above here, then place desired
                //              widget in the (now free) slot above
                // Moving down: place widget below here, then place desired
                //              widget in the (now free) slot below
                $allWidgetsInDocument[$i] = $allWidgetsInDocument[$i + $delta];
                $allWidgetsInDocument[$i + $delta] = $this->placedWidget;
                $madeChanges = true;
                break;
            }
        }

        if ($madeChanges) {
            for ($i = 0; $i < count($allWidgetsInDocument); $i++) {
                $placedWidget = $allWidgetsInDocument[$i];
                $placedWidget->setPriority(count($allWidgetsInDocument) - $i);
                $repo->savePlacedWidget($placedWidget);
            }
        }
    }

}