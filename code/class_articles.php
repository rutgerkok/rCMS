<?php

//Maakt geen gebruik van oude vertaalmethode


class Articles {
    /*
     * Alles met artikelen: laat een overzicht zien van de nieuwste artikelen, of alleen een simpele lijst. Een artikel bekijken is ook mogelijk.
     * METHODES
     * - get_article_data($id) - geeft een array terug met informatie over het opgegeven artikel
     * - get_articles_data($where_clausule) - geeft een array terug met informatie over de opgegeven artikelen (max 10 artikelen worden teruggegeven)
     * - get_articles_list_category($category,$not = false,$metainfo = true,$lines_in_metainfo = false) - laat alle artikelen zien die (niet = arg1) tot de opgegeven categorie(=arg0) behoren. arg2 zorgt voor al dan niet metadata
     * - get_article($id) - laat het artikel zien met de opgegeven id (=arg0)
     * - get_articles_search($keywordunprotected,$page) - arg0 zoekwoord, arg1 het paginanummer
     * - get_articles_archive($year = -1) arg0 het jaar, laat weg voor huidige jaar
     */

    protected $website_object;
    protected $database_object;
    protected $category_object; //nodig voor get_articles_archive

    function __construct(Website $website_object, Database $database_object, Categories $category_object = null) {
        $this->website_object = $website_object;
        $this->database_object = $database_object;
        $this->category_object = $category_object;
    }

    function get_article_data($id) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting

        $id = (int) $id; //maak id veilig voor in gebruik query;

        $sql = "SELECT artikel_titel, artikel_gemaakt, artikel_bewerkt, ";
        $sql.= "artikel_intro, artikel_afbeelding, artikel_inhoud, ";
        $sql.= "categorie_naam, gebruiker_naam, artikel_gepind, ";
        $sql.= "artikel_verborgen, artikel_reacties FROM `artikel` ";
        $sql.= "LEFT JOIN `categorie` USING ( categorie_id ) ";
        $sql.= "LEFT JOIN `gebruikers` USING ( gebruiker_id) ";
        $sql.= "WHERE artikel_id = $id ";

        $result = $oDB->query($sql);

