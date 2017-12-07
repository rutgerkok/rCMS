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
use Rcms\Template\CategoryTemplate;

/**
 * Shows all articles in a category.
 */
final class CategoryPage extends Page {

    /**
     * @var bool True when edit/delete links are shown for articles, false otherwise.
     */
    private $isArticleModerator;

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
        $this->isArticleModerator = $request->hasRank($website, Authentication::RANK_MODERATOR);
        $this->showCategoryEditLinks = $request->hasRank($website, Authentication::RANK_ADMIN);

        $categoryId = $request->getParamInt(0, 0);

        $categoriesRepo = new CategoryRepository($website->getDatabase());
        $this->category = $categoriesRepo->getCategory($categoryId);

        $articlesRepo = new ArticleRepository($website->getDatabase(), $this->isArticleModerator);
        $this->articles = $articlesRepo->getArticlesData($categoryId);


    }

    public function getPageTitle(Text $text) {
        return $this->category->getName();
    }

    public function getTemplate(Text $text) {
        return new CategoryTemplate($text, $this->category, $this->articles, $this->isArticleModerator, $this->showCategoryEditLinks);
    }

    public function getPageType() {
        return Page::TYPE_NORMAL;
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

}
