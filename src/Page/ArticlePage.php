<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Comments;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\ArticleView;

class ArticlePage extends Page {

    /** @var Article $article The article object, or null if not found */
    protected $article;

    public function init(Request $request) {
        $articleId = $request->getParamInt(0);
        $oArticles = new Articles($request->getWebsite());
        $this->article = $oArticles->getArticleData($articleId);
    }

    public function getPageTitle(Request $request) {
        if ($this->article) {
            return htmlSpecialChars($this->article->title);
        } else {
            return $request->getWebsite()->t("articles.view");
        }
    }

    public function getView(Website $website) {
        return new ArticleView($website, $this->article, new Comments($website));
    }

}
