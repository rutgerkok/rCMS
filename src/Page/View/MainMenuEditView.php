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
                    <a class="arrow" href="{$text->e($text->getUrlPage("category_list"))}">
                        {$text->t("categories.edit_categories")}
                    </a>
                </p>
HTML
            );
            $this->writeMenuForm($stream);
            $this->writeFooter($stream);
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
            $this->writeMenuForm($stream);
            $this->writeFooter($stream);
        }
    }

    private function writeCategoriesForm(StreamInterface $stream) {
        $text = $this->text;

        $stream->write(<<<HTML
            <fieldset>
                <legend>{$text->t("links.main_menu.use_categories_instead")}</legend>
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

        $titleHtml = ($this->usesCategories())? $text->t("links.main_menu.use_menu_instead")
                : $text->t("links.main_menu.use_other_menu");

        $currentMenuId = $this->usesCategories()? 0 : $this->menu->getId();

        // Find all menus that are currently not in use as the main menu
        $selectableMenus = array_filter($this->menus, function(Menu $menu) use ($currentMenuId) {
            return $menu->getId() !== $currentMenuId;
        });

        if (empty($selectableMenus)) {
            $this->writeEmptyMenuForm($stream);
        } else {
            $this->writePopulatedMenuForm($stream, $selectableMenus);
        }
    }

    private function writePopulatedMenuForm(StreamInterface $stream, array $selectableMenus) {
        $text = $this->text;

        $titleHtml = ($this->usesCategories())? $text->t("links.main_menu.use_menu_instead")
            : $text->t("links.main_menu.use_other_menu");

        $stream->write(<<<HTML
             <fieldset>
                <form method="POST" action="{$text->e($text->getUrlPage("edit_main_menu"))}">
                    <legend>{$titleHtml}</legend>
                    <p>
                        <label for="main_menu_id">{$text->t("links.menu.for_main_menu")}</label>:
                        {$this->getMenuList($selectableMenus)}
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

    private function writeEmptyMenuForm(StreamInterface $stream) {
        $text = $this->text;

        if ($this->usesCategories()) {
            $titleHtml = $text->t("links.main_menu.use_menu_instead");
            $explanationHtml = $text->t("links.menu.no_menus_created");
        } else {
            $titleHtml = $text->t("links.main_menu.use_other_menu");
            $explanationHtml = $text->t("links.menu.no_other_menus_created");
        }

        $stream->write(<<<HTML
             <fieldset>
                <legend>{$titleHtml}</legend>
                <p>
                    {$explanationHtml}
                    <a class="arrow" href="{$text->e($text->getUrlPage("add_menu"))}">
                        {$text->t("links.menu.add")}.
                    </a>
                </p>
             </fieldset>
HTML
        );

    }

    private function getMenuList(array $selectableMenus) {
        $text = $this->text;

        $returnValue = '<select class="button" name="main_menu_id" id="main_menu_id">';
        foreach ($selectableMenus as $menu) {
            $returnValue.= '<option value="' . $menu->getId() . '">';
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
