<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Comments;
use Rcms\Core\Website;
use Rcms\Page\View\CommentsTreeView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Page with the latest comments on the site.
 */
class CommentsPage extends Page {

    /** @var Comment[] The latest comments on the site. */
    private $comments;

    public function init(Website $oWebsite) {
        $oComments = new Comments($oWebsite);
        $this->comments = $oComments->getCommentsLatest();
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("comments.comments");
    }

    public function getView(Website $oWebsite) {
        return new CommentsTreeView($oWebsite, $this->comments, true);
    }

}
