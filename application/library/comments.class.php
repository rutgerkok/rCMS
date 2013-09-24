<?php

class Comments {
    /* @var $websiteObject Website */

    protected $websiteObject;
    /* @var $databaseObject Database */
    protected $databaseObject;
    /* @var $authentication_object Authentication */
    protected $authentication_object;

    /**
     * Constructs a new comment object.
     * @param Website $oWebsite The website.
     * @param Authentication $oAuth Unneeded, provided for backwards compability.
     */
    function __construct(Website $oWebsite, Authentication $oAuth = null) {
        $this->databaseObject = $oWebsite->getDatabase();
        $this->websiteObject = $oWebsite;
        $this->authentication_object = $oAuth;
        if ($this->authentication_object == null) {
            $this->authentication_object = $oWebsite->getAuth();
        }
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
    function makeComment($validate, $comment_id, $author_name, $author_email, $comment_body, $account_id, $article_id) {
        if ($validate) {
            $oWebsite = $this->websiteObject;
            $loggedIn = $oWebsite->isLoggedIn();
            $valid = true;
            if (!$loggedIn) {
                // Author name
                if (strLen(trim($author_name)) === 0) {
                    // Name not found
                    $oWebsite->addError($oWebsite->t("users.name") . ' ' . $oWebsite->t("errors.not_entered"));
                    $valid = false;
                } else {
                    $author_name = htmlSpecialChars(trim($author_name));
                    if (!Validate::display_name($author_name)) {
                        $oWebsite->addError($oWebsite->t("users.name") . ' ' . Validate::get_last_error($oWebsite));
                        $valid = false;
                    }
                }

                // Author email
                if (strLen(trim($author_email)) === 0) {
                    // Email not found, that's ok
                    $author_email = "";
                } else {
                    $author_email = trim($author_email);
                    if (!Validate::email($author_email)) {
                        $oWebsite->addError($oWebsite->t("users.email") . ' ' . Validate::get_last_error($oWebsite));
                        $valid = false;
                    }
                }
            }

            // Comment
            if (!$this->checkCommentBody($comment_body)) {
                $valid = false;
            }
        }
        return new Comment($comment_id, $article_id, $account_id, $author_name, $author_name, $author_email, 0, 0, 0, $comment_body, Comment::NORMAL_STATUS);
    }

    /**
     * Sets the body of the comment. Displays errors if there are errors.
     * (Use Website->errorCount())
     * @param array $comment The comment.
     * @param type $text The new text for the comment.
     * @return array[] The comment with the text.
     */
    function setBody(Comment $comment, $text) {
        $this->checkCommentBody($text);
        $comment->setBodyRaw($text);
        return $comment;
    }

    function checkCommentBody($comment_body) {
        $oWebsite = $this->websiteObject;
        $valid = true;

        if (!isSet($comment_body) || strLen(trim($comment_body)) === 0) {
            $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_entered"));
            $valid = false;
        } else {
            if ($comment_body != strip_tags($comment_body)) {
                $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.contains_html"));
                $valid = false;
            }
            if (strLen($comment_body) < 10) {
                // WAY too long
                $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->tReplaced("errors.is_too_short_num", 10));
                $valid = false;
            }

            $comment_body = htmlSpecialChars($comment_body);
            if (strLen($comment_body) > 65565) {
                // WAY too long
                $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.is_too_long"));
                $valid = false;
            }
        }
        return $valid;
    }

    function save(Comment $comment) {
        if ($comment->save($this->databaseObject)) {
            return true;
        } else {
            $oWebsite = $this->websiteObject;
            $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_saved")); //reactie is niet opgeslagen
            return false;
        }
    }

