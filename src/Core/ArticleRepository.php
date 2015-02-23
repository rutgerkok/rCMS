<?php

namespace Rcms\Core;

use DateTime;
use PDO;
use PDOException;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;
use Rcms\Core\Repository\Query;

class ArticleRepository extends Repository {

    const TABLE_NAME = "artikel";

    protected $primaryField;
    protected $titleField;
    protected $createdField;
    protected $editedField;
    protected $introField;
    protected $bodyField;
    protected $featuredImageField;
    protected $categoryIdField;
    protected $categoryNameField;
    protected $authorIdField;
    protected $authorNameField;
    protected $pinnedField;
    protected $hiddenField;
    protected $showCommentsField;
    protected $calendarField;

    /**
     * @var Website The website.
     */
    protected $website;

    /**
     * Constructs the article displayer.
     * @param Website $website The website to use.
     * @param Database $databaseObject Not needed, for backwards compability.
     */
    public function __construct(Website $website,
            Database $databaseObject = null) {
        parent::__construct($databaseObject ? : $website->getDatabase());
        $this->website = $website;

        $this->primaryField = new Field(Field::TYPE_PRIMARY_KEY, "id", "artikel_id");
        $this->titleField = new Field(Field::TYPE_STRING, "title", "artikel_titel");
        $this->createdField = new Field(Field::TYPE_DATE, "created", "artikel_gemaakt");
        $this->editedField = new Field(Field::TYPE_DATE, "lastEdited", "artikel_bewerkt");
        $this->introField = new Field(Field::TYPE_STRING, "intro", "artikel_intro");
        $this->bodyField = new Field(Field::TYPE_STRING, "body", "artikel_inhoud");
        $this->featuredImageField = new Field(Field::TYPE_STRING, "featuredImage", "artikel_afbeelding");
        $this->categoryIdField = new Field(Field::TYPE_INT, "categoryId", "categorie_id");
        $this->categoryNameField = new Field(Field::TYPE_STRING, "category", "categorie_naam");
        $this->categoryNameField->createLink(CategoryRepository::TABLE_NAME, $this->categoryIdField);
        $this->authorIdField = new Field(Field::TYPE_INT, "authorId", "gebruiker_id");
        $this->authorNameField = new Field(Field::TYPE_STRING, "author", "user_display_name");
        $authorIdInUsersTable = new Field(Field::TYPE_INT, "authorId", "user_id");
        $this->authorNameField->createLink(UserRepository::TABLE_NAME, $this->authorIdField, $authorIdInUsersTable);
        $this->pinnedField = new Field(Field::TYPE_BOOLEAN, "pinned", "artikel_gepind");
        $this->hiddenField = new Field(Field::TYPE_BOOLEAN, "hidden", "artikel_verborgen");
        $this->showCommentsField = new Field(Field::TYPE_BOOLEAN, "showComments", "artikel_reacties");
        $this->calendarField = new Field(Field::TYPE_DATE, "onCalendar", "artikel_verwijsdatum");
    }

    public function createEmptyObject() {
        return new Article();
    }

