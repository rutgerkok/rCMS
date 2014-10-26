<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\CommentRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\CommentsTreeView;

/**
 * Page with the latest comments on the site.
 */
class CommentsPage extends Page {

    /** @var Comment[] The latest comments on the site. */
    private $comments;
    /** @var User|null The user viewing the comments, null if logged out. */
    private $viewingUser;

    public function init(Request $request) {
        $oComments = new CommentRepository($request->getWebsite());
        $this->comments = $oComments->getCommentsLatest();
        $this->viewingUser = $request->getWebsite()->getAuth()->getCurrentUser();
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageTitle(Text $text) {
        return $text->t("comments.comments");
    }

    public function getView(Text $text) {
        return new CommentsTreeView($text, $this->comments, true, $this->viewingUser);
    }

}