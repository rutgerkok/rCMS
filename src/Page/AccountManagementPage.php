<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\User;
use Rcms\Core\Website;

class AccountManagementPage extends Page {

    const USERS_PER_PAGE = 50;

    public function getMinimumRank() {
        return Ranks::ADMIN;
    }

    public function getPageTitle(Text $text) {
        return $text->t("users.account_management");
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    /**
     * Adds errors if the page number is invalid. Returns whether the page
     * number was valid.
     */
    public function check_valid_page_id(Website $website, $page, $usersCount) {
        if ($page < 0) {
            $website->addError($website->t("main.page") . " " . $website->tReplaced("errors.is_too_low_num", $page));
            return false;
        }
        $pageCount = ceil($usersCount / self::USERS_PER_PAGE);
        if ($page >= $pageCount) {
            $website->addError($website->t("main.page") . " " . $website->tReplaced("errors.is_too_high_num", $pageCount - 1));
            return false;
        }
        return true;
    }

    public function getPageContent(Website $website, Request $request) {
        $page = $request->getParamInt(0, 0);
        $usersCount = $website->getUserRepository()->getRegisteredUsersCount();

        // Check page id
        if (!$this->check_valid_page_id($website, $page, $usersCount)) {
            return "";
        }

        // Display user count
        $textToDisplay = "<p>" . $website->tReplaced("users.there_are_num_registered_users", $usersCount) . "</p>";
        if ($usersCount == 1) {
            $textToDisplay = "<p>" . $website->t("users.there_is_one_registered_user") . "</p>";
        }

        // Display menu bar
        $textToDisplay.= $this->get_menu_bar($website, $page, $usersCount);

        // Users table
        $start = $page * self::USERS_PER_PAGE;
        $textToDisplay.= $this->get_users_table($website, $request, $start);
        // Link to admin page
        $textToDisplay.= '<p><br /><a class="arrow" href="' . $website->getUrlPage('admin') . '">' . $website->t("main.admin") . '</a></p>';
        return $textToDisplay;
    }

    public function get_menu_bar(Website $website, $page, $users) {
        $pages = ceil($users / self::USERS_PER_PAGE);

        // No need for a menu when there is only one page
        if ($pages <= 1) {
            return "";
        }

        $returnValue = '<p class="result_selector_menu">';
        // Link to previous page
        if ($page > 0) {
            $returnValue.= '<a class="arrow" href="' . $website->getUrlPage("account_management", $page - 1);
            $returnValue.= '">' . $website->t("articles.page.previous") . '</a> ';
        }
        $returnValue.= $website->tReplaced('articles.page.current', $page + 1, $pages);
        // Link to next page
        if (($page + 1) < $pages) {
            $returnValue.= ' <a class="arrow" href="' . $website->getUrlPage("account_management", $page + 1);
            $returnValue.= '">' . $website->t("articles.page.next") . '</a>';
        }
        $returnValue.= '</p>';
        return $returnValue;
    }

    /** Gets a table of all users */
    public function get_users_table(Website $website, Request $request, $start) {
        $start = (int) $start;

        $oAuth = $website->getRanks();
        $users = $website->getUserRepository()->getRegisteredUsers($start, self::USERS_PER_PAGE);
        $current_user_id = $request->getCurrentUser()->getId();

        // Start table
        $returnValue = "<table>\n";
        $returnValue.="<tr><th>" . $website->t("users.username") . "</th><th>" . $website->t("users.display_name") . "</th><th>" . $website->t("users.email") . "</th><th>" . $website->t("users.rank") . "</th><th>" . $website->t("main.edit") . "</th></tr>\n"; //login-naam-email-admin-bewerk
        $returnValue.='<tr><td colspan="5"><a class="arrow" href="' . $website->getUrlPage("create_account_admin") . '">' . $website->t("users.create") . "...</a></td></tr>\n"; //maak nieuwe account


        if (count($users) > 0) {
            foreach ($users as $user) {
                // Email
                $email_link = '<em>' . $website->t("main.not_set") . '</em>';
                $email = $user->getEmail();
                if ($email) {
                    $email = htmlSpecialChars($email);
                    $email_link = '<a href="mailto:' . $email . '">' . $email . '</a>';
                }

                // Others
                $username = $user->getUsername(); // Usernames are severly restricted, so no need to escape
                $display_name = htmlSpecialChars($user->getDisplayName());
                $rank_name = $website->t($oAuth->getRankName($user->getRank()));
                if ($user->getStatus() == User::STATUS_BANNED) {
                    $rank_name = $website->t("users.status.banned");
                }
                if ($user->getStatus() == User::STATUS_DELETED) {
                    $rank_name = $website->t("users.status.deleted");
                }
                $username_link = '<a href="' . $website->getUrlPage("account", $user->getId()) . '">' . $username . '</a>';
                $login_link = '<a class="arrow" href="' . $website->getUrlPage("login_other", $user->getId()) . '">' . $website->t("main.log_in") . '</a>';
                if ($user->getId() == $current_user_id || !$user->canLogIn()) {
                    // No need to log in as that account
                    $login_link = "";
                }

                // Rest of row
                $returnValue.= <<<EOT
                    <tr>
                        <td>$username_link</td>
                        <td>$display_name</td>
                        <td>$email_link</td>
                        <td>$rank_name</td>
                        <td>$login_link</td>
                    </tr>
EOT;
            }
        }
        $returnValue.="</table>";
        return $returnValue;
    }

}
