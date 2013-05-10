<?php

//Maakt geen gebruik van oude vertaalmethode


class Articles {

    protected $website_object;
    protected $database_object;
    protected $category_object; //nodig voor get_articles_archive

    function __construct(Website $website_object, Database $database_object, Categories $category_object = null) {
        $this->website_object = $website_object;
        $this->database_object = $database_object;
        $this->category_object = $category_object;
    }

    // DATA FUNCTIONS

    /**
     * Returns an article with an id. Respects page protection. Id is casted.
     * @param int $id The id of the article
     * @return Article2|null The article, or null if it isn't found.
     */
    function get_article($id) {
        return new Article($id, $this->database_object);
    }

    /**
     * Gets more articles at once. Protected because of it's dangerous
     * $where_clausule, which can be vulnerable to SQL injection.
     * @param type $where_clausule Everything that should come after WHERE.
     * @param type $limit Limit the number of rows.
     * @param type $start Start position of the limit.
     * @return array Array of Articles.
     */
    protected function get_articles($where_clausule = "", $limit = 9, $start = 0) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $logged_in_staff = $oWebsite->logged_in_staff() ? 1 : 0; //ingelogd? (nodig om de juiste artikelen op te halen)
        $limit = (int) $limit; //stuk veiliger
        $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
        $sql.= "`artikel_intro`, `artikel_afbeelding`, `categorie_naam`, ";
        $sql.= "`gebruiker_naam`, `artikel_gepind`, `artikel_verborgen`, ";
        $sql.= "`artikel_id` FROM `artikel` ";
        $sql.= "LEFT JOIN `categorie` USING (`categorie_id`) ";
        $sql.= "LEFT JOIN `gebruikers` USING (`gebruiker_id`) ";
        if (!$logged_in_staff) {
            $sql.= "WHERE artikel_verborgen = 0 ";
            if (!empty($where_clausule)) {
                $sql.= "AND $where_clausule ";
            }
        } else {
            if (!empty($where_clausule)) {
                $sql.= "WHERE $where_clausule ";
            }
        }

