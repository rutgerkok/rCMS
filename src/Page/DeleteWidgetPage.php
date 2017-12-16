<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Core\Widget\PlacedWidget;
use Rcms\Core\Widget\WidgetRepository;
use Rcms\Core\Widget\WidgetRunner;
use Rcms\Template\EmptyTemplate;
use Rcms\Template\WidgetDeleteTemplate;

/**
 * A page for deleting a widget.
 */
final class DeleteWidgetPage extends Page {
    
    /**
     * @var WidgetRunner The widgets on the website.
     */
    private $widgetRunner;
    
    /**
     * @var PlacedWidget The widget being deleted.
     */
    private $placedWidget;
    
    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;
    
    public function init(Website $website, Request $request) {
        $this->widgetRunner = new WidgetRunner($website, $request);
        
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
        return Ranks::ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        return $text->t("widgets.delete");
    }

    public function getTemplate(Text $text) {
        if ($this->requestToken === null) {
            // No token, assume already deleted
            return new EmptyTemplate($text);
        }
        return new WidgetDeleteTemplate($text, $this->widgetRunner, $this->placedWidget, $this->requestToken);
    }


}
