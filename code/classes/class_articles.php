<?php

//Maakt geen gebruik van oude vertaalmethode


class Articles {

    protected $website_object;
    protected $database_object;

    // Article constants

    const OLDEST_TOP = 1;
    const METAINFO = 2;
    const IMAGES = 2;
    const ARCHIVE = 4;

    /**
     * Constructs the article displayer.
     * @param Website $website_object The website to use.
     * @param Database $database_object Not needed, for backwards compability.
     */
    public function __construct(Website $website_object, Database $database_object = null) {
        $this->website_object = $website_object;
        $this->database_object = $database_object;
        if ($this->database_object == null) {
            $this->database_object = $website_object->get_database();
        }
    }

    // DATA FUNCTIONS

    /**
     * Returns an article with an id. Respects page protection. Id is casted.
     * @param int $id The id of the article
     * @return Article|null The article, or null if it isn't found.
     */
    public function get_article_data($id) {
        try {
            return new Article($id, $this->database_object);
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
    protected function get_articles_data_unsafe($where_clausule = "", $limit = 9, $start = 0, $oldest_top = false, $pinned_top = true) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $logged_in_staff = $oWebsite->logged_in_staff() ? 1 : 0; //ingelogd? (nodig om de juiste artikelen op te halen)
        $limit = (int) $limit; //stuk veiliger
        $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
        $sql.= "`artikel_intro`, `artikel_afbeelding`, `categorie_naam`, ";
        $sql.= "`user_id`, `user_display_name`, `artikel_gepind`, ";
        $sql.= "`artikel_verborgen`, `artikel_id` FROM `artikel` ";
        $sql.= "LEFT JOIN `categorie` USING (`categorie_id`) ";
        $sql.= "LEFT JOIN `users` ON `user_id` = `gebruiker_id` ";
        if (!$logged_in_staff) {
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
            while ($row = $oDB->fetch($result)) {
                $return_value[] = new Article($row[10], $row);
            }
            return $return_value;
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
    public function get_articles_data_archive($year = -1, $category_id = -1) {
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

        return $this->get_articles_data_unsafe(join(" AND ", $where_clausules), $limit);
    }

    /**
     * Gets the latest articles for the given user.
     * @param int $user_id The id of the user.
     * @return \Article List of articles.
     */
    public function get_articles_data_user($user_id) {
        $user_id = (int) $user_id;
        return $this->get_articles_data_unsafe("`gebruiker_id` = $user_id", 5, 0, false, false);
    }

    /**
     * Gets all articles, optionally from a category.
     * @param int $category_id Id of the category. Set it to 0 to get articles from all categories.
     * @param int $limit Maximum number of articles to return.
     * @return \Article List of articles.
     */
    public function get_articles_data($category_id = 0, $limit = 9) {
        $category_id = (int) $category_id;
        if ($category_id != 0) {
            return $this->get_articles_data_unsafe("`categorie_id` = $category_id", $limit);
        } else {
            return $this->get_articles_data_unsafe("", $limit);
        }
    }

    /**
     * Gets an array with how many articles there are in each year in a given
     * category.
     * @param int $category_id The category id. Use 0 to search in all categories.
     * @return array Key is year, value is count.
     */
    public function get_article_count_in_years($category_id = 0) {
        $category_id = (int) $category_id;
        $oDB = $this->database_object;

        $sql = "SELECT YEAR(`artikel_gemaakt`), COUNT(*) FROM `artikel` ";
        if ($category_id != 0) {
            $sql.= "WHERE `categorie_id` = $category_id ";
        }
        $sql.= "GROUP BY YEAR(`artikel_gemaakt`)";
        $result = $oDB->query($sql);
        $return_array = array();
        while (list($year, $count) = $oDB->fetch($result)) {
            $return_array[$year] = $count;
        }
        return $return_array;
    }

    // DISPLAY FUNCTIONS FOR INDIVIDUAL ARTICLES

    public function get_article_text_full(Article $article, Comments $oComments = null) {
        // Store some variables for later use
        $oWebsite = $this->website_object;
        $id = (int) $article->id;
        $logged_in = $oWebsite->logged_in_staff();
        $return_value = '';

        if (!$article->hidden || $logged_in) {

            $return_value.= "<h2>" . htmlspecialchars($article->title) . "</h2>";

            // Echo the sidebar
            $return_value.= '<div id="sidebarpagesidebar">';
            if (!empty($article->featured_image))
                $return_value.= '<p><img src="' . htmlspecialchars($article->featured_image) . '" alt="' . htmlspecialchars($article->title) . '" /></p>';
            $return_value.= '<p class="meta">';
            $return_value.= $oWebsite->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $article->created;
            if ($article->last_edited)
                $return_value.= " <br />  " . $oWebsite->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $article->last_edited;
            $return_value.= " <br /> " . $oWebsite->t('main.category') . ": " . $article->category;
            $return_value.= " <br /> " . $oWebsite->t('articles.author') . ': ';
            $return_value.= '<a href="' . $oWebsite->get_url_page("account", $article->author_id) . '">' . $article->author . '</a>';
            if ($article->pinned)
                $return_value.= "<br />" . $oWebsite->t('articles.pinned') . " "; //gepind
            if ($article->hidden)
                $return_value.= "<br />" . $oWebsite->t('articles.hidden'); //verborgen
            if ($logged_in && $article->show_comments)
                $return_value.= "<br />" . $oWebsite->t('comments.allowed'); //reacties
            $return_value.= '</p>';
            if ($logged_in) {
                $return_value.= "<p style=\"clear:both\">";
                $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->get_url_page("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                        '<a class="arrow" href="' . $oWebsite->get_url_page("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
                $return_value.= "</p>";
            }
            if ($article->show_comments) {
                $return_value.= <<<EOT
                        <!-- AddThis Button BEGIN -->
                            <div class="addthis_toolbox addthis_default_style ">
                                <a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
                                <br /><br />
                                <a class="addthis_button_tweet"></a>
                                <br /><br />
                                <a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
                                <br /><br />
                                <a class="addthis_counter addthis_pill_style"></a>
                            </div>
                            <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=xa-50f99223106b78e7"></script>
                        <!-- AddThis Button END -->
EOT;
            }
            $return_value.= '</div>';

            $return_value.= '<div id="sidebarpagecontent">';
            //artikel
            if ($logged_in && $article->hidden)
                $return_value.= '<p class="meta">' . $oWebsite->t('articles.is_hidden') . "<br /> \n" . $oWebsite->t('articles.hidden.explained') . '</p>';
            $return_value.= '<p class="intro">' . htmlspecialchars($article->intro) . '</p>';
            $return_value.= $article->body;
            // Show comments
            if ($article->show_comments && $oComments != null) {
                $comments = $oComments->get_comments_article($id);
                $comment_count = count($comments);

                // Title
                $return_value.= '<h3 class="notable">' . $oWebsite->t("comments.comments");
                if ($comment_count > 0) {
                    $return_value.= ' (' . $comment_count . ')';
                }
                $return_value.= "</h3>\n\n";

                // "No comments found" if needed
                if ($comment_count == 0) {
                    $return_value.= '<p><em>' . $oWebsite->t("comments.no_comments_found") . '</em></p>';
                }

                // Comment add link
                $return_value.= '<p><a class="arrow" href="' . $oWebsite->get_url_page("add_comment", $id) . '">' . $oWebsite->t("comments.add") . "</a></p>";
                // Show comments

                $current_user_id = $oWebsite->get_current_user_id();
                $show_actions = $oWebsite->logged_in_staff();
                foreach ($comments as $comment) {
                    if ($show_actions || $oComments->get_user_id($comment) == $current_user_id) {
                        $return_value.= $oComments->get_comment_html($comment, true);
                    } else {
                        $return_value.= $oComments->get_comment_html($comment, false);
                    }
                }
            }
            $return_value.= '</div>';
        } else {
            $oWebsite->add_error($oWebsite->t('main.article') . ' ' . $oWebsite->t('errors.not_public'));
        }


        return $return_value;
    }

    public function get_article_text_small(Article $article, $show_metainfo, $show_edit_delete_links) {
        $oWebsite = $this->website_object;
        $return_value = "\n\n<div class=\"article_teaser\" onclick=\"location.href='" . $oWebsite->get_url_page("article", $article->id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
        $return_value.= "<h3>" . htmlspecialchars($article->title) . "</h3>\n";
        if ($show_metainfo) {
            $return_value.= '<p class="meta">';
            $return_value.= $oWebsite->t('articles.created') . " " . $article->created . ' - '; //gemaakt op
            if ($article->last_edited) {
                $return_value.= lcfirst($oWebsite->t('articles.last_edited')) . " " . $article->last_edited . '<br />'; //laatst bewerkt op
            }
            // Category
            $return_value.= $oWebsite->t('main.category') . ": " . $article->category;
            // Author
            $return_value.= " - " . $oWebsite->t('articles.author') . ": ";
            $return_value.= '<a href="' . $oWebsite->get_url_page("account", $article->author_id) . '">' . $article->author . "</a>";
            if ($article->pinned) {
                $return_value.= " - " . $oWebsite->t('articles.pinned'); //vastgepind?
            }
            if ($article->hidden) {
                $return_value.= " - " . $oWebsite->t('articles.hidden'); //verborgen?
            }
            $return_value.= '</p>';
        }

        if (!empty($article->featured_image)) {
            $return_value.= '<img src="' . htmlspecialchars($article->featured_image) . '" alt="' . htmlspecialchars($article->title) . '" />';
        }

        $return_value.= '<p class="intro">';
        $return_value.= htmlspecialchars($article->intro);
        $return_value.= '</p> <p class="article_teaser_links">';
        $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("article", $article->id) . '">' . $oWebsite->t('main.read') . '</a>';
        if ($show_edit_delete_links) {
            $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->get_url_page("edit_article", $article->id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                    '<a class="arrow" href="' . $oWebsite->get_url_page("delete_article", $article->id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
        }
        $return_value.= "</p>";

        $return_value.= '<p style="clear:both"></p>';

        $return_value.= "</div>";

        return $return_value;
    }

    public function get_article_text_listentry(Article $article, $display_images = false) {
        $return_value = '<li><a href="' . $this->website_object->get_url_page("article", $article->id) . '"';
        $return_value.= 'title="' . $article->intro . '">';
        if ($display_images && !empty($article->featured_image)) {
            $return_value.= '<div class="linklist_icon_image"><img src="' . htmlspecialchars($article->featured_image) . '" alt="' . htmlspecialchars($article->title) . '" /></div>';
        }
        $return_value.= "<span>" . htmlspecialchars($article->title) . "</span></a></li>\n";
        return $return_value;
    }

    // DISPLAY FUNCTIONS FOR MULTIPLE ARTICLES

    public function get_articles_list_category($categories, $limit = 9, $options = 0) {
        $oWebsite = $this->website_object;

        // Should hidden articles be shown?
        $logged_in_staff = $oWebsite->logged_in_staff();

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
        $result = $this->get_articles_data_unsafe($where_clausule, $limit, 0, $options & self::OLDEST_TOP);

        //verwerk resultaten
        $main_category_id = (count($categories) == 1) ? $categories[0] : 0;
        if ($result) {
            $return_value = '';

            if ($logged_in_staff) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $main_category_id)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
            }

            // Display articles
            foreach ($result as $article) {
                $return_value .= $this->get_article_text_small($article, $options & self::METAINFO, $logged_in_staff);
            }

            if ($logged_in_staff) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $main_category_id)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
            }

            // Archive link
            if ($options & self::ARCHIVE) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("archive", $main_category_id) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>';
            }

            return $return_value;
        } else {
            $return_value = '<p><em>' . $oWebsite->t("errors.nothing_found") . "</em></p>";
            if ($logged_in_staff) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $main_category_id)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>'; //maak nieuw artikel
            }
            return $return_value;
        }
    }

