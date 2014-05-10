<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Categories;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\ArticleListView;
use Rcms\Page\View\CategoriesView;
use Rcms\Page\View\EmptyView;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class CategoryPage extends Page {

    /** @var Article $article The article object, or null if not found */
    protected $pageTitle;
    protected $view;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $categoryId = $request->getParamInt(0);
        $oArticles = new Articles($oWebsite);
        $oCategories = new Categories($oWebsite);

        if ($categoryId == 0) {
            // Display a list of categories
            $this->pageTitle = $oWebsite->t("categories.all");
            $this->view = new CategoriesView($oWebsite, $oCategories->getCategories());
        } else {
            // Display articles in a catgory
            $this->pageTitle = $oCategories->getCategoryName($categoryId);
            if (empty($this->pageTitle)) {
                $this->pageTitle = $oWebsite->t("main.category");
                $oWebsite->addError($oWebsite->t("main.category") . " " . $oWebsite->t("errors.not_found"));
                $this->view = new EmptyView($oWebsite);
            } else {
                $articles = $oArticles->getArticlesData($categoryId);
                $this->view = new ArticleListView($oWebsite, $articles, $categoryId, true, true);
            }
        }
    }

    public function getPageTitle(Request $request) {
        return $this->pageTitle;
    }

    public function getView(Website $website) {
        return $this->view;
    }

}
