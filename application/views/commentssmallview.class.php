<?php

/**
 * Description of commentsmallview
 */
class CommentsSmallView extends View {

    const MAX_TEXT_LENGTH = 150;

    /** @var Comment[] Comments to display. */
    protected $comments;

    public function __construct(Website $oWebsite, $comments) {
        parent::__construct($oWebsite);
        $this->comments = $comments;
    }

    public function getText() {
        $returnValue = "";

        foreach ($this->comments as $comment) {
            $returnValue.= $this->getSingleComment($comment);
        }
        
        if (count($this->comments) == 0) {
            $returnValue.= "<p><em>" . $this->oWebsite->t("errors.nothing_found") . "</em></p>\n";
        }

        return $returnValue;
    }

    protected function getSingleComment(Comment $comment) {
        $oWebsite = $this->oWebsite;
        $id = $comment->getId();
        $contextUrl = $oWebsite->getUrlPage("article", $comment->getArticleId()) . '#comment_' . $id;
        $returnValue = '<article class="comment_preview">';

        // Get author name (and link) and use it as the title
        $authorName = htmlSpecialChars($comment->getUserDisplayName());
        $authorId = $comment->getUserId();
        if ($authorId > 0) {
            // Add link to author profile
            $authorName = '<a href="' . $oWebsite->getUrlPage("account", $authorId) . '">' . $authorName . "</a>";
        }
        $returnValue.= '<header><h3 class="comment_title">' . $authorName . "</h3></header>\n";

        // Get body text and limit its length
        // (Whole body links to context of comment)
        $bodyRaw = $comment->getBodyRaw();
        if (strLen($bodyRaw) > self::MAX_TEXT_LENGTH) {
            $bodyRaw = subStr($bodyRaw, 0, self::MAX_TEXT_LENGTH - 3) . '...';
        }
        $body = htmlSpecialChars($bodyRaw);
        $returnValue.= <<<EOT
            <p>
                <a class="disguised_link" href="$contextUrl">
                    $body
                </a>
            </p>
EOT;
        
        // Add a link for some context
        $returnValue.= <<<EOT
            <footer>
                <p>
                    <a class="arrow" href="$contextUrl">
                        {$oWebsite->t("comments.view_context")}
                    </a>
                </p>
            </footer>
EOT;

        $returnValue.= "</article>";
        return $returnValue;
    }

}
