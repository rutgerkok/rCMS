<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\ArticleRepository;
use Rcms\Core\CommentRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\User;
use Rcms\Core\Website;

use Rcms\Template\ArticleTemplate;

class ArticlePage extends Page {

    /** @var Article The article object, or null if not found. */
    protected $article;
    /** @var Comment[] Array of comments for the article, or null if comments. */
    protected $comments;
    /** @var boolean True if edit and delete links must be displayed for the article. */
    protected $editLinks;
    /** @var User The user viewing the comments. */
    protected $currentUser;

    public function init(Website $website, Request $request) {
        $articleId = $request->getParamInt(0);
        $isModerator = $request->hasRank(Ranks::MODERATOR);
        $oArticles = new ArticleRepository($website->getDatabase(), $isModerator);
        $this->article = $oArticles->getArticleOrFail($articleId);
        $this->editLinks = $request->hasRank(Ranks::MODERATOR);
        $this->currentUser = $request->getCurrentUser();
        if ($this->article->showComments) {
            $oComments = new CommentRepository($website->getDatabase());
            $this->comments = $oComments->getCommentsArticle($this->article->getId());
        } else {
            $this->comments = [];
        }
    }

    public function getPageTitle(Text $text) {
        return $this->article->getTitle();
    }

    public function getTemplate(Text $text) {
        return new ArticleTemplate($text, $this->article, $this->editLinks, $this->comments, $this->currentUser);
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }

}
