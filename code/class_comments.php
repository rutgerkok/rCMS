<?php

class Comments {
    /* @var $website_object Website */

    protected $website_object;
    /* @var $database_object Database */
    protected $database_object;
    /* @var $authentication_object Authentication */
    protected $authentication_object;

    function __construct(Website $oWebsite, Authentication $oAuth) {
        $this->database_object = $oWebsite->get_database();
        $this->website_object = $oWebsite;
        $this->authentication_object = $oAuth;
    }

    /**
     * Create a new comment array. Doesn't save.
     * @param type $validate Whether or not to validate the input
     * @param type $comment_id The id of the comment. 0 for new comments.
     * @param type $author_name Name of the author, for logged out users.
     * @param type $author_email Email of the author, for logged out users.
     * @param type $comment_body Body of the comment.
     * @param type $author_id Id of the author, for logged in users.
     * @param type $article_id Id of the article to comment on.
     * @return type
     */
    function make_comment($validate, $comment_id, $author_name, $author_email, $comment_body, $account_id, $article_id) {
        if ($validate) {
            $oWebsite = $this->website_object;
            $logged_in = $oWebsite->logged_in();
            $valid = true;
            if (!$logged_in) {
                // Author name
                if (strlen(trim($author_name)) === 0) {
                    // Name not found
                    $oWebsite->add_error($oWebsite->t("users.name") . ' ' . $oWebsite->t("errors.not_entered"));
                    $valid = false;
                } else {
                    $author_name = htmlentities(trim($author_name));
                    if (!Validate::display_name($author_name)) {
                        $oWebsite->add_error($oWebsite->t("users.name") . ' ' . Validate::get_last_error($oWebsite));
                        $valid = false;
                    }
                }

                // Author email
                if (strlen(trim($author_email)) === 0) {
                    // Email not found, that's ok
                    $author_email = "";
                } else {
                    $author_email = trim($author_email);
                    if (!Validate::email($author_email)) {
                        $oWebsite->add_error($oWebsite->t("users.email") . ' ' . Validate::get_last_error($oWebsite));
                        $valid = false;
                    }
                }
            }

            // Comment
            if (!$this->check_comment_body($comment_body)) {
                $valid = false;
            }
        }
        return array((int) $comment_id, $author_name, $author_email, null, $comment_body, $account_id, "", "", $article_id);
    }
    
    /**
     * Sets the body of the comment. Displays errors if there are errors.
     * (Use Website->error_count())
     * @param array $comment The comment.
     * @param type $text The new text for the comment.
     * @return array[] The comment with the text.
     */
    function set_body($comment, $text) {
        $this->check_comment_body($text);
        $comment[4] = $text;
        return $comment;
    }

