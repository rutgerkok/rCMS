<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Config;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Link;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\MainMenuEditView;

/**
 * A page to set which menu is used as the main menu.
 */
class EditMainMenuPage extends Page {

    /**
     * @var Menu|null The menu used as the main menu, or null when categories are used.
     */
    private $menu;

    /**
     * @var Menu[] Array of all menus on the site, indexed by menu id.
     */
    private $menus;

    /**
     * @var RequestToken The token used for the next request.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $menuId = (int) $website->getConfig()->get(Config::OPTION_MAIN_MENU_ID);

        $menuRepo = new MenuRepository($website->getDatabase());
        $this->menus = $menuRepo->getAllMenus();
        $this->menu = isSet($this->menus[$menuId])? $this->menus[$menuId] : null;

        if (Validate::requestToken($request)) {
            $this->handleRequest($website, $request);
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }
    
    private function handleRequest(Website $website, Request $request) {
        $text = $website->getText();
        $menuId = $request->getRequestInt("main_menu_id", 0);
        if ($menuId === 0) {
            $this->menu = null;
            $website->getConfig()->set($website->getDatabase(), Config::OPTION_MAIN_MENU_ID, 0);
            $text->addMessage($text->t("links.main_menu.now_using_categories"),
                    Link::of($text->getUrlPage("rename_categories"), $text->t("categories.edit")),
                    Link::of($text->getUrlMain(), $text->t("main.home")));
        } else if (isSet($this->menus[$menuId])) {
            $this->menu = $this->menus[$menuId];
            $website->getConfig()->set($website->getDatabase(), Config::OPTION_MAIN_MENU_ID, $this->menu->getId());
            $text->addMessage($text->tReplaced("links.main_menu.now_using_this_menu", $this->menu->getName()),
                    Link::of($text->getUrlPage("edit_menu", $this->menu->getId()), $text->t("links.menu.edit")),
                    Link::of($text->getUrlMain(), $text->t("main.home")));
        } else {
            throw new NotFoundException();
        }
    }

    public function getPageTitle(Text $text) {
        return $text->t("links.main_menu.edit");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getView(Text $text) {
        return new MainMenuEditView($text, $this->requestToken, $this->menus, $this->menu);
    }

}
