<?php

class Articles {

    protected $websiteObject;
    protected $databaseObject;

    /**
     * Constructs the article displayer.
     * @param Website $websiteObject The website to use.
     * @param Database $databaseObject Not needed, for backwards compability.
     */
    public function __construct(Website $websiteObject, Database $databaseObject = null) {
        $this->websiteObject = $websiteObject;
        $this->databaseObject = $databaseObject;
        if ($this->databaseObject == null) {
            $this->databaseObject = $websiteObject->getDatabase();
        }
    }

    // DATA FUNCTIONS

    /**
     * Returns an article with an id. Respects page protection. Id is casted.
     * @param int $id The id of the article
     * @return Article|null The article, or null if it isn't found.
     */
    public function getArticleData($id) {
        try {
            return new Article($id, $this->databaseObject);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * Gets more articles at once. Protected because of it's dangerous
     * $where_clausule, which can be vulnerable to SQL injection. Other parameters
     * won't harm the database when their value is incorrect.
     * @param string $whereClausules Everything that should come after WHERE.
     * @param int $limit Limit the number of rows.
     * @param int $start Start position of the limit.
     * @param boolean $oldestTop Whether old articles should be at the top.
     * @param boolean $pinnedTop Set this to false to ignore pinned articles.
     * @return \Article Array of Articles.
     */
    protected function getArticlesDataUnsafe($whereClausules = "", $limit = 9, $start = 0, $oldestTop = false, $pinnedTop = true) {
        $oDB = $this->databaseObject; //afkorting
        $oWebsite = $this->websiteObject; //afkorting
        $start = (int) $start;
        $limit = (int) $limit;
        $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
        $sql.= "`artikel_intro`, `artikel_afbeelding`, `categorie_id`, ";
        $sql.= "`categorie_naam`, `user_id`, `user_display_name`, `artikel_gepind`, ";
        $sql.= "`artikel_verborgen`, `artikel_id` FROM `artikel` ";
        $sql.= "LEFT JOIN `categorie` USING (`categorie_id`) ";
        $sql.= "LEFT JOIN `users` ON `user_id` = `gebruiker_id` ";
        if (!$oWebsite->isLoggedInAsStaff()) {
            // Don't display hidden articles in the list
            $sql.= "WHERE `artikel_verborgen` = 0 ";
            if (!empty($whereClausules)) {
                $sql.= "AND ($whereClausules) ";
            }
        } else {
            if (!empty($whereClausules)) {
                $sql.= "WHERE $whereClausules ";
            }
        }

        // Sorting conditions
        $sql.= "ORDER BY ";
        if ($pinnedTop) {
            $sql.= "`artikel_gepind` DESC, ";
        }
        $sql.= "`artikel_gemaakt` ";
        if (!$oldestTop) {
            $sql.= "DESC ";
        }

        // Limit
        $sql.= "LIMIT $start, $limit";

        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) > 0) {
            while ($row = $oDB->fetchNumeric($result)) {
                $returnValue[] = new Article($row[11], $row);
            }
            return $returnValue;
        } else {
            return array();
        }
    }

    /**
     * Gets a potentially long list of articles with the given year and
     * category. 0 can be used for both the year and category as a wildcard.
     * @param int $year The year to display.
     * @param int $category_id The category id of the articles.
     * @return \Article List of articles.
     */
    public function getArticlesDataArchive($year = -1, $category_id = -1) {
        $year = (int) $year;
        $category_id = (int) $category_id;

        // Set the limit extremely high when viewing the articles of just one
        // year, to prevent strange missing articles at the end of the year.
        $limit = ($year == 0) ? 50 : 500;

        // Add where clausules
        $whereClausules = array();
        if ($year != 0) {
            $whereClausules[] = "YEAR(`artikel_gemaakt`) = $year";
        }
        if ($category_id != 0) {
            $whereClausules[] = "`categorie_id` = $category_id";
        }

        return $this->getArticlesDataUnsafe(join(" AND ", $whereClausules), $limit, 0, false, false);
    }

