<?php

namespace Rcms\Page\View;


use Psr\Http\Message\StreamInterface;
use Rcms\Core\Menu;
use Rcms\Core\MenuRepository;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

class MenuRenameView extends View {

    /**
     * @var Menu The menu being renamed.
     */
    private $menu;

    /**
     * @var RequestToken Token for preventing unwanted requests.
     */
    private $requestToken;

    public function __construct(Text $text, Menu $menu, RequestToken $requestToken) {
        parent::__construct($text);
        $this->menu = $menu;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $menu = $this->menu;
        $token = $this->requestToken;

        $stream->write(<<<HTML
            <form method="post" action="{$text->e($text->getUrlPage("rename_menu", $menu->getId()))}">
                <p>
                    {$text->t("main.fields_required")}
                </p>
                <p>
                    <label for="menu_name">
                        {$text->t("links.menu.name")}: <span class="required">*</span>
                    </label> <br />
                    <input type="text" name="menu_name" id="menu_name" value="{$text->e($menu->getName())}" size="20"
                            maxlength="{$text->e(MenuRepository::NAME_MAX_LENGTH)}" />
                </p>
                <p>
                    <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($token->getTokenString())}" />
                    <input class="button primary_button" type="submit" value="{$text->t("editor.save")}" />
                </p>
            </form>
HTML
        );

    }
}
