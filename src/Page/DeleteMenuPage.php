<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Link;
use Rcms\Core\LinkRepository;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\LinkEditFooterView;
use Rcms\Page\View\MenuDeleteView;

/**
 * Page for deleting a whole menu.
 */
final class DeleteMenuPage extends Page {

    /**
     * @var Menu The menu being deleted.
     */
    private $menu;

    /**
     * @var Menu[] All menus, indexed by menu id.
     */
    private $allMenus;

    /**
     * @var int Amount of links in the menu that is being deleted.
     */
    private $linkCount;

    /**
     * @var RequestToken The request token.
     */
    private $requestToken;

    /**
     * @var bool Set to true when menu is deleted successfully.
     */
    private $deleted = false;

    public function init(Website $website, Request $request) {
        // Retrieve menus
        $menuRepo = new MenuRepository($website->getDatabase());
        $this->allMenus = $menuRepo->getAllMenus();

        // Retrieve the menu to be deleted
        $menuId = $request->getParamInt(0, 0);
        if (!isSet($this->allMenus[$menuId])) {
            // Asking to delete non-existing menu
            throw new NotFoundException();
        }
        $this->menu = $this->allMenus[$menuId];

        // Retrieve links
        $linkRepo = new LinkRepository($website->getDatabase());
        $this->linkCount = $linkRepo->getLinkCountByMenu($this->menu->getId());

        $this->respondToRequest($linkRepo, $menuRepo, $website->getText(), $request);

        // Request token
        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("links.menu.delete");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getViews(Text $text) {
        if ($this->deleted) {
            return [new LinkEditFooterView($text)];
        }
        return [new MenuDeleteView($text, $this->menu, $this->linkCount, $this->allMenus, $this->requestToken),
            new LinkEditFooterView($text)];
    }

    private function respondToRequest(LinkRepository $linkRepo, MenuRepository $menuRepo, Text $text, Request $request) {
        if (!Validate::requestToken($request)) {
            return;
        }

        $moveLinksToMenuId = $request->getRequestInt("move_option", 0);
        if ($moveLinksToMenuId === 0) {
            $linkRepo->deleteLinksInMenu($this->menu);
        } else {
            $linkRepo->moveLinks($this->menu, $this->allMenus[$moveLinksToMenuId]);
        }
        $menuRepo->deleteMenu($this->menu->getId());
        $text->addMessage($text->t("links.menu") . " " . $text->t("editor.is_deleted"),
            Link::of($text->getUrlPage("links"), $text->t("links.overview")));
        $this->deleted = true;
    }
}
