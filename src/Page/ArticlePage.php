<?php

namespace Rcms\Page;

use Rcms\Core\ArticleRepository;
use Rcms\Core\CommentRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\User;
use Rcms\Page\View\ArticleView;

class ArticlePage extends Page {

    /** @var Article The article object, or null if not found. */
    protected $article;
    /** @var Comment[] Array of comments for the article, or null if comments. */
    protected $comments;
    /** @var boolean True if edit and delete links must be displayed for the article. */
    protected $editLinks;
    /** @var User The user viewing the comments. */
    protected $currentUser;

    public function init(Request $request) {
        $articleId = $request->getParamInt(0);
        $oArticles = new ArticleRepository($request->getWebsite());
        $this->article = $oArticles->getArticleData($articleId);
        $this->editLinks = $request->getWebsite()->isLoggedInAsStaff();
        $this->currentUser = $request->getWebsite()->getAuth()->getCurrentUser();
        if ($this->article->showComments) {
            $oComments = new CommentRepository($request->getWebsite());
            $this->comments = $oComments->getCommentsArticle($this->article->id);
        } else {
            $this->comments = array();
        }
    }

    public function getPageTitle(Text $text) {
        return htmlSpecialChars($this->article->title);
    }

    public function getView(Text $text) {
        return new ArticleView($text, $this->article, $this->editLinks, $this->comments, $this->currentUser);
    }

}
