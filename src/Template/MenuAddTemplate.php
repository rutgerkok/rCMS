<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * A form for adding a menu.
 */
class MenuAddTemplate extends Template {
    
    /**
     * @var RequestToken Token required to submit the request.
     */
    private $requestToken;
    
    /**
     * @var string Proposed name for the menu.
     */
    private $name;
    
    /**
     * Creates a new view with a form for adding a menu.
     * @param Text $text The text object.
     * @param RequestToken $requestToken Token required to submit the request.
     * @param string $name Proposed name for the menu.
     */
    public function __construct(Text $text, RequestToken $requestToken, $name) {
        parent::__construct($text);
        $this->requestToken = $requestToken;
        $this->name = (string) $name;
    }
    
    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <p>
                {$text->t("main.fields_required")}
            </p>
            <form action="{$text->e($text->getUrlPage("add_menu"))}" method="post">
                <p>
                    <label for="menu_name">
                        {$text->t("links.menu.name")}: <span class="required">*</span>
                    </label> <br />
                    <input type="text" name="menu_name" value="{$text->e($this->name)}" />
                </p>
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                    <input type="submit" class="button primary_button" value="{$text->t("editor.save")}" />
                </p> 
            </form>
HTML
        );
    }
}