    public function get_articles_bullet_list($categories, $limit = 9, $options = 0) {
        $oWebsite = $this->website_object;

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
        $result = $this->get_articles_data_unsafe($where_clausule, $limit, 0, $options & self::OLDEST_TOP);
        $return_value = '<ul class="linklist">';
        foreach ($result as $article) {
            $return_value.= $this->get_article_text_listentry($article, $options & self::METAINFO);
        }
        $return_value .= "</ul>\n";

        // Add create new article link
        $main_category_id = (count($categories) == 1) ? $categories[0] : 0;
        if ($oWebsite->logged_in_staff()) {
            $return_value .= '<p><a class="arrow" href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $main_category_id));
            $return_value .= '">' . $oWebsite->t("articles.create") . "</a></p>\n";
        }

        // Archive link
        if ($options & self::ARCHIVE) {
            $return_value.= '<p><a href="' . $oWebsite->get_url_page("archive", $main_category_id) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>';
        }

        return $return_value;
    }

    public function get_articles_search($keywordunprotected, $page) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $logged_in_staff = $oWebsite->logged_in_staff();
        $articles_per_page = 5; //vijf resultaten per pagina
        $start = ($page - 1) * $articles_per_page;

        $keyword = $oDB->escape_data($keywordunprotected); //maak zoekwoord veilig voor in gebruik query;

