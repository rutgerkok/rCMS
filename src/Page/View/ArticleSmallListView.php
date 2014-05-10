<?php

namespace Rcms\Page\View;

use Rcms\Core\Website;
use Rcms\Core\Article;

/**
 * Gets the articles as a list, like this:
 * 
 * <ul>
 *   <li>Item</li>
 *   <li>Item</li>
 * </ul>
 */
class ArticleSmallListView extends View {

    /** @var Article[] $articles List of articles */
    protected $articles;
    protected $images;
    protected $archive;
    protected $mainCategoryId;

    /**
     * Creates a new view of a list of articles.
     * @param Website $oWebsite The website object.
     * @param Article[] $articles List of articles to display.
     * @param int $mainCategoryId The id of the category of the archive and the
     *     Add-article link. Use 0 if the articles are of multiple categories.
     * @param boolean $images Whether images should be shown.
     * @param boolean $archive Whether a link to the archive should be shown.
     */
    public function __construct(Website $oWebsite, $articles,
            $mainCategoryId = 0, $images = false, $archive = false) {
        parent::__construct($oWebsite);
        $this->articles = $articles;
        $this->mainCategoryId = (int) $mainCategoryId;
        $this->images = (boolean) $images;
        $this->archive = (boolean) $archive;
    }

    public function getText() {
        return $this->getArticlesList($this->articles, $this->mainCategoryId, $this->images, $this->archive);
    }

    public function getArticlesList($articles, $mainCategoryId, $images = false,
            $archive = false) {
        $oWebsite = $this->oWebsite;

        // Build article list
        $returnValue = '';
        if (count($articles) > 0) {
            $returnValue = '<ul class="linklist">';
            foreach ($articles as $article) {
                $returnValue.= $this->getArticleTextListEntry($article, $images);
            }
            $returnValue .= "</ul>\n";
        }

        // Add article link
        if ($oWebsite->isLoggedInAsStaff()) {
            $returnValue .= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", 0, array("article_category" => $mainCategoryId));
            $returnValue .= '">' . $oWebsite->t("articles.create") . "</a></p>\n";
        }

        // Archive link
        if ($archive) {
            $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("archive", $mainCategoryId) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>';
        }

        return $returnValue;
    }

    /** Returns a single article enclosed in li tags */
    public function getArticleTextListEntry(Article $article,
            $displayImage = false) {
        $returnValue = '<li><a href="' . $this->oWebsite->getUrlPage("article", $article->id) . '"';
        $returnValue.= 'title="' . $article->intro . '">';
        if ($displayImage && !empty($article->featuredImage)) {
            $returnValue.= '<div class="linklist_icon_image"><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" /></div>';
        }
        $returnValue.= "<span>" . htmlSpecialChars($article->title) . "</span></a></li>\n";
        return $returnValue;
    }

}
