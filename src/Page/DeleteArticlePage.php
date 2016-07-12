<?php

namespace Rcms\Page;

use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Validate;
use Rcms\Core\Website;

use Rcms\Page\View\ArticleDeleteView;

class DeleteArticlePage extends Page {

    /** @var View The view to be displayed on this page. */
    protected $view;

    /** @var Article The article to delete */
    protected $article;

    public function init(Website $website, Request $request) {
        $text = $website->getText();
        $articleId = $request->getParamInt(0);
        $showAdminPageLink = $website->isLoggedInAsStaff(true);

        $oArticles = new ArticleRepository($website);
        $article = $oArticles->getArticleOrFail($articleId);
        $this->article = $article;
        $formToken = RequestToken::generateNew();

        $action = $request->getRequestString("action");
        if ($action == "delete" && Validate::requestToken($request)) {
            // Bye bye article
            if ($oArticles->delete($article)) {
                $this->view = new ArticleDeleteView($text, $article, $formToken, $showAdminPageLink, ArticleDeleteView::STATE_DELETED);
            } else {
                $this->view = new ArticleDeleteView($text, $article, $formToken, $showAdminPageLink, ArticleDeleteView::STATE_ERROR);
            }
            return;
        } elseif ($action == "make_private" && Validate::requestToken($request)) {
            // Hide article for visitors
            $article->setHidden(true);
            if ($oArticles->saveArticle($article)) {
                $this->view = new ArticleDeleteView($text, $article, $formToken, $showAdminPageLink, ArticleDeleteView::STATE_HIDDEN);
            } else {
                $this->view = new ArticleDeleteView($text, $article, $formToken, $showAdminPageLink, ArticleDeleteView::STATE_ERROR);
            }
            return;
        } else {
            // Ask what to do
            $this->view = new ArticleDeleteView($text, $article, $formToken, $showAdminPageLink, ArticleDeleteView::STATE_CONFIRMATION);
        }

        $formToken->saveToSession();
    }

    public function getPageTitle(Text $text) {
        if ($this->article) {
            return $text->t("main.delete") . ' "' . $this->article->getTitle() . '"';
        } else {
            return $this->getShortPageTitle($text);
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("articles.delete");
    }

    public function getView(Text $text) {
        return $this->view;
    }

}