        $sql.= "ORDER BY artikel_gepind DESC, artikel_gemaakt DESC ";
        $sql.= "LIMIT $start , $limit";

        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) > 0) {
            while ($row = $oDB->fetch($result)) {
                $return_value[] = new Article($row[9], $row);
            }
            return $return_value;
        } else {
            return array();
        }
    }

    // DISPLAY FUNCTIONS FOR INDIVIDUAL ARTICLES

    function get_article_text_full(Article $article, Comments $oComments = null) {
        // Store some variables for later use
        $oWebsite = $this->website_object;
        $id = (int) $article->id;
        $logged_in = $oWebsite->logged_in_staff();
        $return_value = '';

        if ($article->exists) {
            if (!$article->hidden || $logged_in) {

                $return_value.= "<h2>{$article->title}</h2>";

                // Echo the sidebar
                $return_value.= '<div id="sidebarpagesidebar">';
                if (!empty($article->featured_image))
                    $return_value.= "<p><img src=\"{$article->featured_image}\" alt=\"{$article->title}\" /></p>";
                $return_value.= '<p class="meta">';
                $return_value.= $oWebsite->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $article->created;
                if ($article->last_edited)
                    $return_value.= " <br />  " . $oWebsite->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $article->last_edited . "";
                $return_value.= " <br /> " . $oWebsite->t('main.category') . ": " . $article->category;
                $return_value.= " <br /> " . $oWebsite->t('articles.author') . ": $article->author"; //auteur
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
                $return_value.= '<p class="intro">' . $article->intro . '</p>';
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
        } else {
            $oWebsite->add_error($oWebsite->t('main.article') . ' ' . $oWebsite->t('errors.not_found'));
        }

        return $return_value;
    }

    function get_article_text_small(Article $article, $show_metainfo, $show_edit_delete_links) {
        $oWebsite = $this->website_object;
        $return_value = "\n\n<div class=\"artikelintro\" onclick=\"location.href='" . $oWebsite->get_url_page("article", $article->id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
        $return_value.= "<h3>" . $article->title . "</h3>\n";
        if ($show_metainfo) {
            $return_value.= '<p class="meta">';
            $return_value.= $oWebsite->t('articles.created') . " " . $article->created . ' - '; //gemaakt op
            if ($article->last_edited) {
                $return_value.= lcfirst($oWebsite->t('articles.last_edited')) . " " . $article->last_edited . '<br />'; //laatst bewerkt op
            }
            $return_value.= $oWebsite->t('main.category') . ": " . $article->category; //categorie
            $return_value.= " - " . $oWebsite->t('articles.author') . ": $article->author"; //auteur
            if ($article->pinned) {
                $return_value.= " - " . $oWebsite->t('articles.pinned'); //vastgepind?
            }
            if ($article->hidden) {
                $return_value.= " - " . $oWebsite->t('articles.hidden'); //verborgen?
            }
            $return_value.= '</p>';
        }

        if (!empty($article->featured_image)) {
            $return_value.= "<img src=\"$article->featured_image\" alt=\"{$article->title}\" />";
        }

        $return_value.= '<p class="intro ';
        if (!empty($article->featured_image)) {
            $return_value.= 'introsmall'; //maak introtekst kleiner
        }
        $return_value.= '">';

        $return_value.= $article->intro;

        $return_value.= "<br />";
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

    function get_article_text_listentry(Article $article) {
        $return_value = '<li><a href="' . $this->website_object->get_url_page("article", $article->id) . '"';
        $return_value.= 'title="' . $article->intro . '">' . $article->title . "</a></li>\n";
        return $return_value;
    }

    // DISPLAY FUNCTIONS FOR MULTIPLE ARTICLES

    function get_articles_list_category($categories, $show_metainfo = false, $limit = 9) {
        $oWebsite = $this->website_object;

        // Should hidden articles be shown?
        $logged_in_staff = $oWebsite->logged_in_staff();

        // Categories can also be a single number, so convert
        if (!is_array($categories)) {
            $category_id = $categories;
            unset($categories);
            $categories[0] = $category_id;
        }

        // Build the 
        $where_clausule = '';
        foreach ($categories as $i => $category_id) {
            $category_id = (int) $category_id; //beveiliging

            if ($i > 0) {
                $where_clausule.=" OR ";
            }
            $where_clausule.="categorie_id = $category_id";
        }

        //haal resultaten op
        $result = $this->get_articles($where_clausule, $limit);

        //verwerk resultaten
        $first_category = $categories[0];
        if ($result) {
            $return_value = '';

            if ($logged_in_staff) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $first_category)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
            }

            // Display articles
            foreach ($result as $article) {
                $return_value .= $this->get_article_text_small($article, $show_metainfo, $logged_in_staff);
            }

            if ($logged_in_staff) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $first_category)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
            }
            $return_value.='<p><a href="' . $oWebsite->get_url_page("archive", 0, array("year" => date('Y'), "cat" => $first_category)) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>'; //archief
            return $return_value;
        } else {
            $return_value = '<p><em>' . $oWebsite->t("errors.nothing_found") . "</em></p>";
            if ($logged_in_staff) {
                $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $first_category)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>'; //maak nieuw artikel
            }
            return $return_value;
        }
    }

    function get_articles_small_list($categories, $limit = 9) {
        if (!is_array($categories)) { // Create array if needed
            $category_id = $categories;
            unset($categories);
            $categories[0] = $category_id;
        }

        $where_clausule = "";
        foreach ($categories as $count => $category_id) {
            $category_id = (int) $category_id; // Security
            if ($count > 0)
                $where_clausule.=" OR ";
            $where_clausule.="`categorie_id` = $category_id";
        }

        $result = $this->get_articles($where_clausule, $limit);
        $return_value = '<ul class="linklist">';
        foreach ($result as $article) {
            $return_value.= $this->get_article_text_listentry($article);
        }
        return $return_value . "</ul>";
    }

    function get_articles_search($keywordunprotected, $page) {
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
        $results = $this->get_articles("(artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%')", $articles_per_page, $start);

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

    // MISCELLANEOUS FUNCTIONS

    function get_articles_archive($year = 0, $cat_display = 0) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $oCats = $this->category_object; //afkorting

        $events = false;

        $logged_in = $oWebsite->logged_in_staff(); //ingelogd? (nodig voor links om te bewerken)
        $return_value = '';

        //CATEGORIE BEPALEN (bepaal welke categorieen moeten worden weergegeven)
        $cat_display = (int) $cat_display;

        //where clausule voor query
        $where = "`categorie_id`=$cat_display";
        if ($cat_display == 0) {
            $where = "1"; //alles
        }
        //lijstje met categorie�n maken
        $cat_list = $oCats->get_categories();

        //JAREN BEPALEN
        $startyear = date('Y'); //vooralsnog, als de database zometeen iets anders meldt, wordt dat jaar gebruikt
        $endyear = date('Y');

        $year = (int) $year;
        if ($year == 0) {
            $year = (int) date('Y');
        }


        $sql = "SELECT YEAR(`artikel_gemaakt`) FROM artikel WHERE $where ORDER BY `artikel_gemaakt` LIMIT 0,1";
        $result = $oDB->query($sql);

        if ($result) {
            $result = $oDB->fetch($result);
            $result = $result[0];
            if ($result > 0)
                $startyear = $result;
        }

        //MENUBALK WEERGEVEN
        $return_value.= '<p class="lijn">';
        //categorie
        $return_value .= '<span style="width:5.5em;display:block;float:left">' . $oWebsite->t('main.category') . ':</span> ';
        if ($cat_display == 0) {
            $return_value .= '<strong>' . $oWebsite->t('categories.all') . '</strong> ';
        } else {
            $return_value .= '<a href="' . $oWebsite->get_url_page("archive", 0, array("year" => $year)) . '">' . $oWebsite->t('categories.all') . '</a> ';
        }
        foreach ($cat_list as $id => $name) {
            if ($id == $cat_display) {
                $return_value .= "<strong>$name</strong>\n";
            } else {
                $return_value .= '<a href="' . $oWebsite->get_url_page("archive", 0, array("year" => $year, "cat" => $id)) . '">' . $name . "</a> \n";
            }
        }

        //jaren
        $return_value .= '<br /><span style="width:5.5em;display:block;float:left">' . $oWebsite->t('main.year') . ':</span>';
        for ($i = $startyear; $i <= $endyear; $i++) {
            if ($i == $year) {
                $return_value .= '<strong>' . $i . '</strong> ';
            } else {
                $return_value .= '<a href="' . $oWebsite->get_url_page("archive", 0, array("year" => $i, "cat" => $cat_display)) . '">' . $i . '</a> ';
            }
        }

        $return_value.= '</p>';

        //RESULTATEN OPHALEN
        $sql = "SELECT artikel_id, artikel_titel, categorie_naam, MONTH(artikel_gemaakt) FROM `artikel` LEFT JOIN `categorie` USING ( categorie_id ) WHERE $where AND YEAR(`artikel_gemaakt`)=$year ORDER BY `artikel_gemaakt`";
        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) > 0) {
            $lastmonth = -1;
            //geef de tabel weer
            while (list($id, $title, $category, $month) = $oDB->fetch($result)) {
                if ($month != $lastmonth) { //nieuwe maand, start nieuwe alinea
                    if (isset($tablestarted))
                        $return_value.="</table>\n";

                    $return_value.="<br /><table style=\"width:100%;background:white\">\n";
                    $return_value.="<tr><th colspan=\"3\">" . ucfirst(strftime('%B', mktime(0, 0, 0, $month, 1, $year))) . "</th></tr>\n";
                    $lastmonth = $month;
                    $tablestarted = true;
                }


                if ($logged_in) {
                    $return_value.='<tr> <td style="width:71%"> <a class="arrow" href="' . $oWebsite->get_url_page("article", $id) . '">' . $title . '</a> </td>'; //titel invoegen
                    $return_value.= '<td style=\"width:16%\"> <a class="arrow" href="' . $oWebsite->get_url_page("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp; ' . //bewerken
                            '<a class="arrow" href="' . $oWebsite->get_url_page("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a> </td>'; //verwijderen
                } else {
                    $return_value.='<tr><td style="width:87%" colspan="2"> <a class="arrow" href="' . $oWebsite->get_url_page("article", $id) . '">' . $title . '</a> </td>'; //title invoegen
                }
                $return_value.="<td style=\"width:12%\">$category</td>\n";
                $return_value.="</tr>\n";
            }
            $return_value.='</table>';
        } else { //niets gevonden, geef suggesties
            if ($year == date('Y') && $cat_display == 0) { //in alle categorieen van dit jaar is niks gevonden
                $return_value.='<p>' . str_replace("#", $year, $oWebsite->t('articles.not_found.year')) . ' <a href="' . $oWebsite->get_url_page("archive", 0, array("year" => date('Y') - 1)) . '">' . str_replace("#", date('Y') - 1, $oWebsite->t('categories.search.all_in_year')) . '</a>.</p>';
            } else {
                $return_value.='<p>' . str_replace("#", $year, $oWebsite->t('articles.not_found.year_in_category')) . ' <a href="' . $oWebsite->get_url_page("archive", 0, array("year" => date('Y'))) . '">' . str_replace("#", date('Y'), $oWebsite->t('categories.search.all_in_year')) . '</a>.</p>';
            }
        }
        return $return_value;
    }

}

