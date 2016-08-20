<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\CommentRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Rcms\Template\CommentsTreeTemplate;

/**
 * Page with the latest comments on the site.
 */
class CommentsPage extends Page {

    /** @var Comment[] The latest comments on the site. */
    private $comments;
    /** @var User|null The user viewing the comments, null if logged out. */
    private $viewingUser;

    public function init(Website $website, Request $request) {
        $oComments = new CommentRepository($website->getDatabase());
        $this->comments = $oComments->getCommentsLatest();
        $this->viewingUser = $website->getAuth()->getCurrentUser();
    }

    public function getMinimumRank() {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageTitle(Text $text) {
        return $text->t("comments.comments");
    }

    public function getTemplate(Text $text) {
        return new CommentsTreeTemplate($text, $this->comments, true, $this->viewingUser);
    }

}
