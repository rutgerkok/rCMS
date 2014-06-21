<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Categories;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\ArticleArchiveView;

/**
 * Page with links to all admin tasks of the site
 */
class ArchivePage extends Page {

    private $allCategories;
    private $articleCountInYears;
    private $selectedCategory;
    private $selectedYear;
    private $foundArticles;
    private $showEditLinks;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $this->showEditLinks = $oWebsite->isLoggedInAsStaff();

        $this->selectedYear = $request->getRequestInt("year", 0);
        $this->selectedCategory = $request->getParamInt(0);

        // Fetch all categories
        $categories = new Categories($oWebsite);
        $this->allCategories = $categories->getCategories();

        // Check if valid category
        if ($this->selectedCategory != 0 && !array_key_exists($this->selectedCategory, $this->allCategories)) {
            $oWebsite->addError($oWebsite->t("main.category") . " " . $oWebsite->t("errors.not_found"));
            $this->selectedCategory = 0;
        }

        // Fetch all articles
        $articles = new Articles($oWebsite);
        $this->articleCountInYears = $articles->getArticleCountInYears($this->selectedCategory);
        $this->foundArticles = $articles->getArticlesDataArchive($this->selectedYear, $this->selectedCategory);
    }

    public function getPageTitle(Text $text) {
        return $text->t("articles.archive");
    }

    public function getView(Text $text) {
        return new ArticleArchiveView($text, $this->foundArticles, 
                $this->allCategories, $this->articleCountInYears, 
                $this->selectedCategory, $this->selectedYear, $this->showEditLinks);
    }

}
