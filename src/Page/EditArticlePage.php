<?php

namespace Rcms\Page;

use Rcms\Core\Article;
use Rcms\Core\ArticleEditor;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Exception\RedirectException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;

use Rcms\Page\View\ArticleEditView;
use Rcms\Page\View\Support\CKEditor;

class EditArticlePage extends Page {

    /** @var ArticleEditor $article_editor */
    protected $articleEditor;

    /** @var Category[] All categories on the site. */
    protected $allCategories;
    protected $message; // Message at the top of the page
    protected $token; // Token, always set

    public function init(Website $website, Request $request) {
        $text = $website->getText();
        $currentUser = $website->getAuth()->getCurrentUser();
        $articleId = $request->getParamInt(0);

        $articleRepository = new ArticleRepository($website);
        $article = $this->getArticle($articleRepository, $currentUser, $articleId);
        $articleEditor = new ArticleEditor($article);
        $this->articleEditor = $articleEditor;

        $categoryRepository = new CategoryRepository($website);
        $this->allCategories = $categoryRepository->getCategories();
        
        $this->richEditor = new CKEditor($website->getText(), $website->getConfig(), $website->getThemeManager());

        // Validate token, then save new one to session
        $validToken = Validate::requestToken($request);
        $this->token = RequestToken::generateNew();
        $this->token->saveToSession();

        // Now check input
        if (!$articleEditor->processInput($website->getText(), $request, $categoryRepository)) {
            return;
        }
        if ($request->hasRequestValue("submit") && $validToken) {
            // Try to save
            $article = $articleEditor->getArticle();
            if ($articleRepository->save($article)) {
                if ($articleId == 0) {
                    // New article created
                    $text->addMessage($text->t("main.article") . " " . $text->t("editor.is_created"));
                } else {
                    // Article updated
                    $text->addMessage($text->t("main.article") . " " . $text->t("editor.is_edited"));
                }
                $this->message.= ' <a class="arrow" href="' . $website->getUrlPage("article", $article->id) . '">';
                $this->message.= $website->t("articles.view") . "</a>";

                // Check for redirect
                if ($request->getRequestString("submit") == $website->t("editor.save_and_quit")) {
                    $urlRaw = htmlspecialchars_decode($website->getUrlPage("article", $article->id));
                    throw new RedirectException($urlRaw);
                }
            }
        }
    }

    /**
     * Gets the article with the given id. If the id is 0, a new article is
     * created.
     * @param ArticleRepository $repository Repository to fetch articles from.
     * @param User $currentUser Becomes the author if a new article is created.
     * @param int $id Id of the article. Use 0 to create a new article.
     * @return Article The article.
     * @throws NotFoundException If no article exists with the given id.
     */
    protected function getArticle(ArticleRepository $repository, User $currentUser, $id) {
        if ($id === 0) {
            $article = new Article();
            $article->setAuthor($currentUser);
            return $article;
        } else {
            return $repository->getArticleOrFail($id);
        }
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageTitle(Text $text) {
        // Empty, to allow a textbox on the top of the page
        return "";
    }

    public function getShortPageTitle(Text $text) {
        if ($this->articleEditor != null) {
            $articleTitle = $this->articleEditor->getArticle()->title;
            if (empty($articleTitle)) {
                // New article
                return $text->t("articles.create");
            }
        }
        return $text->t("articles.edit");
    }

    public function getView(Text $text) {
        return new ArticleEditView($text, $this->articleEditor->getArticle(),
                $this->token, $this->richEditor, $this->allCategories);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    

}
