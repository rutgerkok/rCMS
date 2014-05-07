<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Authentication;
use Rcms\Core\Website;
use Rcms\Page\View\ArticleDeleteView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class DeleteArticlePage extends Page {

    /** @var View The view to be displayed on this page. */
    protected $view;

    /** @var Article The article to delete */
    protected $article;

    public function init(Website $oWebsite) {
        $articleId = $oWebsite->getRequestInt("id");

        $oArticles = new Articles($oWebsite);
        $article = $oArticles->getArticleData($articleId);
        $this->article = $article;
        if (!$article) {
            // Article not found
            $oWebsite->addError($oWebsite->t("main.article") . " " . $oWebsite->t("errors.not_found"));
            $this->view = new EmptyView($oWebsite);
            return;
        }

        $action = $oWebsite->getRequestString("action");
        if ($action == "delete") {
            // Bye bye article
            if ($article->delete($oWebsite->getDatabase())) {
                $this->view = new ArticleDeleteView($oWebsite, $article, ArticleDeleteView::STATE_DELETED);
            } else {
                $this->view = new ArticleDeleteView($oWebsite, $article, ArticleDeleteView::STATE_ERROR);
            }
            return;
        } elseif ($action == "make_private") {
            // Hide article for visitors
            $article->hidden = true;
            if ($article->save($oWebsite->getDatabase())) {
                $this->view = new ArticleDeleteView($oWebsite, $article, ArticleDeleteView::STATE_HIDDEN);
            } else {
                $this->view = new ArticleDeleteView($oWebsite, $article, ArticleDeleteView::STATE_ERROR);
            }
            return;
        } else {
            // Ask what to do
            $this->view = new ArticleDeleteView($oWebsite, $article, ArticleDeleteView::STATE_CONFIRMATION);
        }
    }

    public function getPageTitle(Website $oWebsite) {
        if ($this->article) {
            return $oWebsite->t("main.delete") . ' "' . htmlSpecialChars($this->article->title) . '"';
        } else {
            return $this->getShortPageTitle($oWebsite);
        }
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("editor.article.delete");
    }

    public function getView(Website $oWebsite) {
        return $this->view;
    }

}
