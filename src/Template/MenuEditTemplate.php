<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\Menu;
use Rcms\Core\Text;

/**
 * Template for the search screen of links.
 */
final class MenuEditTemplate extends Template {

    /** @var Menu The menu the links are in. */
    private $menu;

    /** @var Link[] Array of links. */
    private $links;

    /**
     * Constructs a new menu search view.
     * @param Text $text The website object.
     * @param Menu $menu The menu the links are in.
     * @param Link[] $links Array of links.
     */
    public function __construct(Text $text, Menu $menu, array $links) {
        parent::__construct($text);
        $this->menu = $menu;
        $this->links = $links;
    }

    public function writeText(StreamInterface $stream) {
        $stream->write('<section class="menu_editor">');
        
        if (empty($this->links)) {
            // Write error message
            $stream->write("<article><p>" . $this->text->t("links.menu.empty") . "</p></article>");
        } else {
            // Write link list
            $this->writeLinks($stream);
        }
        $this->writeManageLinks($stream);
        $stream->write("</section>");
    }

    private function writeLinks(StreamInterface $stream) {
        $text = $this->text;
        foreach ($this->links as $link) {
            $stream->write(<<<HTML
                <article>
                    <header>
                        <h3>
                            {$text->e($link->getText())}
                        </h3>
                    </header>
                    <p class="url_box">
                        <a href="{$text->e($link->getUrl())}">
                            {$text->e($link->getUrl())}
                        </a>
                    </p>
                    <footer>
                        <p>
                            <a class="arrow" href="{$text->e($text->getUrlPage("edit_link", $link->getId()))}">
                                {$text->t("main.edit")}
                            </a>
                            <a class="arrow" href="{$text->e($text->getUrlPage("delete_link", $link->getId()))}">
                                {$text->t("main.delete")}
                            </a>
                        </p>
                    </footer>
                </article>
HTML
            );
        }
    }

    private function writeManageLinks(StreamInterface $stream) {
        $text = $this->text;
        $menu = $this->menu;
        $stream->write(<<<HTML
                <article>
                    <p class="menu_actions">
                        <a class="arrow" href="{$text->e($text->getUrlPage("add_link", $menu->getId()))}">{$text->t("links.create")}</a>
                        <a class="arrow" href="{$text->e($text->getUrlPage("rename_menu", $menu->getId()))}">{$text->t("links.menu.rename")}</a>
                        <a class="arrow" href="{$text->e($text->getUrlPage("delete_menu", $menu->getId()))}">{$text->t("links.menu.delete")}</a>
                    </p>
                </article>
HTML
        );
    }

}
