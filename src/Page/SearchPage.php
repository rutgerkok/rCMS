<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class SearchPage extends Page {

    const ARTICLES_PER_PAGE = 6;

    protected $keyword;
    protected $pageNumber;
    protected $displayedArticles;
    protected $totalResults;
    protected $highestPageNumber;
    protected $links;

    public function init(Website $oWebsite) {
        $this->keyword = trim($oWebsite->getRequestString("searchbox"));
        $this->pageNumber = $oWebsite->getRequestInt("page", 0);

        // Fetch article count
        $articles = new Articles($oWebsite);
        $this->totalResults = $articles->getMatchesFor($this->keyword);
        // Count total number of pages, limit current page number
        $this->highestPageNumber = floor($this->totalResults / self::ARTICLES_PER_PAGE);
        if ($this->pageNumber < 0 || $this->pageNumber > $this->highestPageNumber) {
            $this->pageNumber = 0;
        }
        // Fetch articles
        $this->displayedArticles = $articles->getArticlesDataMatch($this->keyword, self::ARTICLES_PER_PAGE, $this->pageNumber * self::ARTICLES_PER_PAGE);

        // Fetch links
        $menus = new Menus($oWebsite);
        $this->links = $menus->getLinksBySearch($this->keyword);
    }

    public function getPageTitle(Website $oWebsite) {
        if ($this->keyword) {
            return $oWebsite->tReplaced("articles.search_for", htmlSpecialChars($this->keyword));
        } else {
            return $this->getShortPageTitle($oWebsite);
        }
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("main.search");
    }

    public function getViews(Website $oWebsite) {
        return array(
            new ArticleSearchView($oWebsite, $this->keyword, $this->displayedArticles, $this->pageNumber, $this->totalResults, $this->highestPageNumber),
            new LinkSearchView($oWebsite, $this->links)
        );
    }

}
