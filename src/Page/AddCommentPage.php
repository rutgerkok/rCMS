<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Rcms\Core\Article;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Comment;
use Rcms\Core\CommentRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\Renderer\Responses;
use Rcms\Page\View\AddCommentView;
use Rcms\Page\View\EmptyView;

/**
 * Displays a form that adds a comment to a page.
 */
final class AddCommentPage extends Page {

    /**
     * @var Comment The comment being posted.
     */
    private $comment;
    /**
     * @var RequestToken Unique request token.
     */
    private $requestToken;
    /**
     * @var UriInterface|null Link to redirect to after posting a comment.
     */
    private $redirectLink = null;

    public function getPageTitle(Text $text) {
        return $text->t("comments.add");
    }

    public function init(Website $website, Request $request) {
        $text = $website->getText();
        $this->requestToken = RequestToken::generateNew();
        
        $articleId = $request->getParamInt(0, 0);
        $articleRepo = new ArticleRepository($website);
        $article = $articleRepo->getArticleOrFail($articleId);

        if (!$article->showComments) {
            $text->addError($text->t("comments.commenting_not_allowed_on_article"));
            return;
        }

        $user = $website->getAuth()->getCurrentUser();
        $this->comment = $this->fetchComment($request, $article, $user);
        
        if ($request->hasRequestValue("submit") && Validate::requestToken($request)) {
            // Validate and save comment
            $repo = new CommentRepository($website->getDatabase());
            if ($repo->validateComment($this->comment, $text)) {
                $repo->saveComment($this->comment);
                $this->redirectLink = $this->comment->getUrl($text);
            }
        }
        
        $this->requestToken->saveToSession();
    }

    private function fetchComment(Request $request, Article $article, User $user = null) {
        $commentText = $request->getRequestString("comment", "");
        if ($user !== null) {
            return Comment::createForUser($user, $article, $commentText);
        } else {
            $displayName = $request->getRequestString("name", "");
            $email = $request->getRequestString("email", "");
            return Comment::createForVisitor($displayName, $email, $article, $commentText);
        }
    }
    
    public function getView(Text $text) {
        if ($this->redirectLink !== null) {
            return new EmptyView($text);
        }
        return new AddCommentView($text, $this->comment, $this->requestToken);
    }
    
    public function modifyResponse(ResponseInterface $response) {
        if ($this->redirectLink !== null) {
            return Responses::withTemporaryRedirect($response, $this->redirectLink);
        }
        return $response;
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
