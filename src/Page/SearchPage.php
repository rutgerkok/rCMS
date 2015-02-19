<?php

namespace Rcms\Page;

use Rcms\Core\ArticleRepository;
use Rcms\Core\LinkRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Rcms\Page\View\ArticleSearchView;
use Rcms\Page\View\LinkSearchView;
use Rcms\Page\View\SearchFormView;

class SearchPage extends Page {

    const ARTICLES_PER_PAGE = 6;
    const MIN_SEARCH_LENGTH = 3;

    protected $keyword;
    protected $pageNumber;
    protected $displayedArticles;
    protected $totalResults;
    protected $highestPageNumber;

    /** @var Link[] Links to display. */
    protected $links;

    /** @var boolean Whether edit and delete links are shown. */
    protected $showEditLinks;

    public function init(Website $website, Request $request) {
        $this->keyword = trim($request->getRequestString("searchbox"));
        $this->pageNumber = $request->getRequestInt("page", 0);
        $this->showEditLinks = $website->isLoggedInAsStaff();

        if (strLen($this->keyword) < self::MIN_SEARCH_LENGTH) {
            // Don't search for too short words
            if (!empty($this->keyword)) {
                $website->addError($website->t("articles.search_term") . " "
                        . $website->tReplaced("errors.is_too_short_num", self::MIN_SEARCH_LENGTH));
            }
            return;
        }

        // Fetch article count
        $articles = new ArticleRepository($website);
        $this->totalResults = $articles->getMatchesFor($this->keyword);
        // Count total number of pages, limit current page number
        $this->highestPageNumber = floor($this->totalResults / self::ARTICLES_PER_PAGE);
        if ($this->pageNumber < 0 || $this->pageNumber > $this->highestPageNumber) {
            $this->pageNumber = 0;
        }
        // Fetch articles
        $this->displayedArticles = $articles->getArticlesDataMatch($this->keyword, self::ARTICLES_PER_PAGE, $this->pageNumber * self::ARTICLES_PER_PAGE);

        // Fetch links
        $menus = new LinkRepository($website);
        $this->links = $menus->getLinksBySearch($this->keyword);
    }

    public function getPageTitle(Text $text) {
        if ($this->keyword) {
            return $text->tReplaced("articles.search_for", $this->keyword);
        } else {
            return $this->getShortPageTitle($text);
        }
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.search");
    }

    public function getViews(Text $text) {
        $views = array();
        if (isSet($this->displayedArticles)) {
            $views[] = new ArticleSearchView($text, $this->keyword, $this->displayedArticles, 
                    $this->pageNumber, $this->totalResults, $this->highestPageNumber, $this->showEditLinks);
            $views[] = new LinkSearchView($text, $this->links);
        }
        $views[] = new SearchFormView($text, $this->keyword);
        return $views;
    }

}
