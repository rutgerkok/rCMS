<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Link;
use Rcms\Core\LinkRepository;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Page\View\AllLinksEditView;

/**
 * Shows the menu editor of all menus on one page.
 */
class LinksPage extends Page {

    /**
     * @var Link[][] A map of menuId => Link[].
     */
    private $allLinks;

    /**
     * @var Menu[] A map of menuId => Menu.
     */
    private $allMenus;

    /**
     * @var RequestToken Token for adding menus.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $linkRepo = new LinkRepository($website);
        $menuRepo = new MenuRepository($website->getDatabase());

        $this->allLinks = $linkRepo->getAllLinksByMenu();
        $this->allMenus = $menuRepo->getAllMenus();

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    public function getPageTitle(Text $text) {
        return $text->t("main.links");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getView(Text $text) {
        return new AllLinksEditView($text, $this->requestToken, $this->allLinks, $this->allMenus);
    }

}