/**
 * Represents a single article
 */
class Article {

    public $exists;
    public $id;
    public $title;
    public $created;
    public $last_edited;
    public $intro;
    public $featured_image;
    public $category;
    public $author;
    public $pinned;
    public $hidden;
    public $body;
    public $show_comments;

    public function __construct($id, $data) {
        $id = (int) $id;
        $this->id = $id;
        if ($data instanceof Database) {
            // Fetch from database
            $this->id = (int) $id;
            $oDatabase = $data;
            $sql = "SELECT `artikel_titel`, `artikel_gemaakt`, `artikel_bewerkt`, ";
            $sql.= "`artikel_intro`, `artikel_afbeelding`, ";
            $sql.= "`categorie_naam`, `gebruiker_naam`, `artikel_gepind`, ";
            $sql.= "`artikel_verborgen`, `artikel_inhoud`, `artikel_reacties` FROM `artikel` ";
            $sql.= "LEFT JOIN `categorie` USING ( `categorie_id` ) ";
            $sql.= "LEFT JOIN `gebruikers` USING ( `gebruiker_id` ) ";
            $sql.= "WHERE artikel_id = {$this->id} ";
            $result = $oDatabase->query($sql);
            $data = $oDatabase->fetch($result);
        }
        // Set all variables
        if (is_array($data) && count($data) >= 9) {
            $this->title = $data[0];
            $this->created = $data[1];
            $this->last_edited = $data[2];
            $this->intro = $data[3];
            $this->featured_image = $data[4];
            $this->category = $data[5];
            $this->author = $data[6];
            $this->pinned = (boolean) $data[7];
            $this->hidden = (boolean) $data[8];
            if (count($data) >= 11) {
                $this->body = $data[9];
                $this->show_comments = (boolean) $data[10];
            }
            $this->exists = true;
        } else {
            $this->exists = false;
        }
    }

}

?>