        if (strlen($keyword) < 3) {
            return ''; //moet vooraf al op worden gecontroleerd
        }


        //aantal ophalen
        $articlecount_sql = "SELECT count(*) FROM `artikel` WHERE artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%' ";
        $articlecount_result = $oDB->query($articlecount_sql);
        $articlecount_resultrow = $oDB->fetch($articlecount_result);
        $resultcount = (int) $articlecount_resultrow[0];
        unset($articlecount_sql, $articlecount_result, $articlecount_resultrow);

        //resultaat ophalen
        $results = $this->get_articles_data_unsafe("(artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%')", $articles_per_page, $start);

        //artikelen ophalen
        $return_value = '';

        if ($results) {
            //Geef aantal resultaten weer
            $return_value.= ($resultcount == 1) ? "<p>" . $oWebsite->t('articles.search.result_found') . "</p>" : "<p>" . $oWebsite->t_replaced('articles.search.results_found', $resultcount) . "</p>";

            //paginanavigatie
            $return_value.= '<p class="lijn">';
            if ($page > 1)
                $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("search", 0, array("searchbox" => $keywordunprotected, "page" => $page - 1)) . '">' . $oWebsite->t('articles.page.previous') . '</a> '; //vorige pagina
            $return_value.= str_replace("\$", ceil($resultcount / $articles_per_page), str_replace("#", $page, $oWebsite->t('articles.page.current'))); //pagina X van Y
            if ($resultcount > $start + $articles_per_page)
                $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("search", 0, array("searchbox" => $keywordunprotected, "page" => $page + 1)) . '">' . $oWebsite->t('articles.page.next') . '</a>'; //volgende pagina
            $return_value.= '</p>';

            foreach ($results as $result) {
                $return_value .= $this->get_article_text_small($result, true, $logged_in_staff);
            }

            //paginanavigatie
            $return_value.= '<p class="lijn">';
            if ($page > 1)
                $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("search", 0, array("searchbox" => $keywordunprotected, "page" => $page - 1)) . '">' . $oWebsite->t('articles.page.previous') . '</a> '; //vorige pagina
            $return_value.= str_replace("\$", ceil($resultcount / $articles_per_page), str_replace("#", $page, $oWebsite->t('articles.page.current'))); //pagina X van Y
            if ($resultcount > $start + $articles_per_page)
                $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("search", 0, array("searchbox" => $keywordunprotected, "page" => $page + 1)) . '">' . $oWebsite->t('articles.page.next') . '</a>'; //volgende pagina
            $return_value.= '</p>';
        }
        else {
            $return_value.='<p><em>' . $oWebsite->t('articles.search.no_results_found') . '</em></p>'; //niets gevonden
        }