    function check_comment_body($comment_body) {
        $oWebsite = $this->website_object;
        $valid = true;
        
        if (!isset($comment_body) || strlen(trim($comment_body)) === 0) {
            $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_entered"));
            $valid = false;
        } else {
            if ($comment_body != strip_tags($comment_body)) {
                $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.contains_html"));
                $valid = false;
            }
            if (strlen($comment_body) < 10) {
                // WAY too long
                $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t_replaced("errors.is_too_short_num", 10));
                $valid = false;
            }

            $comment_body = htmlentities($comment_body);
            if (strlen($comment_body) > 65565) {
                // WAY too long
                $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.is_too_long"));
                $valid = false;
            }
        }
        return $valid;
    }

    function save($comment) {
        //sla alles op
        //verdeeld over twee dingen: de procedure voor ingelogde en de procedure voor niet-ingelogde gebruikers
        //niet-ingelogde gebruikers krijgen naam en eventueel email opgeslagen
        //ingelogde gebruikers krijgen hun id opgeslagen
        $oWebsite = $this->website_object;
        $oDB = $this->database_object;
        list($comment_id_raw, $author_name_raw, $author_email_raw, $comment_date_raw, $comment_body_raw, $user_id_raw, $account_name, $account_email, $article_id_raw) = $comment;

        // Security
        $comment_body = $oDB->escape_data($comment_body_raw);
        $author_name = $oDB->escape_data($author_name_raw);
        $author_email = $oDB->escape_data($author_email_raw);
        $comment_date = $oDB->escape_data($comment_date_raw);
        $comment_id = (int) $comment_id_raw;
        $article_id = (int) $article_id_raw;
        $user_id = (int) $user_id_raw;

        if ($comment_date_raw == null) {
            $comment_date_raw = "NOW()";
        }

        if ($article_id == 0) {
            $oWebsite->add_error("Cannot save; No article id found in Comments->save()!");
            return false;
        }

        if ($comment_id == 0) {
            // Add new comment
            $sql = "INSERT INTO `reacties` ( ";
            $sql.= "`artikel_id`, `gebruiker_id`, `reactie_naam`, `reactie_email`,  `reactie_gemaakt`,  `reactie_inhoud`";
            $sql.= ") VALUES (";
            $sql.= " $article_id, $user_id, \"$author_name\", \"$author_email\", $comment_date_raw, \"$comment_body\" )";
        } else {
            // Update statement
            $sql = "UPDATE `reacties` ";
            $sql.= "SET `artikel_id` = $article_id, ";
            $sql.= "`gebruiker_id` = $user_id, ";
            $sql.= "`reactie_naam` = \"$author_name\", ";
            $sql.= "`reactie_email` = \"$author_email\", ";
            $sql.= "`reactie_gemaakt` = \"$comment_date\", ";
            $sql.= "`reactie_inhoud` = \"$comment_body\", ";
            $sql.= "`artikel_id` = $article_id ";
            $sql.= "WHERE `reactie_id` = $comment_id ";
        }

        if ($oDB->query($sql)) {
            return true;
        } else {
            $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_saved")); //reactie is niet opgeslagen
            return false;
        }
    }

    function delete_comment($id) {
        $oWebsite = $this->website_object;
        $oDB = $this->database_object;

        $id = (int) $id;
        if ($id > 0) {
            $sql = "DELETE FROM `reacties` WHERE `reactie_id` = $id";
            if ($oDB->query($sql) && $oDB->affected_rows() > 0) {
                return true;
            } else {
                $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_found")); //reactie niet gevonden
                return false; //meldt dat het mislukt is
            }
        } else {
            $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_found")); //reactie niet gevonden
            return false; //heeft geen zin om met id=0 of iets anders query uit te voeren
        }
    }

    /**
     * Echoes the standard comment editor. You'll need to provide the <form>
     * tags by yourself, as well as hidden fields id and p, and the submit button.
     * @param type $article_id
     * @param type $comment The comment to edit can be null.
     * @return boolean
     */
    function echo_editor($comment) {
        // $comment can be null
        $oWebsite = $this->website_object;
        if (!isset($_REQUEST['id']) || ((int) $_REQUEST['id']) == 0) {
            $oWebsite->add_error($oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.not_found")); //Artikel niet gevonden
            return false;
        }

        if ($oWebsite->logged_in()) {
            $this->echo_editor_logged_in($comment);
        } else {
            $this->echo_editor_normal($comment);
        }
        return true;
    }

    function echo_editor_logged_in($comment) {
        $oWebsite = $this->website_object;
        $comment_body = ($comment == null) ? "" : $comment[4];
        echo <<<EOT
            <p>
                <em>{$oWebsite->t("main.fields_required")}</em> <!-- velden met een * zijn verplicht -->
            </p>
            <p>	
                <!-- reactie -->
                {$oWebsite->t("comments.comment")}<span class="required">*</span>:<br />
                <textarea name="comment" id="comment" rows="10" cols="60" style="width:98%">$comment_body</textarea>
            </p>	
EOT;
    }

    function echo_editor_normal($comment) {
        $oWebsite = $this->website_object;

        if ($comment == null) {
            $name = "";
            $email = "";
            $comment_body = "";
        } else {
            $name = $comment[1];
            $email = $comment[2];
            $comment_body = $comment[4];
        }

        echo <<<EOT
            <p>
                <em>{$oWebsite->t("main.fields_required")}</em> <!-- velden met een * zijn verplicht -->
            </p>
            <p>
                <!-- naam -->
                {$oWebsite->t("users.name")}<span class="required">*</span>:<br />
                <input type="text" name="name" id="name" maxlength="20" style="width:98%" value="$name" /><br />
            </p>
            <p>
                <!-- email -->
                {$oWebsite->t("users.email")}:<br />
                <input type="email" name="email" id="email" style="width:98%" value="$email" /><br />
                <em>{$oWebsite->t("comments.email_explained")}</em><br />
            </p>
            <p>	
                <!-- reactie -->
                {$oWebsite->t("comments.comment")}<span class="required">*</span>:<br />
                <textarea name="comment" id="comment" rows="10" cols="60" style="width:98%">$comment_body</textarea>
            </p>
EOT;
    }

    /**
     * Gets the HTML of the comment
     * 
     * @param type $comment The comment to show.
     * @param type $show_actions Whether or not to show the edit and delete link
     * @return string The HTML output.
     */
    function get_comment_html($comment, $show_actions) { //geeft reactie kant-en-klaar terug
        list($comment_id, $author_name, $author_email, $comment_date_raw, $comment_body, $account_id, $account_name, $account_email, $article_id) = $comment;
        // Time format
        $comment_date = str_replace(' 0', ' ', strftime("%A %d %B %Y %X", strtotime($comment_date_raw)));
        // Name format
        if (empty($author_name)) {
            $author_name = $account_name; // Comment name is not set for logged in users
            $author_email = $account_email;
        }
        // Header
        $return_value = "<h3 id=\"comment-$comment_id\">$author_name ($comment_date)</h3>"; //naam en datum
        // Show email, edit and delete links
        if ($show_actions) {
            $oWebsite = $this->website_object;
            $return_value.= "<p>\n";
            if (strlen($author_email) > 0) {
                // Email
                $return_value.= $oWebsite->t("users.email") . ': <a href="mailto:' . $author_email . '">' . $author_email . "</a> &nbsp;&nbsp;&nbsp;\n";
            }
            // Edit + delete
            $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("edit_comment", $comment_id) . '">' . $oWebsite->t("main.edit") . "</a>\n";
            $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("delete_comment", $comment_id) . '">' . $oWebsite->t("main.delete") . "</a>\n";
            $return_value.= "</p>";
        }
        // Show comment body
        $return_value.= "<p>" . nl2br($comment_body) . "</p>";
        // Return
        return $return_value;
    }

    /**
     * Returns an array with the data of the comment in the format
     * list($comment_id, $comment_name, $comment_date_raw, $comment_body, $account_id, $account_name, $article_id).
     * 
     * Shows an error and returns null if the comment wasn't found.
     * 
     * @param int $comment_id The id of the comment
     * @return array[] The comment.
     */
    function get_comment($comment_id) {
        $comment_id = (int) $comment_id; // SQL injection prevention
        $oDB = $this->database_object; // Get the database object
        $oWebsite = $this->website_object;

        $sql = "SELECT `reactie_id`, `reactie_naam`, `reactie_email`, `reactie_gemaakt`,";
        $sql.= "`reactie_inhoud`, `gebruiker_id`, `gebruiker_naam`, `gebruiker_email`, `artikel_id` FROM `reacties`";
        $sql.= "LEFT JOIN `gebruikers` USING ( `gebruiker_id` )";
        $sql.= "WHERE `reactie_id` = $comment_id";

        $result = $oDB->query($sql);
        if ($oDB->rows($result) == 1) {
            return $oDB->fetch($result);
        } else {
            $oWebsite->add_error($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_found"));
            return null;
        }
    }

    /**
     * Get all comments for an article. Safe method.
     * @param int $article_id The article of the comments.
     * @return array[][] The comments.
     */
    function get_comments_article($article_id) {
        $article_id = (int) $article_id;
        return $this->get_comments_query("`artikel_id` = $article_id ", 0, false);
    }
    
    /**
     * Get the latest comments on the site. Safe method.
     * @return array[][] The comments.
     */
    function get_comments_latest() {
        return $this->get_comments_query("", 20, true);
    }
    
    // Unsafe method - doesn't sanitize input
    private function get_comments_query($where_clausule, $limit, $new_comments_first) {
        $oDB = $this->database_object;

        $sql = "SELECT `reactie_id`, `reactie_naam`, `reactie_email`, `reactie_gemaakt`,";
        $sql.= "`reactie_inhoud`, `gebruiker_id`, `gebruiker_naam`, `gebruiker_email`, `artikel_id` FROM `reacties`";
        $sql.= "LEFT JOIN `gebruikers` USING ( `gebruiker_id` )";
        if(strlen($where_clausule) > 0) {
            $sql.= " WHERE $where_clausule";
        }
        $sql.= "ORDER BY `reactie_gemaakt`";
        if($new_comments_first) {
            $sql.= " DESC";
        }
        if($limit > 0) {
            $sql.= " LIMIT " . $limit;
        }

        $result = $oDB->query($sql);
        $comments = array();

        while ($comment = $oDB->fetch($result)) {
            $comments[] = $comment;
        }

        return $comments;
    }

    /**
     * Get the id of the user who posted the comment.
     * @param array[] $comment The comment array.
     * @return int The id of the user that posted the comment.
     */
    function get_user_id($comment) {
        return (int) $comment[5];
    }

    function get_article_id($comment) {
        return (int) $comment[8];
    }
    
    function get_comment_id($comment) {
        return (int) $comment[0];
    }

    

}

?>