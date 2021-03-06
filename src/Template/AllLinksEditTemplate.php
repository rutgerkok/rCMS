<?php

namespace Rcms\Template;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Link;
use Rcms\Core\Menu;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * A view of all links on the website.
 */
class AllLinksEditTemplate extends Template {

    /**
     * @var RequestToken Token for adding a menu.
     */
    private $requestToken;

    /**
     * @var Link[][] A map of menuId => Link[].
     */
    private $allLinks;

    /**
     * @var Menu[] A map of menuId => Menu.
     */
    private $allMenus;

    /**
     * Creates a new view to edit all links.
     * @param Text $text The text object.
     * @param Link[][] $allLinks A map of menuId => Link[].
     * @param Menu[] $allMenus A map of menuId => Menu
     */
    public function __construct(Text $text, RequestToken $requestToken,
            array $allLinks, array $allMenus) {
        parent::__construct($text);
        $this->requestToken = $requestToken;
        $this->allLinks = $allLinks;
        $this->allMenus = $allMenus;
    }

    public function writeText(StreamInterface $stream) {
        $this->writeMenus($stream);
        $this->writeNewMenuForm($stream);
        $this->writeFooter($stream);
    }

    private function writeMenus(StreamInterface $stream) {
        $text = $this->text;

        foreach ($this->allMenus as $menu) {
            $menuId = $menu->getId();
            $linksInMenu = isSet($this->allLinks[$menuId]) ? $this->allLinks[$menuId] : [];

            $menuEditTemplate = new MenuEditTemplate($text, $menu, $linksInMenu);

            $stream->write(<<<HTML
                <article>
                    <header>
                        <h3 class="notable">{$text->e($menu->getName())}</h3>
                    </header>
HTML
            );

            $menuEditTemplate->writeText($stream);
            $stream->write("</article>");
        }
        if (empty($this->allMenus)) {
            $stream->write("<p>{$text->t("links.menu.no_menus_created")}</p>");
        }
    }

    private function writeNewMenuForm(StreamInterface $stream) {
        $text = $this->text;
        $stream->write(<<<HTML
                <article>
                    <header>
                        <h3 class="notable">{$text->t("links.menu.add")}</h3>
                    </header>
HTML
        );

        $view = new MenuAddTemplate($this->text, $this->requestToken, "");
        $view->writeText($stream);

        $stream->write("</article>");
    }
    
    private function writeFooter(StreamInterface $stream) {
        $text = $this->text;
        
        $stream->write(<<<HTML
            <hr />
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("edit_main_menu"))}">
                    {$text->t("links.main_menu.edit")}
                </a>
                <br />
                <a class="arrow" href="{$text->e($text->getUrlPage("admin"))}">
                    {$text->t("main.admin")}
                </a>  
            </p>
HTML
        );
    }

}
