<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\LinkRepository;
use Rcms\Core\Menu;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

final class LinkAddTemplate extends Template {

    /**
     * @var Menu The menu that the link will be added to.
     */
    private $menu;

    /**
     * @var string Text of the link.
     */
    private $linkText;

    /**
     * @var string URL text of the link.
     */
    private $linkUrl;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    public function __construct(Text $text, Menu $menu, $linkText, $linkUrl, RequestToken $requestToken) {
        parent::__construct($text);

        $this->menu = $menu;
        $this->linkText = (string) $linkText;
        $this->linkUrl = (string) $linkUrl;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <p>
                {$text->tReplaced("links.adding_link_to_menu", $text->e($this->menu->getName()))}
                {$text->t("main.fields_required")}
            </p>
            <form action="{$text->e($text->getUrlPage("add_link", $this->menu->getId()))}" method="post">
                <p>
                    <label for="link_url">
                        {$text->t("links.url")}: <span class="required">*</span>
                    </label><br />
                    <input type="url" size="50" id="link_url" name="link_url"
                            maxlength="{$text->e(LinkRepository::MAX_URL_LENGTH)}" value="{$text->e($this->linkUrl)}" />
                </p>

                <p>
                    <label for="link_text">
                        {$text->t("links.text")}: <span class="required">*</span>
                    </label><br />
                    <input type="text" size="50" id="link_text" name="link_text"
                            maxlength="{$text->e(LinkRepository::MAX_LINK_TEXT_LENGTH)}" value="{$text->e($this->linkText)}" />
                </p>

                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" class="button primary_button" name="submit" value="{$text->t("editor.save")}" />
                    <a class="button" href="{$text->e($text->getUrlPage("edit_menu", $this->menu->getId()))}">
                        {$text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML
        );
    }

}
