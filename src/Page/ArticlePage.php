<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Comments;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\ArticleView;

class ArticlePage extends Page {

    /** @var Article The article object, or null if not found */
    protected $article;
    /** 
     * @var Comment[] Array of comments for the article, or null if comments
     */
    protected $comments;

    public function init(Request $request) {
        $articleId = $request->getParamInt(0);
        $oArticles = new Articles($request->getWebsite());
        $this->article = $oArticles->getArticleData($articleId);
        if ($this->article->showComments) {
            $oComments = new Comments($request->getWebsite());
            $this->comments = $oComments->getCommentsArticle($this->article->id);
        } else {
            $this->comments = array();
        }
    }

    public function getPageTitle(Text $text) {
        if ($this->article) {
            return htmlSpecialChars($this->article->title);
        } else {
            return $text->t("articles.view");
        }
    }

    public function getView(Text $text) {
        return new ArticleView($text, $this->article, $this->comments);
    }

}
