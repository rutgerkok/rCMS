<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Link;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\LinkEditFooterView;
use Rcms\Page\View\MenuRenameView;

class RenameMenuPage extends Page  {

    /**
     * @var Menu The menu being edited.
     */
    private $menu;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $menuId = $request->getParamInt(0, 0);
        $menuRepo = new MenuRepository($website->getDatabase());
        $this->menu = $menuRepo->getMenu($menuId);

        $this->menu->setName($request->getRequestString("menu_name", $this->menu->getName()));
        if (Validate::requestToken($request)) {
            $this->trySaveMenu($menuRepo, $website->getText());
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function trySaveMenu(MenuRepository $menuRepo, Text $text) {
        if (!Validate::stringLength($this->menu->getName(), 1, MenuRepository::NAME_MAX_LENGTH)) {
            $text->addError($text->t("links.menu") . " " . Validate::getLastError($text));
            return;
        }
        $menuRepo->saveMenu($this->menu);
        $text->addMessage($text->t("links.menu") . " " . $text->t("editor.is_changed"),
            Link::of($text->getUrlPage("edit_menu", $this->menu->getId()), $text->t("links.menu.go_back")));
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        return $text->t("links.menu.rename");
    }

    public function getViews(Text $text) {
        return [
            new MenuRenameView($text, $this->menu, $this->requestToken),
            new LinkEditFooterView($text)
        ];
    }
}
