<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\ArticleDeleteView;

class DeleteArticlePage extends Page {

    /** @var View The view to be displayed on this page. */
    protected $view;

    /** @var Article The article to delete */
    protected $article;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $text = $oWebsite->getText();
        $articleId = $request->getParamInt(0);

        $oArticles = new Articles($oWebsite);
        $article = $oArticles->getArticleData($articleId);
        $this->article = $article;
        if (!$article) {
            // Article not found
            $oWebsite->addError($oWebsite->t("main.article") . " " . $oWebsite->t("errors.not_found"));
            $this->view = new EmptyView($text);
            return;
        }

        $action = $request->getRequestString("action");
        if ($action == "delete") {
            // Bye bye article
            if ($article->delete($oWebsite->getDatabase())) {
                $this->view = new ArticleDeleteView($text, $article, ArticleDeleteView::STATE_DELETED);
            } else {
                $this->view = new ArticleDeleteView($text, $article, ArticleDeleteView::STATE_ERROR);
            }
            return;
        } elseif ($action == "make_private") {
            // Hide article for visitors
            $article->hidden = true;
            if ($article->save($oWebsite->getDatabase())) {
                $this->view = new ArticleDeleteView($text, $article, ArticleDeleteView::STATE_HIDDEN);
            } else {
                $this->view = new ArticleDeleteView($text, $article, ArticleDeleteView::STATE_ERROR);
            }
            return;
        } else {
            // Ask what to do
            $this->view = new ArticleDeleteView($text, $article, ArticleDeleteView::STATE_CONFIRMATION);
        }
    }

    public function getPageTitle(Text $text) {
        if ($this->article) {
            return $text->t("main.delete") . ' "' . htmlSpecialChars($this->article->title) . '"';
        } else {
            return $this->getShortPageTitle($text);
        }
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("editor.article.delete");
    }

    public function getView(Text $text) {
        return $this->view;
    }

}
