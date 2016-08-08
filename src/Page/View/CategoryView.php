<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Category;
use Rcms\Core\Text;

/**
 * Displays a category and some articles in it.
 */
class CategoryView extends View {

    /**
     * @var View Used to write the articles.
     */
    private $articlesTemplate;

    /**
     * @var Category The category being displayed.
     */
    private $category;

    /**
     * @var bool Whether edit and delete links are shown.
     */
    private $editLinks;

    public function __construct(Text $text, Category $category, array $articles,
            $showArticleEditLinks, $showCategoryEditLinks) {
        parent::__construct($text);

        $this->category = $category;
        $this->editLinks = $showCategoryEditLinks;
        $this->articlesTemplate = new ArticleListView($text, $articles, $category->getId(), true, true, $showArticleEditLinks);
    }

    public function writeText(StreamInterface $stream) {
        $stream->write($this->category->getDescriptionHtml());
        $this->writeEditLinks($stream);

        $this->articlesTemplate->writeText($stream);
    }

    private function writeEditLinks(StreamInterface $stream) {
        if (!$this->editLinks) {
            return;
        }

        $text = $this->text;
        $id = $this->category->getId();
        $stream->write(<<<HTML
            <p>
                <a class="arrow" href="{$text->url("edit_category", $id)}">
                    {$text->t("categories.edit")}
                </a>
                <a class="arrow" href="{$text->url("delete_category", $id)}">
                    {$text->t("categories.delete")}
                </a>
            </p>
HTML
        );
    }

}
