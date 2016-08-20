<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

class LinkDeleteTemplate extends Template {

    /**
     * @var Link The link being deleted.
     */
    private $link;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    public function __construct(Text $text, Link $link, RequestToken $requestToken) {
        parent::__construct($text);
        $this->link = $link;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $link = $this->link;
        $token = $this->requestToken;

        $stream->write(<<<HTML
            <p>{$text->tReplaced("links.delete.confirm", $text->e($link->getText()))}</p>
            <form method="post" action="{$text->e($text->getUrlPage("delete_link", $link->getId()))}">
                <p>
                    <input type="submit" class="button dangerous_button" value="{$text->t("main.delete")}" />
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($token->getTokenString())}" />
                    <a class="button" href="{$text->e($text->getUrlPage("edit_menu", $link->getMenuId()))}">
                        {$text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML
        );
    }
}
