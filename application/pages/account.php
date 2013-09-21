<?php

/**
 * The code for the profile page of an user.
 */
class AccountPage extends Page {

    /** @var User $user */
    protected $user;
    protected $can_edit_user;

    public function init(Website $oWebsite) {
        $user_id = $oWebsite->getRequestInt("id", 0);
        if ($user_id == 0) {
            // Use current user
            $this->user = $oWebsite->getAuth()->getCurrentUser();
        } else {
            // Use provided user
            $this->user = User::getById($oWebsite, $user_id);
        }


        if ($this->user != null) {
            // Don't display banned/deleted users
            if (!$this->user->canLogIn()) {
                if (!$oWebsite->isLoggedInAsStaff()) {
                    // Staff can view everyone
                    $this->user = null;
                }
            }
        }
    }

    public function getMinimumRank(Website $oWebsite) {
        if ($oWebsite->getRequestInt("id", 0) == 0) {
            // Need to be logged in to view your own account
            return Authentication::$USER_RANK;
        } else {
            return parent::getMinimumRank($oWebsite);
        }
    }

    public function getPageTitle(Website $oWebsite) {
        // Get selected user
        $user = $oWebsite->getAuth()->getCurrentUser();
        $given_user_id = $oWebsite->getRequestInt("id", 0);
        if ($given_user_id > 0) {
            $user = User::getById($oWebsite, $given_user_id);
        }
        // If found, use name in page title
        if ($user == null) {
            return $oWebsite->t("users.profile_page");
        } else {
            return $oWebsite->tReplaced("users.profile_page_of", $user->getDisplayName());
        }
    }

    public function getShortPageTitle(Website $oWebsite) {
        return $oWebsite->t("users.profile_page");
    }

    public function getPageContent(Website $oWebsite) {
        if ($this->user == null) {
            // Error - user not found
            $oWebsite->addError($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_found"));
            return "";
        }

        // Display
        $textToDisplay = <<<EOT
            <div id="sidebar_page_sidebar">
                <h3 class="notable">{$this->user->getDisplayName()}</h3>
                <p><img src="{$this->user->getAvatarUrl()}" style="max-width: 95%" /></p>
                {$this->get_edit_links_html($oWebsite)}
            </div>
            <div id="sidebar_page_content">
                {$this->get_status_html($oWebsite)}
                {$this->get_articles_html($oWebsite)}
                {$this->get_comments_html($oWebsite)}
            </div>
            
EOT;
        return $textToDisplay;
    }

    /** Returns the HTML of the articles of the user, including the header */
    public function get_articles_html(Website $oWebsite) {
        $oArticles = new Articles($oWebsite);
        $articles = $oArticles->getArticlesDataUser($this->user->getId());
        $oArticleView = new ArticleListView($oWebsite, $articles, 0, true, false);
        $loggedInStaff = $oWebsite->isLoggedInAsStaff();
        if (count($articles) > 0) {
            $returnValue = '<h3 class="notable">' . $oWebsite->t("main.articles") . "</h3>\n";
            $returnValue.= $oArticleView->getText();
            return $returnValue;
        } else {
            return "";
        }
    }

    /** Returns the HTML of the status of the user, including the header */
    public function get_status_html(Website $oWebsite) {
        $status_text = $this->user->getStatusText();
        if ($status_text) {
            $status_text = '<em>' . nl2br(htmlSpecialChars($status_text)) . '</em>';
        }

        // It's safe to display the edit links, as only moderators and up can
        // view account pages of banned/deleted users.
        // Check if account is banned
        if ($this->user->getStatus() == Authentication::BANNED_STATUS) {
            // Banned
            return <<<EOT
                <div class="error">
                    {$oWebsite->tReplaced("users.status.banned.this_account", $status_text)}.
                    {$oWebsite->t("users.user_page_hidden")}
                    <a class="arrow" href="{$oWebsite->getUrlPage("edit_account_status", $this->user->getId())}">
                        {$oWebsite->t("main.edit")}
                    </a>
                </div>
EOT;
        }

        // Check if account is deleted
        if ($this->user->getStatus() == Authentication::DELETED_STATUS) {
            return <<<EOT
                <div class="error">
                    {$oWebsite->tReplaced("users.status.deleted.this_account", $status_text)}.
                    {$oWebsite->t("users.user_page_hidden")}
                    <a class="arrow" href="{$oWebsite->getUrlPage("edit_account_status", $this->user->getId())}">
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
        $viewing_user = $oWebsite->getAuth()->getCurrentUser();
        $returnValue = "";

        // Get privileges
        $is_viewing_themselves = false;
        $is_viewing_as_moderator = false;
        $is_viewing_as_admin = false;
        if ($viewing_user != null) {
            $is_viewing_themselves = ($this->user->getId() == $viewing_user->getId());
            if ($oWebsite->isLoggedInAsStaff(false)) {
                $is_viewing_as_moderator = true;
            }
            if ($oWebsite->isLoggedInAsStaff(true)) {
                $is_viewing_as_admin = true;
            }
        }

        // Gravatar link + help
        if ($is_viewing_themselves) {
            // No way that other admins can edit someone's avatar, so only display help text for owner
            $returnValue.= <<<EOT
                <p>
                     {$oWebsite->tReplaced("users.gravatar.explained", '<a href="http://gravatar.com/">gravatar.com</a>')}
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
            if ($this->user->canLogIn()) {
                $edit_links[] = $this->get_edit_link($oWebsite, "log_in_other", "main.log_in");
            }
        }

        if (count($edit_links) > 0) {
            $returnValue.= "<p>\n" . implode($edit_links) . "</p>\n";
        }

        return $returnValue;
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
            <a class="arrow" href="{$oWebsite->getUrlPage($page_id, $this->user->getId())}">
                {$oWebsite->t($translation_id)}
            </a><br />
EOT;
    }

    /** Returns the HTML of the comments of the user, including the header */
    public function get_comments_html(Website $oWebsite) {
        $oComments = new Comments($oWebsite);
        $comments = $oComments->getCommentsUser($this->user->getId());
        $returnValue = '<h3 class="notable">' . $oWebsite->t("comments.comments") . "</h3>\n";
        if (count($comments) > 0) {
            foreach ($comments as $comment) {
                // Add comment
                $returnValue .= $oComments->getCommentHTML($comment, $this->can_edit_user);
                // Add a link to context
                $returnValue .= '<p><a class="arrow" href="';
                $returnValue .= $oWebsite->getUrlPage("article", $oComments->getArticleId($comment));
                $returnValue .= "#comment-" . $oComments->getCommentId($comment);
                $returnValue .= '">' . $oWebsite->t("comments.view_context") . "</a></p>";
            }
        } else {
            $returnValue .= "<p><em>" . $oWebsite->t("comments.no_comments_found_user") . "</em></p>";
        }
        return $returnValue;
    }

}

$this->registerPage(new AccountPage());
?>