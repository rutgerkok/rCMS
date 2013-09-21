<?php

// Protect against calling this script directly
if (!isset($this)) {
    die();
}

class ArticlePage extends Page {

    /** @var Article $article The article object, or null if not found */
    protected $article;

    public function init(Website $oWebsite) {
        $articleId = $oWebsite->getRequestInt("id");
        $oArticles = new Articles($oWebsite);
        $this->article = $oArticles->getArticleData($articleId);
    }

    public function getPageTitle(Website $oWebsite) {
        if ($this->article) {
            return htmlSpecialChars($this->article->title);
        } else {
            return $oWebsite->t("articles.view");
        }
    }

    public function getView(Website $oWebsite) {
        return new ArticleView($oWebsite, $this->article, new Comments($oWebsite));
    }

}
