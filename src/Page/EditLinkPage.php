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
use Rcms\Page\View\LinkEditView;
use Rcms\Page\View\LinkEditFooterView;
use Zend\Diactoros\Uri;

/**
 * Description of EditLinkPage
 */
class EditLinkPage extends Page {

    /**
     * @var Link The link being edited.
     */
    private $link;

    /**
     * @var RequestToken The request token for the upcoming request.
     */
    private $requestToken;

    public function init(Website $website, Request $request) {
        $linkId = $request->getParamInt(0, 0);
        $linkRepo = new LinkRepository($website);
        $this->link = $linkRepo->getLink($linkId);

        if (Validate::requestToken($request)) {
            $this->handleRequest($website->getText(), $request, $linkRepo);
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function handleRequest(Text $text, Request $request,
            LinkRepository $linkRepo) {
        $valid = true;

        $linkText = $request->getRequestString("link_text", "");
        $this->link->setText($linkText);
        if (!Validate::nameOfLink($linkText)) {
            $text->addError($this->t("links.text") . " " . Validate::getLastError($text));
            $valid = false;
        }

        $url = $request->getRequestString("link_url", "");
        if (Validate::url($url)) {
            $this->link->setUrl(new Uri($url));
        } else {
            $text->addError($text->t("links.url") . " " . Validate::getLastError($text));
            $valid = false;
        }

        if ($valid) {
            $linkRepo->saveLink($this->link);
            $text->addMessage($text->t("main.link") . ' ' . $text->t("editor.is_edited"),
                    Link::of($text->getUrlPage("edit_menu", $this->link->getMenuId()), $text->t("links.menu.go_back")));
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("links.edit");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getViews(Text $text) {
        return [new LinkEditView($text, $this->link, $this->requestToken),
            new LinkEditFooterView($text)];
    }

}
