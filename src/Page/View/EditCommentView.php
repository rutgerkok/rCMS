<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Comment;
use Rcms\Core\Text;
use Rcms\Core\RequestToken;

/**
 * A form for editing comments.
 */
class EditCommentView extends View {

    private $comment;

    /**
     * @var RequestToken Request token used in the form.
     */
    private $requestToken;

    public function __construct(Text $text, Comment $comment,
            RequestToken $requestToken) {
        parent::__construct($text);
        $this->comment = $comment;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $submitUrlHtml = $text->e($text->getUrlPage("edit_comment", $this->comment->getId()));

        $stream->write(<<<HTML
            <p>
                <em>{$text->t("main.fields_required")}</em>
            </p>
            <form action="{$submitUrlHtml}" method="post">
HTML
        );

        if ($this->comment->getUserId() === 0) {
            // Visitor, allow to edit name and e-mail
            $this->writeNameAndEmailForm($stream);
        }

        // Write comment form
        $commentHtml = $text->e($this->comment->getBodyRaw());
        $tokenNameHtml = RequestToken::FIELD_NAME;
        $tokenValueHtml = $text->e($this->requestToken->getTokenString());
        $commentUrlHtml = $text->e($this->comment->getUrl($text));
        $stream->write(<<<HTML
            <p>	
                {$text->t("comments.comment")}<span class="required">*</span>:<br />
                <textarea name="comment" id="comment" rows="10" cols="60" style="width:98%">$commentHtml</textarea>
            </p>
            <p>
                <input type="hidden" name="{$tokenNameHtml}" value="{$tokenValueHtml}" />
                <input type="submit" name="submit" value="{$text->t('editor.save')}" class="button primary_button" />
                <a href="{$commentUrlHtml}" class="button">{$text->t("editor.quit")}</a>
            </p>
            </form>
HTML
        );
    }

    private function writeNameAndEmailForm(StreamInterface $stream) {
        $text = $this->text;
        $nameHtml = $text->e($this->comment->getUserDisplayName());
        $emailHtml = $text->e($this->comment->getUserEmail());
        $stream->write(<<<HTML
            <p>
                {$text->t("users.name")}<span class="required">*</span>:<br />
                <input type="text" name="name" id="name" maxlength="20" style="width:98%" value="{$nameHtml}" />
            </p>
            <p>
                {$text->t("users.email")}:<br />
                <input type="email" name="email" id="email" style="width:98%" value="{$emailHtml}" />
            </p>
HTML
        );
    }

}
