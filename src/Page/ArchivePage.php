<?php

/**
 * Page with links to all admin tasks of the site
 */
class ArchivePage extends Page {

    private $allCategories;
    private $articleCountInYears;
    private $selectedCategory;
    private $selectedYear;
    private $foundArticles;

    public function init(Website $oWebsite) {
        $this->selectedYear = $oWebsite->getRequestInt("year", 0);
        $this->selectedCategory = $oWebsite->getRequestInt("id", 0);

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

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("articles.archive");
    }

    public function getView(Website $oWebsite) {
        return new ArticleArchiveView($oWebsite, $this->foundArticles, $this->allCategories, $this->articleCountInYears, $this->selectedCategory, $this->selectedYear);
    }

}