        if ($result && $oDB->rows($result) == 1) {
            //$title,$created, $last_edited,$intro,$featured_image,$body,$article_category, $author, $pinned, $hidden, $comments
            return $oDB->fetch($result);
        } else {
            return null;
        }
    }

    function get_articles_data($where_clausule = "", $limit = 9, $start = 0) { //WAARSCHUWING: ZORG DAT ER NIKS GEVAARLIJKS STAAT IN DE $where_clausule
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $logged_in = $oWebsite->logged_in() ? 1 : 0; //ingelogd? (nodig om de juiste artikelen op te halen)
        $limit = (int) $limit; //stuk veiliger

        $sql = "SELECT artikel_id, artikel_titel, artikel_intro, "; //haal id, titel, intro ...
        $sql.= "artikel_afbeelding, artikel_gemaakt, artikel_bewerkt, "; //... afbeelding, datums, ...
        $sql.= "categorie_naam, gebruiker_naam, artikel_gepind, artikel_verborgen "; //... categorie, auteur, gepind en verborgen op
        $sql.= "FROM `artikel` "; //uit de tabel artikel
        $sql.= "LEFT JOIN `categorie` USING ( categorie_id ) "; //join 1
        $sql.= "LEFT JOIN `gebruikers` USING ( gebruiker_id ) "; //join 2
        $sql.= "WHERE artikel_verborgen <= $logged_in "; //verborgen artikelen niet voor niet ingelogde gebruikers
        if (!empty($where_clausule))
            $sql.= "AND $where_clausule "; //nog een extra voorwaarde?
        $sql.= "ORDER BY artikel_gepind DESC, artikel_gemaakt DESC "; //sorteren
        $sql.= "LIMIT $start , $limit"; //nooit teveel

        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) > 0) {
            while ($row = $oDB->fetch($result)) {
                $return_value[] = $row;
            }
            return $return_value;
        } else {
            return null;
        }
    }

    function get_article($id) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting

        $id = (int) $id; //maak id veilig voor in gebruik query;
        $logged_in = $oWebsite->logged_in();
        $return_value = '';
        $article_data = $this->get_article_data($id);

        if ($article_data != null) {
            list($title, $created, $last_edited, $intro, $featured_image, $body, $article_category, $author, $pinned, $hidden, $comments) = $article_data;
            unset($article_data); //ruimt op

            if (!$hidden || $logged_in) {

                $return_value.= "<h2>$title</h2>";

                $return_value.= '<div class="artikelzijkolom">';
                //zijbalk
                if (!empty($featured_image))
                    $return_value.= "<p><img src=\"$featured_image\" alt=\"$title\" /></p>";
                $return_value.= '<p class="meta">';
                $return_value.= $oWebsite->t('articles.created') . " <br />&nbsp;&nbsp;&nbsp;" . $created;
                if ($last_edited)
                    $return_value.= " <br />  " . $oWebsite->t('articles.last_edited') . " <br />&nbsp;&nbsp;&nbsp;" . $last_edited . "";
                $return_value.= " <br /> " . $oWebsite->t('main.category') . ": " . $article_category;
                $return_value.= " <br /> " . $oWebsite->t('articles.author') . ": $author"; //auteur
                if ($pinned)
                    $return_value.= "<br />" . $oWebsite->t('articles.pinned') . " "; //gepind
                if ($hidden)
                    $return_value.= "<br />" . $oWebsite->t('articles.hidden'); //verborgen
                if ($logged_in && $comments)
                    $return_value.= "<br />" . $oWebsite->t('articles.comments.allowed'); //reacties
                $return_value.= '</p>';
                if ($logged_in)
                    $return_value.= "<p style=\"clear:both\">";
                if ($logged_in)
                    $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->get_url_page("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                            '<a class="arrow" href="' . $oWebsite->get_url_page("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
                if ($logged_in)
                    $return_value.= "</p>";
                if ($comments)
                    $return_value.= <<<EOT

					<!-- AddThis Button BEGIN -->
					<div class="addthis_toolbox addthis_default_style ">
					<a class="addthis_button_facebook_like"></a>
					<br />
					<br />
					<a class="addthis_button_tweet"></a>
					<br />
					<br />
					<a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
					</div>
					<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4ee269aa4314962e"></script>
					<!-- AddThis Button END -->
EOT;
                $return_value.= '</div>';



                $return_value.= '<div class="artikel">';
                //artikel
                if ($logged_in && $hidden)
                    $return_value.= '<p class="meta">' . $oWebsite->t('articles.is_hidden') . "<br /> \n" . $oWebsite->t('articles.hidden.explained') . '</p>';
                $return_value.= '<p class="intro">' . $intro . '</p>';
                $return_value.= $body;
                //reacties
                if ($comments) {

                    $sql = "SELECT reactie_id, reactie_email, reactie_naam, reactie_gemaakt, reactie_inhoud, gebruiker_naam FROM `reacties` LEFT JOIN `gebruikers` USING ( gebruiker_id ) WHERE artikel_id = $id";
                    $result = $oDB->query($sql);
                    if ($oDB->rows($result) > 0) { //geef reacties weer
                        $return_value.="<h3 class=\"notable\">" . $oWebsite->t('articles.comments') . " (" . $oDB->rows($result) . ")</h3>";
                        $return_value.='<p><a href="' . $oWebsite->get_url_page("add_comment", $id) . '\" class="arrow">' . $oWebsite->t('articles.comment.add') . "</a></p>"; //link: reageer

                        while (list($comment_id, $comment_email, $comment_name, $comment_date_raw, $comment, $account_name) = $oDB->fetch($result)) { //geef alle reacties weer //geef reactie weer
                            $comment_date = str_replace(' 0', ' ', strftime("%A %d %B %Y %X", strtotime($comment_date_raw)));
                            if (empty($comment_name))
                                $comment_name = $account_name; //ingelogde gebruikers correct weergeven
                            $return_value.= "<h3>$comment_name ($comment_date)</h3>"; //naam en datum
                            $return_value.= "<p>";
                            if ($logged_in && !empty($comment_email))
                                $return_value.= "<a href=\"mailto:$comment_email\">$comment_email</a> &nbsp;&nbsp;&nbsp;"; //mail
                            if ($logged_in)
                                $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("delete_comment", $comment_id) . '">' . $oWebsite->t('main.delete') . '</a> </p>'; //verwijder
                            $return_value.= "<p>" . nl2br($comment) . "</p>";
                        }

                        $return_value.='<p><a href="' . $oWebsite->get_url_page("add_comment", $id) . '\" class="arrow">' . $oWebsite->t('articles.comment.add') . "</a></p>"; //link: reageer
                    }
                    else {
                        $return_value.="<h3 class=\"notable\">" . $oWebsite->t('articles.comments') . "</h3>";
                        $return_value.= "<p><em>" . $oWebsite->t('articles.comments.not_found') . "</em></p>"; //geen reacties gevonden
                        $return_value.='<p><a href="' . $oWebsite->get_url_page("add_comment", $id) . '\" class="arrow">' . $oWebsite->t('articles.comment.add') . "</a></p>"; //link: reageer
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

///////////////////////////////////////////	


    function get_articles_list_category($categories, $not = false, $metainfo = true, $limit = 9) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $logged_in = $oWebsite->logged_in(); //ingelogd? (nodig voor links om te bewerken)

        if (!is_array($categories)) { //maak een array
            $category_id = $categories;
            unset($categories);
            $categories[0] = $category_id;
        }

        $where_clausule = '';
        foreach ($categories as $count => $category_id) {
            $category_id = (int) $category_id; //beveiliging

            if ($not) { //alles behalve deze categorie
                if ($count > 0)
                    $where_clausule.=" AND ";
                $where_clausule.="categorie_id != $category_id";
            }
            else { //niks behalve deze categorie
                if ($count > 0)
                    $where_clausule.=" OR ";
                $where_clausule.="categorie_id = $category_id";
            }
        }

        //haal resultaten op
        $result = $this->get_articles_data($where_clausule, $limit);

        //verwerk resultaten
        if ($result) {
            $return_value = '';
            $category = ($not) ? 0 : $categories[0];


            if ($logged_in) {
                if ($not) {
                    $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
                }//maak nieuw artikel
                else {
                    $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $category)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
                }//maak nieuw artikel in categorie
            }
            foreach ($result as $row) {
                list($id, $title, $intro, $featured_image, $created, $last_edited, $article_category, $author, $pinned, $hidden) = $row;
                $return_value.= "\n\n<div class=\"artikelintro\" onclick=\"location.href='" . $oWebsite->get_url_page("article", $id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
                $return_value.= "<h3>$title</h3>";
                if ($metainfo)
                    $return_value.= '<p class="meta">';
                if ($metainfo)
                    $return_value.= $oWebsite->t('articles.created') . " " . $created . ' - '; //gemaakt op
                if ($metainfo && $last_edited)
                    $return_value.= lcfirst($oWebsite->t('articles.last_edited')) . " " . $last_edited . '<br />'; //laatst bewerkt op
                if ($metainfo)
                    $return_value.= $oWebsite->t('main.category') . ": " . $article_category; //categorie
                if ($metainfo)
                    $return_value.= " - " . $oWebsite->t('articles.author') . ": $author"; //auteur
                if ($metainfo && $pinned)
                    $return_value.= " - " . $oWebsite->t('articles.pinned'); //vastgepind?
                if ($metainfo && $hidden)
                    $return_value.= " - " . $oWebsite->t('articles.hidden'); //verborgen?
                if ($metainfo)
                    $return_value.= '</p>';

                if (!empty($featured_image))
                    $return_value.= "<img src=\"$featured_image\" alt=\"$title\" />";

                $return_value.= '<p class="intro ';
                if (!empty($featured_image))
                    $return_value.= 'introsmall'; //maak introtekst kleiner
                $return_value.= '">';

                $return_value.= $intro;

                $return_value.= "<br />";
                $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("article", $id) . '">' . $oWebsite->t('main.read') . '</a>';
                if ($logged_in)
                    $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->get_url_page("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                            '<a class="arrow" href="' . $oWebsite->get_url_page("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
                $return_value.= "</p>";

                $return_value.= '<p style="clear:both"></p>';

                $return_value.= "</div>";
            }

            if ($logged_in) {
                if ($not) {
                    $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
                }//maak nieuw artikel
                else {
                    $return_value.= '<p><a href="' . $oWebsite->get_url_page("edit_article", 0, array("article_category" => $category)) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>';
                }//maak nieuw artikel in categorie
            }
            $return_value.='<p><a href="' . $oWebsite->get_url_page("archive", 0, array("year" => date('Y'), "cat" => $category)) . '" class="arrow">' . $oWebsite->t('articles.archive') . '</a></p>'; //archief
            return $return_value;
        } else {

            if ($logged_in) {
                return '<p><a href="' . $oWebsite->get_url_page("edit_article", 0) . '" class="arrow">' . $oWebsite->t('articles.create') . '</a></p>'; //maak nieuw artikel
            } else {
                return '';
            }
        }
    }

///////////////////////////////////////////	
    function get_articles_search($keywordunprotected, $page) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $logged_in = $oWebsite->logged_in(); //ingelogd? (nodig voor links om te bewerken)
        $metainfo = true; //waarom zou je anders willen?
        $articles_per_page = 5; //vijf resultaten per pagina
        $start = ($page - 1) * $articles_per_page;

        $keyword = $oDB->escape_data($keywordunprotected); //maak zoekwoord veilig voor in gebruik query;

        if (strlen($keyword) < 3) {
            return ''; //moet vooraf al op worden gecontroleerd
        }


        //aantal ophalen
        $sql = "SELECT count(*) FROM `artikel` WHERE artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%' ";
        $result = $oDB->query($sql);
        $result = $oDB->fetch($result);
        $resultcount = (int) $result[0];



        //resultaat ophalen
        $result = $this->get_articles_data(
                "(artikel_titel LIKE '%$keyword%' OR artikel_intro LIKE '%$keyword%' OR artikel_inhoud LIKE '%$keyword%')", $articles_per_page, $start
        );

        //artikelen ophalen
        $return_value = '';

        if ($result) {
            //Geef aantal resultaten weer
            $return_value.= ($resultcount == 1) ? "<p>" . $oWebsite->t('articles.search.result_found') . "</p>" : "<p>" . str_replace('#', $resultcount, $oWebsite->t('articles.search.results_found')) . "</p>";

            $i = 0; //vijf keer artikel weergeven, zesde keer alleen linkje met meer
            //paginanavigatie
            $return_value.= '<p class="lijn">';
            if ($page > 1)
                $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("search", 0, array("searchbox" => $keywordunprotected, "page" => $page - 1)) . '">' . $oWebsite->t('articles.page.previous') . '</a> '; //vorige pagina
            $return_value.= str_replace("\$", ceil($resultcount / $articles_per_page), str_replace("#", $page, $oWebsite->t('articles.page.current'))); //pagina X van Y
            if ($resultcount > $start + $articles_per_page)
                $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("search", 0, array("searchbox" => $keywordunprotected, "page" => $page + 1)) . '">' . $oWebsite->t('articles.page.next') . '</a>'; //volgende pagina
            $return_value.= '</p>';

            foreach ($result as $row) {
                list($id, $title, $intro, $featured_image, $created, $last_edited, $article_category, $author, $pinned, $hidden) = $row;
                $return_value.= "\n\n<div class=\"artikelintro\" onclick=\"location.href='" . $oWebsite->get_url_page("article", $id) . "'\" onmouseover=\"this.style.cursor='pointer'\">";
                $return_value.= "<h3>$title</h3>";
                if ($metainfo)
                    $return_value.= '<p class="meta">';
                if ($metainfo)
                    $return_value.= $oWebsite->t('articles.created') . " " . $created . ' - '; //gemaakt op
                if ($metainfo && $last_edited)
                    $return_value.= lcfirst($oWebsite->t('articles.last_edited')) . " " . $last_edited . '<br />'; //laatst bewerkt op
                if ($metainfo)
                    $return_value.= $oWebsite->t('main.category') . ": " . $article_category; //categorie
                if ($metainfo)
                    $return_value.= " - " . $oWebsite->t('articles.author') . ": $author"; //auteur
                if ($metainfo && $pinned)
                    $return_value.= " - " . $oWebsite->t('articles.pinned'); //vastgepind?
                if ($metainfo && $hidden)
                    $return_value.= " - " . $oWebsite->t('articles.hidden'); //verborgen?
                if ($metainfo)
                    $return_value.= '</p>';

                if (!empty($featured_image))
                    $return_value.= "<img src=\"$featured_image\" alt=\"$title\" />";

                $return_value.= '<p class="intro ';
                if (!empty($featured_image))
                    $return_value.= 'introsmall'; //maak introtekst kleiner
                $return_value.= '">';

                $return_value.= $intro;

                $return_value.= "<br />";
                $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("article", $id) . '">' . $oWebsite->t('main.read') . '</a>';
                if ($logged_in)
                    $return_value.= '&nbsp;&nbsp;&nbsp;<a class="arrow" href="' . $oWebsite->get_url_page("edit_article", $id) . '">' . $oWebsite->t('main.edit') . '</a>&nbsp;&nbsp;' . //edit
                            '<a class="arrow" href="' . $oWebsite->get_url_page("delete_article", $id) . '">' . $oWebsite->t('main.delete') . '</a>'; //delete
                $return_value.= "</p>";

                $return_value.= '<p style="clear:both"></p>';

                $return_value.= "</div>";
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

///////////////////////////////////////////	
    function get_articles_archive($year = 0, $cat_display = 0) {
        $oDB = $this->database_object; //afkorting
        $oWebsite = $this->website_object; //afkorting
        $oCats = $this->category_object; //afkorting

        $events = false;

        $logged_in = $oWebsite->logged_in(); //ingelogd? (nodig voor links om te bewerken)
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

?>