<?php

namespace Rcms\Page;

use Rcms\Core\ArticleRepository;
use Rcms\Core\Ranks;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Rcms\Template\ArticleArchiveTemplate;

/**
 * Page with links to all admin tasks of the site
 */
class ArchivePage extends Page {

    private $allCategories;
    private $articleCountInYears;
    private $selectedCategory;
    private $selectedYear;
    private $foundArticles;
    private $isModerator;

    public function init(Website $website, Request $request) {
        $this->isModerator = $request->hasRank(Ranks::MODERATOR);

        $this->selectedYear = $request->getRequestInt("year", 0);
        $this->selectedCategory = $request->getParamInt(0);

        // Fetch all categories
        $categories = new CategoryRepository($website->getDatabase());
        $this->allCategories = $categories->getCategoriesArray();

        // Check if valid category
        if ($this->selectedCategory != 0 && !array_key_exists($this->selectedCategory, $this->allCategories)) {
            $website->addError($website->t("main.category") . " " . $website->t("errors.not_found"));
            $this->selectedCategory = 0;
        }

        // Fetch all articles
        $articles = new ArticleRepository($website->getDatabase(), $this->isModerator);
        $this->articleCountInYears = $articles->getArticleCountInYears($this->selectedCategory);
        $this->foundArticles = $articles->getArticlesDataArchive($this->selectedYear, $this->selectedCategory);
    }

    public function getPageTitle(Text $text) {
        return $text->t("articles.archive");
    }

    public function getTemplate(Text $text) {
        return new ArticleArchiveTemplate($text, $this->foundArticles,
                $this->allCategories, $this->articleCountInYears,
                $this->selectedCategory, $this->selectedYear, $this->isModerator);
    }

    public function getMinimumRank() {
        return Ranks::LOGGED_OUT;
    }
}
