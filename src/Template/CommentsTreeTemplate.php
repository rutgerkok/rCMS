<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Authentication;
use Rcms\Core\Comment;
use Rcms\Core\Text;
use Rcms\Core\User;

/**
 * Renders a list of comments and all of its subcomments.
 */
class CommentsTreeTemplate extends Template {

    /** @var Comment[] The comment list */
    private $comments;
    private $viewedByStaff;
    private $viewedOutOfContext;
    private $viewerId;

    /**
     * Creates the commment renderer.
     * @param Text $text The website text object.
     * @param Comment[] $comments List of comments.
     * @param boolean $viewedOutOfContext Whether there should be a link to the article.
     * @param User|null $viewer The user viewing the comments, null if logged out.
     */
    public function __construct(Text $text, $comments, $viewedOutOfContext,
            User $viewer = null) {
        parent::__construct($text);
        $this->comments = $comments;
        $this->viewedByStaff = $viewer === null?  false : $viewer->hasRank(Authentication::RANK_MODERATOR);
        $this->viewedOutOfContext = $viewedOutOfContext;
        $this->viewerId = $viewer ? $viewer->getId() : 0;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write($this->getCommentTree($this->comments));
    }

    /**
     * Gets whether the given comment can be edited by the current viewer.
     * @param omment $comment The comment.
     * @return boolean True if it can be edited, false otherwise.
     */
    private function canEditComment(Comment $comment) {
        if ($this->viewedByStaff) {
            return true;
        }
        if ($this->viewerId > 0) {
            return $this->viewerId === $comment->getUserId();
        }
        return false;
    }

    protected function getSingleComment(Comment $comment) {
        $text = $this->text;
        $id = $comment->getId();
        $author = htmlSpecialChars($comment->getUserDisplayName());
        $postDate = "";
        if ($comment->getDateCreated() !== null) {
            $postDate = strFTime('%a %d %b %Y %X', $comment->getDateCreated()->getTimestamp());
        }
        $body = nl2br(htmlSpecialChars($comment->getBodyRaw()));
        $avatarUrl = User::getAvatarUrlFromEmail($comment->getUserEmail(), 40);

        // Add link and rank to author when linked to account
        if ($comment->getUserId() > 0) {
            $author = '<a href="' . $text->e($text->getUrlPage("account", $comment->getUserId())) . '">' . $author . '</a>';
        }

        // Edit and delete links
        $actionLinksHtml = $this->getActionLinks($comment);

        // Reply and context links
        if ($this->viewedOutOfContext) {
            $replyOrContextLink = <<<EOT
                <a class="arrow" href="{$text->e($comment->getUrl($text))}">
                    {$text->t("comments.view_context")}
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
                    <h3 class="comment_title">$author</h3>
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

    protected function getActionLinks(Comment $comment) {
        if (!$this->canEditComment($comment)) {
            return "";
        }
        $text = $this->text;
        $id = $comment->getId();
        $email = htmlSpecialChars($comment->getUserEmail());
        $returnValue = "";
        if ($email) {
            $returnValue.= '<a class="comment_email" href="mailto:' . $email . '">' . $email . '</a>';
        }
        $returnValue.= <<<EOT
            <a class="arrow" href="{$text->e($text->getUrlPage("edit_comment", $id))}">
                {$text->t("main.edit")}
            </a>
            <a class="arrow" href="{$text->e($text->getUrlPage("delete_comment", $id))}">
                {$text->t("main.delete")}
            </a>
EOT;
        return $returnValue;
    }

    /**
     * Recursive function to display a comment tree.
     * @param Comment[] The comments.
     * @return string The HTML.
     */
    protected function getCommentTree(array $comments) {
        $output = "";
        foreach ($comments as $comment) {
            // Display the comment
            $output.= $this->getSingleComment($comment);

            // Display the child comments, if any
            $childs = $comment->getChildComments();
            if (count($childs) > 0) {
                $output.= $this->getCommentTree($childs);
            }
        }

        return $output;
    }

}

