<?php

/**
 * The code for the profile page of an user.
 */
class AccountPage extends Page {

    const GRAVATAR_URL_BASE = "http://www.gravatar.com/avatar/";

    /** @var User $user */
    protected $user;
    protected $can_edit_user;

    public function init(Website $oWebsite) {
        $user_id = $oWebsite->get_request_int("id", 0);
        if ($user_id == 0) {
            // Use current user
            $this->user = $oWebsite->get_authentication()->get_current_user();
        } else {
            // Use provided user
            $this->user = User::get_by_id($oWebsite, $user_id);
        }


        if ($this->user != null) {
            // Don't display banned/deleted users
            if (!$this->user->can_log_in()) {
                if (!$oWebsite->logged_in_staff()) {
                    // Staff can view everyone
                    $this->user = null;
                }
            }
        }
    }

    public function get_minimum_rank(Website $oWebsite) {
        if ($oWebsite->get_request_int("id", 0) == 0) {
            // Need to be logged in to view your own account
            return Authentication::$USER_RANK;
        } else {
            return parent::get_minimum_rank($oWebsite);
        }
    }

    public function get_page_title(Website $oWebsite) {
        // Get selected user
        $user = $oWebsite->get_authentication()->get_current_user();
        $given_user_id = $oWebsite->get_request_int("id", 0);
        if ($given_user_id > 0) {
            $user = User::get_by_id($oWebsite, $given_user_id);
        }
        // If found, use name in page title
        if ($user == null) {
            return $oWebsite->t("users.profile_page");
        } else {
            return $oWebsite->t_replaced("users.profile_page_of", $user->get_display_name());
        }
    }

    public function get_short_page_title(Website $oWebsite) {
        return $oWebsite->t("users.profile_page");
    }

