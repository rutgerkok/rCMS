<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Comments;
use Rcms\Core\Request;
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

    public function init(Request $request) {
        $oComments = new Comments($request->getWebsite());
        $this->comments = $oComments->getCommentsLatest();
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$MODERATOR_RANK;
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("comments.comments");
    }

    public function getView(Website $website) {
        return new CommentsTreeView($website, $this->comments, true);
    }

}
