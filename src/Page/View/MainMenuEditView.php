<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Menu;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;

/**
 * View for the search screen of links.
 */
final class MainMenuEditView extends View {

    /**
     * @var RequestToken Token for use in the form.
     */
    private $requestToken;

    /**
     * @var Menu[] All menus on the website.
     */
    private $menus;

    /**
     * @var Menu|null The menu the links are in, or null when the categories are used instead.
     */
    private $menu;

    /**
     * Constructs a new menu search view.
     * @param Text $text The website object.
     * @param RequestToken $requestToken Token for in the form.
     * @param Menu[] $menus All menus on the website.
     * @param Menu|null $menu The menu the links are in.
     */
    public function __construct(Text $text, RequestToken $requestToken, array $menus, Menu $menu = null) {
        parent::__construct($text);
        $this->requestToken = $requestToken;
        $this->menus = $menus;
        $this->menu = $menu;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
    
        if ($this->usesCategories()) {
            $stream->write(<<<HTML
                <p>
                    {$text->t("links.main_menu.explained")}
                    {$text->t("links.main_menu.uses_categories")}
                </p>
                <p>
                    <a class="arrow" href="{$text->e($text->getUrlPage("rename_category"))}">
                        {$text->t("categories.edit")}
                    </a>
                </p>
HTML
            );
        } else {
            $stream->write(<<<HTML
                <p>
                    {$text->t("links.main_menu.explained")}
                    {$text->tReplaced("links.main_menu.uses_menu", $text->e($this->menu->getName()))}
                </p>
                <p>
                    <a class="arrow" href="{$text->e($text->getUrlPage("edit_menu", $this->menu->getId()))}">
                        {$text->tReplaced("links.menu.edit_this", $text->e($this->menu->getName()))}
                    </a>
                </p>
HTML
            );
            $this->writeCategoriesForm($stream);
        }
        $this->writeMenuForm($stream);
        $this->writeFooter($stream);
    }
    
    private function writeCategoriesForm(StreamInterface $stream) {
        $text = $this->text;
        
        $stream->write(<<<HTML
            <fieldset>
                <legend>{$text->t("links.main_menu.use_categories")}</legend>
                <form method="POST" action="{$text->e($text->getUrlPage("edit_main_menu"))}">
                    <p>
                        <input type="hidden" name="main_menu_id" value="0" />
                        <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                        <input type="submit" class="button primary_button" value="{$text->t("links.main_menu.switch_to_categories")}" />
                    </p>
                </form>
             </fieldset>
HTML
        );
    }
    
    private function writeMenuForm(StreamInterface $stream) {
        $text = $this->text;
        
        $stream->write(<<<HTML
             <fieldset>
                <form method="POST" action="{$text->e($text->getUrlPage("edit_main_menu"))}">
                    <legend>{$text->t("links.main_menu.use_menu")}</legend>
                    <p>
                        <label for="main_menu_id">{$text->t("links.menu.for_main_menu")}</label>:
                        {$this->getMenuList()}
                        <input type="hidden" name="{$text->e(RequestToken::FIELD_NAME)}" value="{$text->e($this->requestToken->getTokenString())}" />
                        <input type="submit" class="button primary_button" value="{$text->t("editor.save")}" />
                    </p>
                    <p>
                        <a class="arrow" href="{$text->e($text->getUrlPage("add_menu"))}">
                            {$text->t("links.menu.add")}
                        </a>
                    </p>
                </form>
             </fieldset>
HTML
        );
    }
    
    private function getMenuList() {
        $text = $this->text;
        $currentMenuId = $this->usesCategories()? 0 : $this->menu->getId();
        
        $returnValue = '<select class="button" name="main_menu_id" id="main_menu_id">';
        foreach ($this->menus as $menu) {
            $returnValue.= '<option value="' . $menu->getId() . '" ';
            if ($menu->getId() == $currentMenuId) {
                $returnValue.= 'selected="selected"';
            }
            $returnValue.= '>';
            $returnValue.= $text->e($menu->getName());
            $returnValue.= '</option>';
        }
        $returnValue.= '</select>';
        return $returnValue;
    }
    
    private function usesCategories() {
        return $this->menu === null;
    }
    
    private function writeFooter(StreamInterface $stream) {
        $text = $this->text;
        $footer = <<<HTML
            <hr />
            <p>
                <a class="arrow" href="{$text->e($text->getUrlPage("admin"))}">
                    {$text->t("main.admin")}
                </a>
            </p>
HTML;
        $stream->write($footer);
    }

}
