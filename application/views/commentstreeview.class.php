<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Renders a list of comments and all of its subcomments.
 */
class CommentsTreeView extends View {

    /** @var Comment[] $comments The comment list */
    private $comments;
    private $viewedByStaff;
    private $viewedOutOfContext;
    private $viewerId;

    /**
     * Creates the commment renderer.
     * @param Website $oWebsite The website object.
     * @param Comment[] $comments List of comments.
     * @param boolean $viewedOutOfContext Whether there should be a link to the article.
     * @param User|null $viewer The user viewing the comments.
     */
    public function __construct(Website $oWebsite, $comments, $viewedOutOfContext) {
        parent::__construct($oWebsite);
        $viewer = $oWebsite->getAuth()->getCurrentUser();
        $this->comments = $comments;
        $this->viewedByStaff = $viewer ? $viewer->isStaff() : false;
        $this->viewedOutOfContext = $viewedOutOfContext;
        $this->viewerId = $viewer ? $viewer->getId() : 0;
    }

    public function getText() {
        return $this->getCommentTree($this->comments, $this->viewedByStaff, $this->viewedOutOfContext);
    }

    public static function getSingleComment(Website $oWebsite, Comment $comment, $editDeleteLinks, $viewedOutOfContext) {
        $id = $comment->getId();
        $author = htmlSpecialChars($comment->getUserDisplayName());
        $postDate = strFTime('%a %d %b %Y %X', $comment->getDateCreated());
        $body = nl2br(htmlSpecialChars($comment->getBodyRaw()));
        $avatarUrl = User::getAvatarUrlFromEmail($comment->getUserEmail(), 40);

        // Add link and rank to author when linked to account
        if ($comment->getUserId() > 0) {
            $author = '<a href="' . $oWebsite->getUrlPage("account", $comment->getUserId()) . '">' . $author . '</a>';
            $oAuth = $oWebsite->getAuth();
            $rank = $comment->getUserRank();
            if ($oAuth->isHigherOrEqualRank($rank, Authentication::$MODERATOR_RANK)) {
                $rankName = $oAuth->getRankName($rank);
                $author .= ' <span class="comment_author_rank">' . $rankName . '</span>';
            }
        }

        // Edit and delete links
        $actionLinksHtml = $editDeleteLinks ? self::getActionLinks($oWebsite, $comment) : "";

        // Reply and context links
        if ($viewedOutOfContext) {
            $replyOrContextLink = <<<EOT
                <a class="arrow" href="{$oWebsite->getUrlPage("article", $comment->getArticleId())}#comment_$id">
                    {$oWebsite->t("comments.view_context")}
                </a>
EOT;
        } else {
            // No child comments possible yet
            $replyOrContextLink = "";
        }

        $output = <<<COMMENT
            <article class="comment" id="comment_$id">
                <header>
                    <img src="$avatarUrl" alt="" />
                    <h3 class="comment_title">$author </h3>
                    <p class="comment_actions">
                        $actionLinksHtml
                    </p>
                    <p class="comment_date">$postDate</p>
                </header>
                <p class="comment_body">$body</p>
                <footer>
                    <p>$replyOrContextLink</p>
                </footer>
            </article>
COMMENT;
        return $output;
    }

    private static function getActionLinks(Website $oWebsite, Comment $comment) {
        $id = $comment->getId();
        $email = htmlSpecialChars($comment->getUserEmail());
        $returnValue = "";
        if ($email) {
            $returnValue.= '<a class="comment_email" href="mailto:' . $email . '">' . $email . '</a>';
        }
        $returnValue.= <<<EOT
            <a class="arrow" href="{$oWebsite->getUrlPage("edit_comment", $id)}">
                {$oWebsite->t("main.edit")}
            </a>
            <a class="arrow" href="{$oWebsite->getUrlPage("delete_comment", $id)}">
                {$oWebsite->t("main.delete")}
            </a>
EOT;
        return $returnValue;
    }

    /**
     * Recursive function to display a comment tree.
     * @return string The HTML.
     */
    protected function getCommentTree() {
        $output = "";
        foreach ($this->comments as $comment) {
            // Can user edit/delete
            $canEditDelete = false;
            if ($this->viewedByStaff || ($this->viewerId > 0 && $this->viewerId == $comment->getUserId())) {
                $canEditDelete = true;
            }

            // Display the comment
            $output.= self::getSingleComment($this->oWebsite, $comment, $canEditDelete, $this->viewedOutOfContext);

            // Display the child comments, if any
            $childs = $comment->getChildComments();
            if (count($childs) > 0) {
                $output.= $this->getCommentTree();
            }
        }

        return $output;
    }

}

?>
