<?php

namespace Rcms\Page\View;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\Text;
use Rcms\Core\Article;

/**
 * Renders a list of articles.
 */
class ArticleListView extends View {

    /** @var Article[] $articles List of articles */
    protected $articles;
    protected $mainCategoryId;
    protected $metainfo;
    protected $archive;
    protected $editLinks;

    /**
     * Creates a new view for a list of articles.
     * @param Text $text The website object.
     * @param Article[] $articles List of articles.
     * @param int $mainCategoryId The category id for archive and create article links.
     * @param boolean $metainfo Whether author, date and category are shown.
     * @param boolean $archive Whether a link to the archive is shown.
     */
    public function __construct(Text $text, $articles, $mainCategoryId,
            $metainfo, $archive, $editLinks) {
        parent::__construct($text);
        $this->articles = $articles;
        $this->mainCategoryId = (int) $mainCategoryId;
        $this->metainfo = (boolean) $metainfo;
        $this->archive = (boolean) $archive;
        $this->editLinks = (boolean) $editLinks;
    }

    public function writeText(StreamInterface $stream) {
        $text = $this->text;
        $loggedInStaff = $this->editLinks;
        $mainCategoryId = $this->mainCategoryId;

        // Link to creat new article
        if ($loggedInStaff) {
            $stream->write('<p><a href="' . $text->e($text->getUrlPage("edit_article", null, ["article_category" => $mainCategoryId])) . '" class="arrow">' . $text->t('articles.create') . '</a></p>');
        }

        // All articles
        if (count($this->articles) > 0) {
            foreach ($this->articles as $article) {
                $this->writeArticleTextSmall($stream, $article, $this->metainfo, $loggedInStaff);
            }
        } else {
            $stream->write("<p>" . $text->t("errors.nothing_found") . "</p>");
        }

        // Another link to create new article
        if ($loggedInStaff) {
            $stream->write('<p><a href="' . $text->e($text->getUrlPage("edit_article", null, ["article_category" => $mainCategoryId])) . '" class="arrow">' . $text->t('articles.create') . '</a></p>');
        }

        // Archive link
        if ($this->archive) {
            $stream->write('<p><a href="' . $text->e($text->getUrlPage("archive", $mainCategoryId)) . '" class="arrow">' . $text->t('articles.archive') . '</a></p>');
        }
    }

    public function writeArticleTextSmall(StreamInterface $stream, Article $article, $show_metainfo,
            $show_edit_delete_links) {
        $text = $this->text;
        

        $stream->write("\n\n" . '<article class="article_teaser ');
        if (!empty($article->featuredImage)) {
            $stream->write("with_featured_image");
        }
        $stream->write('">');

        // Title
        $titleHtml = $text->e($article->getTitle());
        $stream->write("<header>");
        $stream->write("<h3>" . $this->encloseInArticleLink($article, $titleHtml) . "</h3>");

        if ($show_metainfo) {
            $stream->write('<p class="meta">');
            // Created and last edited
            $stream->write($text->t('articles.created') . " " . $text->formatDateTime($article->getDateCreated()) . ' - ');
            if ($article->getDateLastEdited()) {
                $stream->write(lcFirst($text->t('articles.last_edited')) . " " . $text->formatDateTime($article->getDateLastEdited()) . '<br />');
            }
            // Category
            $stream->write($text->t('main.category') . ": ");
            $stream->write('<a href="' . $text->e($text->getUrlPage("category", $article->categoryId)) . '">');
            $stream->write($text->e($article->category) . '</a>');
            // Author
            $stream->write(" - " . $text->t('articles.author') . ": ");
            $stream->write('<a href="' . $text->e($text->getUrlPage("account", $article->authorId)) . '">');
            $stream->write($text->e($article->author) . "</a>");
            // Pinned
            if ($article->pinned) {
                $stream->write(" - " . $text->t('articles.pinned'));
            }
            // Hidden
            if ($article->isHidden()) {
                $stream->write(" - " . $text->t('articles.hidden'));
            }
            $stream->write('</p>');
        }
        $stream->write("</header>");

        // Featured image
        if (!empty($article->featuredImage)) {
            $imageHtml = '<img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->getTitle()) . '" />';
            $stream->write($this->encloseInArticleLink($article, $imageHtml));
        }

        // Intro
        $stream->write('<p class="article_teaser_text">');
        $stream->write($this->encloseInArticleLink($article, htmlSpecialChars($article->getIntro())));
        $stream->write('</p>');
        $stream->write('<footer class="article_teaser_links"><p>');
        // Edit and delete links
        $stream->write('<a class="arrow" href="' .  $text->e($text->getUrlPage("article", $article->getId())) . '">' . $text->t('main.read') . '</a>');
        if ($show_edit_delete_links) {
            $stream->write('<a class="arrow" href="' . $text->e($text->getUrlPage("edit_article", $article->getId())) . '">' . $text->t('main.edit') . '</a>' .
                    '<a class="arrow" href="' . $text->e($text->getUrlPage("delete_article", $article->getId())) . '">' . $text->t('main.delete') . '</a>');
        }
        $stream->write("</p></footer>");

        $stream->write('<p style="clear:both"></p>');
        $stream->write("</article>");
    }

    /**
     * Enloses the given HTML in an invisble link to the article.
     * @param Article $article Article to link to.
     * @param string $html HTML to enclose.
     * @return string The linked HTML.
     */
    private function encloseInArticleLink(Article $article, $html) {
        $text = $this->text;
        return <<<LINKED
            <a class="disguised_link" href="{$text->e($text->getUrlPage("article", $article->getId()))}">
                $html
            </a>
LINKED;
    }

}
