<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Category;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Page\View\CategoryListView;

/**
 * An overview of all categories on the website.
 */
final class CategoryListPage extends Page {

    /**
     * @var Category[] List of all categories.
     */
    private $categories;

    /**
     * @var boolean Whether edit and delete links are shown.
     */
    private $editLinks;

    public function init(Website $website, Request $request) {
        parent::init($website, $request);

        $categoryRepo = new CategoryRepository($website->getDatabase());
        $this->categories = $categoryRepo->getCategoriesComplete();

        $this->editLinks = $website->isLoggedInAsStaff(true);
    }

    public function getMinimumRank() {
        return Authentication::RANK_MODERATOR;
    }

    public function getPageTitle(Text $text) {
        return $text->t("categories.all");
    }

    public function getView(Text $text) {
        return new CategoryListView($text, $this->categories, $this->editLinks);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
