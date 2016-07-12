<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Validate;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Page\View\LinkEditFooterView;
use Rcms\Page\View\MenuAddView;
use Rcms\Page\View\MenuEditView;

/**
 * Page for adding a new menu.
 */
class AddMenuPage extends Page {

    /**
     * @var RequestToken The CSRF token protecting the next request.
     */
    private $requestToken;

    /**
     * @var Menu|null The menu, when successfully created.
     */
    private $menu = null;

    /**
     * @var string The proposed name for the new menu.
     */
    private $menuName = "";

    public function init(Website $website, Request $request) {
        $this->requestToken = RequestToken::generateNew();
        $this->menuName = $request->getRequestString("menu_name", "");

        if (Validate::requestToken($request)) {
            $this->handleSubmitedForm($website, $request);
        }
        
        $this->requestToken->saveToSession();
    }
    
    private function handleSubmitedForm(Website $website, Request $request) {
        $text = $website->getText();

        if (Validate::stringLength($this->menuName, 1, MenuRepository::MAX_MENU_NAME_LENGTH)) {
            $menuRepo = new MenuRepository($website->getDatabase());
            $menuId = $menuRepo->addMenu($this->menuName);
            $this->menu = Menu::createMenu($menuId, $this->menuName);
            $text->addMessage($text->t("links.menu.created"));
        } else {
            $text->addError($text->t("links.menu.name") . ' '. Validate::getLastError($text));
        }
    }

    public function getPageTitle(Text $text) {
        if ($this->menu !== null) {
            return $this->menu->getName();
        }
        return $text->t("links.menu.add");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getViews(Text $text) {
        $views = [];
        if ($this->menu === null) {
            $views[] = new MenuAddView($text, $this->requestToken, $this->menuName);
        } else {
            $views[] = new MenuEditView($text, $this->menu, []);
        }
        $views[] = new LinkEditFooterView($text);
        return $views;
    }

}
