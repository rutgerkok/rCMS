<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Ranks;
use Rcms\Core\Comment;
use Rcms\Core\CommentRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Middleware\Responses;
use Rcms\Template\CommentEditTemplate;
use Rcms\Template\EmptyTemplate;

/**
 * Displays a form that edits a comment on a page.
 */
final class EditCommentPage extends Page {

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
        return $text->t("comments.edit");
    }

    public function getMinimumRank() {
        return Ranks::USER;
    }

    public function init(Website $website, Request $request) {
        $text = $website->getText();
        $this->requestToken = RequestToken::generateNew();

        $commentId = $request->getParamInt(0, 0);

        $user = $request->getCurrentUser();

        $repo = new CommentRepository($website->getDatabase());
        $this->comment = $repo->getCommentOrFail($commentId);

        if ($user->getId() !== $this->comment->getUserId() &&
                !$user->hasRank(Ranks::MODERATOR)) {
            // Can only edit own comment unless moderator
            throw new NotFoundException();
        }

        if ($request->hasRequestValue("submit") && Validate::requestToken($request)) {
            // Validate and save comment
            $this->updateCommentFromRequest($this->comment, $request);

            if ($repo->validateComment($this->comment, $text)) {
                $repo->saveComment($this->comment);
                $this->redirectLink = $this->comment->getUrl($text);
            }
        }

        $this->requestToken->saveToSession();
    }

    private function updateCommentFromRequest(Comment $comment, Request $request) {
        $comment->setBodyRaw($request->getRequestString("comment", ""));
        if ($comment->isByVisitor()) {
            $name = $request->getRequestString("name", "");
            $email = $request->getRequestString("email", "");
            $comment->setByVisitor($name, $email);
        }
    }

    public function getTemplate(Text $text) {
        if ($this->redirectLink !== null) {
            return new EmptyTemplate($text);
        }
        return new CommentEditTemplate($text, $this->comment, $this->requestToken);
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
