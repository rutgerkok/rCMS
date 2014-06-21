<?php

namespace Rcms\Page;

use Rcms\Core\Articles;
use Rcms\Core\Categories;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Page\View\ArticleListView;
use Rcms\Page\View\CategoriesView;
use Rcms\Page\View\EmptyView;

class CategoryPage extends Page {

    /** @var Article $article The article object, or null if not found */
    protected $pageTitle;
    protected $view;
    private $showEditLinks;

    public function init(Request $request) {
        $oWebsite = $request->getWebsite();
        $text = $oWebsite->getText();

        $this->showEditLinks = $oWebsite->isLoggedInAsStaff();

        $categoryId = $request->getParamInt(0);
        $oArticles = new Articles($oWebsite);
        $oCategories = new Categories($oWebsite);

        if ($categoryId == 0) {
            // Display a list of categories
            $this->pageTitle = $text->t("categories.all");
            $this->view = new CategoriesView($text, $oCategories->getCategories());
        } else {
            // Display articles in a catgory
            $this->pageTitle = $oCategories->getCategoryName($categoryId);
            if (empty($this->pageTitle)) {
                $this->pageTitle = $text->t("main.category");
                $text->addError($text->t("main.category") . " " . $oWebsite->t("errors.not_found"));
                $this->view = new EmptyView($text);
            } else {
                $articles = $oArticles->getArticlesData($categoryId);
                $this->view = new ArticleListView($text, $articles, $categoryId, true, true, $this->showEditLinks);
            }
        }
    }

    public function getPageTitle(Text $text) {
        return $this->pageTitle;
    }

    public function getView(Text $text) {
        return $this->view;
    }

}
