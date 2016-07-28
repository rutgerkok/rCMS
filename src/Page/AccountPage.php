<?php

namespace Rcms\Page;

use Rcms\Core\ArticleRepository;
use Rcms\Core\Authentication;
use Rcms\Core\CommentRepository;
use Rcms\Core\NotFoundException;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\Request;
use Rcms\Core\Website;

use Rcms\Page\View\ArticleListView;
use Rcms\Page\View\CommentsTreeView;

/**
 * The code for the profile page of an user.
 */
class AccountPage extends Page {

    /** @var User $user */
    protected $user;
    protected $can_edit_user;

    public function init(Website $website, Request $request) {
        $userId = $request->getParamInt(0);
        if ($userId === 0) {
            // Use current user
            $this->user = $website->getAuth()->getCurrentUser();
            if ($this->user == null) {
                throw new NotFoundException();
            }
        } else {
            // Use provided user
            $this->user = $website->getAuth()->getUserRepository()->getById($userId);
        }

        if ($this->user !== null) {
            // Don't display banned/deleted users
            if (!$this->user->canLogIn()) {
                if (!$website->isLoggedInAsStaff()) {
                    // Staff can view everyone
                    $this->user = null;
                }
            }
        }

        if ($this->user === null) {
            // Trigger 404
            throw new NotFoundException();
        }
    }

    public function getMinimumRank() {
        return Authentication::RANK_LOGGED_OUT;
    }

    public function getPageTitle(Text $text) {
        return $text->tReplaced("users.profile_page_of", $this->user->getDisplayName());
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("users.profile_page");
    }

    public function getPageContent(Website $website, Request $request) {
        // Display
        $textToDisplay = <<<EOT
            <div id="sidebar_page_sidebar">
                <h3 class="notable">{$this->user->getDisplayName()}</h3>
                <p><img src="{$this->user->getAvatarUrl()}" style="max-width: 95%" /></p>
                {$this->get_edit_links_html($website)}
            </div>
            <div id="sidebar_page_content">
                {$this->get_status_html($website)}
                {$this->get_articles_html($website)}
                {$this->get_comments_html($website)}
            </div>
            
EOT;
        return $textToDisplay;
    }

    /** Returns the HTML of the articles of the user, including the header */
    public function get_articles_html(Website $website) {
        $oArticles = new ArticleRepository($website);
        $articles = $oArticles->getArticlesDataUser($this->user->getId());
        $loggedInStaff = $website->isLoggedInAsStaff();
        $oArticleView = new ArticleListView($website->getText(), $articles, 0, true, false, $loggedInStaff);
        if (count($articles) > 0) {
            $returnValue = '<h3 class="notable">' . $website->t("main.articles") . "</h3>\n";
            $returnValue.= $oArticleView->getText();
            return $returnValue;
        } else {
            return "";
        }
    }

    /** Returns the HTML of the status of the user, including the header */
    public function get_status_html(Website $website) {
        $status_text = $this->user->getStatusText();
        if ($status_text) {
            $status_text = '<em>' . nl2br(htmlSpecialChars($status_text)) . '</em>';
        }

        // It's safe to display the edit links, as only moderators and up can
        // view account pages of banned/deleted users.
        // Check if account is banned
        if ($this->user->getStatus() == Authentication::STATUS_BANNED) {
            // Banned
            return <<<EOT
                <div class="error">
                    {$website->tReplaced("users.status.banned.this_account", $status_text)}.
                    {$website->t("users.user_page_hidden")}
                    <a class="arrow" href="{$website->getUrlPage("edit_account_status", $this->user->getId())}">
                        {$website->t("main.edit")}
                    </a>
                </div>
EOT;
        }

        // Check if account is deleted
        if ($this->user->getStatus() == Authentication::STATUS_DELETED) {
            return <<<EOT
                <div class="error">
                    {$website->tReplaced("users.status.deleted.this_account", $status_text)}.
                    {$website->t("users.user_page_hidden")}
                    <a class="arrow" href="{$website->getUrlPage("edit_account_status", $this->user->getId())}">
                        {$website->t("main.edit")}
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
    public function get_edit_links_html(Website $website) {
        $viewing_user = $website->getAuth()->getCurrentUser();
        $returnValue = "";

        // Get privileges
        $is_viewing_themselves = false;
        $is_viewing_as_moderator = false;
        $is_viewing_as_admin = false;
        if ($viewing_user != null) {
            $is_viewing_themselves = ($this->user->getId() == $viewing_user->getId());
            if ($website->isLoggedInAsStaff(false)) {
                $is_viewing_as_moderator = true;
            }
            if ($website->isLoggedInAsStaff(true)) {
                $is_viewing_as_admin = true;
            }
        }

        // Gravatar link + help
        if ($is_viewing_themselves) {
            // No way that other admins can edit someone's avatar, so only display help text for owner
            $returnValue.= <<<EOT
                <p>
                     {$website->tReplaced("users.gravatar.explained", '<a href="http://gravatar.com/">gravatar.com</a>')}
                </p>
EOT;
        }

        // Add all account edit links
        $edit_links = [];

        if (!$is_viewing_themselves && $is_viewing_as_moderator) {
            // Accessed by a moderator that isn't viewing his/her own account
            // Add (un)ban link
            $edit_links[] = $this->get_edit_link($website, "edit_account_status", "users.status.edit");
        }

        if ($is_viewing_themselves || $is_viewing_as_admin) {
            // Accessed by the user themselves or an admin
            // Display links to edit profile
            $edit_links[] = $this->get_edit_link($website, "edit_email", "users.email.edit");
            $edit_links[] = $this->get_edit_link($website, "edit_password", "users.password.edit");
            $edit_links[] = $this->get_edit_link($website, "edit_display_name", "users.display_name.edit");
        }
        if (!$is_viewing_themselves && $is_viewing_as_admin) {
            // Accessed by an admin that isn't viewing his/her own account
            // Add rank edit link and login link
            $edit_links[] = $this->get_edit_link($website, "edit_rank", "users.rank.edit");

            // Only display login link if account is not deleted/banned
            if ($this->user->canLogIn()) {
                $edit_links[] = $this->get_edit_link($website, "login_other", "main.log_in");
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
     * @param Website $website The website object.
     * @param string $page_id The id of the page.
     * @param string $translation_id The translation id of the text to display.
     * @return string The link.
     */
    public function get_edit_link(Website $website, $page_id, $translation_id) {
        return <<<EOT
            <a class="arrow" href="{$website->getUrlPage($page_id, $this->user->getId())}">
                {$website->t($translation_id)}
            </a><br />
EOT;
    }

    /** Returns the HTML of the comments of the user, including the header */
    public function get_comments_html(Website $website) {
        $oComments = new CommentRepository($website->getDatabase());
        $comments = $oComments->getCommentsUser($this->user->getId());
        
        $returnValue = '<h3 class="notable">' . $website->t("comments.comments") . "</h3>\n";
        if (count($comments) > 0) {
            $commentsView = new CommentsTreeView($website->getText(), $comments, true, $this->user);
            $returnValue .= $commentsView->getText();
        } else {
            $returnValue .= "<p><em>" . $website->t("comments.no_comments_found_user") . "</em></p>";
        }
        return $returnValue;
    }

}
