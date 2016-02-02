<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Zend\Diactoros\Response\RedirectResponse;

/**
 * This class exists to redirect any old view_article
 * urls to the newer article urls.
 */
class ViewArticlePage extends Page {

    /**
     * @var UriInterface The URL where the article is currently located.
     */
    private $articleUrl;

    public function init(Website $website, Request $request) {
        $id = $request->getParamInt(0, 0);
        $this->articleUrl = $website->getUrlPage("article", $id);
    }

    public function getPageTitle(Text $text) {
        return "";
    }

    public function getResponse(Website $website, Request $request) {
        return new RedirectResponse($this->articleUrl, 301);
    }

}
