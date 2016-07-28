<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Link;
use Rcms\Core\LinkRepository;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\EmptyView;
use Rcms\Page\View\LinkDeleteView;
use Rcms\Page\View\LinkEditFooterView;

class DeleteLinkPage extends Page {

    /**
     * @var Link|null The link being deleted, or null if already deleted.
     */
    private $link;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $linkId = $request->getParamInt(0, 0);
        $linkRepo = new LinkRepository($website);
        $this->link = $linkRepo->getLink($linkId);

        if (Validate::requestToken($request)) {
            $this->deleteLink($linkRepo, $website->getText());
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function deleteLink(LinkRepository $linkRepo, Text $text) {
        $linkRepo->deleteLink($this->link);

        $text->addMessage($text->t("main.link") . " " . $text->t("editor.is_deleted"),
            Link::of($text->getUrlPage("edit_menu", $this->link->getMenuId()), $text->t("links.menu.go_back")));

        $this->link = null; // mark as deleted
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("links.delete");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getViews(Text $text) {
        if ($this->link === null) {
            return [new LinkEditFooterView($text)];
        }
        return [new LinkDeleteView($text, $this->link, $this->requestToken), new LinkEditFooterView($text)];
    }
}
