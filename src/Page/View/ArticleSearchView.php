<?php

namespace Rcms\Page\View;

use Rcms\Core\Website;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Renders a list of articles with buttons to go the next or previous page
 */
class ArticleSearchView extends ArticleListView {

    protected $keyword;
    protected $totalNumberOfArticles;
    protected $pageNumber;
    protected $highestPageNumber;

    public function __construct(Website $oWebsite, $keyword, array $displayedArticles,
            $pageNumber, $totalNumberOfArticles, $highestPageNumber) {
        parent::__construct($oWebsite, $displayedArticles, 0, true, false);
        $this->keyword = $keyword;
        $this->totalNumberOfArticles = (int) $totalNumberOfArticles;
        $this->pageNumber = (int) $pageNumber;
        $this->highestPageNumber = (int) $highestPageNumber;
    }

    public function getText() {
        $oWebsite = $this->oWebsite;
        $resultcount = $this->totalNumberOfArticles;

        $returnValue = '';
        if (count($this->articles) > 0) {
            // Display result count
            if ($resultcount == 1) {
                $returnValue.= "<p>" . $oWebsite->t('articles.search.result_found') . "</p>";
            } else {
                $returnValue.= "<p>" . $oWebsite->tReplaced('articles.search.results_found', $resultcount) . "</p>";
            }

            // Display articles
            $returnValue.= $this->getMenuBar();
            $returnValue.= parent::getText();
            $returnValue.= $this->getMenuBar();
        } else {
            $returnValue.='<p><em>' . $oWebsite->t('articles.search.no_results_found') . '</em></p>'; //niets gevonden
        }

        return $returnValue;
    }

    protected function getMenuBar() {
        $oWebsite = $this->oWebsite;
        $keywordHtml = htmlSpecialChars($this->keyword);
        $page = $this->pageNumber;

        $returnValue = '<p class="lijn">';

        // Link to previous page
        if ($page > 0) {
            $returnValue.= ' <a class="arrow" href="';
            $returnValue.= $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordHtml, "page" => $page - 1));
            $returnValue.= '">' . $oWebsite->t('articles.page.previous') . '</a> ';
        }

        // Current page (converting from zero-indexed to one-indexed)
        $returnValue.= str_replace("\$", $this->highestPageNumber + 1, $oWebsite->tReplaced('articles.page.current', $page + 1));

        // Next page
        if ($page < $this->highestPageNumber) {
            $returnValue.= ' <a class="arrow" href="';
            $returnValue.= $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordHtml, "page" => $page + 1));
            $returnValue.= '">' . $oWebsite->t('articles.page.next') . '</a>';
        }

        $returnValue.= '</p>';

        return $returnValue;
    }

    public static function getStartNumber($page) {
        return $page * self::ARTICLES_PER_PAGE;
    }

}
