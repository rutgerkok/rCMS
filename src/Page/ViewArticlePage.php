<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;
use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\Renderer\Responses;

/**
 * This class exists to redirect any old view_article
 * urls to the newer article urls.
 */
class TemplateArticlePage extends Page {

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

    public function modifyResponse(ResponseInterface $response) {
        return Responses::withPermanentRedirect($response, $this->articleUrl);
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
