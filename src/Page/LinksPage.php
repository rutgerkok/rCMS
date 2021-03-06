<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Link;
use Rcms\Core\LinkRepository;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Template\AllLinksEditTemplate;

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
        $linkRepo = new LinkRepository($website->getDatabase());
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
        return Ranks::ADMIN;
    }

    public function getTemplate(Text $text) {
        return new AllLinksEditTemplate($text, $this->requestToken, $this->allLinks, $this->allMenus);
    }

}
