<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\LinkRepository;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Page\View\LinkEditFooterView;
use Rcms\Page\View\MenuEditView;

/**
 * Description of EditMenuPage
 */
class EditMenuPage extends Page {

    /**
     *
     * @var Menu The menu being edited.
     */
    private $menu;
    private $links;

    public function init(Website $website, Request $request) {
        $menuId = $request->getParamInt(0, 0);
        $menuRepo = new MenuRepository($website->getDatabase());
        $linkRepo = new LinkRepository($website->getDatabase());

        $this->menu = $menuRepo->getMenu($menuId);
        $this->links = $linkRepo->getLinksByMenu($menuId);
    }

    public function getPageTitle(Text $text) {
        return $this->menu->getName();
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }
    
    public function getViews(Text $text) {
        return [
            new MenuEditView($text, $this->menu, $this->links),
            new LinkEditFooterView($text)
            ];
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
