<?php

class CategoryPage extends Page {

    /** @var Article $article The article object, or null if not found */
    protected $pageTitle;
    protected $view;

    public function init(Website $oWebsite) {
        $categoryId = $oWebsite->getRequestInt("id");
        $oArticles = new Articles($oWebsite);
        $oCategories = new Categories($oWebsite);

        if ($categoryId == 0) {
            // Display a list of categories
            $this->pageTitle = $oWebsite->t("categories.all");
            $this->view = new CategoriesView($oWebsite, $oCategories);
        } else {
            // Display articles in a catgory
            $this->pageTitle = $oCategories->getCategoryName($categoryId);
            if(empty($this->pageTitle)) {
                $this->pageTitle = $oWebsite->t("main.category");
                $oWebsite->addError($oWebsite->t("main.category") . " " . $oWebsite->t("errors.not_found"));
                $this->view = new View();
            } else {
                $articles = $oArticles->getArticlesData($categoryId);
                $this->view = new ArticleListView($oWebsite, $articles, $categoryId, true, true);
            }
        }
    }

    public function getPageTitle(Website $oWebsite) {
        return $this->pageTitle;
    }

    public function getView(Website $oWebsite) {
        return $this->view;
    }

}

$this->registerPage(new CategoryPage());
