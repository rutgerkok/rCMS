<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Category;
use Rcms\Core\Text;

final class CategoryListView extends View {

    /**
     * @var Category[] All categories on the website.
     */
    private $categories;
    private $editLinks;

    /**
     * Creates a new view of all categories.
     * @param Text $text The text object.
     * @param Category[] $categories All categories on the website.
     */
    public function __construct(Text $text, array $categories, $editLinks) {
        parent::__construct($text);
        $this->categories = $categories;
        $this->editLinks = (boolean) $editLinks;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;

        // Link to create new category
        if ($this->editLinks) {
            $stream->write('<p><a href="' . $text->url("edit_category") . '" class="arrow">' . $text->t('categories.create') . '</a></p>');
        }

        // All categories
        foreach ($this->categories as $category) {
            $this->writeCategory($stream, $category);
        }

        // Another link to create new category
        if ($this->editLinks) {
            $stream->write('<p><a href="' . $text->url("edit_category") . '" class="arrow">' . $text->t('categories.create') . '</a></p>');
        }
    }

    public function writeCategory(StreamInterface $stream, Category $category) {
        $text = $this->text;


        $stream->write("\n\n" . '<article class="article_teaser">');

        // Title
        $titleHtml = $text->e($category->getName());
        $stream->write("<header>");
        $stream->write("<h3>" . $this->encloseInCategoryLink($category, $titleHtml) . "</h3>");
        $stream->write("</header>");

        // Intro
        $stream->write($this->encloseInCategoryLink($category, $category->getDescriptionHtml()));
        $stream->write('<footer class="article_teaser_links"><p>');
        // Edit and delete links
        $stream->write('<a class="arrow" href="' . $text->url("category", $category->getId()) . '">' . $text->t('categories.view_articles') . '</a>');
        if ($this->editLinks) {
            $deleteClass = $category->isStandardCategory() ? "arrow-disabled" : "arrow";
            $stream->write(<<<HTML
                <a class="arrow" href="{$text->url("edit_article", 0, ["article_category" => $category->getId()])}">
                    {$text->t('articles.create')}
                </a>
                <a class="arrow" href="{$text->url("edit_category", $category->getId())}">
                    {$text->t('categories.edit')}
                </a>
                <a class="{$deleteClass}" href="{$text->url("delete_category", $category->getId())}">
                    {$text->t('categories.delete')}
                </a>
HTML
            );
        }
        $stream->write("</p></footer>");

        $stream->write('<p style="clear:both"></p>');
        $stream->write("</article>");
    }

    /**
     * Enloses the given HTML in an invisble link to the category.
     * @param Category $category Article to link to.
     * @param string $html HTML to enclose.
     * @return string The linked HTML.
     */
    private function encloseInCategoryLink(Category $category, $html) {
        $text = $this->text;
        return <<<LINKED
            <a class="disguised_link" href="{$text->url("category", $category->getId())}">
                $html
            </a>
LINKED;
    }

}