        return $return_value;
    }

}

/**
 * Represents a single article. All data is raw HTML, handle with extreme
 * caution (read: htmlspecialchars)
 */
class Article {

    public $id;
    public $title;
    public $created;
    public $last_edited;
    public $intro;
    public $featured_image;
    public $category;
    public $author;
    public $author_id;
    public $pinned;
    public $hidden;
    public $body;
    public $show_comments;

    public function __construct($id, $data) {
        $id = (int) $id;
        $this->id = $id;
        if ($data instanceof Database) {
            // Fetch from database
            $oDatabase = $data;
            $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
            $sql.= "`artikel_intro`, `artikel_afbeelding`, ";
            $sql.= "`categorie_naam`, `user_id`, `user_display_name`, `artikel_gepind`, ";
            $sql.= "`artikel_verborgen`, `artikel_inhoud`, `artikel_reacties` FROM `artikel` ";
            $sql.= "LEFT JOIN `categorie` USING ( `categorie_id` ) ";
            $sql.= "LEFT JOIN `users` ON `user_id` = `gebruiker_id` ";
            $sql.= "WHERE artikel_id = {$this->id} ";
            $result = $oDatabase->query($sql);
            if ($result && $oDatabase->rows($result) >= 1) {
                $data = $oDatabase->fetch($result);
            } else {
                throw new InvalidArgumentException("Article not found");
            }
        }
        // Set all variables
        if (is_array($data) && count($data) >= 10) {
            $this->title = $data[0];
            $this->created = $data[1];
            $this->last_edited = $data[2];
            $this->intro = $data[3];
            $this->featured_image = $data[4];
            $this->category = $data[5];
            $this->author_id = (int) $data[6];
            $this->author = $data[7];
            $this->pinned = (boolean) $data[8];
            $this->hidden = (boolean) $data[9];
            if (count($data) >= 12) {
                $this->body = $data[10];
                $this->show_comments = (boolean) $data[11];
            }
        }
    }

}

?>