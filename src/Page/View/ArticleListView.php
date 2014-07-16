<?php

namespace Rcms\Page\View;

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

    public function getText() {
        $output = '';
        $text = $this->text;
        $loggedInStaff = $this->editLinks;
        $mainCategoryId = $this->mainCategoryId;

        // Link to creat new article
        if ($loggedInStaff) {
            $output.= '<p><a href="' . $text->getUrlPage("edit_article", null, array("article_category" => $mainCategoryId)) . '" class="arrow">' . $text->t('articles.create') . '</a></p>';
        }

        // All articles
        if (count($this->articles) > 0) {
            foreach ($this->articles as $article) {
                $output.= $this->getArticleTextSmall($article, $this->metainfo, $loggedInStaff);
            }
        } else {
            $output.= "<p>" . $text->t("errors.nothing_found") . "</p>";
        }

        // Another link to create new article
        if ($loggedInStaff) {
            $output.= '<p><a href="' . $text->getUrlPage("edit_article", null, array("article_category" => $mainCategoryId)) . '" class="arrow">' . $text->t('articles.create') . '</a></p>';
        }

        // Archive link
        if ($this->archive) {
            $output.= '<p><a href="' . $text->getUrlPage("archive", $mainCategoryId) . '" class="arrow">' . $text->t('articles.archive') . '</a></p>';
        }

        return $output;
    }

    public function getArticleTextSmall(Article $article, $show_metainfo,
            $show_edit_delete_links) {
        $text = $this->text;
        $returnValue = "\n\n" . '<div class="article_teaser ';
        if (!empty($article->featuredImage)) {
            $returnValue.= "with_featured_image";
        }
        $returnValue.= '"onclick\"location.href=\'' . $text->getUrlPage("article", $article->id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
        $returnValue.= "<h3>" . htmlSpecialChars($article->title) . "</h3>\n";
        if ($show_metainfo) {
            $returnValue.= '<p class="meta">';
            // Created and last edited
            $returnValue.= $text->t('articles.created') . " " . $article->created . ' - ';
            if ($article->lastEdited) {
                $returnValue.= lcFirst($text->t('articles.last_edited')) . " " . $article->lastEdited . '<br />';
            }
            // Category
            $returnValue.= $text->t('main.category') . ": ";
            $returnValue.= '<a href="' . $text->getUrlPage("category", $article->categoryId) . '">';
            $returnValue.= htmlSpecialChars($article->category) . '</a>';
            // Author
            $returnValue.= " - " . $text->t('articles.author') . ": ";
            $returnValue.= '<a href="' . $text->getUrlPage("account", $article->authorId) . '">';
            $returnValue.= htmlSpecialChars($article->author) . "</a>";
            // Pinned
            if ($article->pinned) {
                $returnValue.= " - " . $text->t('articles.pinned');
            }
            // Hidden
            if ($article->hidden) {
                $returnValue.= " - " . $text->t('articles.hidden');
            }
            $returnValue.= '</p>';
        }

        // Featured image
        if (!empty($article->featuredImage)) {
            $returnValue.= '<img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" />';
        }

        // Intro
        $returnValue.= '<div class="article_teaser_text">';
        $returnValue.= '<p>';
        $returnValue.= htmlSpecialChars($article->intro);
        $returnValue.= '</p> <p class="article_teaser_links">';
        // Edit and delete links
        $returnValue.= '<a class="arrow" href="' . $text->getUrlPage("article", $article->id) . '">' . $text->t('main.read') . '</a>';
        if ($show_edit_delete_links) {
            $returnValue.= '<a class="arrow" href="' . $text->getUrlPage("edit_article", $article->id) . '">' . $text->t('main.edit') . '</a>' . //edit
                    '<a class="arrow" href="' . $text->getUrlPage("delete_article", $article->id) . '">' . $text->t('main.delete') . '</a>'; //delete
        }
        $returnValue.= "</p>";
        $returnValue.= "</div>";

        $returnValue.= '<p style="clear:both"></p>';
        $returnValue.= "</div>";

        return $returnValue;
    }

}
