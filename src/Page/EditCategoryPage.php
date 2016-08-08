<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Category;
use Rcms\Core\CategoryRepository;
use Rcms\Core\Link;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Website;
use Rcms\Page\View\CategoryEditView;
use Rcms\Page\View\Support\RichEditor;
use Rcms\Page\View\Support\CKEditor;

/**
 * Page for editing a single category.
 */
final class EditCategoryPage extends Page {

    /**
     * @var Category The category being edited.
     */
    private $category;

    /**
     * @var RequestToken The token used for protecting the request.
     */
    private $requestToken;

    /**
     * @var RichEditor The editor component.
     */
    private $richEditor;

    public function init(Website $website, Request $request) {
        $categoryId = $request->getParamInt(0, 0);
        $categoriesRepo = new CategoryRepository($website->getDatabase());

        if ($categoryId === 0) {
            $this->category = new Category(0, "");
        } else {
            $this->category = $categoriesRepo->getCategory($categoryId);
        }

        if (Validate::requestToken($request)) {
            $this->updateCategory($categoriesRepo, $request, $website->getText());
        }

        $this->requestToken = RequestToken::generateNew();
        $this->requestToken->saveToSession();

        $this->richEditor = new CKEditor($website->getText(), $website->getConfig(), $website->getThemeManager());
    }

    private function updateCategory(CategoryRepository $categoryRepo,
            Request $request, Text $text) {
        $this->category->setName($request->getRequestString("category_name", ""));
        $this->category->setDescriptionHtml($request->getRequestString("category_description", ""));

        $valid = true;
        if (!Validate::stringLength($this->category->getName(), CategoryRepository::NAME_MIN_LENGTH, CategoryRepository::NAME_MAX_LENGTH)) {
            $text->addError($text->t("categories.name") . ' ' . Validate::getLastError($text));
            $valid = false;
        }
        if (!Validate::stringLength($this->category->getDescriptionHtml(), CategoryRepository::DESCRIPTION_MIN_LENGTH, CategoryRepository::DESCRIPTION_MAX_LENGTH)) {
            $text->addError($text->t("categories.description") . ' ' . Validate::getLastError($text));
            $valid = false;
        }

        if ($valid) {
            $newCategory = $this->category->getId() === 0;
            $categoryRepo->saveCategory($this->category);

            // Add a confirmation
            $confirmation = $text->t("main.category") . " " . $text->t("editor.is_edited");
            if ($newCategory) {
                $confirmation = $text->t("main.category") . " " . $text->t("editor.is_created");
            }
            $viewCategory = Link::of($text->getUrlPage("category", $this->category->getId()), $text->t("categories.view_category"));
            $viewCategories = Link::of($text->getUrlpage("category_list"), $text->t("categories.view_all_categories"));
            $text->addMessage($confirmation, $viewCategory, $viewCategories);
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_ADMIN;
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        if ($this->category->getId() === 0) {
            return $text->t("categories.create");
        }
        return $text->t("categories.edit_a_category");
    }

    public function getView(Text $text) {
        return new CategoryEditView($text, $this->category, $this->richEditor, $this->requestToken);
    }

}
