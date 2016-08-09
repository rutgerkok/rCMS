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
use Rcms\Page\View\EmptyView;
use Rcms\Page\View\WidgetDeleteView;

/**
 * A page for deleting a widget.
 */
final class DeleteWidgetPage extends Page {
    
    /**
     * @var InstalledWidgets The widgets on the website.
     */
    private $installedWidgets;
    
    /**
     * @var PlacedWidget The widget being deleted.
     */
    private $placedWidget;
    
    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;
    
    public function init(Website $website, Request $request) {
        $this->installedWidgets = $website->getWidgets();
        
        $widgetId = $request->getParamInt(0, 0);
        $widgetRepo = new WidgetRepository($website);
        $this->placedWidget = $widgetRepo->getPlacedWidget($widgetId);
        
        if (Validate::requestToken($request)) {
            $widgetRepo->deletePlacedWidget($this->placedWidget);
            $text = $website->getText();
            $text->addMessage($text->t("main.widget") . ' ' . $text->t("editor.is_deleted"),
                Link::of($text->getUrlPage("edit_document", $this->placedWidget->getDocumentId()), $text->t("main.ok")));
      
        } else {
            $this->requestToken = RequestToken::generateNew();
            $this->requestToken->saveToSession();
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        return $text->t("widgets.delete");
    }

    public function getView(Text $text) {
        if ($this->requestToken === null) {
            // No token, assume already deleted
            return new EmptyView($text);
        }
        return new WidgetDeleteView($text, $this->installedWidgets, $this->placedWidget, $this->requestToken);
    }


}