    /**
     * Gets the latest articles for the given user.
     * @param int $user_id The id of the user.
     * @return \Article List of articles.
     */
    public function getArticlesDataUser($user_id) {
        $user_id = (int) $user_id;
        return $this->getArticlesDataUnsafe("`gebruiker_id` = $user_id", 5, 0, false, false);
    }

    /**
     * Gets all articles, optionally from one or more categories.
     * @param int $category_ids Ids of the categories. Set it to 0 to get articles from all categories.
     * @param int $limit Maximum number of articles to return.
     * @return \Article List of articles.
     */
    public function getArticlesData($category_ids = 0, $limit = 9, $oldestTop = false) {
        $limit = (int) $limit;

        if (!is_array($category_ids)) {
            // Single category
            if ($category_ids == 0) {
                return $this->getArticlesDataUnsafe("", $limit, 0, $oldestTop);
            } else {
                $category_ids = (int) $category_ids;
                return $this->getArticlesDataUnsafe("`categorie_id` = $category_ids", $limit, 0, $oldestTop);
            }
        }

        // Multiple categories
        $pieces = array();
        foreach ($category_ids as $category_id) {
            $category_id = (int) $category_id;
            $pieces[] = "`categorie_id` = $category_id";
        }
        $whereClausule = join(" OR ", $pieces);
        return $this->getArticlesDataUnsafe($whereClausule, $limit, 0, $oldestTop);
    }

    /**
     * Gets an array with how many articles there are in each year in a given
     * category.
     * @param int $category_id The category id. Use 0 to search in all categories.
     * @return array Key is year, value is count.
     */
    public function getArticleCountInYears($category_id = 0) {
        $category_id = (int) $category_id;
        $oDB = $this->databaseObject;

        $sql = "SELECT YEAR(`artikel_gemaakt`), COUNT(*) FROM `artikel` ";
        if ($category_id != 0) {
            $sql.= "WHERE `categorie_id` = $category_id ";
        }
        $sql.= "GROUP BY YEAR(`artikel_gemaakt`)";
        $result = $oDB->query($sql);
        $return_array = array();
        while (list($year, $count) = $oDB->fetchNumeric($result)) {
            $return_array[$year] = $count;
        }
        return $return_array;
    }

    /**
     * Returns how many matches this search will give.
     * @param string $keywordUnprotected Unescaped keyword.
     * @return int The number of matches, or false if an error occured.
     */
    public function getMatchesFor($keywordUnprotected) {
        $oDB = $this->databaseObject;
        $keyword = $oDB->escapeData($keywordUnprotected);

        $sql = "SELECT count(*) FROM `artikel` WHERE `artikel_titel` LIKE '%$keyword%' OR `artikel_intro` LIKE '%$keyword%' OR `artikel_inhoud` LIKE '%$keyword%'";
        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) == 1) {
            $firstRow = $oDB->fetchNumeric($result);
            return (int) $firstRow[0];
        } else {
            return false;
        }
    }

    /**
     * Returns the matches of a search.
     * @param string $keywordUnprotected Unescaped keyword.
     * @param int $articlesPerPage Number of articles on each page.
     * @param int $start The offset of the articles.
     * @return Article[] List of articles.
     */
    public function getArticlesDataMatch($keywordUnprotected, $articlesPerPage, $start) {
        $oDB = $this->databaseObject;
        $keyword = $oDB->escapeData($keywordUnprotected);
        $articlesPerPage = (int) $articlesPerPage;
        $start = (int) $start;
        if ($start < 0) {
            $start = 0;
        }

        return $this->getArticlesDataUnsafe("(`artikel_titel` LIKE '%$keyword%' OR `artikel_intro` LIKE '%$keyword%' OR `artikel_inhoud` LIKE '%$keyword%')", $articlesPerPage, $start);
    }

}

?>