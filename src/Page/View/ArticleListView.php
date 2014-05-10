<?php

namespace Rcms\Page\View;

use Rcms\Core\Website;
use Rcms\Core\Article;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Renders a list of articles.
 */
class ArticleListView extends View {

    /** @var Article[] $articles List of articles */
    protected $articles;
    protected $mainCategoryId;
    protected $metainfo;
    protected $archive;

    /**
     * Creates a new view for a list of articles.
     * @param Website $oWebsite The website object.
     * @param Article[] $articles List of articles.
     * @param int $mainCategoryId The category id for archive and create article links.
     * @param boolean $metainfo Whether author, date and category are shown.
     * @param boolean $archive Whether a link to the archive is shown.
     */
    public function __construct(Website $oWebsite, $articles, $mainCategoryId,
            $metainfo, $archive) {
        parent::__construct($oWebsite);
        $this->articles = $articles;
        $this->mainCategoryId = (int) $mainCategoryId;
        $this->metainfo = (boolean) $metainfo;
        $this->archive = (boolean) $archive;
    }

    public function getText() {
        $output = '';
        $oWebsite = $this->oWebsite;
        $loggedInStaff = $oWebsite->isLoggedInAsStaff();
        $mainCategoryId = $this->mainCategoryId;

        // Link to creat new article
        if ($loggedInStaff) {
            $output.= '<p><a href="' . $oWebsite->getUrlPage("edit_article", null, array("article_category" => $mainCategoryId)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
        }

        // All articles
        if (count($this->articles) > 0) {
            foreach ($this->articles as $article) {
                $output.= $this->getArticleTextSmall($article, $this->metainfo, $loggedInStaff);
            }
        } else {
            $output.= "<p>" . $oWebsite->t("errors.nothing_found") . "</p>";
        }

        // Another link to create new article
        if ($loggedInStaff) {
            $output.= '<p><a href="' . $oWebsite->getUrlPage("edit_article", null, array("article_category" => $mainCategoryId)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
        }

        // Archive link
        if ($this->archive) {
            $output.= '<p><a href="' . $oWebsite->getUrlPage("archive", $mainCategoryId) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>';
        }

        return $output;
    }

    public function getArticleTextSmall(Article $article, $show_metainfo,
            $show_edit_delete_links) {
        $oWebsite = $this->oWebsite;
        $returnValue = "\n\n<div class=\"article_teaser\" onclick=\"location.href='" . $oWebsite->getUrlPage("article", $article->id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
        $returnValue.= "<h3>" . htmlSpecialChars($article->title) . "</h3>\n";
        if ($show_metainfo) {
            $returnValue.= '<p class="meta">';
            // Created and last edited
            $returnValue.= $oWebsite->t('articles.created') . " " . $article->created . ' - ';
            if ($article->lastEdited) {
                $returnValue.= lcFirst($oWebsite->t('articles.last_edited')) . " " . $article->lastEdited . '<br />';
            }
            // Category
            $returnValue.= $oWebsite->t('main.category') . ": ";
            $returnValue.= '<a href="' . $oWebsite->getUrlPage("category", $article->categoryId) . '">';
            $returnValue.= htmlSpecialChars($article->category) . '</a>';
            // Author
            $returnValue.= " - " . $oWebsite->t('articles.author') . ": ";
            $returnValue.= '<a href="' . $oWebsite->getUrlPage("account", $article->authorId) . '">';
            $returnValue.= htmlSpecialChars($article->author) . "</a>";
            // Pinned
            if ($article->pinned) {
                $returnValue.= " - " . $oWebsite->t('articles.pinned');
            }
            // Hidden
            if ($article->hidden) {
                $returnValue.= " - " . $oWebsite->t('articles.hidden');
            }
            $returnValue.= '</p>';
        }

        // Featured image
        if (!empty($article->featuredImage)) {
            $returnValue.= '<img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" />';
        }

        // Intro
        $returnValue.= '<p>';
        $returnValue.= htmlSpecialChars($article->intro);
        $returnValue.= '</p> <p class="article_teaser_links">';
        // Edit and delete links
        $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlPage("article", $article->id) . '">' . $oWebsite->t('main.read') . '</a>';
        if ($show_edit_delete_links) {
            $returnValue.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", $article->id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                    '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_article", $article->id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
        }
        $returnValue.= "</p>";

        $returnValue.= '<p style="clear:both"></p>';
        $returnValue.= "</div>";

        return $returnValue;
    }

}