    public function get_page_content(Website $oWebsite) {
        if ($this->user == null) {
            // Error - user not found
            $oWebsite->add_error($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_found"));
            return "";
        }

        // Display
        $text_to_display = <<<EOT
            <div id="sidebarpagesidebar">
                <h3 class="notable">{$this->user->get_display_name()}</h3>
                <p><img src="{$this->get_gravatar_url($this->user)}" style="max-width: 95%" /></p>
                {$this->get_edit_links_html($oWebsite)}
            </div>
            <div id="sidebarpagecontent">
                {$this->get_status_html($oWebsite)}
                {$this->get_articles_html($oWebsite)}
                {$this->get_comments_html($oWebsite)}
            </div>
            
EOT;
        return $text_to_display;
    }

    /** Returns the url of the gravatar of the user */
    public function get_gravatar_url() {
        if (strlen($this->user->get_email()) > 0) {
            $gravatar_url = self::GRAVATAR_URL_BASE . md5(strtolower($this->user->get_email()));
        } else {
            // No email given
            $gravatar_url = self::GRAVATAR_URL_BASE . "00000000000000000000000000000000";
        }
        $gravatar_url.= "?size=400&d=mm";
        return $gravatar_url;
    }

    /** Returns the HTML of the articles of the user, including the header */
    public function get_articles_html(Website $oWebsite) {
        $oArticles = new Articles($oWebsite);
        $articles = $oArticles->get_articles_data_user($this->user->get_id());
        $logged_in_staff = $oWebsite->logged_in_staff();
        if (count($articles) > 0) {
            $return_value = '<h3 class="notable">' . $oWebsite->t("main.articles") . "</h3>\n";
            foreach ($articles as $article) {
                $return_value .= $oArticles->get_article_text_small($article, true, $logged_in_staff);
            }
            return $return_value;
        } else {
            return "";
        }
    }

    /** Returns the HTML of the status of the user, including the header */
    public function get_status_html(Website $oWebsite) {
        $status_text = $this->user->get_status_text();
        if ($status_text) {
            $status_text = '<em>' . nl2br(htmlspecialchars($status_text)) . '</em>';
        }

        // It's safe to display the edit links, as only moderators and up can
        // view account pages of banned/deleted users.
        // Check if account is banned
        if ($this->user->get_status() == Authentication::BANNED_STATUS) {
            // Banned
            return <<<EOT
                <div class="error">
                    {$oWebsite->t_replaced("users.status.banned.this_account", $status_text)}.
                    {$oWebsite->t("users.user_page_hidden")}
                    <a class="arrow" href="{$oWebsite->get_url_page("edit_account_status", $this->user->get_id())}">
                        {$oWebsite->t("main.edit")}
                    </a>
                </div>
EOT;
        }

        // Check if account is deleted
        if ($this->user->get_status() == Authentication::DELETED_STATUS) {
            return <<<EOT
                <div class="error">
                    {$oWebsite->t_replaced("users.status.deleted.this_account", $status_text)}.
                    {$oWebsite->t("users.user_page_hidden")}
                    <a class="arrow" href="{$oWebsite->get_url_page("edit_account_status", $this->user->get_id())}">
                        {$oWebsite->t("main.edit")}
                    </a>
                </div>
EOT;
        }

        return '';
    }

    /**
     * Returns links to edit the profile, based on the permissions of the user
     * that is viewing this page. 
     */
    public function get_edit_links_html(Website $oWebsite) {
        $viewing_user = $oWebsite->get_authentication()->get_current_user();
        $return_value = "";

        // Get privileges
        $is_viewing_themselves = false;
        $is_viewing_as_moderator = false;
        $is_viewing_as_admin = false;
        if ($viewing_user != null) {
            $is_viewing_themselves = ($this->user->get_id() == $viewing_user->get_id());
            if ($oWebsite->logged_in_staff(false)) {
                $is_viewing_as_moderator = true;
            }
            if ($oWebsite->logged_in_staff(true)) {
                $is_viewing_as_admin = true;
            }
        }

        // Gravatar link + help
        if ($is_viewing_themselves) {
            // No way that other admins can edit someone's avatar, so only display help text for owner
            $return_value.= <<<EOT
                <p>
                     {$oWebsite->t_replaced("users.gravatar.explained", '<a href="http://gravatar.com/">gravatar.com</a>')}
                </p>
EOT;
        }

        // Add all account edit links
        $edit_links = array();

        if (!$is_viewing_themselves && $is_viewing_as_moderator) {
            // Accessed by a moderator that isn't viewing his/her own account
            // Add (un)ban link
            $edit_links[] = $this->get_edit_link($oWebsite, "edit_account_status", "editor.status.edit");
        }

        if ($is_viewing_themselves || $is_viewing_as_admin) {
            // Accessed by the user themselves or an admin
            // Display links to edit profile
            $edit_links[] = $this->get_edit_link($oWebsite, "edit_email", "editor.email.edit");
            $edit_links[] = $this->get_edit_link($oWebsite, "edit_password", "editor.password.edit");
            $edit_links[] = $this->get_edit_link($oWebsite, "edit_display_name", "editor.display_name.edit");
        }
        if (!$is_viewing_themselves && $is_viewing_as_admin) {
            // Accessed by an admin that isn't viewing his/her own account
            // Add rank edit link and login link
            $edit_links[] = $this->get_edit_link($oWebsite, "edit_rank", "editor.rank.edit");

            // Only display login link if account is not deleted/banned
            if ($this->user->can_log_in()) {
                $edit_links[] = $this->get_edit_link($oWebsite, "log_in_other", "main.log_in");
            }
        }

        if (count($edit_links) > 0) {
            $return_value.= "<p>\n" . implode($edit_links) . "</p>\n";
        }

        return $return_value;
    }

    /**
     * Gets a link with the specified url and text. User id and link class will
     * be added.
     * @param Website $oWebsite The website object.
     * @param string $page_id The id of the page.
     * @param string $translation_id The translation id of the text to display.
     * @return string The link.
     */
    public function get_edit_link(Website $oWebsite, $page_id, $translation_id) {
        return <<<EOT
            <a class="arrow" href="{$oWebsite->get_url_page($page_id, $this->user->get_id())}">
                {$oWebsite->t($translation_id)}
            </a><br />
EOT;
    }

    /** Returns the HTML of the comments of the user, including the header */
    public function get_comments_html(Website $oWebsite) {
        $oComments = new Comments($oWebsite);
        $comments = $oComments->get_comments_user($this->user->get_id());
        $return_value = '<h3 class="notable">' . $oWebsite->t("comments.comments") . "</h3>\n";
        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                // Add comment
                $return_value .= $oComments->get_comment_html($comment, $this->can_edit_user);
                // Add a link to context
                $return_value .= '<p><a class="arrow" href="';
                $return_value .= $oWebsite->get_url_page("article", $oComments->get_article_id($comment));
                $return_value .= "#comment-" . $oComments->get_comment_id($comment);
                $return_value .= '">' . $oWebsite->t("comments.view_context") . "</a></p>";
            }
        } else {
            $return_value .= "<p><em>" . $oWebsite->t("comments.no_comments_found_user") . "</em></p>";
        }
        return $return_value;
    }

}

$this->register_page(new AccountPage());
?>