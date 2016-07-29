<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\Menu;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Zend\Diactoros\Stream;

/**
 * View for deleting a menu. Provides UI for moving the links to another menu first.
 */
final class MenuDeleteView extends View {

    /**
     * @var Menu The menu that is being deleted.
     */
    private $menu;

    /**
     * @var int Amount of links in the menu that is being deleted.
     */
    private $linkCount;

    /**
     * @var Menu[] All menus on the website.
     */
    private $allMenus;

    /**
     * @var RequestToken Token for protecting the request.
     */
    private $requestToken;

    /**
     * Creates a view for deleting a menu.
     * @param Text $text The text object.
     * @param Menu $menu The menu being considered for deletion.
     * @param int $linkCount The amount of links in the menu.
     * @param Menu[] $otherMenus The other menus on the website.
     * @param RequestToken $requestToken Token for protecting the request.
     */
    public function __construct(Text $text, Menu $menu, $linkCount, array $otherMenus,
                                RequestToken $requestToken) {
        parent::__construct($text);

        $this->menu = $menu;
        $this->linkCount = (int) $linkCount;
        $this->allMenus = $otherMenus;
        $this->requestToken = $requestToken;
    }

    public function writeText(StreamInterface $stream) {
        if ($this->linkCount === 0) {
            // Empty mene
            $this->writeFormForEmptyMenu($stream);
        } else if (count($this->allMenus) > 1) {
            // Menu with links, but no place to move other links to
            $this->writeFormWithMoveOption($stream);
        } else {
            // Menu with links, links can optionally be moved
            $this->writeFormWithoutMoveOption($stream);
        }
    }

    private function getSubmitButton() {
        $text = $this->text;
        return <<<HTML
            <p>
                <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                <input type="submit" class="button dangerous_button" value="{$text->t("links.menu.delete")}" />
                <a class="button" href="{$text->e($text->getUrlPage("edit_menu", $this->menu->getId()))}">
                    {$text->t("main.cancel")}
                </a>
            </p>
HTML;
    }

    private function writeFormWithMoveOption(StreamInterface $stream) {
        $text = $this->text;

        $stream->write('<form method="post" action="' . $text->e($text->getUrlPage("delete_menu", $this->menu->getId())) . '">');
        if ($this->linkCount === 1) {
            $stream->write('<p>' . $text->tReplaced("links.menu.delete.link_question", $text->e($this->menu->getName())) . '</p>');
        } else {
            $stream->write('<p>' . $text->tReplaced("links.menu.delete.links_question", $text->e($this->menu->getName()), $this->linkCount) . '</p>');
        }

        $stream->write('<p>');
        $stream->write('<label for="move_option">' . $text->t("links.menu.delete.answer") . '</label> ');
        $stream->write('<select name="move_option" id="move_option">');
        $stream->write('<option value="0">' . $text->t("links.menu.delete.delete_all_links") . '</option>');
        foreach ($this->allMenus as $menu) {
            if ($menu->getId() === $this->menu->getId()) {
                // Moving links to current menu makes no sense
                continue;
            }
            $stream->write('<option value="' . $menu->getId() . '">');
            $stream->write($text->tReplaced("links.menu.delete.move_links_to", $text->e($menu->getName())));
            $stream->write('</option>');
        }
        $stream->write('</select>');
        $stream->write('</p>');

        $stream->write($this->getSubmitButton());
        $stream->write('</form>');
    }

    private function writeFormWithoutMoveOption(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <form method="post" action="{$text->e($text->getUrlPage("delete_menu", $this->menu->getId()))}">
                <p>
                    {$text->tReplaced("links.menu.delete.confirm", $text->e($this->menu->getName()), $this->linkCount)}
                </p>
                {$this->getSubmitButton()}
            </form>
HTML
        );
    }

    private function writeFormForEmptyMenu(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <form method="post" action="{$text->e($text->getUrlPage("delete_menu", $this->menu->getId()))}">
                <p>
                    {$text->tReplaced("links.menu.delete.confirm_empty", $text->e($this->menu->getName()))}
                </p>
                {$this->getSubmitButton()}
            </form>
HTML
        );
    }
}
