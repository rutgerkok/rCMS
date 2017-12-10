<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

use Rcms\Core\Article;
use Rcms\Core\ArticleEditor;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\CategoryRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Link;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;

use Rcms\Middleware\Responses;
use Rcms\Template\ArticleEditTemplate;
use Rcms\Template\Support\CKEditor;

class EditArticlePage extends Page {

    /** @var ArticleEditor $article_editor */
    protected $articleEditor;

    /** @var Category[] All categories on the site. */
    protected $allCategories;
    protected $token; // Token, always set

    /**
     * @var UriInterface|null Page to redirect to. Usually there is no
     * redirect, so this value is null.
     */
    private $redirectUrl = null;

    public function init(Website $website, Request $request) {
        $text = $website->getText();
        $currentUser = $request->getCurrentUser($website);
        $articleId = $request->getParamInt(0);

        $articleRepository = new ArticleRepository($website->getDatabase(), true);
        $article = $this->getArticle($articleRepository, $currentUser, $articleId);
        $articleEditor = new ArticleEditor($article);
        $this->articleEditor = $articleEditor;

        $categoryRepository = new CategoryRepository($website->getDatabase());
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
            if ($articleRepository->saveArticle($article)) {
                $viewArticleLink = Link::of($website->getUrlPage("article", $article->getId()), $website->t("articles.view"));
                if ($articleId == 0) {
                    // New article created
                    $text->addMessage($text->t("main.article") . " " . $text->t("editor.is_created"), $viewArticleLink);
                } else {
                    // Article updated
                    $text->addMessage($text->t("main.article") . " " . $text->t("editor.is_edited"), $viewArticleLink);
                }

                // Check for redirect
                if ($request->getRequestString("submit") == $website->t("editor.save_and_quit")) {
                    $this->redirectUrl = $website->getUrlPage("article", $article->getId());
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
            $article = $repository->getArticleOrFail($id);
            if ($article->authorId === 0) {
                // There was a bug in previous versions of the CMS where the
                // author wasn't saved
                $article->setAuthor($currentUser);
            }
            return $article;
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageTitle(Text $text) {
        // Empty, to allow a textbox on the top of the page
        return "";
    }

    public function getShortPageTitle(Text $text) {
        if ($this->articleEditor != null) {
            $articleTitle = $this->articleEditor->getArticle()->getTitle();
            if (empty($articleTitle)) {
                // New article
                return $text->t("articles.create");
            }
        }
        return $text->t("articles.edit");
    }

    public function getTemplate(Text $text) {
        return new ArticleEditTemplate($text, $this->articleEditor->getArticle(),
                $this->token, $this->richEditor, $this->allCategories);
    }

    public function modifyResponse(ResponseInterface $response) {
        if ($this->redirectUrl != null) {
            $response = Responses::withTemporaryRedirect($response, $this->redirectUrl);
        }
        return $response;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }



}
