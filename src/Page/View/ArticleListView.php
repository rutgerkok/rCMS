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
            $stream->write('<p><a href="' . $text->e($text->getUrlPage("edit_article", null, array("article_category" => $mainCategoryId))) . '" class="arrow">' . $text->t('articles.create') . '</a></p>');
        }

        // All articles
        if (count($this->articles) > 0) {
            foreach ($this->articles as $article) {
                $stream->write($this->getArticleTextSmall($article, $this->metainfo, $loggedInStaff));
            }
        } else {
            $stream->write("<p>" . $text->t("errors.nothing_found") . "</p>");
        }

        // Another link to create new article
        if ($loggedInStaff) {
            $stream->write('<p><a href="' . $text->e($text->getUrlPage("edit_article", null, array("article_category" => $mainCategoryId))) . '" class="arrow">' . $text->t('articles.create') . '</a></p>');
        }

        // Archive link
        if ($this->archive) {
            $stream->write('<p><a href="' . $text->e($text->getUrlPage("archive", $mainCategoryId)) . '" class="arrow">' . $text->t('articles.archive') . '</a></p>');
        }
    }

    public function getArticleTextSmall(Article $article, $show_metainfo,
            $show_edit_delete_links) {
        $text = $this->text;
        

        $returnValue = "\n\n" . '<div class="article_teaser ';
        if (!empty($article->featuredImage)) {
            $returnValue.= "with_featured_image";
        }
        $returnValue.= '">';

        // Title
        $titleHtml = htmlSpecialChars($article->getTitle());
        $returnValue.= "<h3>" . $this->encloseInArticleLink($article, $titleHtml) . "</h3>";

        if ($show_metainfo) {
            $returnValue.= '<p class="meta">';
            // Created and last edited
            $returnValue.= $text->t('articles.created') . " " . $text->formatDateTime($article->getDateCreated()) . ' - ';
            if ($article->getDateLastEdited()) {
                $returnValue.= lcFirst($text->t('articles.last_edited')) . " " . $text->formatDateTime($article->getDateLastEdited()) . '<br />';
            }
            // Category
            $returnValue.= $text->t('main.category') . ": ";
            $returnValue.= '<a href="' . $text->e($text->getUrlPage("category", $article->categoryId)) . '">';
            $returnValue.= htmlSpecialChars($article->category) . '</a>';
            // Author
            $returnValue.= " - " . $text->t('articles.author') . ": ";
            $returnValue.= '<a href="' . $text->e($text->getUrlPage("account", $article->authorId)) . '">';
            $returnValue.= htmlSpecialChars($article->author) . "</a>";
            // Pinned
            if ($article->pinned) {
                $returnValue.= " - " . $text->t('articles.pinned');
            }
            // Hidden
            if ($article->isHidden()) {
                $returnValue.= " - " . $text->t('articles.hidden');
            }
            $returnValue.= '</p>';
        }

        // Featured image
        if (!empty($article->featuredImage)) {
            $imageHtml = '<img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->getTitle()) . '" />';
            $returnValue.= $this->encloseInArticleLink($article, $imageHtml);
        }

        // Intro
        $returnValue.= '<div class="article_teaser_text">';
        $returnValue.= '<p>';
        $returnValue.= $this->encloseInArticleLink($article, htmlSpecialChars($article->getIntro()));
        $returnValue.= '</p>';
        $returnValue.= '<p class="article_teaser_links">';
        // Edit and delete links
        $returnValue.= '<a class="arrow" href="' .  $text->e($text->getUrlPage("article", $article->getId())) . '">' . $text->t('main.read') . '</a>';
        if ($show_edit_delete_links) {
            $returnValue.= '<a class="arrow" href="' . $text->e($text->getUrlPage("edit_article", $article->getId())) . '">' . $text->t('main.edit') . '</a>' . //edit
                    '<a class="arrow" href="' . $text->e($text->getUrlPage("delete_article", $article->getId())) . '">' . $text->t('main.delete') . '</a>'; //delete
        }
        $returnValue.= "</p>";
        $returnValue.= "</div>";

        $returnValue.= '<p style="clear:both"></p>';
        $returnValue.= "</div>";

        return $returnValue;
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
