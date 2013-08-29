<?php

//Maakt geen gebruik van oude vertaalmethode


class Articles {

    protected $websiteObject;
    protected $databaseObject;

    // Article constants

    const OLDEST_TOP = 1;
    const METAINFO = 2;
    const IMAGES = 2;
    const ARCHIVE = 4;

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
     * $where_clausule, which can be vulnerable to SQL injection.
     * @param type $where_clausule Everything that should come after WHERE.
     * @param type $limit Limit the number of rows.
     * @param type $start Start position of the limit.
     * @return \Article Array of Articles.
     */
    protected function getArticlesDataUnsafe($where_clausule = "", $limit = 9, $start = 0, $oldest_top = false, $pinned_top = true) {
        $oDB = $this->databaseObject; //afkorting
        $oWebsite = $this->websiteObject; //afkorting
        $loggedInStaff = $oWebsite->isLoggedInAsStaff() ? 1 : 0; //ingelogd? (nodig om de juiste artikelen op te halen)
        $limit = (int) $limit; //stuk veiliger
        $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
        $sql.= "`artikel_intro`, `artikel_afbeelding`, `categorie_id`, ";
        $sql.= "`categorie_naam`, `user_id`, `user_display_name`, `artikel_gepind`, ";
        $sql.= "`artikel_verborgen`, `artikel_id` FROM `artikel` ";
        $sql.= "LEFT JOIN `categorie` USING (`categorie_id`) ";
        $sql.= "LEFT JOIN `users` ON `user_id` = `gebruiker_id` ";
        if (!$loggedInStaff) {
            $sql.= "WHERE `artikel_verborgen` = 0 ";
            if (!empty($where_clausule)) {
                $sql.= "AND $where_clausule ";
            }
        } else {
            if (!empty($where_clausule)) {
                $sql.= "WHERE $where_clausule ";
            }
        }

        // Sorting conditions
        $sql.= "ORDER BY ";
        if ($pinned_top) {
            $sql.= "`artikel_gepind` DESC, ";
        }
        $sql.= "`artikel_gemaakt` ";
        if (!$oldest_top) {
            $sql.= "DESC ";
        }

        // Limit
        $sql.= "LIMIT $start , $limit";

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
        $where_clausules = array();
        if ($year != 0) {
            $where_clausules[] = "YEAR(`artikel_gemaakt`) = $year";
        }
        if ($category_id != 0) {
            $where_clausules[] = "`categorie_id` = $category_id";
        }

        return $this->getArticlesDataUnsafe(join(" AND ", $where_clausules), $limit);
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
     * Gets all articles, optionally from a category.
     * @param int $category_id Id of the category. Set it to 0 to get articles from all categories.
     * @param int $limit Maximum number of articles to return.
     * @return \Article List of articles.
     */
    public function getArticlesData($category_id = 0, $limit = 9) {
        $category_id = (int) $category_id;
        if ($category_id != 0) {
            return $this->getArticlesDataUnsafe("`categorie_id` = $category_id", $limit);
        } else {
            return $this->getArticlesDataUnsafe("", $limit);
        }
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

    // DISPLAY FUNCTIONS FOR INDIVIDUAL ARTICLES

    public function getArticleTextFull(Article $article, Comments $oComments = null) {
        // Store some variables for later use
        $oWebsite = $this->websiteObject;
        $id = (int) $article->id;
        $loggedIn = $oWebsite->isLoggedInAsStaff();
        $returnValue = '';

        if (!$article->hidden || $loggedIn) {

            $returnValue.= "<h2>" . htmlSpecialChars($article->title) . "</h2>";

            // Echo the sidebar
            $returnValue.= '<div id="sidebar_page_sidebar">';
            if (!empty($article->featuredImage))
                $returnValue.= '<p><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" /></p>';
            $returnValue.= '<p class="meta">';
            $returnValue.= $oWebsite->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $article->created;
            if ($article->lastEdited)
                $returnValue.= " <br />  " . $oWebsite->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $article->lastEdited;
            $returnValue.= " <br /> " . $oWebsite->t('main.category') . ": " . $article->category;
            $returnValue.= " <br /> " . $oWebsite->t('articles.author') . ': ';
            $returnValue.= '<a href="' . $oWebsite->getUrlPage("account", $article->authorId) . '">' . $article->author . '</a>';
            if ($article->pinned)
                $returnValue.= "<br />" . $oWebsite->t('articles.pinned') . " "; //gepind
            if ($article->hidden)
                $returnValue.= "<br />" . $oWebsite->t('articles.hidden'); //verborgen
            if ($loggedIn && $article->showComments)
                $returnValue.= "<br />" . $oWebsite->t('comments.allowed'); //reacties
            $returnValue.= '</p>';
            if ($loggedIn) {
                $returnValue.= "<p style=\"clear:both\">";
                $returnValue.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                        '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
                $returnValue.= "</p>";
            }
            if ($article->showComments) {
                $returnValue.= <<<EOT
                        <!-- AddThis Button BEGIN -->
                            <div class="addthis_toolbox addthis_default_style ">
                                <a class="addthis_button_facebook_like" fb:like:layout="buttonCount"></a>
                                <br /><br />
                                <a class="addthis_button_tweet"></a>
                                <br /><br />
                                <a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
                                <br /><br />
                                <a class="addthisCounter addthis_pill_style"></a>
                            </div>
                            <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=xa-50f99223106b78e7"></script>
                        <!-- AddThis Button END -->
EOT;
            }
            $returnValue.= '</div>';

            $returnValue.= '<div id="sidebar_page_content">';
            //artikel
            if ($loggedIn && $article->hidden)
                $returnValue.= '<p class="meta">' . $oWebsite->t('articles.is_hidden') . "<br /> \n" . $oWebsite->t('articles.hidden.explained') . '</p>';
            $returnValue.= '<p class="intro">' . htmlSpecialChars($article->intro) . '</p>';
            $returnValue.= $article->body;
            // Show comments
            if ($article->showComments && $oComments != null) {
                $comments = $oComments->get_comments_article($id);
                $commentCount = count($comments);

                // Title
                $returnValue.= '<h3 class="notable">' . $oWebsite->t("comments.comments");
                if ($commentCount > 0) {
                    $returnValue.= ' (' . $commentCount . ')';
                }
                $returnValue.= "</h3>\n\n";

                // "No comments found" if needed
                if ($commentCount == 0) {
                    $returnValue.= '<p><em>' . $oWebsite->t("comments.no_comments_found") . '</em></p>';
                }

                // Comment add link
                $returnValue.= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("add_comment", $id) . '">' . $oWebsite->t("comments.add") . "</a></p>";
                // Show comments

                $current_user_id = $oWebsite->getCurrentUserId();
                $show_actions = $oWebsite->isLoggedInAsStaff();
                foreach ($comments as $comment) {
                    if ($show_actions || $oComments->get_user_id($comment) == $current_user_id) {
                        $returnValue.= $oComments->get_comment_html($comment, true);
                    } else {
                        $returnValue.= $oComments->get_comment_html($comment, false);
                    }
                }
            }
            $returnValue.= '</div>';
        } else {
            $oWebsite->addError($oWebsite->t('main.article') . ' ' . $oWebsite->t('errors.not_public'));
        }


        return $returnValue;
    }

    public function getArticleTextSmall(Article $article, $show_metainfo, $show_edit_delete_links) {
        $oWebsite = $this->websiteObject;
        $returnValue = "\n\n<div class=\"article_teaser\" onclick=\"location.href='" . $oWebsite->getUrlPage("article", $article->id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
        $returnValue.= "<h3>" . htmlSpecialChars($article->title) . "</h3>\n";
        if ($show_metainfo) {
            $returnValue.= '<p class="meta">';
            $returnValue.= $oWebsite->t('articles.created') . " " . $article->created . ' - '; //gemaakt op
            if ($article->lastEdited) {
                $returnValue.= lcFirst($oWebsite->t('articles.last_edited')) . " " . $article->lastEdited . '<br />'; //laatst bewerkt op
            }
            // Category
            $returnValue.= $oWebsite->t('main.category') . ": " . $article->category;
            // Author
            $returnValue.= " - " . $oWebsite->t('articles.author') . ": ";
            $returnValue.= '<a href="' . $oWebsite->getUrlPage("account", $article->authorId) . '">' . $article->author . "</a>";
            if ($article->pinned) {
                $returnValue.= " - " . $oWebsite->t('articles.pinned'); //vastgepind?
            }
            if ($article->hidden) {
                $returnValue.= " - " . $oWebsite->t('articles.hidden'); //verborgen?
            }
            $returnValue.= '</p>';
        }

        if (!empty($article->featuredImage)) {
            $returnValue.= '<img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" />';
        }

        $returnValue.= '<p class="intro">';
        $returnValue.= htmlSpecialChars($article->intro);
        $returnValue.= '</p> <p class="article_teaser_links">';
        $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlPage("article", $article->id) . '">' . $oWebsite->t('main.read') . '</a>';
        if ($show_edit_delete_links) {
            $returnValue.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", $article->id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                    '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_article", $article->id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
        }
        $returnValue.= "</p>";

        $returnValue.= '<p style="clear:both"></p>';

        $returnValue.= "</div>";

        return $returnValue;
    }

    public function get_article_text_listentry(Article $article, $display_images = false) {
        $returnValue = '<li><a href="' . $this->websiteObject->getUrlPage("article", $article->id) . '"';
        $returnValue.= 'title="' . $article->intro . '">';
        if ($display_images && !empty($article->featuredImage)) {
            $returnValue.= '<div class="linklist_icon_image"><img src="' . htmlSpecialChars($article->featuredImage) . '" alt="' . htmlSpecialChars($article->title) . '" /></div>';
        }
        $returnValue.= "<span>" . htmlSpecialChars($article->title) . "</span></a></li>\n";
        return $returnValue;
    }

    // DISPLAY FUNCTIONS FOR MULTIPLE ARTICLES

    public function get_articles_list_category($categories, $limit = 9, $options = 0) {
        $oWebsite = $this->websiteObject;

        // Should hidden articles be shown?
        $loggedInStaff = $oWebsite->isLoggedInAsStaff();

        // Categories can also be a single number, so convert
        if (!is_array($categories)) {
            $category_id = $categories;
            unset($categories);
            $categories[0] = $category_id;
        }

        // Build the query
        $where_clausule = '';
        foreach ($categories as $i => $category_id) {
            $category_id = (int) $category_id; //beveiliging

            if ($i > 0) {
                $where_clausule.=" OR ";
            }
            $where_clausule.="categorie_id = $category_id";
        }

        //haal resultaten op
        $result = $this->getArticlesDataUnsafe($where_clausule, $limit, 0, $options & self::OLDEST_TOP);

        //verwerk resultaten
        $main_category_id = (count($categories) == 1) ? $categories[0] : 0;
        if ($result) {
            $returnValue = '';

            if ($loggedInStaff) {
                $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("edit_article", 0, array("article_category" => $main_category_id)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
            }

            // Display articles
            foreach ($result as $article) {
                $returnValue .= $this->getArticleTextSmall($article, $options & self::METAINFO, $loggedInStaff);
            }

            if ($loggedInStaff) {
                $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("edit_article", 0, array("article_category" => $main_category_id)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
            }

            // Archive link
            if ($options & self::ARCHIVE) {
                $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("archive", $main_category_id) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>';
            }

            return $returnValue;
        } else {
            $returnValue = '<p><em>' . $oWebsite->t("errors.nothing_found") . "</em></p>";
            if ($loggedInStaff) {
                $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("edit_article", 0, array("article_category" => $main_category_id)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>'; //maak nieuw artikel
            }
            return $returnValue;
        }
    }

    public function get_articles_bullet_list($categories, $limit = 9, $options = 0) {
        $oWebsite = $this->websiteObject;

        if (!is_array($categories)) { // Create array if needed
            $category_id = $categories;
            unset($categories);
            $categories[0] = $category_id;
        }

        // Build query
        $where_clausule = "";
        foreach ($categories as $count => $category_id) {
            $category_id = (int) $category_id; // Security
            if ($count > 0)
                $where_clausule.=" OR ";
            $where_clausule.="`categorie_id` = $category_id";
        }

        // Build article list
        $result = $this->getArticlesDataUnsafe($where_clausule, $limit, 0, $options & self::OLDEST_TOP);
        $returnValue = '<ul class="linklist">';
        foreach ($result as $article) {
            $returnValue.= $this->get_article_text_listentry($article, $options & self::METAINFO);
        }
        $returnValue .= "</ul>\n";

        // Add create new article link
        $main_category_id = (count($categories) == 1) ? $categories[0] : 0;
        if ($oWebsite->isLoggedInAsStaff()) {
            $returnValue .= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("edit_article", 0, array("article_category" => $main_category_id));
            $returnValue .= '">' . $oWebsite->t("articles.create") . "</a></p>\n";
        }

        // Archive link
        if ($options & self::ARCHIVE) {
            $returnValue.= '<p><a href="' . $oWebsite->getUrlPage("archive", $main_category_id) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>';
        }

        return $returnValue;
    }

    public function get_articles_search($keywordunprotected, $page) {
        $oDB = $this->databaseObject; //afkorting
        $oWebsite = $this->websiteObject; //afkorting
        $loggedInStaff = $oWebsite->isLoggedInAsStaff();
        $articles_per_page = 5; //vijf resultaten per pagina
        $start = ($page - 1) * $articles_per_page;

        $keyword = $oDB->escapeData($keywordunprotected); //maak zoekwoord veilig voor in gebruik query;

        if (strLen($keyword) < 3) {
            return ''; //moet vooraf al op worden gecontroleerd
        }


        //aantal ophalen
        $articlecount_sql = "SELECT count(*) FROM `artikel` WHERE artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%' ";
        $articlecount_result = $oDB->query($articlecount_sql);
        $articlecount_resultrow = $oDB->fetchNumeric($articlecount_result);
        $resultcount = (int) $articlecount_resultrow[0];
        unset($articlecount_sql, $articlecount_result, $articlecount_resultrow);

        //resultaat ophalen
        $results = $this->getArticlesDataUnsafe("(artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%')", $articles_per_page, $start);

        //artikelen ophalen
        $returnValue = '';

        if ($results) {
            //Geef aantal resultaten weer
            $returnValue.= ($resultcount == 1) ? "<p>" . $oWebsite->t('articles.search.result_found') . "</p>" : "<p>" . $oWebsite->tReplaced('articles.search.results_found', $resultcount) . "</p>";

            //paginanavigatie
            $returnValue.= '<p class="lijn">';
            if ($page > 1)
                $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordunprotected, "page" => $page - 1)) . '">' . $oWebsite->t('articles.page.previous') . '</a> '; //vorige pagina
            $returnValue.= str_replace("\$", ceil($resultcount / $articles_per_page), str_replace("#", $page, $oWebsite->t('articles.page.current'))); //pagina X van Y
            if ($resultcount > $start + $articles_per_page)
                $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordunprotected, "page" => $page + 1)) . '">' . $oWebsite->t('articles.page.next') . '</a>'; //volgende pagina
            $returnValue.= '</p>';

            foreach ($results as $result) {
                $returnValue .= $this->getArticleTextSmall($result, true, $loggedInStaff);
            }

            //paginanavigatie
            $returnValue.= '<p class="lijn">';
            if ($page > 1)
                $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordunprotected, "page" => $page - 1)) . '">' . $oWebsite->t('articles.page.previous') . '</a> '; //vorige pagina
            $returnValue.= str_replace("\$", ceil($resultcount / $articles_per_page), str_replace("#", $page, $oWebsite->t('articles.page.current'))); //pagina X van Y
            if ($resultcount > $start + $articles_per_page)
                $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("search", 0, array("searchbox" => $keywordunprotected, "page" => $page + 1)) . '">' . $oWebsite->t('articles.page.next') . '</a>'; //volgende pagina
            $returnValue.= '</p>';
        }
        else {
            $returnValue.='<p><em>' . $oWebsite->t('articles.search.no_results_found') . '</em></p>'; //niets gevonden
        }

        return $returnValue;
    }

}

?>