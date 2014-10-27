<?php

namespace Rcms\Page;

use Rcms\Core\Exception\RedirectException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Rcms\Page\View\EmptyView;

/**
 * This class exists to redirect any old view_article
 * urls to the newer article urls.
 */
class ViewArticlePage extends Page {

    public function init(Website $website, Request $request) {
        $id = $request->getParamInt(0, 0);
        $rawUrl = urldecode($website->getUrlPage("article", $id));

        throw new RedirectException($rawUrl, RedirectException::TYPE_ALWAYS);
    }

    public function getPageTitle(Text $text) {
        return "";
    }

    public function getView(Text $text) {
        return new EmptyView($text);
    }

}
