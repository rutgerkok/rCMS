<?php

class Authentication {

    public static $ADMIN_RANK = 1;
    public static $MODERATOR_RANK = 0;
    public static $USER_RANK = 2;

    const NORMAL_STATUS = 0;
    const BANNED_STATUS = 1;
    const DELETED_STATUS = 2;

    /* @var $website_object Website */

    protected $website_object;
    private $current_user;
    private $login_failed = false;

    public function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
    }

    /**
     * Get the current user. Use the User.save function to save changes to the database.
     * @return User
     */
    public function get_current_user() {
        if (isset($this->current_user)) {
            // Object cached
            return $this->current_user;
        } else {
            if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
                $this->current_user = User::get_by_id($this->website_object, (int) $_SESSION['user_id']);
                if ($this->current_user == null) {
                    // Invalid user id in session, probably because the user was just deleted
                    unset($_SESSION['user_id']);
                }
                return $this->current_user;
            } else {
                // Not logged in
                return null;
            }
        }
    }

    /**
     * Save that user object in the session
     * @param User $user The user to login
     */
    public function set_current_user($user) {
        if ($user == null || !($user instanceof User)) {
            // Log out
            unset($_SESSION['user_id']);
            unset($this->current_user);
        } else {
            // Log in
            $_SESSION['user_id'] = $user->get_id();
            $this->current_user = $user;
        }
    }

    /**
     * Logs the user in with the given username and password.
     * @param string $username The username.
     * @param string $password The unhashed password.
     * @return boolean Whether the login was succesfull
     */
    public function log_in($username, $password) {
        $user = User::get_by_name($this->website_object, $username);
        if ($user != null && $user->verify_password($password)) {
            // Matches!
            $this->set_current_user($user);
            return true;
        }
        return false;
    }

    /**
     * Checks whether the user has access to the current page. If not, a login
     * screen is optionally displayed.
     * @param int $minimum_rank The minimum rank required.
     * @param boolean $showform Whether a login form should be shown on failure.
     * @return boolean Whether the login was succesfull.
     */
    public function check($minimum_rank, $showform = true) {
        $minimum_rank = (int) $minimum_rank;
        $current_user = $this->get_current_user();

        // Try to login if data was sent
        if (isset($_POST['user']) && isset($_POST['pass'])) {
            if ($this->log_in($_POST['user'], $_POST['pass'])) {
                $current_user = $this->get_current_user();
            } else {
                $this->login_failed = true;
            }
        }

        if ($current_user != null && $this->is_higher_or_equal_rank($current_user->get_rank(), $minimum_rank)) {
            // Logged in with enough rights
            return true;
        } else {
            // Not logged in with enough rights
            if ($showform) {
                echo $this->get_login_form($minimum_rank);
            }
            return false;
        }
    }

    function get_login_form($minimum_rank = 0) { //laat een inlogformulier zien
        //huidige pagina ophalen
        $oWebsite = $this->website_object;
        $logintext = $oWebsite->t("users.please_log_in");
        $return_value = "";
        if ($this->login_failed) {
            $return_value.= <<<EOT
                <div class="error">
                    <p>{$oWebsite->t("errors.invalid_username_or_password")}</p>
                </div>
EOT;
        }
        if ($minimum_rank != self::$USER_RANK)
            $logintext.=' <strong><em> ' . $oWebsite->t("users.as_administrator") . '</em></strong>';
        $return_value.= <<<EOT
            <form method="post" action="{$oWebsite->get_url_main()}">
                    <h3>$logintext</h3>
                    <p>
                            <label for="user">{$oWebsite->t('users.username')}:</label> <br />
                            <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                            <label for="pass">{$oWebsite->t('users.password')}:</label> <br />
                            <input type="password" name="pass" id="pass" /> <br />

                            <input type="submit" value="{$oWebsite->t('main.log_in')}" class="button" />

EOT;
        foreach ($_REQUEST as $key => $value) {
            // Repost all variables
            if ($key != "user" && $key != "pass") {
                $return_value.= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
            }
        }
        // End form and return it
        $return_value.= <<<EOT
                    </p>
            </form>
EOT;
        return $return_value;
    }

    function log_out() {
        $this->set_current_user(null);
    }

    /** Gets the number of registered users. Returns 0 on failure. */
    public function get_registered_users_count() {
        $oDB = $this->website_object->get_database();
        $sql = "SELECT COUNT(*) FROM `users`";
        $result = $oDB->query($sql);
        if ($result) {
            $first_row = $oDB->fetch($result);
            return $first_row[0];
        }
        return 0;
    }

    /**
     * Gets all registered users.
     * @param int $start The index to start searching.
     * @param int $limit The maximum number of users to find.
     * @return \User List of users.
     */
    public function get_registered_users($start, $limit) {
        // Variables and casting
        $users = array();
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();
        $start = max(0, (int) $start);
        $limit = max(1, (int) $limit);

        // Execute query
        $sql = "SELECT `user_id`, `user_login`, `user_display_name`, ";
        $sql.= "`user_password`, `user_email`, `user_rank`, `user_joined`, ";
        $sql.= "`user_last_login`, `user_status`, `user_status_text`, ";
        $sql.= "`user_extra_data` FROM `users` LIMIT $start, $limit";
        $result = $oDB->query($sql);

        // Parse and return results
        while (list(
        $id, $username, $display_name, $password_hashed, $email,
        $rank, $joined, $last_login, $status, $status_text, $extra_data
        ) = $oDB->fetch($result)) {
            $users[] = new User(
                            $oWebsite, $id, $username, $display_name,
                            $password_hashed, $email, $rank, $joined, $last_login,
                            $status, $status_text, $extra_data
            );
        }
        return $users;
    }

    /**
     * Returns the highest id in use for a rank.
     * @return int The highest id in use for a rank.
     */
    public function get_highest_rank_id() {
        return 2;
    }

    public function get_standard_rank_id() {
        return self::$USER_RANK;
    }

    /** Returns true if the given number is a valid rank id */
    public function is_valid_rank($id) {
        if ($id == self::$USER_RANK || $id == self::$ADMIN_RANK || $id == self::$MODERATOR_RANK) {
            return true;
        } else {
            return false;
        }
    }

    public function get_rank_name($id) {
        $oWebsite = $this->website_object;
        switch ($id) {
            case 0: return $oWebsite->t("users.rank.moderator");
            case 1: return $oWebsite->t("users.rank.admin");
            case 2: return $oWebsite->t("users.rank.user");
            default: return $oWebsite->t("users.rank.unknown");
        }
    }

    public function is_higher_or_equal_rank($rank_id, $compare_to) {
        if ($compare_to == self::$USER_RANK) {
            // Comparing to normal user
            return true;
        }
        if ($compare_to == self::$MODERATOR_RANK) {
            // Comparing to moderator
            if ($rank_id == self::$USER_RANK) {
                // Normal users aren't higher or equal
                return false;
            }
            return true;
        }
        if ($compare_to == self::$ADMIN_RANK) {
            // Only other admins have the same rank
            return $rank_id == self::$ADMIN_RANK;
        }
    }

}

?>