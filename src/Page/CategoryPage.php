<?php

namespace Rcms\Page;

use Rcms\Core\Article;
use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\Category;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;
use Rcms\Page\View\CategoryView;

/**
 * Shows all articles in a category.
 */
final class CategoryPage extends Page {

    /**
     * @var bool True when edit/delete links are shown for articles, false otherwise.
     */
    private $showArticleEditLinks;

    /**
     * @var bool True when edit/delete links are shown for categories, false otherwise.
     */
    private $showCategoryEditLinks;

    /**
     * @var Category The category being viewed.
     */
    private $category;

    /**
     * @var Article[] Articles in the category.
     */
    private $articles;

    public function init(Website $website, Request $request) {
        $categoryId = $request->getParamInt(0, 0);

        $categoriesRepo = new CategoryRepository($website->getDatabase());
        $this->category = $categoriesRepo->getCategory($categoryId);

        $articlesRepo = new ArticleRepository($website);
        $this->articles = $articlesRepo->getArticlesData($categoryId);

        $this->showArticleEditLinks = $website->isLoggedInAsStaff();
        $this->showCategoryEditLinks = $website->isLoggedInAsStaff(true);
    }

    public function getPageTitle(Text $text) {
        return $this->category->getName();
    }

    public function getView(Text $text) {
        return new CategoryView($text, $this->category, $this->articles, $this->showArticleEditLinks, $this->showCategoryEditLinks);
    }

    public function getPageType() {
        return Page::TYPE_NORMAL;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
