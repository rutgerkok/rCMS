<?php

namespace Rcms\Core;

use InvalidArgumentException;
use PDO;
use PDOException;
use Rcms\Core\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

final class CategoryRepository extends Repository {

    const TABLE_NAME = "categories";
    const NAME_MIN_LENGTH = 1;
    const NAME_MAX_LENGTH = 30;
    const DESCRIPTION_MIN_LENGTH = 0;
    const DESCRIPTION_MAX_LENGTH = 2000;

    private $idField;
    private $nameField;
    private $descriptionField;

    public function __construct(PDO $oDatabase) {
        parent::__construct($oDatabase);

        $this->idField = new Field(Field::TYPE_PRIMARY_KEY, "id", "category_id");
        $this->nameField = new Field(Field::TYPE_STRING, "name", "category_name");
        $this->descriptionField = new Field(Field::TYPE_STRING, "description", "category_description");
    }

    /**
     * Gets all category names and ids. Category descriptions are not included.
     * @return Category[] All categories.
     */
    public function getCategories() {
        return $this->all()
                        ->orderDescending($this->idField)
                        ->select();
    }

    /**
     * Gets all category names, descriptions and ids.
     * @return Category[] All categories.
     */
    public function getCategoriesComplete() {
        return $this->all()
                        ->withAllFields()
                        ->orderDescending($this->idField)
                        ->select();
    }

    /**
     * Gets an array of links to all categories.
     * @param Text $text The text object, of URL structure.
     * @return Link[] The array of links.
     */
    public function getCategoryLinks(Text $text) {
        $categories = $this->getCategories();

        $links = [];
        foreach ($categories as $category) {
            if ($category->isStandardCategory()) {
                continue; // Don't display "No categories"
            }
            $links[] = Link::of(
                            $text->getUrlPage("category", $category->getId()), $category->getName()
            );
        }

        return $links;
    }

    /**
     * Gets all catgories in an array, category id => category name.
     * @return array The array.
     */
    public function getCategoriesArray() {
        $categoryArray = $this->getCategories();
        $returnArray = [];
        foreach ($categoryArray as $category) {
            $returnArray[$category->getId()] = $category->getName();
        }
        return $returnArray;
    }

    public function getAllFields() {
        return [$this->idField, $this->nameField, $this->descriptionField];
    }

    public function getStandardFields() {
        return [$this->idField, $this->nameField];
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->idField;
    }

    public function createEmptyObject() {
        return new Category();
    }

    /**
     * Gets the category.
     * @param int $id Id of the category.
     * @return Category The category.
     * @throws NotFoundException If the category is not found.
     */
    public function getCategory($id) {
        return $this->where($this->idField, '=', $id)->withAllFields()->selectOneOrFail();
    }

    /**
     * Saves the category to the datebase.
     * @param Category $category The category.
     * @throws NotFoundException If the id is larger than 0 and no entity with
     * the given id exists in the database.
     * @throws InvalidArgumentException If `$this->canBeSaved($entity)` returns
     * false.
     * @throws PDOException When a database error occurs.
     */
    public function saveCategory(Category $category) {
        $this->saveEntity($category);
    }

    /**
     * Deletes the given category. All articles are moved to the standard category.
     * @param ArticleRepository $articleRepo Repo for moving the articles.
     * @param Category $category The category that must be deleted.
     * @throws NotFoundException If this category doesn't exist in the database.
     * @throws PDOException If a database error occurs.
     * @throws InvalidArgumentException If the category is a standard category.
     */
    public function deleteCategory(ArticleRepository $articleRepo,
            Category $category) {
        if ($category->isStandardCategory()) {
            throw new InvalidArgumentException("cannot delete standard category");
        }

        $articleRepo->changeCategories($category->getId(), 1);
        $this->where($this->idField, '=', $category->getId())->deleteOneOrFail();
    }

}
