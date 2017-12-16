<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Category;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Request;
use Rcms\Core\Text;
use Rcms\Core\Website;
use Rcms\Template\CategoryListTemplate;

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

        $this->editLinks = $request->hasRank(Ranks::ADMIN);
    }

    public function getMinimumRank() {
        return Ranks::MODERATOR;
    }

    public function getPageTitle(Text $text) {
        return $text->t("categories.all");
    }

    public function getTemplate(Text $text) {
        return new CategoryListTemplate($text, $this->categories, $this->editLinks);
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

}
