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

        // Test whether user profile is editable by the current user
        $current_user = $oWebsite->get_authentication()->get_current_user();
        if ($current_user != null && $this->user != null) {
            if ($current_user->get_id() == $this->user->get_id()) {
                // Every user can edit themselves
                $this->can_edit_user = true;
            } else if ($oWebsite->logged_in_staff()) {
                // Staff can edit everyone
                $this->can_edit_user = true;
            } else {
                // Too bad
                $this->can_edit_user = false;
            }
        }
    }

    public function get_page_title(Website $oWebsite) {
        if ($this->user == null) {
            return $oWebsite->t("users.profile_page");
        } else {
            return $oWebsite->t_replaced("users.profile_page_of", $this->user->get_display_name());
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
            </div>
            <div id="sidebarpagecontent">
                {$this->get_articles_html($oWebsite)}
                {$this->get_comments_html($oWebsite)}
            </div>
            
EOT;
        return $text_to_display;
    }

    /** Returns the of the gravatar of the user */
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