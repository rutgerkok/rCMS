<?php

namespace Rcms\Page;

use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\CategoryDeleteView;
use Rcms\Page\View\EmptyView;

/**
 * Page for deleting a category. The page is responsible for moving all articles
 * to the category with id 1, which represents the "uncategorized" category.
 */
final class DeleteCategoryPage extends Page {

    /**
     * @var Category The category being deleted.
     */
    private $category;

    /**
     * @var RequestToken|null Token for protecting the request, or null if it is
     * impossible to delete the selected category.
     */
    private $requestToken;

    /**
     *
     * @var bool Set to true when the category was deleted successfully.
     */
    private $deleted = false;

    public function init(Website $website, Request $request) {
        $categoriesRepo = new CategoryRepository($website->getDatabase());
        $categoryId = $request->getParamInt(0, 0);
        $this->category = $categoriesRepo->getCategory($categoryId);

        if ($this->category->isStandardCategory()) {
            $text = $website->getText();
            $editCategory = Link::of($text->getUrlPage("edit_category", $this->category->getId()), $text->t("categories.edit"));
            $viewAll = Link::of($text->getUrlPage("category_list"), $text->t("categories.view_all"));
            $text->addError($text->t("categories.delete.cannot_remove_default"), $editCategory, $viewAll);
            return;
        }

        if (Validate::requestToken($request)) {
            $articlesRepo = new ArticleRepository($website);
            $this->deleteCategory($categoriesRepo, $articlesRepo, $website->getText());
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();
    }

    private function deleteCategory(CategoryRepository $categoryRepo,
            ArticleRepository $articleRepo, Text $text) {
        $categoryRepo->deleteCategory($articleRepo, $this->category);

        $viewAll = Link::of($text->getUrlPage("category_list"), $text->t("categories.view_all"));
        $text->addMessage($text->t("main.category") . " " . $text->t("editor.is_deleted"), $viewAll);

        $this->deleted = true;
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("categories.delete_a_category");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getView(Text $text) {
        if ($this->requestToken == null || $this->deleted) {
            return new EmptyView($text);
        }
        return new CategoryDeleteView($text, $this->category, $this->requestToken);
    }

}
