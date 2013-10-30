<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

/**
 * Renders a list of articles with buttons to go the next or previous page
 */
class ArticleSearchView extends ArticleListView {

    const ARTICLES_PER_PAGE = 5;

    protected $keyword;
    protected $totalNumberOfArticles;
    protected $pageNumber;

    public function __construct(Website $oWebsite, $keyword, $displayedArticles, $pageNumber, $totalNumberOfArticles) {
        parent::__construct($oWebsite, $displayedArticles, 0, true, false);
        $this->keyword = $keyword;
        $this->totalNumberOfArticles = $totalNumberOfArticles;
        $this->pageNumber = $pageNumber;
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
        $keywordHTML = htmlSpecialChars($this->keyword);
        $page = $this->pageNumber;
        $resultCount = $this->totalNumberOfArticles;
        $returnValue = '';

        $returnValue.= '<p class="lijn">';
        if ($page > 1) {
            $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordHTML, "page" => $page - 1)) . '">' . $oWebsite->t('articles.page.previous') . '</a> ';
        }
        $returnValue.= str_replace("\$", ceil($resultCount / self::ARTICLES_PER_PAGE), $oWebsite->tReplaced('articles.page.current', $page)); //pagina X van Y
        if ($resultCount > self::getStartNumber($page) + self::ARTICLES_PER_PAGE) {
            $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordHTML, "page" => $page + 1)) . '">' . $oWebsite->t('articles.page.next') . '</a>';
        }
        $returnValue.= '</p>';

        return $returnValue;
    }

    public static function getStartNumber($page) {
        return ($page - 1) * self::ARTICLES_PER_PAGE;
    }

}

?>
