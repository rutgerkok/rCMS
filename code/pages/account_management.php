<?php

class AccountManagementPage extends Page {

    const USERS_PER_PAGE = 50;

    public function get_minimum_rank(Website $oWebsite) {
        return Authentication::$ADMIN_RANK;
    }

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("users.account_management");
    }

    public function get_page_type() {
        return "BACKSTAGE";
    }

    /**
     * Adds errors if the page number is invalid. Returns whether the page
     * number was valid.
     */
    public function check_valid_page_id(Website $oWebsite, $page, $users_count) {
        if ($page < 0) {
            $oWebsite->add_error($oWebsite->t("main.page") . " " . $oWebsite->t_replaced("errors.is_too_low_num", $page));
            return false;
        }
        $page_count = ceil($users_count / self::USERS_PER_PAGE);
        if ($page >= $page_count) {
            $oWebsite->add_error($oWebsite->t("main.page") . " " . $oWebsite->t_replaced("errors.is_too_high_num", $page_count - 1));
            return false;
        }
        return true;
    }

    public function get_page_content(Website $oWebsite) {
        $page = max(0, $oWebsite->get_request_int("id", 0));
        $users_count = $oWebsite->get_authentication()->get_registered_users_count();

        // Check page id
        if (!$this->check_valid_page_id($oWebsite, $page, $users_count)) {
            return "";
        }

        // Display user count
        $text_to_display = "<p>" . $oWebsite->t_replaced("users.there_are_num_registered_users", $users_count) . "</p>";
        if ($users_count == 1) {
            $text_to_display = "<p>" . $oWebsite->t("users.there_is_one_registered_user") . "</p>";
        }

        // Display menu bar
        $text_to_display.= $this->get_menu_bar($oWebsite, $page, $users_count);

        // Users table
        $start = $page * self::USERS_PER_PAGE;
        $text_to_display.= $this->get_users_table($oWebsite, $start);
        // Link to admin page
        $text_to_display.= '<p><br /><a class="arrow" href="' . $oWebsite->get_url_page('admin') . '">' . $oWebsite->t("main.admin") . '</a></p>';
        return $text_to_display;
    }

    public function get_menu_bar(Website $oWebsite, $page, $users) {
        $pages = ceil($users / self::USERS_PER_PAGE);

        // No need for a menu when there is only one page
        if ($pages <= 1) {
            return "";
        }

        $return_value = '<p class="lijn">';
        // Link to previous page
        if ($page > 0) {
            $return_value.= '<a class="arrow" href="' . $oWebsite->get_url_page("account_management", $page - 1);
            $return_value.= '">' . $oWebsite->t("articles.page.previous") . '</a> ';
        }
        $return_value.= str_replace("\$", $pages, $oWebsite->t_replaced('articles.page.current', $page + 1));
        // Link to next page
        if (($page + 1) < $pages) {
            $return_value.= ' <a class="arrow" href="' . $oWebsite->get_url_page("account_management", $page + 1);
            $return_value.= '">' . $oWebsite->t("articles.page.next") . '</a>';
        }
        $return_value.= '</p>';
        return $return_value;
    }

    /** Gets a table of all users */
    public function get_users_table(Website $oWebsite, $start) {
        $start = (int) $start;

        $oAuth = $oWebsite->get_authentication();
        $users = $oAuth->get_registered_users($start, self::USERS_PER_PAGE);
        $current_user_id = $oAuth->get_current_user()->get_id();

        // Start table
        $return_value = "<table>\n";
        $return_value.="<tr><th>" . $oWebsite->t("users.username") . "</th><th>" . $oWebsite->t("users.display_name") . "</th><th>" . $oWebsite->t("users.email") . "</th><th>" . $oWebsite->t("users.rank") . "</th><th>" . $oWebsite->t("main.edit") . "</th></tr>\n"; //login-naam-email-admin-bewerk
        $return_value.='<tr><td colspan="5"><a class="arrow" href="' . $oWebsite->get_url_page("create_account") . '">' . $oWebsite->t("users.create") . "...</a></td></tr>\n"; //maak nieuwe account


        if (count($users) > 0) {
            foreach ($users as $user) {
                // Email
                $email_link = '<em>' . $oWebsite->t("main.not_set") . '</em>';
                $email = $user->get_email();
                if ($email) {
                    $email = htmlspecialchars($email);
                    $email_link = '<a href="mailto:' . $email . '">' . $email . '</a>';
                }

                // Others
                $username = $user->get_username(); // Usernames are severly restricted, so no need to escape
                $display_name = htmlspecialchars($user->get_display_name());
                $rank_name = $oAuth->get_rank_name($user->get_rank());
                if($user->get_status() == Authentication::BANNED_STATUS) {
                    $rank_name = $oWebsite->t("users.status.banned");
                }
                if($user->get_status() == Authentication::DELETED_STATUS) {
                    $rank_name = $oWebsite->t("users.statusdeleted");
                }
                $username_link = '<a href="' . $oWebsite->get_url_page("account", $user->get_id()) . '">' . $username . '</a>';
                $login_link = '<a class="arrow" href="' . $oWebsite->get_url_page("log_in_other", $user->get_id()) . '">' . $oWebsite->t("main.log_in") . '</a>';
                if ($user->get_id() == $current_user_id || !$user->can_log_in()) {
                    // No need to log in as that account
                    $login_link = "";
                }

                // Rest of row
                $return_value.= <<<EOT
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
        $return_value.="</table>";
        return $return_value;
    }

}

$this->register_page(new AccountManagementPage());
?>