    public function getPrimaryKey() {
        return $this->primaryField;
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getStandardFields() {
        return array($this->primaryField, $this->titleField, $this->createdField,
            $this->editedField, $this->introField, $this->featuredImageField,
            $this->categoryIdField, $this->categoryNameField, $this->authorIdField,
            $this->authorNameField, $this->pinnedField, $this->hiddenField,
            $this->calendarField);
    }

    public function getAllFields() {
        $fields = $this->getStandardFields();
        $fields[] = $this->bodyField;
        $fields[] = $this->showCommentsField;
        return $fields;
    }

    protected function whereRaw($sql, $params) {
        // Remove hidden articles for normal visitors
        if (!$this->website->isLoggedInAsStaff()) {
            if (!empty($sql)) {
                $sql = "({$sql}) AND ";
            }
            $sql.= "`{$this->hiddenField->getNameInDatabase()}` = 0";
        }

        // Get query
        $query = parent::whereRaw($sql, $params);

        // Set default limit
        $query->limit(9);

        return $query;
    }

    /**
     * Deletes the given article.
     * @param Article $article Article to delete.
     * @return boolean True if the article was deleted, false if deletion failed.
     */
    public function delete(Article $article) {
        try {
            $this->where($this->getPrimaryKey(), '=', $article->id)->deleteOneOrFail();
            return true;
        } catch (NotFoundException $e) {
            return false;
        } catch (PDOException $e) {
            $this->website->getText()->logException("Error deleting article", $e);
            return false;
        }
    }

    /**
     * Saves the given article to the database.
     * @param Article $article Article to save.
     */
    public function save(Article $article) {
        try {
            $this->saveEntity($article);
            return true;
        } catch (PDOException $e) {
            $website = $this->website;
            $website->addError($website->t("main.article") . ' ' . $website->t("errors.not_saved"));
            $website->getText()->logException("Error saving article", $e);
            return false;
        }
    }

    /**
     * 
     * @param int $oldId Old category id.
     * @param int $newId New category id.
     * @throws PDOException If a database error occurs.
     */
    public function changeCategories($oldId, $newId) {
        $oldId = (int) $oldId;
        $newId = (int) $newId;
        $sql = <<<SQL
             UPDATE `{$this->getTableName()}`
             SET `{$this->categoryIdField->getNameInDatabase()}` = :newId
             WHERE `{$this->categoryIdField->getNameInDatabase()}` = :oldId
SQL;
        $statement = $this->pdo->prepare($sql);
        $statement->bindParam(":newId", $newId, PDO::PARAM_INT);
        $statement->bindParam(":oldId", $oldId, PDO::PARAM_INT);
        $statement->execute();
    }

    // Lookup functions

    /**
     * Gets an article by its id.
     * @param int $id Id of the article.
     * @return Article The article.
     * @throws NotFoundException If the article doesn't exist.
     */
    public function getArticleOrFail($id) {
        return $this->where($this->getPrimaryKey(), '=', (int) $id)
                        ->withAllFields()
                        ->selectOneOrFail();
    }

    /**
     * Gets a potentially long list of articles with the given year and
     * category. 0 can be used for both the year and category as a wildcard.
     * @param int $year The year to display.
     * @param int $categoryId The category id of the articles.
     * @return \Article List of articles.
     */
    public function getArticlesDataArchive($year = 0, $categoryId = 0) {
        $year = (int) $year;
        $categoryId = (int) $categoryId;

        // Set the limit extremely high when viewing the articles of just one
        // year, to prevent strange missing articles at the end of the year.
        $limit = ($year == 0) ? 50 : 500;

        // Add where clausules
        $whereClausules = array();
        $params = array();
        if ($year != 0) {
            $whereClausules[] = "YEAR(`artikel_gemaakt`) = :year";
            $params[":year"] = $year;
        }
        if ($categoryId != 0) {
            $whereClausules[] = "`categorie_id` = :categoryId";
            $params[":categoryId"] = $categoryId;
        }

        return $this->whereRaw(join(" AND ", $whereClausules), $params)->limit($limit)->orderAscending($this->createdField)->select();
    }

    /**
     * Gets the latest articles for the given user.
     * @param int $userId The id of the user.
     * @return Article[] List of articles.
     */
    public function getArticlesDataUser($userId) {
        $userId = (int) $userId;
        return $this->where($this->authorIdField, '=', $userId)->limit(9)->select();
    }

    /**
     * Gets all articles with an event date in the given month.
     * @param DateTime $month The month to look up.
     * @return Article[] All articles with an event date in that month.
     */
    public function getArticlesDataCalendarMonth(DateTime $month) {
        $monthNumber = (int) $month->format('n');
        $yearNumber = (int) $month->format('Y');
        return $this->whereRaw("YEAR(`artikel_verwijsdatum`) = :yearNumber AND MONTH(`artikel_verwijsdatum`) = :monthNumber", array(":yearNumber" => $yearNumber, ":monthNumber" => $monthNumber))
                        ->limit(99)->select();
    }

    /**
     * Gets all articles with the event date in the given year.
     * @param DateTime $year The year to look up.
     * @return Article[] All articles with an event date in that year.
     */
    public function getArticlesDataCalendarYear(DateTime $year) {
        $yearNumber = (int) $year->format('Y');
        return $this->whereRaw("YEAR(`artikel_verwijsdatum`) = :yearNumber", array(":yearNumber" => $yearNumber))->limit(300)->select();
    }

    /**
     * Gets all articles, optionally from one or more categories.
     * @param int $categoryIds Ids of the categories. Set it to 0 to get articles from all categories.
     * @param int $limit Maximum number of articles to return.
     * @return Article[] List of articles.
     */
    public function getArticlesData($categoryIds = 0, $limit = 9,
            $oldestTop = false) {
        $limit = (int) $limit;

        if (!is_array($categoryIds)) {
            // Single category
            if ($categoryIds == 0) {
                $query = $this->all();
            } else {
                $query = $this->where($this->categoryIdField, '=', $categoryIds);
            }
        } else {
            // Multiple categories
            $pieces = array();
            $params = array();
            $i = 0;
            foreach ($categoryIds as $categoryId) {
                $categoryId = (int) $categoryId;
                $pieces[] = "`categorie_id` = :categoryId" . $i;
                $params[":categoryId" . $i] = $categoryId;
                $i++;
            }
            $whereClausule = join(" OR ", $pieces);
            $query = $this->whereRaw($whereClausule, $params);
        }

        $query->orderDescending($this->pinnedField);
        if ($oldestTop) {
            $query->orderAscending($this->createdField);
        } else {
            $query->orderDescending($this->createdField);
        }

        return $query->limit($limit)->select();
    }

    /**
     * Gets an array with how many articles there are in each year in a given
     * category.
     * @param int $category_id The category id. Use 0 to search in all categories.
     * @return array Key is year, value is count.
     */
    public function getArticleCountInYears($category_id = 0) {
        $category_id = (int) $category_id;

        $sql = "SELECT YEAR(`artikel_gemaakt`), COUNT(*) FROM `artikel` ";
        if ($category_id != 0) {
            $sql.= "WHERE `categorie_id` = $category_id ";
        }
        $sql.= "GROUP BY YEAR(`artikel_gemaakt`)";
        $result = $this->pdo->query($sql);
        $byYear = array();
        while (list($year, $count) = $result->fetch(PDO::FETCH_NUM)) {
            $byYear[$year] = $count;
        }
        return $byYear;
    }

    /**
     * Returns how many matches this search will give.
     * @param string $keyword The keyword.
     * @return int The number of matches, or false if an error occured.
     */
    public function getMatchesFor($keyword) {
        return $this->searchQuery($keyword)->count();
    }

    /**
     * Gets a query that searches the articles for the given keyword. 
     * @param string $keyword The keyword.
     * @return Query The query.
     */
    protected function searchQuery($keyword) {
        return $this->whereRaw("(`artikel_titel` LIKE CONCAT('%', :keyword, '%') OR `artikel_intro` LIKE CONCAT('%', :keyword, '%') OR `artikel_inhoud` LIKE CONCAT('%', :keyword, '%'))", array(":keyword" => $keyword));
    }

    /**
     * Returns the matches of a search.
     * @param string $keyword The keyword.
     * @param int $articlesPerPage Number of articles on each page.
     * @param int $start The offset of the articles.
     * @return Article[] List of articles.
     */
    public function getArticlesDataMatch($keyword, $articlesPerPage, $start) {
        $articlesPerPage = (int) $articlesPerPage;
        $start = (int) $start;
        if ($start < 0) {
            $start = 0;
        }

        return $this->searchQuery($keyword)
                        ->limit($articlesPerPage)
                        ->offset($start)
                        ->select();
    }

}
