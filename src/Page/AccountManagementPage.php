<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Website;

class AccountManagementPage extends Page {

    const USERS_PER_PAGE = 50;

    public function getMinimumRank(Request $request) {
        return Authentication::$ADMIN_RANK;
    }

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("users.account_management");
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    /**
     * Adds errors if the page number is invalid. Returns whether the page
     * number was valid.
     */
    public function check_valid_page_id(Website $oWebsite, $page, $usersCount) {
        if ($page < 0) {
            $oWebsite->addError($oWebsite->t("main.page") . " " . $oWebsite->tReplaced("errors.is_too_low_num", $page));
            return false;
        }
        $pageCount = ceil($usersCount / self::USERS_PER_PAGE);
        if ($page >= $pageCount) {
            $oWebsite->addError($oWebsite->t("main.page") . " " . $oWebsite->tReplaced("errors.is_too_high_num", $pageCount - 1));
            return false;
        }
        return true;
    }

    public function getPageContent(Request $request) {
        $oWebsite = $request->getWebsite();
        $page = max(0, $oWebsite->getRequestInt("id", 0));
        $usersCount = $oWebsite->getAuth()->getRegisteredUsersCount();

        // Check page id
        if (!$this->check_valid_page_id($oWebsite, $page, $usersCount)) {
            return "";
        }

        // Display user count
        $textToDisplay = "<p>" . $oWebsite->tReplaced("users.there_are_num_registered_users", $usersCount) . "</p>";
        if ($usersCount == 1) {
            $textToDisplay = "<p>" . $oWebsite->t("users.there_is_one_registered_user") . "</p>";
        }

        // Display menu bar
        $textToDisplay.= $this->get_menu_bar($oWebsite, $page, $usersCount);

        // Users table
        $start = $page * self::USERS_PER_PAGE;
        $textToDisplay.= $this->get_users_table($oWebsite, $start);
        // Link to admin page
        $textToDisplay.= '<p><br /><a class="arrow" href="' . $oWebsite->getUrlPage('admin') . '">' . $oWebsite->t("main.admin") . '</a></p>';
        return $textToDisplay;
    }

    public function get_menu_bar(Website $oWebsite, $page, $users) {
        $pages = ceil($users / self::USERS_PER_PAGE);

        // No need for a menu when there is only one page
        if ($pages <= 1) {
            return "";
        }

        $returnValue = '<p class="result_selector_menu">';
        // Link to previous page
        if ($page > 0) {
            $returnValue.= '<a class="arrow" href="' . $oWebsite->getUrlPage("account_management", $page - 1);
            $returnValue.= '">' . $oWebsite->t("articles.page.previous") . '</a> ';
        }
        $returnValue.= $oWebsite->tReplaced('articles.page.current', $page + 1, $pages);
        // Link to next page
        if (($page + 1) < $pages) {
            $returnValue.= ' <a class="arrow" href="' . $oWebsite->getUrlPage("account_management", $page + 1);
            $returnValue.= '">' . $oWebsite->t("articles.page.next") . '</a>';
        }
        $returnValue.= '</p>';
        return $returnValue;
    }

    /** Gets a table of all users */
    public function get_users_table(Website $oWebsite, $start) {
        $start = (int) $start;

        $oAuth = $oWebsite->getAuth();
        $users = $oAuth->getRegisteredUsers($start, self::USERS_PER_PAGE);
        $current_user_id = $oAuth->getCurrentUser()->getId();

        // Start table
        $returnValue = "<table>\n";
        $returnValue.="<tr><th>" . $oWebsite->t("users.username") . "</th><th>" . $oWebsite->t("users.display_name") . "</th><th>" . $oWebsite->t("users.email") . "</th><th>" . $oWebsite->t("users.rank") . "</th><th>" . $oWebsite->t("main.edit") . "</th></tr>\n"; //login-naam-email-admin-bewerk
        $returnValue.='<tr><td colspan="5"><a class="arrow" href="' . $oWebsite->getUrlPage("create_account") . '">' . $oWebsite->t("users.create") . "...</a></td></tr>\n"; //maak nieuwe account


        if (count($users) > 0) {
            foreach ($users as $user) {
                // Email
                $email_link = '<em>' . $oWebsite->t("main.not_set") . '</em>';
                $email = $user->getEmail();
                if ($email) {
                    $email = htmlSpecialChars($email);
                    $email_link = '<a href="mailto:' . $email . '">' . $email . '</a>';
                }

                // Others
                $username = $user->getUsername(); // Usernames are severly restricted, so no need to escape
                $display_name = htmlSpecialChars($user->getDisplayName());
                $rank_name = $oAuth->getRankName($user->getRank());
                if ($user->getStatus() == Authentication::BANNED_STATUS) {
                    $rank_name = $oWebsite->t("users.status.banned");
                }
                if ($user->getStatus() == Authentication::DELETED_STATUS) {
                    $rank_name = $oWebsite->t("users.status.deleted");
                }
                $username_link = '<a href="' . $oWebsite->getUrlPage("account", $user->getId()) . '">' . $username . '</a>';
                $login_link = '<a class="arrow" href="' . $oWebsite->getUrlPage("login_other", $user->getId()) . '">' . $oWebsite->t("main.log_in") . '</a>';
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
