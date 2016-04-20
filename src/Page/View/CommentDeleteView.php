<?php

namespace Rcms\Page\View;

use Rcms\Core\Comment;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Psr\Http\Message\StreamInterface;

/**
 * Description of CommentDeleteView
 */
class CommentDeleteView extends View {

    /**
     * @var CommentsTreeView View to display the comment that will be deleted.
     */
    private $commentsTreeView;
    
    /**
     * @var RequestToken The token required to delete the comment.
     */
    private $requestToken;
    
    /**
     * @var Comment The comment being deleted. 
     */
    private $comment;
    
    public function __construct(Text $text, RequestToken $token, Comment $comment) {
        parent::__construct($text);
        $this->requestToken = $token;
        $this->comment = $comment;
        $this->commentsTreeView = new CommentsTreeView($text, [$comment], true);
    }
    
    public function writeText(StreamInterface $stream) {
        $this->commentsTreeView->writeText($stream);

        $text = $this->text;
        $deleteUrlHtml = $text->e($text->getUrlPage("delete_comment", $this->comment->getId()));
        $tokenNameHtml = $text->e(RequestToken::FIELD_NAME);
        $tokenValueHtml = $text->e($this->requestToken->getTokenString());
        
        $stream->write(<<<HTML
             <form method="post" action="{$deleteUrlHtml}">
                <input type="hidden" name="{$tokenNameHtml}" value="{$tokenValueHtml}" />
                <input type="submit" name="confirm" value="{$text->t("editor.delete_permanently")}"
                    class="button dangerous_button" />
                <a class="button">{$text->t("main.cancel")}</a>
             </form>
HTML
        );
    }
}
