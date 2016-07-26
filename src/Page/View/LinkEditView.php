<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\LinkRepository;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * View to edit links.
 */
final class LinkEditView extends View {

    /**
     * @var Link The link that is being edited.
     */
    private $link;

    /**
     * @var RequestToken Token for validating request origin.
     */
    private $requestToken;
    
    public function __construct(Text $text, Link $link, RequestToken $requestToken) {
        parent::__construct($text);
        $this->link = $link;
        $this->requestToken = $requestToken;
    }
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $maxUrlLength = LinkRepository::MAX_URL_LENGTH;
        $maxTextLength = LinkRepository::MAX_LINK_TEXT_LENGTH;
        
        $stream->write(<<<HTML
            <p>
                {$text->t("main.fields_required")}
            </p>
            <form action="{$text->getUrlPage("edit_link", $this->link->getId())}" method="post">
                <p>
                    <label for="link_url">
                        {$text->t("links.url")}:<span class="required">*</span>
                    </label><br />
                    <input type="url" size="50" id="link_url" name="link_url"
                        maxlength="{$maxUrlLength}" value="{$text->e($this->link->getUrl())}" />
                </p>

                <p>
                    <label for="link_text">
                        {$text->t("links.text")}:<span class="required">*</span>
                    </label><br />
                    <input type="text" size="50" id="link_text" name="link_text"
                        maxlength="{$maxTextLength}" value="{$text->e($this->link->getText())}" />
                </p>

                <p>
                    <input type="submit" class="button primary_button"
                        name="submit" value="{$text->t("editor.save")}" />
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}"
                        value="{$text->e($this->requestToken->getTokenString())}" />
                    <a class="button" href="{$text->e($text->getUrlPage("edit_menu", $this->link->getMenuId()))}">
                        {$text->t("main.cancel")}
                    </a>
                </p>
            </form>
HTML
                );
    }
}
