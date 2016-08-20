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
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\LinkAddTemplate;
use Rcms\Template\LinkEditFooterTemplate;
use Rcms\Template\LinkEditTemplate;
use Rcms\Template\MenuEditTemplate;
use Zend\Diactoros\Uri;

/**
 * Page for adding a link.
 */
final class AddLinkPage extends Page {

    /**
     * @var Menu The menu the link will be stored in.
     */
    private $menu;

    /**
     * @var string Name of the link, displayed in menus.
     */
    private $linkName;

    /**
     * @var string URL string of the link.
     */
    private $linkUrl;

    /**
     * @var RequestToken Token protecting the request.
     */
    private $requestToken;

    private $addedLink = false;

    public function init(Website $website, Request $request) {
        $menuId = $request->getParamInt(0, 0);
        $menuRepo = new MenuRepository($website->getDatabase());
        $this->menu = $menuRepo->getMenu($menuId);

        $this->linkName = $request->getRequestString("link_text", "");
        $this->linkUrl = $request->getRequestString("link_url", "");

        if (Validate::requestToken($request)) {
            $this->saveLink($website);
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("links.create");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getTemplates(Text $text) {
        if ($this->addedLink) {
            return [new LinkEditFooterTemplate($text)];
        }
        return [new LinkAddTemplate($text, $this->menu, $this->linkName, $this->linkUrl, $this->requestToken),
            new LinkEditFooterTemplate($text)];
    }

    private function saveLink(Website $website) {
        $text = $website->getText();

        $valid = true;
        if (!Validate::url($this->linkUrl)) {
            $text->addError($text->t("links.url") . " " . Validate::getLastError($text));
            $valid = false;
        }
        if (!Validate::stringLength($this->linkName, 1, LinkRepository::MAX_LINK_TEXT_LENGTH)) {
            $text->addError($text->t("links.text") . " " . Validate::getLastError($text));
            $valid = false;
        }

        if (!$valid) {
            return;
        }

        $link = Link::createSaveable(0, $this->menu->getId(), new Uri($this->linkUrl), $this->linkName);
        $linkRepo = new LinkRepository($website->getDatabase());
        $linkRepo->saveLink($link);

        $text->addMessage($text->t("main.link") . " " . $text->t("editor.is_created"),
                Link::of($text->getUrlPage("add_link", $this->menu->getId()), $text->t("links.create_another")));
        $this->addedLink = true;
    }
}
