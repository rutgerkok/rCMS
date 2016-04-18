<?php

namespace Rcms\Page;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\Comment;
use Rcms\Core\CommentRepository;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\Renderer\Responses;
use Rcms\Page\View\EditCommentView;
use Rcms\Page\View\EmptyView;

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

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_USER;
    }

    public function init(Website $website, Request $request) {
        $text = $website->getText();
        $this->requestToken = RequestToken::generateNew();

        $commentId = $request->getParamInt(0, 0);

        $auth = $website->getAuth();
        $user = $auth->getCurrentUser();

        $repo = new CommentRepository($website);
        $this->comment = $repo->getComment($commentId);

        if ($user->getId() !== $this->comment->getUserId() &&
                !$auth->isHigherOrEqualRank($user->getRank(), Authentication::RANK_MODERATOR)) {
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

    public function getView(Text $text) {
        if ($this->redirectLink !== null) {
            return new EmptyView($text);
        }
        return new EditCommentView($text, $this->comment, $this->requestToken);
    }

    public function modifyResponse(ResponseInterface $response) {
        if ($this->redirectLink !== null) {
            return Responses::withTemporaryRedirect($response, $this->redirectLink);
        }
        return $response;
    }

}
