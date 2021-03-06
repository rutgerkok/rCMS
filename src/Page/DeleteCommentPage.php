<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Comment;
use Rcms\Core\CommentRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Template\CommentDeleteTemplate;
use Rcms\Template\EmptyTemplate;

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
        return Ranks::USER;
    }

    public function init(Website $website, Request $request) {
        $commentId = $request->getParamInt(0, 0);

        $repo = new CommentRepository($website->getDatabase());
        $this->comment = $repo->getCommentOrFail($commentId);

        $user = $request->getCurrentUser();

        // Check if user is allowed to delete this comment
        if ($user->getId() !== $this->comment->getUserId() && !$user->hasRank(Ranks::MODERATOR)) {
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

    public function getTemplate(Text $text) {
        if ($this->requestToken == null) {
            return new EmptyTemplate($text);
        }
        return new CommentDeleteTemplate($text, $this->requestToken, $this->comment);
    }
    
    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
