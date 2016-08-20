<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Rcms\Core\Comment;

/**
 * Description of commentsmallview
 */
class CommentsSmallTemplate extends Template {

    const MAX_TEXT_LENGTH = 150;

    /** @var Comment[] Comments to display. */
    protected $comments;

    public function __construct(Text $text, $comments) {
        parent::__construct($text);
        $this->comments = $comments;
    }

    public function writeText(StreamInterface $stream) {
        foreach ($this->comments as $comment) {
            $stream->write($this->getSingleComment($comment));
        }

        if (count($this->comments) == 0) {
            $stream->write("<p><em>" . $this->text->t("errors.nothing_found") . "</em></p>\n");
        }
    }

    protected function getSingleComment(Comment $comment) {
        $text = $this->text;
        $id = $comment->getId();
        $contextUrl = $text->e($text->getUrlPage("article", $comment->getArticleId())->withFragment('comment_' . $id));
        $returnValue = '<article class="comment_preview">';

        // Get author name (and link) and use it as the title
        $authorName = htmlSpecialChars($comment->getUserDisplayName());
        $authorId = $comment->getUserId();
        if ($authorId > 0) {
            // Add link to author profile
            $authorName = '<a href="' . $text->e($text->getUrlPage("account", $authorId)) . '">' . $authorName . "</a>";
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
                        {$text->t("comments.view_context")}
                    </a>
                </p>
            </footer>
EOT;

        $returnValue.= "</article>";
        return $returnValue;
    }

}
