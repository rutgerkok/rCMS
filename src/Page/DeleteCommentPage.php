<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Comment;
use Rcms\Core\CommentRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\CommentDeleteView;
use Rcms\Page\View\EmptyView;

/**
 * A page used to delete comments.
 */
final class DeleteCommentPage extends Page {

    /**
     * @var Comment The comment being deleted.
     */
    private $comment;

    /**
     * @var RequestToken|null The request token, or null no form should be displayed.
     */
    private $requestToken = null;

    public function getPageTitle(Text $text) {
        return $text->t("comments.delete");
    }

    public function getMinimumRank() {
        return Authentication::RANK_USER;
    }

    public function init(Website $website, Request $request) {
        $commentId = $request->getParamInt(0, 0);

        $repo = new CommentRepository($website->getDatabase());
        $this->comment = $repo->getCommentOrFail($commentId);

        $user = $website->getAuth()->getCurrentUser();

        // Check if user is allowed to delete this comment
        if ($user->getId() !== $this->comment->getUserId() && !$user->hasRank(Authentication::RANK_MODERATOR)) {
            throw new NotFoundException();
        }
        
        // Check if form was submitted
        if (Validate::requestToken($request)) {
            $repo->deleteComment($commentId);
            $text = $website->getText();
            $articleLink = $text->getUrlPage("article", $this->comment->getArticleId());
            $text->addMessage($text->t("comments.comment") . ' ' . $text->t("editor.is_deleted"),
                    Link::of($articleLink, $text->t("main.ok")));
        } else {
            $this->requestToken = RequestToken::generateNew();
            $this->requestToken->saveToSession();
        }
    }

    public function getView(Text $text) {
        if ($this->requestToken == null) {
            return new EmptyView($text);
        }
        return new CommentDeleteView($text, $this->requestToken, $this->comment);
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