    function deleteComment($id) {
        $oWebsite = $this->websiteObject;
        $oDB = $this->databaseObject;

        $id = (int) $id;
        if ($id > 0) {
            $sql = "DELETE FROM `comments` WHERE `comment_id` = $id";
            if ($oDB->query($sql) && $oDB->affectedRows() > 0) {
                return true;
            } else {
                $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_found")); //reactie niet gevonden
                return false; //meldt dat het mislukt is
            }
        } else {
            $oWebsite->addError($oWebsite->t("comments.comment") . ' ' . $oWebsite->t("errors.not_found")); //reactie niet gevonden
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
    function echoEditor($comment) {
        // $comment can be null
        $oWebsite = $this->websiteObject;
        if (!isSet($_REQUEST['id']) || ((int) $_REQUEST['id']) == 0) {
            $oWebsite->addError($oWebsite->t("main.article") . ' ' . $oWebsite->t("errors.not_found")); //Artikel niet gevonden
            return false;
        }

        if ($oWebsite->isLoggedIn()) {
            $this->echoEditorLoggedIn($comment);
        } else {
            $this->echoEditorNormal($comment);
        }
        return true;
    }

    function echoEditorLoggedIn($comment) {
        $oWebsite = $this->websiteObject;
        $comment_body = ($comment == null) ? "" : htmlSpecialChars($comment->getBodyRaw());
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

    function echoEditorNormal($comment) {
        $oWebsite = $this->websiteObject;

        if ($comment == null) {
            $name = "";
            $email = "";
            $comment_body = "";
        } else {
            $name = htmlSpecialChars($comment->getUserDisplayName());
            $email = htmlSpecialChars($comment->getUserEmail());
            $comment_body = htmlSpecialChars($comment->getBodyRaw());
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
    function getCommentHTML(Comment $comment, $show_actions) { //geeft reactie kant-en-klaar terug
        $oWebsite = $this->websiteObject;

        $comment_id = $comment->getId();
        $author_name = htmlSpecialChars($comment->getUserDisplayName());
        $author_email = $comment->getUserEmail();
        $comment_date_raw = $comment->getDateCreated();
        $comment_body = nl2br(htmlSpecialChars($comment->getBodyRaw()));
        $account_id = $comment->getUserId();
        // Time format
        $comment_date = str_replace(' 0', ' ', strftime("%A %d %B %Y %X", $comment_date_raw));
        // Name format
        if ($comment->getUserRank() != Authentication::$LOGGED_OUT_RANK) {
            // Name of author is not set when user id is set
            $author_name = '<a href="' . $oWebsite->getUrlPage("account", $account_id) . '">' . $author_name . '</a>';
        }
        // Header
        $returnValue = "<h3 id=\"comment-$comment_id\">$author_name ($comment_date)</h3>"; //naam en datum
        // Show email, edit and delete links
        if ($show_actions) {
            $oWebsite = $this->websiteObject;
            $returnValue.= "<p>\n";
            if (strLen($author_email) > 0) {
                // Email
                $returnValue.= $oWebsite->t("users.email") . ': <a href="mailto:' . $author_email . '">' . $author_email . "</a> &nbsp;&nbsp;&nbsp;\n";
            }
            // Edit + delete
            $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlPage("edit_comment", $comment_id) . '">' . $oWebsite->t("main.edit") . "</a>\n";
            $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlPage("delete_comment", $comment_id) . '">' . $oWebsite->t("main.delete") . "</a>\n";
            $returnValue.= "</p>";
        }
        // Show comment body
        $returnValue.= "<p>" . $comment_body . "</p>";
        // Return
        return $returnValue;
    }

    /**
     * Returns a comment.
     * 
     * Returns null if the comment wasn't found.
     * 
     * @param int $commentId The id of the comment
     * @return Comment The comment.
     */
    function getComment($commentId) {
        return Comment::getById($this->databaseObject, $commentId);
    }

    /**
     * Gets all comments for an article. Safe method.
     * @param int $article_id The article of the comments.
     * @return array[][] The comments.
     */
    function getCommentsArticle($article_id) {
        $article_id = (int) $article_id;
        return $this->getCommentsQuery("`article_id` = $article_id", 0, false);
    }

    /**
     * Gets the latest comments on the site. Safe method.
     * @return array[][] The comments.
     */
    function getCommentsLatest() {
        return $this->getCommentsQuery("", 20, true);
    }

    /**
     * Gets the latest 10 comments of the given user.
     * @param int $user_id The id of the user.
     * @return array The comments.
     */
    public function getCommentsUser($user_id) {
        $user_id = (int) $user_id;
        return $this->getCommentsQuery("`user_id` = $user_id", 10, true);
    }

    // Unsafe method - doesn't sanitize input
    private function getCommentsQuery($where_clausule, $limit, $new_comments_first) {
        $oDB = $this->databaseObject;

        $sql = <<<SQL
SELECT `comment_id`, `article_id`, `user_id`, `user_display_name`, 
`user_login`, `user_email`, `user_rank`, `comment_name`, `comment_email`, 
`comment_created`, `comment_last_edited`, `comment_body`, `comment_status` 
FROM `comments` LEFT JOIN `users` USING(`user_id`)
SQL;
        if (strLen($where_clausule) > 0) {
            $sql.= " WHERE $where_clausule ";
        }
        $sql.= "ORDER BY `comment_created`";
        if ($new_comments_first) {
            $sql.= " DESC";
        }
        if ($limit > 0) {
            $sql.= " LIMIT " . $limit;
        }

        $result = $oDB->query($sql);
        $comments = array();

        while ($commentArray = $oDB->fetchAssoc($result)) {
            $comments[] = Comment::getByArray($commentArray["comment_id"], $commentArray);
        }

        return $comments;
    }

    /**
     * Get the id of the user who posted the comment.
     * @param array[] $comment The comment array.
     * @return int The id of the user that posted the comment.
     */
    function getUserId(Comment $comment) {
        return $comment->getUserId();
    }

    function getArticleId(Comment $comment) {
        return $comment->getArticleId();
    }

    function getCommentId(Comment $comment) {
        return $comment->getId();
    }

}

?>