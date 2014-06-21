<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Menus;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\ArticleSearchView;
use Rcms\Page\View\LinkSearchView;

class SearchPage extends Page {

    const ARTICLES_PER_PAGE = 6;

    protected $keyword;
    protected $pageNumber;
    protected $displayedArticles;
    protected $totalResults;
    protected $highestPageNumber;
    protected $links;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $this->keyword = trim($request->getRequestString("searchbox"));
        $this->pageNumber = $request->getRequestInt("page", 0);

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

    public function getPageTitle(Text $text) {
        if ($this->keyword) {
            return $text->tReplaced("articles.search_for", htmlSpecialChars($this->keyword));
        } else {
            return $this->getShortPageTitle($text);
        }
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.search");
    }

    public function getViews(Text $text) {
        return array(
            new ArticleSearchView($text, $this->keyword, $this->displayedArticles, $this->pageNumber, $this->totalResults, $this->highestPageNumber),
            new LinkSearchView($text, $this->links)
        );
    }

}
